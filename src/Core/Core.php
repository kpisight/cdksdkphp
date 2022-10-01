<?php 

include_once __DIR__ . '/../Http/Request.php';
include_once __DIR__ . '/Extract/Extract.php';
include_once __DIR__ . '/../Response/Response.php';
include_once __DIR__ . '/Model/ServiceRo.php';
include_once __DIR__ . '/Model/Types.php';

class Core {

    public function __construct(
        public $authentication,
        public $environment,
        public $lines = false
    ){
        $this->setConfig();
        $this->http = new Http($this->configData());
        $this->extract = new Extract();
        $this->response = new Response();
        $this->serviceRo = new ServiceRo();
        $this->types = new Types();
    }

    public function extract($data = [], $map = []){

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

        $items = json_decode(
            json_encode(
                (array)simplexml_load_string($response, 'SimpleXMLElement', LIBXML_NOCDATA)
        ), 
        true);

        $responseObj = $this->types->renderTypeObj($data['type']);
        if(!isset($items[$responseObj])){
            return [
                'status' => 'error',
                'message' => 'No data available for this request.',
                'returned' => $items
            ];
        }

        $extractData = [];
        foreach($items[$responseObj] as $item){
            if($this->lines){
                if(isset($item[$this->serviceRo->LBRLINECODE]['V'])){
                    $lineCount = count((array)$item[$this->serviceRo->LBRLINECODE]['V']);
                    for($i=0;$i<$lineCount;$i++){
                        $extractData[] = $this->parseResponse($item,$map,$i);
                    }
                }
            }else {
                $extractData[] = $this->parseResponseRaw($item,$map);
            }
        }
        return $extractData;
    }

    private function parseResponse($data,$map,$number = 0){
        $response = [];
        $fields = array_values($map);
        $keys = array_keys($map);
        $count = count($fields);
        for($i=0;$i<$count;$i++){
            if(isset($data[$fields[$i]]['V'])){
                if(is_array($data[$fields[$i]]['V'])){
                    $response[$keys[$i]] = $data[$fields[$i]]['V'][$number];
                }else {
                    $response[$keys[$i]] = $data[$fields[$i]]['V'];
                }
            }else {
                $response[$keys[$i]] = $this->convertBlankArrayData($data[$fields[$i]]);
            }
        }
        return $response;
    }


    private function parseResponseRaw($data,$map){
        $response = [];
        $fields = array_values($map);
        $keys = array_keys($map);
        $count = count($fields);
        for($i=0;$i<$count;$i++){
            $response[$keys[$i]] = $data[$fields[$i]];
        }
        return $response;
    }


    private function convertBlankArrayData($data){
        $isArray = is_array($data);
        if(!$isArray){
            return $data;
        }
        if(empty($data)){
            return '';
        }
        return $data;
    }




}