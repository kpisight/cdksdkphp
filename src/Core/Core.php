<?php 

include_once __DIR__ . '/../Http/Request.php';
include_once __DIR__ . '/Extract/Extract.php';
include_once __DIR__ . '/../Response/Response.php';

class Core {

    public function __construct(
        public $authentication,
        public $environment
    ){
        $this->setConfig();
        $this->http = new Http($this->configData());
        $this->extract = new Extract();
        $this->response = new Response();
    }

    public function extract($data = []){

        if(!isset($data['request'])){
            return $this->response->errorResponse("Missing 'request' param in SDK object.", false);
        }
        if(!isset($data['type'])){
            return $this->response->errorResponse("Missing 'type' param in SDK object.", false);
        }

        $cleanParams = [];
        foreach($data['request'] as $key => $value){
            $cleanParams = $this->extract->queryBuilder($cleanParams, [$key => $value]);
        }

        $response = $this->http->post($data['type'],$cleanParams);
        if(isset($response['status'])){
            return [
                'status' => 'error', 
                'message' => $response['message']
            ];
        }

        $response = simplexml_load_string($response);
        return $response;

    }

}