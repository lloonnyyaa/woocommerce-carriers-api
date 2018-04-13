<?php

/*
  Plugin Name: Woo shipping
  Description: PUDO & Penguin Pickup shipping for WC
  Version: 1.0
  Author: Viaduct.pro
  Text Domain: pp_shipping
 */

namespace vdws;

use vdws\components\Plugin;

if (!defined('WPINC') || !in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    die;
}

function autoload($class) {
    $class = ltrim($class, '\\');
    if (strpos($class, __NAMESPACE__) !== 0) {
        return;
    }

    $class = str_replace(__NAMESPACE__, '', $class);

    $path = plugin_dir_path(__FILE__) . str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';

    require_once( $path );
}

spl_autoload_register(__NAMESPACE__ . '\\autoload');

Plugin::$path = plugin_dir_path(__FILE__);
Plugin::$url = plugin_dir_url(__FILE__);
Plugin::run();