<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit942d90b3bffbcbfe6a4403d39ac6406b
{
    public static $prefixLengthsPsr4 = array (
        'S' => 
        array (
            'Spatie\\ArrayToXml\\' => 18,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Spatie\\ArrayToXml\\' => 
        array (
            0 => __DIR__ . '/..' . '/spatie/array-to-xml/src',
        ),
    );

    public static $classMap = array (
        'Salamek\\Zasilkovna\\ApiRest' => __DIR__ . '/..' . '/salamek/zasilkovna/src/ApiRest.php',
        'Salamek\\Zasilkovna\\ApiSoap' => __DIR__ . '/..' . '/salamek/zasilkovna/src/ApiSoap.php',
        'Salamek\\Zasilkovna\\Branch' => __DIR__ . '/..' . '/salamek/zasilkovna/src/Branch.php',
        'Salamek\\Zasilkovna\\Enum\\Currency' => __DIR__ . '/..' . '/salamek/zasilkovna/src/Enum/Currency.php',
        'Salamek\\Zasilkovna\\Enum\\LabelDecomposition' => __DIR__ . '/..' . '/salamek/zasilkovna/src/Enum/LabelDecomposition.php',
        'Salamek\\Zasilkovna\\Enum\\LabelPosition' => __DIR__ . '/..' . '/salamek/zasilkovna/src/Enum/LabelPosition.php',
        'Salamek\\Zasilkovna\\Exception\\OfflineException' => __DIR__ . '/..' . '/salamek/zasilkovna/src/Exception/OfflineException.php',
        'Salamek\\Zasilkovna\\Exception\\PacketAttributesFault' => __DIR__ . '/..' . '/salamek/zasilkovna/src/Exception/PacketAttributesFault.php',
        'Salamek\\Zasilkovna\\Exception\\RestFault' => __DIR__ . '/..' . '/salamek/zasilkovna/src/Exception/RestFault.php',
        'Salamek\\Zasilkovna\\Exception\\SecurityException' => __DIR__ . '/..' . '/salamek/zasilkovna/src/Exception/SecurityException.php',
        'Salamek\\Zasilkovna\\Exception\\WrongDataException' => __DIR__ . '/..' . '/salamek/zasilkovna/src/Exception/WrongDataException.php',
        'Salamek\\Zasilkovna\\IApi' => __DIR__ . '/..' . '/salamek/zasilkovna/src/IApi.php',
        'Salamek\\Zasilkovna\\Label' => __DIR__ . '/..' . '/salamek/zasilkovna/src/Label.php',
        'Salamek\\Zasilkovna\\Model\\BranchStorageFile' => __DIR__ . '/..' . '/salamek/zasilkovna/src/Model/BranchStorageFile.php',
        'Salamek\\Zasilkovna\\Model\\BranchStorageMemory' => __DIR__ . '/..' . '/salamek/zasilkovna/src/Model/BranchStorageMemory.php',
        'Salamek\\Zasilkovna\\Model\\BranchStorageSqLite' => __DIR__ . '/..' . '/salamek/zasilkovna/src/Model/BranchStorageSqLite.php',
        'Salamek\\Zasilkovna\\Model\\ClaimAttributes' => __DIR__ . '/..' . '/salamek/zasilkovna/src/Model/ClaimAttributes.php',
        'Salamek\\Zasilkovna\\Model\\DispatchOrder' => __DIR__ . '/..' . '/salamek/zasilkovna/src/Model/DispatchOrder.php',
        'Salamek\\Zasilkovna\\Model\\IBranchStorage' => __DIR__ . '/..' . '/salamek/zasilkovna/src/Model/IBranchStorage.php',
        'Salamek\\Zasilkovna\\Model\\IModel' => __DIR__ . '/..' . '/salamek/zasilkovna/src/Model/IModel.php',
        'Salamek\\Zasilkovna\\Model\\PacketAttributes' => __DIR__ . '/..' . '/salamek/zasilkovna/src/Model/PacketAttributes.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit942d90b3bffbcbfe6a4403d39ac6406b::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit942d90b3bffbcbfe6a4403d39ac6406b::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit942d90b3bffbcbfe6a4403d39ac6406b::$classMap;

        }, null, ClassLoader::class);
    }
}
