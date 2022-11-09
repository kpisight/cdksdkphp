<?php 

include_once __DIR__ . '/../Http/Request.php';
include_once __DIR__ . '/../Http/Cache.php';
include_once __DIR__ . '/Extract/Extract.php';
include_once __DIR__ . '/../Response/Response.php';
include_once __DIR__ . '/Model/ServiceRo.php';
include_once __DIR__ . '/Model/Employee.php';
include_once __DIR__ . '/Model/Types.php';
include_once __DIR__ . '/TestSuite/TestSuite.php';

class Core extends TestSuite {

    public function __construct(
        public $authentication,
        public $environment,
        public $lines = false,
        public $testSuiteConfig = [],
        public $cache = false,
        public $cacheDir = ''
    ){
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

        //$this->runTestSuite($items[$responseObj]);

        $extractData = [];
        foreach($items[$responseObj] as $item){

            if($this->lines && (in_array($data['type'],$this->types->roServiceTypes())))
            {
                $RO = $item[$this->serviceRo->RONUMBER];
                
                //$this->runTestSuite2($item,$RO);

                $extractPartsCost = $this->parsePartsData($item,$prtsMap);
                $extractPartsPercent = $this->parsePartsDataPercent($item);
                $partsCostMap = $this->mapToPartsCost($item,$extractPartsCost,$extractPartsPercent);

                $this->runTestSuite2($partsCostMap,$RO);

                //$this->runTestSuite2($extractPartsCost,$RO);
                //$this->runTestSuite2($partsCostMap, $RO);

                if(isset($item[$this->serviceRo->LBRLINECODE]['V'])){
                    $lineCount = count((array)$item[$this->serviceRo->LBRLINECODE]['V']);
                    for($i=0;$i<$lineCount;$i++){
                        $extractData[] = $this->parseResponse($item,$map,$i,$partsCostMap);
                    }
                }

                //$this->runTestSuite2(end($extractData),$RO);
                
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


    private function mapToPartsCost($item,$prtsCosts = [],$extractPartsPercent = []){


        $lineCodes = [];
        $lineCodeMap = [];
        $sequenceNoMap = [];
        $sequences = [];

        if(!isset($item[$this->serviceRo->PRTLABORSEQUENCENO]['V'])){
            return [];
        }

        if(is_array($item[$this->serviceRo->PRTLABORSEQUENCENO]['V'])){
            foreach($item[$this->serviceRo->PRTLABORSEQUENCENO]['V'] as $key => $sequenceNo){
                $sequenceNoMap[$sequenceNo] = $item[$this->serviceRo->PRTLINECODE]['V'][$key];
            }
        }else {
            $sequenceNoMap[$item[$this->serviceRo->PRTLABORSEQUENCENO]['V']] = $item[$this->serviceRo->PRTLINECODE]['V'];
        }

        if(is_array($item[$this->serviceRo->LBRLINECODE]['V'])){
            foreach($item[$this->serviceRo->LBRLINECODE]['V'] as $lineCode){
                $lineCodes[] = $lineCode;
            }
        }else {
            $lineCodes[] = $item[$this->serviceRo->LBRLINECODE]['V'];
        }


        $key = 0;
        foreach($lineCodes as $value){

            
                  // -- Debug Only ::
               /*$lineCodeMap[] = [
                    'line' => $value,
                    'key' => $key,
                    'opcode' => $item[$this->serviceRo->LBROPCODE]['V'][$key] ?? '',
                    'labourSequenceNo' => $sequenceNoMap[$item[$this->serviceRo->LBRSEQUENCENO]['V'][$key]] ?? false,
                    'parts' => [
                        'PARTS_COST' => $prtsCosts['PARTS_COST'][$sequenceNoMap[$item[$this->serviceRo->LBRSEQUENCENO]['V'][$key]]] ?? 0,
                        'PARTS_SALE' => $prtsCosts['PARTS_SALE'][$sequenceNoMap[$item[$this->serviceRo->LBRSEQUENCENO]['V'][$key]]] ?? 0
                    ],
                    'percentages' => $extractPartsPercent[$key]
                ];*/

            if(
                !isset($sequenceNoMap[$item[$this->serviceRo->LBRSEQUENCENO]['V'][$key]])
            ){
                
                $partsCostMap[] = [
                    'PARTS_COST' => 0,
                    'PARTS_SALE' => 0
                ];

            }else {

                $partCost = $prtsCosts['PARTS_COST'][
                    $sequenceNoMap[
                        $item[$this->serviceRo->LBRSEQUENCENO]['V'][$key]
                    ]
                ] ?? 0;

                $partSale = $prtsCosts['PARTS_SALE'][
                    $sequenceNoMap[
                        $item[$this->serviceRo->LBRSEQUENCENO]['V'][$key]
                    ]
                ] ?? 0;

                $partsCostMap[] = [
                    'PARTS_COST' => $partCost*((int)$extractPartsPercentData/100),
                    'PARTS_SALE' => $partSale*((int)$extractPartsPercentData/100)
                ];

            }
            $key++;
        }

        //return $sequenceNoMap;

//        return $lineCodeMap;


        return $partsCostMap;

    }

    private function parsePartsDataPercent($item){

        $keys = array_keys($item);
        $prtPercentage = [];
        $lbrLines = [];
        foreach($keys as $key){
            if($key === $this->serviceRo->PRTLINECODE){
                if(isset($item[$key]['V'])){
                    if(is_array($item[$key]['V'])){
                        $count = count($item[$key]['V']);
                        for($i=0;$i<$count;$i++){
                            $prtPercentage[$i] = [ 
                                $item[$this->serviceRo->PRTLINECODE]['V'][$i] => $item[$this->serviceRo->PRTMCDPERCENTAGE]['V'][$i]
                            ];
                            if(isset($prtPercentage[$i-1][$item[$this->serviceRo->PRTLINECODE]['V'][$i]])){
                                if(
                                    $prtPercentage[$i-1][$item[$this->serviceRo->PRTLINECODE]['V'][$i]] === $item[$this->serviceRo->PRTMCDPERCENTAGE]['V'][$i]
                                ){
                                    unset($prtPercentage[$i-1]);
                                }
                            }
                        }
                    }else {
                        $prtPercentage[0] = [ 
                            $item[$this->serviceRo->PRTLINECODE]['V'][0] => $item[$this->serviceRo->PRTMCDPERCENTAGE]['V']
                        ];
                    }
                }
            }

            if($key === $this->serviceRo->LBRLINECODE){
                $lbrLines[] = $item[$this->serviceRo->LBRLINECODE]['V'];
            }
        }

        $lbrLines = $lbrLines[0];
        $partPercentages = [];
        foreach($prtPercentage as $part){
            $partPercentages[] = $part; 
        }

        $count = is_array($lbrLines) ? count($lbrLines) : 1;
        $pCount = count($partPercentages);

        $percentageIndexes = [];
        foreach($partPercentages as $index => $percent){
            if(is_array($percent)){
                $key = array_keys($percent);
                if(isset($percentageIndexes[$key[0]])){
                    $countIndex = count($percentageIndexes[$key[0]]);
                    $percentageIndexes[$key[0]][$countIndex] = $percent[$key[0]];
                }else {
                    $percentageIndexes[$key[0]][0] = $percent[$key[0]];
                }
            }
        }

        $newPartPercentages = [];
        $lineCounters = [];
        if(is_array($lbrLines)){
            foreach($lbrLines as $line){
                if(isset($lineCounters[$line])){
                    $lineCounters[$line] = $lineCounters[$line]+1;
                }else {
                    $lineCounters[$line] = 1;
                }
            }
            $indexKeys = array_keys($percentageIndexes);
            foreach($lbrLines as $i => $line){
                if(!in_array($line,$indexKeys)){
                    $newPartPercentages[$line] = [0];
                }else {
                    $counter = $lineCounters[$line];
                    for($c=0;$c<$counter;$c++){
                        $newPartPercentages[$line][$c] = $percentageIndexes[$line][$c] ?? $percentageIndexes[$line];
                    }
                }
            }
        }else {
            $lineCounters[$lbrLines] = 1;
            $indexKeys = array_keys($percentageIndexes);
            if(!in_array($lbrLines,$indexKeys)){
                $newPartPercentages[$line] = [0];
            }else {
                $counter = $lineCounters[$lbrLines];
                for($c=0;$c<$counter;$c++){
                    $newPartPercentages[$lbrLines][$c] = $percentageIndexes[$lbrLines][$c];
                }
            }
        }

        $combinedPercentagesList = [];
        foreach($newPartPercentages as $part){
            $count = count($part);
            if($count>1){
                for($i=0;$i<$count;$i++){
                    $combinedPercentagesList[] = $part[$i];
                }
            }else {
                $combinedPercentagesList[] = $part[0];
            }
        }

        return $combinedPercentagesList;

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













    /****
     * 
     *  LEGACY
     * 
     * 
     */




    private function parsePartsData($item,$prtsMap){
        
        $keys = array_keys($item);

        $prtsExtendedCost = [];
        $prtsExtendedSale = [];
        $prtsLineCode = [];

        $prtsCost = [];
        $prtsSale = [];
        
        foreach($keys as $key){
            
            if(!isset($item[$key]['V'])){
                continue;
            }

            if(isset($item[$key]['V']) && ($key === $this->serviceRo->PRTEXTENDEDCOST)){
                if(is_array($item[$key]['V'])){
                    foreach($item[$key]['V'] as $value){
                        $prtsExtendedCost[] = $this->cleanResponse($value, true);
                    }
                }else {
                    $prtsExtendedCost[] = $this->cleanResponse($item[$key]['V'], true);
                }
            }

            if(isset($item[$key]['V']) && ($key === $this->serviceRo->PRTEXTENDEDSALE)){
                if(is_array($item[$key]['V'])){
                    foreach($item[$key]['V'] as $value){
                        $prtsExtendedSale[] = $this->cleanResponse($value, true);
                    }
                }else {
                    $prtsExtendedSale[] = $this->cleanResponse($item[$key]['V'], true);
                }
            }

            if(isset($item[$key]['V']) && ($key === $this->serviceRo->PRTLINECODE)){
                if(is_array($item[$key]['V'])){
                    foreach($item[$key]['V'] as $value){
                        $prtsLineCode[] = $this->cleanResponse($value);
                    }
                }else {
                    $prtsLineCode[] = $this->cleanResponse($item[$key]['V']);
                }
            }
        }

        $partsLineCodeList = [];
        foreach($prtsLineCode as $key => $lineCode){
            array_push($partsLineCodeList,$lineCode); 
        }

        $prtsCostSplit = [];
        $prtsSaleSplit = [];
        $l = 0;
        foreach($partsLineCodeList as $lineItem){
            $prtsCostSplit[$lineItem][] = $this->cleanResponse($prtsExtendedCost[$l], true);
            $prtsSaleSplit[$lineItem][] = $this->cleanResponse($prtsExtendedSale[$l], true);
            $l++;
        }

        $partsCostKeys = array_keys($prtsCostSplit);
        foreach($partsCostKeys as $p){
            $prtsCostSplit[$p] = number_format(array_sum($prtsCostSplit[$p]), 2, '.', '');
            $prtsSaleSplit[$p] = number_format(array_sum($prtsSaleSplit[$p]), 2, '.', '');
        }

        $prtsExtendedCostKey = $this->serviceRo->PRTEXTENDEDCOST;
        $prtsExtendedSaleKey = $this->serviceRo->PRTEXTENDEDSALE;
        foreach($prtsMap as $key => $value){
            if($prtsExtendedCostKey === $value){
                $prtsExtendedCostKey = $key;
            }
            if($prtsExtendedSaleKey === $value){
                $prtsExtendedSaleKey = $key;
            }
        }
        
        return [
            $prtsExtendedCostKey => $prtsCostSplit, 
            $prtsExtendedSaleKey => $prtsSaleSplit
        ];

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

    private function cleanResponse($value,$numeric_money = false){
        if(is_numeric($value)){
            if($numeric_money){
                if($value<0){
                    return (string)number_format((float)$value, 2, '.', '');
                }
                return (string)number_format((float)$value, 2, '.', '');
            }
            return (int)$value;
        }
        return $value;
    }   


}