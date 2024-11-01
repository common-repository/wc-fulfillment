<?php
namespace Pexxi\Apiface;

require_once (__DIR__ . '/common.php');

define('PEXXI_APIFACE_PROVIDER_DIR', __DIR__ . '/provider');
define('PEXXI_APIFACE_ERROR_SEPARATOR', '|');
define('PEXXI_APIFACE_PROVIDER_CLASS_PREFIX', 'Pexxi\\Apiface\\Provider');
define('PEXXI_APIFACE_PROVIDER_CLASS_PREFIX_LOW', strtolower('Pexxi\\Apiface\\Provider'));

interface Provider {
}

if (isset($GLOBALS['kernel'])) {
  class Bundle extends  \Symfony\Component\HttpKernel\Bundle\Bundle {

  }
}
else {
  class Bundle {

  }
}

class Manager extends Bundle {

  private $providers = array();

  private static $instance = NULL;

  protected $rootConfig = NULL;

  public function refreshProviders() {

    $rootConfig = $this->rootConfig;

    $providers = array();

    global $PEXXI_APIFACE_PROVIDERS;
    if (is_array($PEXXI_APIFACE_PROVIDERS)) {
      foreach ($PEXXI_APIFACE_PROVIDERS as $providerName => $providerInfo) {
        $ucfName = ucfirst(strtolower($providerName));
        $providers[$ucfName] = array(
          'class' => @$providerInfo['class'],
          'endPoint' => @$providerInfo['endPoint'],
          'config' => @$providerInfo['config']
        );
      }
    }

    $declaredClasses = get_declared_classes();
    foreach ($declaredClasses as $declaredClass) {
      if (strpos($declaredClass, PEXXI_APIFACE_PROVIDER_CLASS_PREFIX) === 0) {
        $ucfName = ucfirst(str_replace(array(
          PEXXI_APIFACE_PROVIDER_CLASS_PREFIX_LOW
        ) , '', strtolower($declaredClass)));
        $providers[$ucfName] = array(
          'class' => $declaredClass,
          'endPoint' => @constant($declaredClass . '::ENDPOINT') ,
          'config' => property_exists($declaredClass, 'CONFIG') ? $declaredClass::$CONFIG : null
        );
      }
    }

    foreach (scandir(PEXXI_APIFACE_PROVIDER_DIR) as $file) {
      if (is_file(PEXXI_APIFACE_PROVIDER_DIR . '/' . $file)) {
        require_once (PEXXI_APIFACE_PROVIDER_DIR . '/' . $file);

        $name = basename($file, ".php");
        $ucfName = ucfirst($name);
        $ucName = strtoupper($name);

        $providerClass = PEXXI_APIFACE_PROVIDER_CLASS_PREFIX . $ucfName;
        $providers[$ucfName] = array(
          'class' => $providerClass,
          'endPoint' => @constant('PEXXI_APIFACE_PROVIDER_' . $ucName . '_ENDPOINT') ,
          'config' => json_decode(@constant('PEXXI_APIFACE_PROVIDER_' . $ucName . '_CONFIG')) ,
        );
      }
    }

    foreach ($providers as $providerName => $providerInfo) {
      if (isset($this->providers[$providerName])) {
        continue;
      }

      $endPoint = $providerInfo['endPoint'];
      $config = $providerInfo['config'];
      if (!$config) {
        $config = new \stdClass();
      }

      $providerClass = $providerInfo['class'];

      if ($rootConfig && isset($rootConfig[$providerName])) {
        $obj1 = json_decode(json_encode($rootConfig[$providerName]) , true);
        $obj2 = json_decode(json_encode($config) , true);
        $config = json_decode(json_encode(array_merge_recursive($obj1, $obj2)));
        if (isset($rootConfig[$providerName]['endPoint'])) {
          $endPoint = $rootConfig[$providerName]['endPoint'];
        }
      }

      try {
        $this->providers[$providerName] = new $providerClass($endPoint, $config);
      }
      catch(\Exception $e) {
        die($e->getMessage());
        continue;
      }
    }

    //dump(array_keys($this->providers));
    
  }

  function __construct($rootConfig = null) {
    //parent::__construct();
    $this->rootConfig = $rootConfig;

    $this->refreshProviders();
  }

  public static function getInstance() {
    global $kernel;
    if (!self::$instance) {
      define('PEXXI_APIFACE_DEBUG', isset($kernel) ? $kernel->isDebug() : @constant('DEBUG'));
      self::$instance = new self();
    }

    return self::$instance;
  }

  public function getProvider($name) {
    return $this->providers[$name];
  }

  public function getAuths($providers = NULL) {
    static $auths = NULL;

    $hash = sha1(serialize(array(
      $providers
    )));

    if (!isset($auths[$hash])) {
      $auths[$hash] = array();
      foreach ($this->providers as $name => $provider) {
        if (isset($providers) && !in_array($name, $providers)) {
          continue;
        }

        if (method_exists($provider, 'getAuth')) {
          $providerAuth = $provider->getAuth();
          $providerAuth->provider = $name;
          $auths[$hash][] = $providerAuth;
        }
      }
    }

    return $auths[$hash];
  }

  public function executeOperation($operationName, $providers = NULL) {

    $methodName = 'executeOperation' . ucfirst($operationName);

    $args = func_get_args();
    array_shift($args);
    array_unshift($args, $methodName);

    return call_user_func_array(array(
      $this,
      'executeMethod'
    ) , $args);
  }

  public function executeMethodCached($methodName, $providers = NULL, $cached = false) {

    static $cache = array();
    if (isset($cache[__METHOD__])) {
      $cache[__METHOD__] = array();
    }

    $methCache = & $cache[__METHOD__];

    $args = func_get_args();
    $hash = sha1(serialize($args));

    array_shift($args);
    array_shift($args);
    array_shift($args);

    if (!$cached || !isset($methCache[$hash])) {
      $methCache[$hash] = array();
      $hashCache = & $methCache[$hash];

      foreach ($this->providers as $name => $provider) {
        if (isset($providers) && !in_array($name, $providers)) {
          continue;
        }

        if (method_exists($provider, $methodName)) {
          $providerResults = call_user_func_array(array(
            $provider,
            $methodName
          ) , $args);

          if ($providerResults === false) {
            $hashCache[$name] = false;
          }
          else if (isset($providerResults['error'])) {
            $hashCache[$name] = $providerResults['error'];
          }
          else {
            foreach ($providerResults as & $providerResult) {
              if (!is_object($providerResult)) {
                $value = $providerResult;
                $providerResult = new \stdClass();
                $providerResult->value = $value;
              }

              $providerResult->provider = $name;
            }

            $hashCache = array_merge($hashCache, $providerResults);
          }
        }
      }
    }

    if (function_exists('gc_collect_cycles')) {
      gc_collect_cycles();
    }

    return $methCache[$hash];
  }

  public function executeMethod($methodName, $providers = NULL) {
    $args = func_get_args();

    array_shift($args);
    array_shift($args);

    array_unshift($args, false);
    array_unshift($args, $providers);
    array_unshift($args, $methodName);

    return call_user_func_array(array(
      $this,
      'executeMethodCached'
    ) , $args);
  }
}
