<?php

namespace vdws\lib;

class PenguinPickupAPI {

    /**
     * Penguin Pickup user id
     * @var string 
     */
    private $applicationID;
    
    /**
     * Penguin Pickup api key
     * @var string 
     */
    private $key;

    public function __construct($applicationID, $key) {
        $this->applicationID = $applicationID;
        $this->key = $key;
    }

    /**
     * Location service will provide a list of all active PPU Locations
     * 
     * @return array
     */
    public function getLocations() {
        $url = 'https://api-ppustg.azurewebsites.net/partner/Location/GetList';
        
        return $this->_getConnection($url);
    }
    
    public function register() {
        
    }

    private function _getConnection($url) {
        $credentials = base64_encode($this->applicationID . ':' . $this->key);

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Basic ' . $credentials));

        $result = curl_exec($ch);

        curl_close($ch);

        return json_decode($result, true);
    }

}
