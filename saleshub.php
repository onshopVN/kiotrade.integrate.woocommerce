<?php
defined( 'ABSPATH' ) || exit;

/*
Plugin name: SalesHub Integeration
Plugin URI: https://saleshub.com
Description: Fully integrate with SalesHub service.
Version: 1.0.0
Author: SalesHub
Author URI: https://saleshub.com
License: GPLv2 or later
Text Domain: saleshub
*/

if ( in_array( 'woocommerce/woocommerce.php' , apply_filters( 'active_plugins' , get_option( 'active_plugins' ) ) ) ) {
    define( 'SALESHUB_VERSION' , '1.0.0' );
    define( 'SALESHUB_PLUGIN_NAME' , plugin_basename(__FILE__) );
    define( 'SALESHUB_DIR' , plugin_dir_path(__FILE__) );

    require_once( SALESHUB_DIR . 'class.saleshub.php' );

    new SalesHub();
}