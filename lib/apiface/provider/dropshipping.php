<?php
namespace Pexxi\Apiface;

class ProviderDropshipping extends Api {

  public $apiToken;

  public function authenticate($params = NULL) {
  }

  public function executeMethod($typeName, $methodName, $args = array() , $meta = array() , $options = array()) {
    if (!isset($this->apiToken)) {
      $this->authenticate();
    }
    $meta['Authorization'] = $this->apiToken;

    $out = parent::executeMethod($typeName, $methodName, $args, $meta, $options);

    // TODO[hardcode]: Rate-limit protection
    usleep(550000);

    return $out;
  }

  protected function getModel() {
    return $this->getBasicModel() + array(
      'datetm' => array(
        'base' => 'datetime',
        'format' => 'Y-m-d'
      ) ,

      'Dropshipping.Basket' => array(
        'type' => 'object',
        'properties' => array(
          'id' => array(
            'type' => 'int',
          ) ,
          'code' => array(
            'type' => 'string',
          ) ,
          'price_retail_vat' => array(
            'type' => 'decimal',
          ) ,
          'quantity' => array(
            'type' => 'int',
          ) ,
          'note' => array(
            'type' => 'string',
          ) ,
        )
      ) ,

      'Dropshipping.Insert' => array(
        'type' => 'object',
        'properties' => array(
          'id' => array(
            'type' => 'int',
          ) ,
          'code' => array(
            'type' => 'string',
          ) ,
          'quantity' => array(
            'type' => 'int',
          ) ,
        )
      ) ,

      'Dropshipping.OrderResponse' => array(
        'type' => 'object',

        'properties' => array(
          'code' => array(
            'type' => 'int'
          ),
          'message' => array(
            'type' => 'string'
          ),
          'data' => array(
            'type' => 'Dropshipping.Order',
            'multiple' => true
          )
        )
      ),

      'Dropshipping.Tracking' => array(
        'type' => 'object',

        'properties' => array(
          'number' => array(
            'type' => 'string'
          ),
          'link' => array(
            'type' => 'string'
          )
        )
      ),

      'Dropshipping.PaymentResponse' => array(
        'type' => 'object',

        'properties' => array(
          'code' => array(
            'type' => 'int'
          ),
          'message' => array(
            'type' => 'string'
          ),
          'data' => array(
            'type' => 'Dropshipping.Payment',
            //'type' => 'object',
            'multiple' => true
          )
        )
      ),

      'Dropshipping.Payment' => array(
        'type' => 'object',

        'properties' => array(
          'id' => array(
            'type' => 'int',
          ) ,
          'name' => array(
            'type' => 'string'
          ),
          'permitted_delivery' => array(
            'type' => 'string',
            'multiple' => true
          ) ,
        ) ,

        'methods' => array(
          'getPayments' => array(
            'protocol' => 'http',
            'operation' => 'get',

            'requestContentCodec' => 'application/json',
            'responseContentCodec' => 'application/json',

            'address' => '/payments?eshop_id={eshop_id}',

            'charset' => 'UTF-8',

            'arguments' => array(
              'eshop_id' => array(
                'type' => 'int'
              ),
            ) ,

            'result' => array(
              'type' => 'Dropshipping.PaymentResponse',

            )
          ),
        )
      ) ,

      'Dropshipping.DeliveryResponse' => array(
        'type' => 'object',

        'properties' => array(
          'code' => array(
            'type' => 'int'
          ),
          'message' => array(
            'type' => 'string'
          ),
          'data' => array(
            'type' => 'Dropshipping.Delivery',
            //'type' => 'object',
            'multiple' => true
          )
        )
      ),

      'Dropshipping.Delivery' => array(
        'type' => 'object',

        'properties' => array(
          'id' => array(
            'type' => 'int',
          ) ,
          'name' => array(
            'type' => 'string'
          ),
          'has_place' => array(
            'type' => 'int'
          ),
          'permitted_payment' => array(
            'type' => 'string',
            'multiple' => true
          ) ,
        ) ,

        'methods' => array(
          'getDeliveries' => array(
            'protocol' => 'http',
            'operation' => 'get',

            'requestContentCodec' => 'application/json',
            'responseContentCodec' => 'application/json',

            'address' => '/deliveries?eshop_id={eshop_id}',

            'charset' => 'UTF-8',

            'arguments' => array(
              'eshop_id' => array(
                'type' => 'int'
              ),
            ) ,

            'result' => array(
              'type' => 'Dropshipping.DeliveryResponse',

            )
          ),
        )
      ) ,

      'Dropshipping.Order' => array(
        'type' => 'object',

        'properties' => array(
          'id' => array(
            'type' => 'int',
          ) ,
          'eshop_id' => array(
            'type' => 'int'
          ),
          'auto_resend' => array(
            'type' => 'int',
          ) ,
          'remote_id' => array(
            'type' => 'string',
          ) ,
          'email' => array(
            'type' => 'string',
          ) ,
          'phone' => array(
            'type' => 'string',
            'value' => '11111111'
          ) ,
          'invoice_firstname' => array(
            'type' => 'string',
          ) ,
          'invoice_surname' => array(
            'type' => 'string',
          ) ,
          'invoice_street' => array(
            'type' => 'string',
          ) ,
          'invoice_city' => array(
            'type' => 'string',
          ) ,
          'invoice_zipcode' => array(
            'type' => 'string',
          ) ,
          'invoice_company' => array(
            'type' => 'string',
          ) ,
          'invoice_ico' => array(
            'type' => 'string',
          ) ,
          'invoice_dic' => array(
            'type' => 'string',
          ) ,
          'contact_like_invoice' => array(
            'type' => 'int',
            'value' => 0
          ) ,
          'contact_firstname' => array(
            'type' => 'string',
          ) ,
          'contact_surname' => array(
            'type' => 'string',
          ) ,
          'contact_street' => array(
            'type' => 'string',
          ) ,
          'contact_city' => array(
            'type' => 'string',
          ) ,
          'contact_zipcode' => array(
            'type' => 'string',
          ) ,
          'contact_company' => array(
            'type' => 'string',
          ) ,
          'contact_ico' => array(
            'type' => 'string',
          ) ,
          'contact_dic' => array(
            'type' => 'string',
          ) ,
          'zpl_note' => array(
            'type' => 'string',
          ) ,
          'note' => array(
            'type' => 'string',
          ) ,
          'payment_id' => array(
            'type' => 'int',
            'value' => 1
          ) ,
          'payment_price_vat' => array(
            'type' => 'decimal',
          ) ,
          'delivery_id' => array(
            'type' => 'int',
          ) ,
          'delivery_price_vat' => array(
            'type' => 'decimal',
          ) ,
          'delivery_place_id' => array(
            'type' => 'int',
          ) ,
          'delivery_place_ext_id' => array(
            'type' => 'string',
          ) ,
          'test' => array(
            'type' => 'string',
            'value' => '0'
          ) ,
          'basket' => array(
            'type' => 'Dropshipping.Basket',
            'multiple' => true
          ) ,
          'inserts' => array(
            'type' => 'Dropshipping.Insert',
            'multiple' => true
          ) ,
          'paid' => array(
            'type' => 'bool'
          ),
          'status_id' => array(
            'type' => 'int'
          ),
          'tracking' => array(
            'type' => 'Dropshipping.Tracking'
          )
        ) ,

        'methods' => array(
          'getOrders' => array(
            'protocol' => 'http',
            'operation' => 'get',

            'requestContentCodec' => 'application/json',
            'responseContentCodec' => 'application/json',

            'address' => '/orders?limit=100&created_from={created_from}',

            'charset' => 'UTF-8',

            'arguments' => array(
              'created_from' => array(
                'type' => 'datetm'
              ),

              'created_to' => array(
                'type' => 'datetm',
                'value' => NULL
              )
            ) ,

            'result' => array(
              'type' => 'Dropshipping.OrderResponse',

            )
          ),

          'updateOrder' => array(
            'protocol' => 'http',
            'operation' => 'post',

            'requestContentCodec' => 'application/json',
            'responseContentCodec' => 'application/json',

            'address' => '/orders',

            'charset' => 'UTF-8',

            'singleArgument' => true,

            'arguments' => array(
              array(
                'type' => 'Dropshipping.Order'
              )
            ) ,

            'result' => array(
              'type' => 'Dropshipping.Order'
            )
          )
        ) ,
      ) ,

      'Dropshipping.ShopResponse' => array(
        'type' => 'object',
        'properties' => array(
          'shop' => array(
            'type' => 'Dropshipping.Shop',
            //'multiple' => true
          )
        ) ,
      ) ,

      'Dropshipping.Shop' => array(
        'type' => 'object',
        'properties' => array(
          'shopitem' => array(
            'type' => 'Dropshipping.Product',
            'multiple' => true
          )
        ) ,
      ) ,

      'Dropshipping.Categories' => array(
        'type' => 'object',
        'properties' => array(
          'category' => array(
            'type' => 'Dropshipping.Category',
            'multiple' => true
          )
        ) ,
      ) ,

      'Dropshipping.Category' => array(
        'type' => 'object',
        'properties' => array(
          '@id' => array(
            'type' => 'string',
          ) ,
          '@parent_id' => array(
            'type' => 'string',
          ) ,
          '@' => array(
            'type' => 'string',
          )
        ) ,
      ) ,

      'Dropshipping.Parameters' => array(
        'type' => 'object',
        'properties' => array() ,
      ) ,

      'Dropshipping.SpecialDeliveryPrices' => array(
        'type' => 'object',
        'properties' => array() ,
      ) ,

      'Dropshipping.BlacklistedDeliveryIds' => array(
        'type' => 'object',
        'properties' => array() ,
      ) ,

      'Dropshipping.Images' => array(
        'type' => 'object',
        'properties' => array(
          'image' => array(
            'type' => 'Dropshipping.Image',
            'multiple' => true
          )
        ) ,
      ) ,

      'Dropshipping.Image' => array(
        'type' => 'object',
        'properties' => array(
          'imgurl' => array(
            'type' => 'string.@',
            'multiple' => true,
          ) ,
        ) ,
      ) ,

      'Dropshipping.Product' => array(
        'type' => 'object',
        'properties' => array(
          'item_id' => array(
            'type' => 'int.@',
            'multiple' => true
          ) ,
          'itemgroup_id' => array(
            'type' => 'int.@',
            'multiple' => true
          ) ,
          'partner_id' => array(
            'type' => 'int.@',
            'multiple' => true
          ) ,
          'product_id' => array(
            'type' => 'int.@',
            'multiple' => true
          ) ,
          'product' => array(
            //'type' => 'string.@', 'multiple' => TRUE
            'type' => 'int.@',
            'multiple' => true
          ) ,
          'product_name' => array(
            'type' => 'string.@',
            'multiple' => true
          ) ,
          'variant_name' => array(
            'type' => 'string.@',
            'multiple' => true
          ) ,
          'manufacturer' => array(
            'type' => 'string.@',
            'multiple' => true
          ) ,
          'code' => array(
            'type' => 'string.@',
            'multiple' => true
          ) ,
          'ext_code' => array(
            'type' => 'string.@',
            'multiple' => true
          ) ,
          'ean' => array(
            'type' => 'string.@',
            'multiple' => true
          ) ,
          /*'description' => array(
            'type' => 'string.@',
            'multiple' => true
          ) ,*/
          'description_html' => array(
            'type' => 'string.@',
            'multiple' => true
          ) ,
          'categories' => array(
            'type' => 'Dropshipping.Categories',
            'multiple' => true
          ) ,
          /*'category_text' => array(
            'type' => 'string.@',
            'multiple' => true
          ) ,*/
          /*'parameters' => array(
            'type' => 'Dropshipping.Parameters',
            'multiple' => true
          ) ,*/
          'imgurl' => array(
            'type' => 'string.@',
            'multiple' => true
          ) ,
          'images' => array(
            'type' => 'Dropshipping.Images',
             'multiple' => true
          ) ,
          'videourl' => array(
            'type' => 'string.@',
            'multiple' => true
          ) ,
          'price_vat' => array(
            'type' => 'decimal.@',
            'multiple' => true
          ) ,
          'price' => array(
            'type' => 'decimal.@',
            'multiple' => true
          ) ,
          /*'minimal_price_vat' => array(
            'type' => 'decimal.@',
            'multiple' => true
          ) ,
          'minimal_price' => array(
            'type' => 'decimal.@',
            'multiple' => true
          ) ,*/
          'price_wholesale' => array(
            'type' => 'decimal.@',
            'multiple' => true
          ) ,
          'vat' => array(
            'type' => 'decimal.@',
            'multiple' => true
          ) ,
          'currency' => array(
            'type' => 'string.@',
            'multiple' => true
          ) ,
          /*'clearance' => array(
            'type' => 'int.@',
            'multiple' => true
          ) ,*/
          'delivery_date' => array(
            'type' => 'int.@',
            'multiple' => true
          ) ,
          'warehouse_quantity' => array(
            'type' => 'int.@',
            'multiple' => true
          ) ,
          /*'inhouse_quantity' => array(
            'type' => 'int.@',
            'multiple' => true
          ) ,
          'partner_quantity' => array(
            'type' => 'int.@',
            'multiple' => true
          ) ,
          'partner_delivery_date' => array(
            'type' => 'int.@',
            'multiple' => true
          ) ,
          'special_delivery_prices' => array(
            'type' => 'Dropshipping.SpecialDeliveryPrices',
            'multiple' => true
          ) ,
          'blacklisted_delivery_ids' => array(
            'type' => 'Dropshipping.BlacklistedDeliveryIds',
            'multiple' => true
          ) ,*/
        ) ,

        'methods' => array(
          'getProducts' => array(
            'protocol' => 'http',
            'operation' => 'get',

            'responseContentCodec' => 'text/xml',

            'cacheInterval' => 1800,

            'chunkMemLimit' => TRUE,
            'chunkSplit' => array('<SHOPITEM>', '</SHOPITEM>'),
            'chunkSize' => 250,

            'address' => '{url}',

            'charset' => 'UTF-8',

            'arguments' => array(
              'url' => array(
                'type' => 'string',
                'encode' => false
              )
            ) ,

            'result' => array(
              'type' => 'Dropshipping.ShopResponse',
              //'multiple' => true,
            )
          ) ,

          'getProductsByEshop' => array(
            'protocol' => 'http',
            'operation' => 'get',

            'responseContentCodec' => 'application/json',

            'address' => '/products?eshop_id={eshop_id}&limit=10000',

            'charset' => 'UTF-8',

            'arguments' => array(
              'eshop_id' => array(
                'type' => 'int',
              )
            ) ,

            'result' => array(
              'type' => 'object',
            )
          ) ,

        ) ,
      ) ,

      'Dropshipping.EshopResponse' => array(
        'type' => 'object',

        'properties' => array(
          'code' => array(
            'type' => 'int'
          ),
          'message' => array(
            'type' => 'string'
          ),
          'data' => array(
            'type' => 'Dropshipping.Eshop',
            'multiple' => true
          )
        )
      ),


      'Dropshipping.Eshop' => array(
        'type' => 'object',

        'properties' => array(
          'id' => array(
            'type' => 'int'
          ),
          'name' => array(
            'type' => 'string'
          ),
          'www' => array(
            'type' => 'string'
          ),
          'product_xml' => array(
            'type' => 'string'
          )
        ),

        'methods' => array(
          'getEshops' => array(
            'protocol' => 'http',
            'operation' => 'get',

            'responseContentCodec' => 'application/json',

            'address' => '/eshops',

            'charset' => 'UTF-8',

            'arguments' => array(
            ) ,

            'result' => array(
              'type' => 'Dropshipping.ShopResponse',
            )
          ) 
        ) 
      )
    );
  }
}

?>
