<?php

namespace vdws\controllers;

use vdws\lib\PenguinPickupAPI;
use vdws\lib\PudoAPI;

class APIController extends PluginController {

    private $pudoPartnerCode;
    private $pudoPassword;
    private $pudoEnabled;
    private $ppApplicationID;
    private $ppKey;
    private $ppEnabled;

    public function init() {
        $this->pudoEnabled = get_option('wc_settings_tab_map_pudo_enable');
        $this->pudoPartnerCode = get_option('wc_settings_tab_map_pudo_code');
        $this->pudoPassword = get_option('wc_settings_tab_map_pudo_password');

        $this->ppEnabled = get_option('wc_settings_tab_map_pp_enable');
        $this->ppApplicationID = get_option('wc_settings_tab_map_pp_id');
        $this->ppKey = get_option('wc_settings_tab_map_pp_key');
    }

    public function getLocations($position, $zip) {
        $pudo = array();
        $pp = array();

        if ($this->pudoEnabled == 'yes' && !empty($this->pudoPartnerCode) && !empty($this->pudoPassword) && isset($zip))
            $pudo = $this->_getPudoLocation($zip);

        if ($this->ppEnabled == 'yes' && !empty($this->ppApplicationID) && !empty($this->ppKey))
            $pp = $this->_getPenguinLocation($position);
        
        return array_merge($pudo, $pp);
    }

    /**
     * Return adapt array with PUDO locations
     * 
     * @param string $zip user zip code
     * @return array
     */
    private function _getPudoLocation($zip) {
        global $woocommerce;

        $cart = $woocommerce->cart->get_cart();

        $weight = $width = $height = $length = 0;
        foreach ($cart as $values) {
            $_product = wc_get_product($values['data']->get_id());
            $weight += $_product->get_weight() * $values["quantity"];
//            $width += $_product->get_width();
//            $height += $_product->get_height();
//            $length += $_product->get_length();
        }

        $pudo = new PudoAPI($this->pudoPartnerCode, $this->pudoPassword);

        $params = array(
            'address' => $zip,
            'weight' => $weight,
            'weightUnit' => "KG",
            'dimensionUnit' => "CM"
        );

        $locations = $pudo->searchDealers($params);

        $country = array(
            'Canada' => 'CA',
            'United States' => 'US'
        );

        $province = array(
            'Alberta' => "AB",
            'British Columbia' => "BC",
            'Manitoba' => "MB",
            'New Brunswick' => "NB",
            'Newfoundland and Labrador' => "NL",
            'Northwest Territories' => "NT",
            'Nova Scotia' => "NS",
            'Nunavut' => "NU",
            'Ontario' => "ON",
            'Prince Edward Island' => "PE",
            'Quebec' => "QC",
            'Saskatchewan' => "SK",
            'Yukon Territory' => "YT"
        );

        $return = array();
        foreach ($locations as $key => $value) {
            $return[] = array(
                'carrier' => 'PUDO',
                'lat' => $value['dealerLatitude'],
                'lng' => $value['dealerLongitude'],
                'city' => $value['dealerCity'],
                'address_1' => $value['dealerAddress1'],
                'address_2' => $value['dealerAddress2'],
                'zip' => $value['dealerPostal'],
                'country' => $country[$value['dealerCountry']],
                'province' => $province[$value['dealerProvince']],
                'location_name' => $value['dealerName'],
                'address' => $value['dealerCity'] . ', ' . $value['dealerAddress1'] . ' ' . $value['dealerAddress2']
            );
        }

        return $return;
    }

    /**
     * Return adapt array with Penguin Pickup locations
     * 
     * @param array $position Array with customer position according to the Google map format
     * @return array
     */
    private function _getPenguinLocation($position = array()) {
        $pp = new PenguinPickupAPI($this->ppApplicationID, $this->ppKey);

        $locations = $pp->getLocations();
        $closest = $this->_getClosest($position, $locations);

        $return = array();
        foreach ($closest as $key => $value) {
            $return[] = array(
                'carrier' => 'Penguin Pickup',
                'lat' => $value['Latitude'],
                'lng' => $value['Longitude'],
                'city' => $value['City'],
                'address_1' => $value['AddressLine1'],
                'address_2' => $value['AddressLine2'],
                'zip' => $value['PostalCode'],
                'province' => $value['Province'],
                'country' => 'CA',
                'location_name' => $value['LocationName'],
                'address' => $value['City'] . ', ' . $value['AddressLine1'] . ' ' . $value['AddressLine2']
            );
        }

        return $return;
    }

    /**
     * Return closest $count locations
     * 
     * @param array $position Array with customer position according to the Google map format
     * @param array $locations List of Penguin Pickup locations
     * @param int $count Number of locations to return
     * @return array
     */
    private function _getClosest($position = array(), $locations = array(), $count = 10) {
        foreach ($locations as $key => $location) {
            $distance = $this->_getDistance($position['lat'], $position['lng'], $location['Latitude'], $location['Longitude']);
            $locations[$key]['distance'] = $distance;
        }

        usort($locations, function($a, $b) {
            return $a['distance'] - $b['distance'];
        });

        return array_slice($locations, 0, $count);
    }

    /**
     * Returns the distance between two points of coordinates
     * 
     * @param float $lat_customer starting Latitude
     * @param float $lon_customer starting Longitude
     * @param float $lat_partner target Latitude
     * @param float $lon_partner target Longitude
     * @return float
     */
    private function _getDistance($lat_customer, $lon_customer, $lat_partner, $lon_partner) {
        $earth_radius = 6372795;

        $lat_customer = $lat_customer * M_PI / 180;
        $lon_customer = $lon_customer * M_PI / 180;
        $lat_partner = $lat_partner * M_PI / 180;
        $lon_partner = $lon_partner * M_PI / 180;

        $cos_customer = cos($lat_customer);
        $cos_partner = cos($lat_partner);
        $sin_customer = sin($lat_customer);
        $sin_partner = sin($lat_partner);

        $delta = $lon_customer - $lon_partner;

        $cdelta = cos($delta);
        $sdelta = sin($delta);

        $y = sqrt(pow($cos_partner * $sdelta, 2) + pow($cos_customer * $sin_partner - $sin_customer * $cos_partner * $cdelta, 2));
        $x = $sin_customer * $sin_partner + $cos_customer * $cos_partner * $cdelta;

        $ad = atan2($y, $x);
        $distance = $ad * $earth_radius;

        return $distance;
    }

}
