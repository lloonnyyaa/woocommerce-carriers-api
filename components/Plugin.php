<?php

namespace vdws\components;

use vdws\controllers\MapController;

class Plugin {

    const VERSION = '1.0.0';
    const NAME = 'Woo shipping';
    const SHORT_NAME = 'vdws';
    const TEXT_DOMAIN = 'pp_shipping';

    public static $config;
    public static $path;
    public static $url;
    
    public static $mapController;

    public static function run() {
        Plugin::$mapController = new MapController();
    }

}
