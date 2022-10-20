<?php 

class Http {

    public function __construct(
        public $credentials = [],
        public $headerType = 'xml',
        public $testEnv = 'uat-3pa',
        public $liveEnv = 'uat',
        public $endpointDomain = 'dmotorworks.com',
        public $pipExtract = 'pip-extract'
    ){
        $this->set();
    }

    public function post($endpoint,$data = []){

        $additionalHeaders = '';

        $requestUrl = $this->apiEndpoint . '/' . $endpoint . '?' . http_build_query($data);
        $requestHeaders = ['Content-Type: ' . $this->setHeader, $additionalHeaders];

        $ch = curl_init($requestUrl);

        curl_setopt($ch, CURLOPT_HTTPHEADER, $requestHeaders);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_USERPWD, $this->username . ":" . $this->password);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_POST, 1);
        //curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);

        // Manage Response :: 
        if (!curl_errno($ch)) {
            $info = curl_getinfo($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $response = $this->response($info,$httpCode,$response);
        }

        curl_close($ch);

        return [
            'request' => [
                'headers' => $requestHeaders, 
                'url' => $request
            ], 
            'response' => $response
        ];

    }

    private function set(){
        $this->setUsername();
        $this->setPassword();
        $this->setEnvironment();
        $this->setHeaderType();
        $this->setLabels();
        $this->setEndpoint();
    }

    private function setHeaderType(){
        switch($this->headerType){
            case 'json' : $this->setHeader = 'application/json'; break;
            case 'xml' : default : $this->setHeader = 'application/xml'; break;
        }
    }

    private function setUsername(){
        $this->username = $this->credentials['username'];
    }

    private function setPassword(){
        $this->password = $this->credentials['password'];
    }

    private function setEnvironment(){
        $this->env = $this->credentials['environment'];
    }

    private function setLabels(){
        $this->labels = $this->credentials['labels'];
    }

    private function setEndpoint(){
        $this->apiEndpoint = 
            'https://' . ($this->env == $this->labels['dev'] ? $this->testEnv : $this->liveEnv) . '.' . $this->endpointDomain . '/' . $this->pipExtract . '/';
    }

    private function response($info,$http_code,$response){

        if($http_code === 200 || $http_code === '200'){
            return $response;
        }

        if(!isset($reponse[1])){
            return [
                'status' => 'error',
                'message' => $response
            ];
        }

        list($headers, $body) = explode("\r\n\r\n", $response, 2);
        switch ($http_code)
        {
            case 200 : case '200' : break;
            case 401 : case '401' :
                 
                $response = [
                    'status' => 'unauthorized', 
                    'code' => $http_code,
                    'response' => $info,
                    'headers' => $headers,
                    'message' => $body
                ];
                break;

            default:
                $response = [
                    'status' => 'error',
                    'code' => $http_code,
                    'response' => $info,

                    'headers' => $headers,
                    'message' => $body
                ];
            break;
        
        }
        return $response;
    }

}