<?php 

include_once __DIR__ . '/../Http/Request.php';
include_once __DIR__ . '/../Http/Cache.php';
include_once __DIR__ . '/Extract/Extract.php';
include_once __DIR__ . '/../Response/Response.php';
include_once __DIR__ . '/Model/ServiceRo.php';
include_once __DIR__ . '/Model/Employee.php';
include_once __DIR__ . '/Model/Types.php';
include_once __DIR__ . '/TestSuite/TestSuite.php';
include_once __DIR__ . '/Parser/Parser.php';

class Core extends Parser {

    public function __construct(
        public $authentication,
        public $environment,
        public $lines = false,
        public $testSuiteConfig = [],
        public $cache = false,
        public $cacheDir = ''
    ){

        // -- Set Test Suite ::
        $this->tests = new TestSuite();

        $this->setConfig();
        $this->http = new Http($this->config->global());
        $this->httpCache = new HttpCache($this->cacheDir);
        $this->extract = new Extract();
        $this->response = new Response();
        $this->serviceRo = new ServiceRo();
        $this->employee = new Employee();
        $this->types = new Types();
    }

    public function extract($data = [], $map = ['master' => [], 'prtextended' => []]){

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

        $response = false;
        if($this->cache){
            $response = $this->httpCache->get($data['type'],$cleanParams);
        }
        if(!$response){
            $response = $this->http->post($data['type'],$cleanParams);
        }

        if($this->cache && $response){
            $cache = $this->httpCache->save($data['type'],$cleanParams,$response);
        }

        if(isset($response['status'])){
            return [
                'status' => 'error', 
                'message' => $response['message'],
                'raw-response' => $response
            ];
        }

        /**
         *  @ Setup Mappers ::
        */
        $feeMap = [];
        $prtsMap = [];
        if(isset($map['fee'])){
            $feeMap = $map['fee'];
        }
        if(isset($map['prtextended'])){
            $prtsMap = $map['prtextended'];
        }
        $map = $map['master'];

        $items = json_decode(
            json_encode(
                (array)simplexml_load_string($response['response'], 'SimpleXMLElement', LIBXML_NOCDATA)
        ), 
        true);

        $responseObj = $this->types->renderTypeObj($data['type']);
        if(!isset($items[$responseObj])){
            return [
                'status' => 'error',
                'message' => 'No data available for this request.',
                'returned' => $items,
                'xml-response' => $response['response'],
                'raw-response' => $response
            ];
        }

        $extractData = [];
        foreach($items[$responseObj] as $item){

            if($this->lines && (in_array($data['type'],$this->types->roServiceTypes())))
            {
                $RO = $item[$this->serviceRo->RONUMBER];
                
                $extractPartsCost = $this->parsePartsData($item,$prtsMap);
                $extractPartsPercent = $this->parsePartsDataPercent($item);
                $partsCostMap = $this->mapToPartsCost(
                    $item,
                    $extractPartsCost,
                    $extractPartsPercent,
                    $prtsMap
                );

                if(isset($item[$this->serviceRo->LBRLINECODE]['V'])){
                    $lineCount = count((array)$item[$this->serviceRo->LBRLINECODE]['V']);
                    for($i=0;$i<$lineCount;$i++){
                        $extractData[] = $this->parseResponse($item,$map,$i,$partsCostMap);
                    }
                }

                if(isset($item[$this->serviceRo->FEEOPCODE]) && isset($item[$this->serviceRo->FEEOPCODE]['V'])){
                    $lineCount = count((array)$item[$this->serviceRo->FEEOPCODE]['V']);
                    for($i=0;$i<$lineCount;$i++){
                        $extractData[] = $this->parseResponse($item,$feeMap,$i,$partsCostMap,$this->serviceRo->feeOpCodeSkip(),true);
                    }
                }

            }else {
                $extractData[] = $this->parseResponseRaw($item,$map);
            }
        }

        return $extractData;
    }




    private function parseResponse($data,$map,$number = 0, $partsCostMap = [], $ignored = [], $isFeeLine = false, $keyNumbers = []){

        $response = [];
        $fields = array_values($map);
        $keys = array_keys($map);
        $count = count($fields);
        
        for($i=0;$i<$count;$i++){

            if(in_array($fields[$i],$ignored)){
                $response[$fields[$i]] = '';
                continue;
            }


            if(
                ($fields[$i] === $this->serviceRo->PRTEXTENDEDCOST) ||
                ($fields[$i] === $this->serviceRo->PRTEXTENDEDSALE)
            ){

                if($isFeeLine){
                    $response[$keys[$i]] = 0;
                }else {
                    if(!isset( $partsCostMap[$number][$keys[$i]])){
                        $response[$keys[$i]] = 0;
                    }else {
                        $response[$keys[$i]] = $partsCostMap[$number][$keys[$i]];
                    }   
                }
                continue;
            }
            
            
            if(isset($data[$fields[$i]]['V'])){
                if(is_array($data[$fields[$i]]['V'])){
                    if(isset($data[$fields[$i]]['V'][$number])){

                        if(in_array($fields[$i],$this->serviceRo->asNumber()))
                        {
                            $response[$keys[$i]] = $this->cleanResponse($data[$fields[$i]]['V'][$number],true);
                        }
                        else 
                        {
                            $response[$keys[$i]] = $this->cleanResponse($data[$fields[$i]]['V'][$number]);
                        }

                    }
                }else {
                    $response[$keys[$i]] = $this->cleanResponse($data[$fields[$i]]['V']);
                }
            }else {
                $response[$keys[$i]] = $this->cleanResponse(
                    $this->convertBlankArrayData($data[$fields[$i]])
                );
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
            $response[$keys[$i]] = $this->cleanResponse($data[$fields[$i]]);
        }
        return $response;
    }

}