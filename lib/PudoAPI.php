<?php

namespace vdws\lib;

class PudoAPI {

    /**
     * PUDO API requests url
     * @var string 
     */
    private $apiUrl = 'https://partnerapi.pudoinc.com/PUDOService.svc/';

    /**
     * PUDO Business Partner Code 
     * @var string
     */
    private $partnerCode;

    /**
     * PUDO Business Partner Password
     * @var string 
     */
    private $password;

    public function __construct($partnerCode, $password) {
        $this->partnerCode = $partnerCode;
        $this->password = $password;
    }

    /**
     * Returns a list of the 10 closest PUDO Points to a Postal Code
     * Weight and Dimensions are required
     * 
     * @param array $params Array with request parameters
     * @return array|string
     */
    public function searchDealers($params = array()) {   
        $result = $this->_getConnection($params);

        if($result["Result"] != "SUCCESS")
            return $result["ErrorMessage"];
        else
            return $result['dealers'];
    }

    /**
     * Returns a list of Return To addresses 
     * Weight and Dimensions are not needed
     * 
     * @param array $params Array with request parameters
     * @return array|string
     */
    public function searchReturnTo($params = array()) {
        $result = $this->_getConnection($params);

        if($result["Result"] != "SUCCESS")
            return $result["ErrorMessage"];
        else
            return $result['dealers'];
    }

    /**
     * Returns a list of addresses a Business Partner can ship from 
     * Weight and Dimensions are not needed
     * 
     * @param array $params Array with request parameters
     * @return array|string
     */
    public function searchShipFrom($params = array()) {
        $result = $this->_getConnection($params);

        if($result["Result"] != "SUCCESS")
            return $result["ErrorMessage"];
        else
            return $result['dealers'];
    }

    public function placeShipment($params = array()) {
        
    }

    public function rateShipment($params = array()) {
        
    }

    public function processShipment($params = array()) {
        
    }

    public function requestPickup($params = array()) {
        
    }

    public function pickupOccurred($params = array()) {
        
    }

    /**
     * Returns a list of all shipments sent out using PUDO
     * 
     * @param array $params
     * @return type
     */
    public function listShipments($params = array()) {
        $result = $this->_getConnection($params);
        
        if($result["Result"] != "SUCCESS")
            return $result["ErrorMessage"];
        else
            return $result['Shipments'];
    }

    private function _getConnection($params = array()) {
        $params['partnerCode'] = $this->partnerCode;
        $params['partnerPassword'] = $this->password;

        $data = debug_backtrace(0, 3);
        $method = ucfirst($data[1]["function"]);

        $url = $this->apiUrl . $method;

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));

        $result = curl_exec($ch);

        curl_close($ch);

        return json_decode($result, true);
    }

}
