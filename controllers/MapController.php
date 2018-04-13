<?php

namespace vdws\controllers;

use vdws\components\Plugin;

class MapController extends PluginController {

    public $googleAPIKey;

    public function init() {
        $this->googleAPIKey = get_option('wc_settings_tab_map_apikey');

        parent::init();
    }

    public function wpActionWpEnqueueScripts() {
        if (is_checkout()) {
            wp_enqueue_style(Plugin::SHORT_NAME . '-style', Plugin::$url . '/views/css/style.css');

            wp_enqueue_script(Plugin::SHORT_NAME . '-map-script', Plugin::$url . '/views/js/map.js', array(), '', true);
            wp_enqueue_script(Plugin::SHORT_NAME . '-google-map', "https://maps.googleapis.com/maps/api/js?key=" . $this->googleAPIKey, array(), '', true);
            wp_enqueue_script(Plugin::SHORT_NAME . '-google-map-marker', "https://developers.google.com/maps/documentation/javascript/examples/markerclusterer/markerclusterer.js", array(), '', true);

            wp_localize_script(Plugin::SHORT_NAME . '-map-script', Plugin::SHORT_NAME . '_data', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce(Plugin::SHORT_NAME . '_nonce')
            ));
        }
    }

    public function wpActionWoocommerceBeforeCheckoutForm() {
        echo $this->render('checkout');
    }

    public function ajaxActionFindCarrierLocations() {
        $nonce = filter_input(INPUT_POST, 'nonce');
        $position = filter_input(INPUT_POST, 'position', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
        $zip = filter_input(INPUT_POST, 'zip');

        if (!wp_verify_nonce($nonce, Plugin::SHORT_NAME . '_nonce')) {
            wp_send_json_error('Access denied');
        }

        $api = new APIController();
        $locations = $api->getLocations($position, $zip);

        wp_send_json_success($locations);
    }

}
