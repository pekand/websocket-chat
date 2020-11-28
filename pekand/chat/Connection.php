<?php

namespace pekand\Chat;

class Connection {

    private $options = [];
    private $endpoint = null;

    public function __construct($options, $endpoint) {
        $this->options = $options;
        $this->endpoint = $endpoint;
    }

    private function call($method = 'GET', $url = '', $params = [], $data = []) {
       
        $ch = curl_init();

        if($this->endpoint != null) {
            $url = $this->endpoint.$url;        
        }

        if (!empty($params) && ($method === 'GET' || $method === 'DELETE')) {
            $url .= '?' . http_build_query($params);
        }

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);


        if(isset($this->options['timeout'])) {
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->options['timeout']);
        }

        if(isset($this->options['bsaicAuth']) && $this->options['bsaicAuth'] === true && isset($this->options['username']) && isset($this->options['password'])) {
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($ch, CURLOPT_USERPWD, $this->options['username'].':'.$this->options['password']);
        }
        
        if(isset($this->options['skipSSL']) && $this->options['skipSSL'] === true) {
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        }

        if(isset($this->options['certificate'])) {
            curl_setopt($ch, CURLOPT_CAINFO, realpath($this->options['certificate']));
            curl_setopt($ch, CURLOPT_CAPATH, '.');
        }

        if (($method === 'POST' || $method === 'PUT' || $method === 'PATCH') && !empty($params)) {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        }

        if(!empty($data)) {
            $payload = json_encode($data);
            curl_setopt( $ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        }

        $response = curl_exec($ch);

        $error = false;
        $errno = 0;
        if (curl_errno($ch)) {
            $error = curl_error($ch);
            $errno = curl_errno($ch);
        }

        if (is_resource($ch)) {
            curl_close($ch);
        }

        if ($error) {
            throw new \Exception($errno .' : '. $error);
        }

        $jsonResponse = @json_decode($response, true);

        if(json_last_error() == JSON_ERROR_NONE) {
            return $jsonResponse;
        }

        return $response;
    }

    public function get($url = '', $params = [], $data = []) {
        return $this->call('GET', $url, $params, $data);
    }

    public function post($url = '', $params = [], $data = []) {         
        return $this->call('POST', $url, $params, $data);
    }

    public function put($url = '', $params = [], $data = []) {        
        return $this->call('PUT', $url, $params, $data);
    }

    public function patch($url = '', $params = [], $data = []) {        
        return $this->call('PATCH', $url, $params, $data);
    }

    public function delete($url = '', $params = []) {        
        return $this->call('DELETE', $url, $params);
    }
}


