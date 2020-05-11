<?php
defined( 'ABSPATH' ) || exit;

class Saleshub
{
    private $baseUrl = null;

    public function __construct()
    {
        add_action('before_woocommerce_init', [$this, 'init']);
    }

    public function init()
    {
        if ( !defined( 'SALESHUB_INITIALIZED' ) ) {
            define( 'SALESHUB_INITIALIZED', true );
            
            require_once( SALESHUB_DIR . 'class.saleshub-wc-integration.php' );
            add_filter( 'woocommerce_integrations' , [ $this, 'add_wc_intergration' ] );

            add_filter( 'plugin_action_links_' . SALESHUB_PLUGIN_NAME, [ $this , 'plugin_action_links' ]);
            add_action( 'wp_head' , [ $this, 'load_script_js' ]);
            add_action( 'woocommerce_after_single_product', [ $this, 'send_product_click' ] );
            add_action( 'woocommerce_order_status_changed', [ $this, 'send_order' ], 10, 4 );
        }
    }

    public function get_remote_ip()
    {
        $ipaddress = "";
	    if (getenv("HTTP_CLIENT_IP")) $ipaddress = getenv("HTTP_CLIENT_IP");
	    else if(getenv("HTTP_X_FORWARDED_FOR")) $ipaddress = getenv("HTTP_X_FORWARDED_FOR");
	    else if(getenv("HTTP_X_FORWARDED")) $ipaddress = getenv("HTTP_X_FORWARDED");
	    else if(getenv("HTTP_FORWARDED_FOR")) $ipaddress = getenv("HTTP_FORWARDED_FOR");
	    else if(getenv("HTTP_FORWARDED")) $ipaddress = getenv("HTTP_FORWARDED");
	    else if(getenv("REMOTE_ADDR")) $ipaddress = getenv("REMOTE_ADDR");
        else $ipaddress = "0";
        return $ipaddress;
    }

    public function get_base_url()
    {
        if ( !isset($this->baseUrl) ) {
            $integration = WooCommerce::instance()->integrations->get_integration( 'saleshub-integration' );
            if ( $integration instanceof Saleshub_WC_Integration ) {
                $this->baseUrl = $integration->get_option( 'saleshub_base_url' );
            } else {
                $this->baseUrl = false;
            }
            
        }

        return $this->baseUrl;
    }

    public function add_wc_intergration($intergrations)
    {
        $intergrations[] = 'Saleshub_WC_Integration';
        return $intergrations;
    }

    public function plugin_action_links($links)
    {
        $link = '<a href="' . menu_page_url('wc-settings', false) . '&tab=integration">Settings</a>';
        array_unshift($links, $link);

        return $links;
    }

    /**
     * Load js script
     */
    public function load_script_js()
    {
        if ( $this->get_base_url() ) {
        ?>
        <script src="<?php echo $this->get_base_url() ?>/js/script.min.js"></script>
        <?php 
        } 
    }

    /**
     * Send product click info to saleshub gateway
     */
    public function send_product_click()
    {
        if ( $this->get_base_url() ) {
            global $product;

            $url = $this->get_base_url() . '/g1/click';
            $params = [
                'product_id' => $product->get_sku() ?: $product->get_id(),
                'product_price' => $product->get_price(),
                'product_category' => [],
                'af_id' => isset($_COOKIE['af_id']) ? $_COOKIE['af_id'] : 0,
                'afc_id' => isset($_COOKIE['afc_id']) ? $_COOKIE['afc_id'] : 0,
                'ip' => $this->get_remote_ip(),
                'script_name' => 'woocommerce'
            ];
            foreach (get_the_terms( $product->get_id(), 'product_cat' ) as $term) {
                $params['product_category'][] = $term->name;        
            }
            $params['product_category'] = implode(',', $params['product_category']);

            wp_safe_remote_request($url, ['body' => $params]);
        }
    }

    /**
     * Send order info to saleshub gateway
     */
    public function send_order($order_id, $status_from, $status_to, $order)
    {
        if ( $status_from === 'pending' ) {
            // new order 
            if (($order->get_date_modified()->getTimestamp() - $order->get_date_created()->getTimestamp()) <= 5) { // 5 second
                $url = $this->get_base_url() . '/g1/product';
                $params[ 'body' ] = [
                    'code_json' => isset($_COOKIE['code_json']) ? $_COOKIE['code_json'] : 0,
                    'af_id' => isset($_COOKIE['af_id']) ? $_COOKIE['af_id'] : 0,
                    'afc_id' => isset($_COOKIE['afc_id']) ? $_COOKIE['afc_id'] : 0,
                    'ip' => $this->get_remote_ip(),
                    'script_name' => 'woocommerce',
                    'order_id' => $order->get_order_number(),
                    'order_date' => $order->get_date_created()->format('Y-m-d H:i:s'),
                    'order_total' => $order->get_total(),
                    'order_status' => 1, // new
                    'product_ids' => []
                ];
                
                foreach ($order->get_items() as $order_item) {
                    $product = $order_item->get_product();
                    $productCategory = [];
                    foreach (get_the_terms( $product->get_id(), 'product_cat' ) as $term) {
                        $productCategory[] = $term->name;        
                    }
                    $productCategory = implode( ',' , $productCategory);
                    $params[ 'body' ][ 'product_ids' ][] = [
                        $order_item->get_product_id(),
                        $order_item->get_subtotal(),
                        $order_item->get_quantity(),
                        $productCategory,
                        $order_item->get_name(),
                        $product->get_sku()
                    ];
                }
                wp_safe_remote_request($url, $params);
            }
    
        }
        if ( $status_to === 'completed' ) {
            // completed 
            $url = $this->get_base_url() . '/g1/order';
            $params['body'] = [
                'code_json' => isset($_COOKIE['code_json']) ? $_COOKIE['code_json'] : 0,
                'af_id' => isset($_COOKIE['af_id']) ? $_COOKIE['af_id'] : 0,
                'afc_id' => isset($_COOKIE['afc_id']) ? $_COOKIE['afc_id'] : 0,
                'ip' => $this->get_remote_ip(),
                'script_name' => 'woocommerce',
                'order_id' => $order->get_order_number(),
                'order_date' => $order->get_date_created()->format('Y-m-d H:i:s'),
                'order_total' => $order->get_total(),
                'order_status' => 4 // finish
            ];

            wp_safe_remote_request($url, $params);
        }
        if ( $status_to === 'cancelled' ) {
            // cancelled
            $url = $this->get_base_url() . '/g1/order';
            $params['body'] = [
                'code_json' => isset($_COOKIE['code_json']) ? $_COOKIE['code_json'] : 0,
                'af_id' => isset($_COOKIE['af_id']) ? $_COOKIE['af_id'] : 0,
                'afc_id' => isset($_COOKIE['afc_id']) ? $_COOKIE['afc_id'] : 0,
                'ip' => $this->get_remote_ip(),
                'script_name' => 'woocommerce',
                'order_id' => $order->get_order_number(),
                'order_date' => $order->get_date_created()->format('Y-m-d H:i:s'),
                'order_total' => $order->get_total(),
                'order_status' => 3 // cancelled
            ];

            wp_safe_remote_request($url, $params);
        }
        if ( $status_to === 'processing' ) {
            if ($order->get_date_paid('edit')) { // paid
                $url = $this->get_base_url() . '/g1/order';
            $params['body'] = [
                'code_json' => isset($_COOKIE['code_json']) ? $_COOKIE['code_json'] : 0,
                'af_id' => isset($_COOKIE['af_id']) ? $_COOKIE['af_id'] : 0,
                'afc_id' => isset($_COOKIE['afc_id']) ? $_COOKIE['afc_id'] : 0,
                'ip' => $this->get_remote_ip(),
                'script_name' => 'woocommerce',
                'order_id' => $order->get_order_number(),
                'order_date' => $order->get_date_created()->format('Y-m-d H:i:s'),
                'order_total' => $order->get_total(),
                'order_status' => 2 // paid
            ];

            wp_safe_remote_request($url, $params);
            }
        }
    }
}