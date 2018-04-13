<?php

namespace vdws\controllers;

use vdws\components\Plugin;

class PluginController {

    const ACTION_PREFIX = 'wpAction';
    const FILTER_PREFIX = 'wpFilter';
    const AJAX_ACTION_PREFIX = 'ajaxAction';

    public function __construct() {
        $this->init();
    }

    public function init() {
        $this->initActions();
    }

//    public function wpFilterPluginActionLinks($links) {        
//        $settings_link = add_query_arg(array('page' => 'wc-settings', 'tab' => 'map',), admin_url('admin.php'));
//
//        array_push($links, '<a href="' . esc_url($settings_link) . '">' . __('Settings', Plugin::TEXT_DOMAIN) . '</a>');
//        return $links;
//    }

    public function wpActionWoocommerceSettingsTabsArray($sections) {
        $sections['map'] = __('Map', Plugin::TEXT_DOMAIN);
        return $sections;
    }

    public function wpActionWoocommerceSettingsTabsMap() {
        woocommerce_admin_fields($this->getSettigs());
    }

    public function wpActionWoocommerceUpdateOptionsMap() {
        woocommerce_update_options($this->getSettigs());
    }

    public function getSettigs() {
        $settings = array(
            'gm_section_title' => array(
                'name' => __('Google map settings', Plugin::TEXT_DOMAIN),
                'type' => 'title',
                'id' => 'wc_settings_tab_map_section_title'
            ),
            'gm_api' => array(
                'name' => __('Google maps Api Key', Plugin::TEXT_DOMAIN),
                'type' => 'text',
                'desc' => __('Enter google maps api key', Plugin::TEXT_DOMAIN),
                'id' => 'wc_settings_tab_map_apikey'
            ),
            'g_section' => array(
                'type' => 'sectionend',
                'id' => 'wc_settings_tab_map_g_section_end'
            ),
            'pudo_section_title' => array(
                'name' => __('PUDO settings', Plugin::TEXT_DOMAIN),
                'type' => 'title',
                'id' => 'wc_settings_tab_map_section_pudo'
            ),
            'pudo_enable' => array(
                'name' => __('Enable PUDO', Plugin::TEXT_DOMAIN),
                'type' => 'checkbox',
                'id' => 'wc_settings_tab_map_pudo_enable'
            ),
            'pudo_code' => array(
                'name' => __('PUDO partner code', Plugin::TEXT_DOMAIN),
                'type' => 'text',
                'id' => 'wc_settings_tab_map_pudo_code'
            ),
            'pudo_pass' => array(
                'name' => __('PUDO password', Plugin::TEXT_DOMAIN),
                'type' => 'text',
                'id' => 'wc_settings_tab_map_pudo_password'
            ),
            'pudo_section' => array(
                'type' => 'sectionend',
                'id' => 'wc_settings_tab_map_pudo_section_end'
            ),
            'pp_section_title' => array(
                'name' => __('Penguin Pickup settings', Plugin::TEXT_DOMAIN),
                'type' => 'title',
                'id' => 'wc_settings_tab_map_section_pp'
            ),
            'pp_enable' => array(
                'name' => __('Enable PP', Plugin::TEXT_DOMAIN),
                'type' => 'checkbox',
                'id' => 'wc_settings_tab_map_pp_enable'
            ),
            'pp_id' => array(
                'name' => __('Penguin Pickup Application ID', Plugin::TEXT_DOMAIN),
                'type' => 'text',
                'id' => 'wc_settings_tab_map_pp_id'
            ),
            'pp_key' => array(
                'name' => __('Penguin Pickup Key', Plugin::TEXT_DOMAIN),
                'type' => 'text',
                'id' => 'wc_settings_tab_map_pp_key'
            ),
            'pp_section' => array(
                'type' => 'sectionend',
                'id' => 'wc_settings_tab_map_pp_section_end'
            )
        );
        return apply_filters('wc_settings_tab_map_settings', $settings);
    }

    public function wpActionAdminNotices() {
        $has_errors = false;

        if (get_option('wc_settings_tab_map_pudo_enable') == 'no' && get_option('wc_settings_tab_map_pp_enable') == 'no') {
            $msg = 'For the correct work Woo shipping plugin, you need to enable one of the carriers: ';
            $has_errors = true;
        }
        if (empty(get_option('wc_settings_tab_map_apikey'))) {
            $msg = 'Please, configure the Woo shipping plugin: ';
            $has_errors = true;
        }
        if ($has_errors):
            ?>
            <div class="notice notice-warning is-dismissible">
                <p><?php echo $msg ?> <a href="<?php echo get_admin_url() ?>admin.php?page=wc-settings&tab=map">settings</a></p>
            </div>
            <?php
        endif;
    }

    public function wpActionWoocommerceShippingInit() {
        require Plugin::$path . '/woocommerce/ShippingClass.php';
    }

    public function wpFilterWoocommerceShippingMethods($methods) {
        $methods['custom_shipping_method'] = \vdws\woocommerce\WC_Custom_Shipping_Method::class;
        return $methods;
    }

    public function initActions() {
        $reflection_class = new \ReflectionClass($this);

        $actions_patern = '/^' . self::ACTION_PREFIX . '(.*)$/';
        $filters_patern = '/^' . self::FILTER_PREFIX . '(.*)$/';
        $ajax_actions_patern = '/^' . self::AJAX_ACTION_PREFIX . '(.*)$/';

        foreach ($reflection_class->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            if (preg_match($actions_patern, $method->name, $m)) {
                $action = strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $m[1]));
                add_action($action, array($this, $method->name), 30);
            } elseif (preg_match($ajax_actions_patern, $method->name, $m)) {
                $action = strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $m[1]));
                $wp_action = 'wp_ajax_' . Plugin::SHORT_NAME . '_' . $action;
                add_action($wp_action, array($this, $method->name));
                $wp_action_nopriv = 'wp_ajax_nopriv_' . Plugin::SHORT_NAME . '_' . $action;
                add_action($wp_action_nopriv, array($this, $method->name));
            } elseif (preg_match($filters_patern, $method->name, $m)) {
                $filter = strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $m[1]));
                add_filter($filter, array($this, $method->name));
            }
        }
    }

    public function render($view, $params = array()) {
        $file = Plugin::$path . 'views' . DIRECTORY_SEPARATOR . $view . '.php';
        if (file_exists($file)) {
            ob_start();
            ob_implicit_flush(false);
            extract($params, EXTR_OVERWRITE);
            require( $file );

            return ob_get_clean();
        } else {
            return '';
        }
    }

}
