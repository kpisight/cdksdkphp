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
include_once __DIR__ . '/Parser/Xml.php';
include_once __DIR__ . '/../Store/SharedStore.php';

class Core extends Parser {

    public function __construct(
        public $authentication,
        public $environment,
        public $lines = false,
        public $testSuiteConfig = [],
        public $cache = false,
        public $cacheDir = '',
        public $rawDir = '',
        public $sharedDir = ''
    ){

        // -- Set Test Suite ::
        $this->tests = new TestSuite();

        $this->setConfig();
        $this->http = new Http($this->config->global());
        $this->httpCache = new HttpCache($this->cacheDir);
        $this->sharedStore = new SharedStore($this->sharedDir);
        $this->extract = new Extract();
        $this->response = new Response();
        $this->serviceRo = new ServiceRo();
        $this->employee = new Employee();
        $this->types = new Types();
        $this->xml = new XmlHandler();
    }

    public function extract($data = [], $sharedFile = false, $fromStore = false){

        if(!isset($data['request'])){
            return $this->response->errorResponse("Missing 'request' param in SDK object.", false);
        }

        if(!isset($data['type'])){
            return $this->response->errorResponse("Missing 'type' param in SDK object.", false);
        }

        if($data['type'] === 'tests'){
            return $data['request']['dealerId'];
        }

        $rawObjKey = strtoupper(
            $this->makeKey(
                $this->createRandPhrase(5)
            )
        );

        $rawFile = __DIR__ . $this->config->rawDir() . $rawObjKey . '.cdk';
        $cleanParams = [];
        foreach($data['request'] as $key => $value){
            $cleanParams = $this->extract->queryBuilder($cleanParams, [$key => $value]);
        }

        if($fromStore){
            $rawData = $this->sharedStore->get($data['type'],$cleanParams);
            file_put_contents(
                $rawFile, $rawData
            );
            return $rawObjKey;
        }
        
        $response = false;
        if($this->cache){
            $response = $this->httpCache->get($data['type'],$cleanParams);
        }

        if(!$response){
            $response = $this->http->post($data['type'],$cleanParams,$rawFile,false);
        }

        if($this->cache && $response){
            file_put_contents(
                $rawFile, $response['response']
            );
            $cache = $this->httpCache->save($data['type'],$cleanParams,$rawFile);
        }

        if($sharedFile){
            $shared = $this->sharedStore->save($data['type'],$cleanParams,$rawFile);
        }

        return $rawObjKey;

    }

    public function renderObject($id, $data = [], $map = ['master' => [], 'prtextended' => []], $test){

        /**
         *  @ Get the DataObject ::
         */
        $rawFile = __DIR__ . $this->config->rawDir() . $id . '.cdk';
        if($test){
            $rawFile = $data['request']['dealerId'];
        }

        /**
         *  @ Break Down the Chunks
         */
        $rawDir = __DIR__ . $this->config->rawDir() . $id;
        if(!is_dir($rawDir)){
            if(!$test){
                mkdir($rawDir);
            }
        }

        $responseObj = $this->types->renderTypeObj($data['type']);
        $responseParentObj = $this->types->renderParentTypeObj($data['type']);

        /**
         *  @ Run Tests (If Set):
         */
        if($test){
            $testXml = $this->xml->test($rawFile,$responseObj,$responseParentObj);
            if(!$testXml){
                echo "ERROR! \n\n";
                return false;
            }
            return $testXml;
        }

        /**
         *  @ Save Chunked Data to Directory ::
         */
        $renderAllXmlChunks = $this->xml->readXml($rawFile,$rawDir,$responseObj,$responseParentObj);

        $availableChunks = array_values(array_diff(scandir($rawDir), array('.', '..')));
        $list = [];
        foreach($availableChunks as $chunk){
            $list[] = $rawDir . '/' . $chunk;
        }

        return $list;
 
    }

    public function handleDataFile($file, $data, $map, $split = []){

        $response = file_get_contents($file);
        $splitIndex = array_keys($split);

        if($splitIndex == 0){
            $dataResponse = $this->handleDataFileSplit($response, $data, $map, $file);

            /**
             *  @ Delete the RAW file ::
             */
            unlink($file);

            return $dataResponse;
        }

        $dataSplit = [];
        foreach($splitIndex as $index){
            $dataSplit[$index] = $this->handleDataFileSplit($response, $data, $split[$index]['map'], $file, $split[$index]['calc_parts']);
        }

        /**
         *  @ Delete the RAW file ::
         */
        unlink($file);

        return $dataSplit;

    }

    public function handleDataFileSplit($response, $data, $map, $file, $calcParts = true)
    {

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
                (array)simplexml_load_string($response, 'SimpleXMLElement', LIBXML_NOCDATA)
            ),
            true);

        $responseObj = $this->types->renderTypeObj($data['type']);
        if(!isset($items[$responseObj])){
            unlink($file);
            return [
                'status' => 'error',
                'message' => 'No data available for this request.',
                'returned' => $items,
                'xml-response' => $response
            ];
        }

        $extractData = [];
        if(!is_array($items[$responseObj])){
            unlink($file);
            return [
                'status' => 'error',
                'message' => 'No data available for this request.',
                'returned' => $items,
                'xml-response' => $response
            ];
        }

        if($this->isAssoc($items[$responseObj])){
            $items[$responseObj] = [$items[$responseObj]];
        }

        foreach($items[$responseObj] as $item)
        {

            if(!is_array($item)){
                echo "An Error Has Occured, the item is a string! \n\n";
                continue;
            }

            if($this->lines && (in_array($data['type'],$this->types->roServiceTypes())) && $calcParts)
            {
                if(!isset($item[$this->serviceRo->RONUMBER])){
                    continue;
                }

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
                        $extractData[] = $this->parseResponse($item,$map,$i,$partsCostMap,$calcParts);
                    }
                }

                if(isset($item[$this->serviceRo->FEEOPCODE]) && isset($item[$this->serviceRo->FEEOPCODE]['V'])){
                    $lineCount = count((array)$item[$this->serviceRo->FEEOPCODE]['V']);
                    for($i=0;$i<$lineCount;$i++){
                        $extractData[] =
                            $this->parseResponse(
                                $item,
                                $feeMap,
                                $i,
                                $partsCostMap,
                                $calcParts,
                                $this->serviceRo->feeOpCodeSkip(),
                                true);
                    }
                }

            }else {
                $lineCount = 1;
                if (isset($item[$this->serviceRo->PRTLINECODE]['V'])) {
                    $lineCount = count((array)$item[$this->serviceRo->PRTLINECODE]['V']);
                }
                for($i=0;$i<$lineCount;$i++) {
                    $extractData[] = $this->parseResponseRaw($item, $map, $i);
                }
            }
        }

        /**
         *  @ Return the Extracted Data ::
         */
        return $extractData;
    }


    public function handleDeconstruct($hash){
        // -- Remove Temp Directory ::
        rmdir(__DIR__ . $this->config->rawDir() . $hash);
        
        // -- Remove Temp File ::
        unlink(__DIR__ . $this->config->rawDir() . $hash . '.cdk');
    }

    
    private function parseResponse(
        $data, $map, $number = 0, $partsCostMap = [], $calcParts = true, $ignored = [], $isFeeLine = false
    ){

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
                (($fields[$i] == $this->serviceRo->PRTEXTENDEDCOST) ||
                ($fields[$i] == $this->serviceRo->PRTEXTENDEDSALE)) && $calcParts
            ){

                if($isFeeLine){
                    $response[$keys[$i]] = 0;
                }else {
                    if(!isset($partsCostMap[$number][$keys[$i]])){
                        $response[$keys[$i]] = 0;
                    }else {
                        $response[$keys[$i]] = $partsCostMap[$number][$keys[$i]];
                    }
                }
                continue;
            }

            if($fields[$i] == $this->serviceRo->PHONENUMBER)
            {

                if(
                    isset($data[$this->serviceRo->PHONEDESC]['V']) &&
                    is_array($data[$this->serviceRo->PHONEDESC]['V']) &&
                    in_array($this->serviceRo->CELL, $data[$this->serviceRo->PHONEDESC]['V']))
                {
                    $response[$keys[$i]] = true;
                }
                else if (
                    isset($data[$this->serviceRo->PHONEDESC]['V']) &&
                    $data[$this->serviceRo->PHONEDESC]['V'] == $this->serviceRo->CELL
                ){
                    $response[$keys[$i]] = true;
                }else {
                    $response[$keys[$i]] = false;
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
                            $response[$keys[$i]] = $this->cleanResponse($data[$fields[$i]]['V'][$number],false,true);
                        }

                    }
                }else {
                    $response[$keys[$i]] = $this->cleanResponse($data[$fields[$i]]['V'],false,true);
                }
            }else {
                $response[$keys[$i]] = $this->cleanResponse(
                    $this->convertBlankArrayData($data[$fields[$i]]), false, true
                );
            }

        }

        return $response;
    }


    private function parseResponseRaw($data,$map,$number){
        $response = [];
        $fields = array_values($map);
        $keys = array_keys($map);
        $count = count($fields);
        for($i=0;$i<$count;$i++){

            if(isset($data[$fields[$i]]['V'])){
                if(is_array($data[$fields[$i]]['V'])){
                    if(isset($data[$fields[$i]]['V'][$number])){

                        if(in_array($fields[$i],$this->serviceRo->asNumber()))
                        {
                            $response[$keys[$i]] = $this->cleanResponse($data[$fields[$i]]['V'][$number],true);
                        }
                        else
                        {
                            $response[$keys[$i]] = $this->cleanResponse($data[$fields[$i]]['V'][$number],false,true);
                        }

                    }
                }else {
                    $response[$keys[$i]] = $this->cleanResponse($data[$fields[$i]]['V'],false,true);
                }
            }else {
                $response[$keys[$i]] = $this->cleanResponse(
                    $this->convertBlankArrayData($data[$fields[$i]]), false, true
                );
            }

            //$response[$keys[$i]] = $this->cleanResponse($data[$fields[$i]],false,true);
        }

        return $response;
    }

    private function isAssoc(array $arr)
    {
        if (array() === $arr) return false;
        return array_keys($arr) !== range(0, count($arr) - 1);
    }
    

}
