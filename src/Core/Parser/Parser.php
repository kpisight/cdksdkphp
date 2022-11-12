<?php 

include_once __DIR__ . '/../Helpers/Helpers.php';

class Parser extends Helpers {

    public function parsePartsData($item,$prtsMap){

        $keys = array_keys($item);

        $lbrLineCodes = [];
        $lbrLbrType = [];
        $lbrSequenceNo = [];
        $lbrDataLines = [];

        $prtLbrSequenceNo = [];
        $prtLbrTypes = [];
        $prtDataLines = [];
        $prtLineCodes = [];

        $prtsExtendedCost = [];
        $prtsExtendedSale = [];

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


            if(isset($item[$key]['V']) && ($key === $this->serviceRo->LBRLINECODE)){
                if(is_array($item[$key]['V'])){
                    foreach($item[$key]['V'] as $value){
                        $lbrLineCodes[] = $this->cleanResponse($value);
                    }
                }else {
                    $lbrLineCodes[] = $this->cleanResponse($item[$key]['V']);
                }
            }
            if(isset($item[$key]['V']) && ($key === $this->serviceRo->LBRLABORTYPE)){
                if(is_array($item[$key]['V'])){
                    foreach($item[$key]['V'] as $value){
                        $lbrLbrType[] = $this->cleanResponse($value);
                    }
                }else {
                    $lbrLbrType[] = $this->cleanResponse($item[$key]['V']);
                }
            }
            if(isset($item[$key]['V']) && ($key === $this->serviceRo->LBRSEQUENCENO)){
                if(is_array($item[$key]['V'])){
                    foreach($item[$key]['V'] as $value){
                        $lbrSequenceNo[] = $this->cleanResponse($value);
                    }
                }else {
                    $lbrSequenceNo[] = $this->cleanResponse($item[$key]['V']);
                }
            }

            if(isset($item[$key]['V']) && ($key === $this->serviceRo->PRTLINECODE)){
                if(is_array($item[$key]['V'])){
                    foreach($item[$key]['V'] as $value){
                        $prtLineCodes[] = $this->cleanResponse($value);
                    }
                }else {
                    $prtLineCodes[] = $this->cleanResponse($item[$key]['V']);
                }
            }

            if(isset($item[$key]['V']) && ($key === $this->serviceRo->PRTLABORTYPE)){
                if(is_array($item[$key]['V'])){
                    foreach($item[$key]['V'] as $value){
                        $prtLbrTypes[] = $this->cleanResponse($value);
                    }
                }else {
                    $prtLbrTypes[] = $this->cleanResponse($item[$key]['V']);
                }
            }

            if(isset($item[$key]['V']) && ($key === $this->serviceRo->PRTLABORSEQUENCENO)){
                if(is_array($item[$key]['V'])){
                    foreach($item[$key]['V'] as $value){
                        $prtLbrSequenceNo[] = $this->cleanResponse($value);
                    }
                }else {
                    $prtLbrSequenceNo[] = $this->cleanResponse($item[$key]['V']);
                }
            }

        }

        foreach($lbrLineCodes as $key => $line){
            $lbrDataLines[] = $lbrLineCodes[$key] . $lbrLbrType[$key] . $lbrSequenceNo[$key];
        }

        foreach($prtLineCodes as $key => $code){
            $prtDataLines[] = $prtLineCodes[$key] . $prtLbrTypes[$key] . $prtLbrSequenceNo[$key];
        }



        $partsLineCodeList = [];
        foreach($prtDataLines as $key => $lineCode){
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

    public function mapToPartsCost($item, $prtsCosts = [], $extractPartsPercent = [], $prtsMap = []){

        $lbrLineCodes = [];
        $lbrLbrType = [];
        $lbrSequenceNo = [];
        $lbrDataLines = [];

        $prtsMap = array_flip($prtsMap);
        $partCostLabel = $prtsMap[$this->serviceRo->PRTEXTENDEDCOST];
        $partSaleLabel = $prtsMap[$this->serviceRo->PRTEXTENDEDSALE];

        $keys = array_keys($item);

        foreach($keys as $key){
            if(isset($item[$key]['V']) && ($key === $this->serviceRo->LBRLINECODE)){
                if(is_array($item[$key]['V'])){
                    foreach($item[$key]['V'] as $value){
                        $lbrLineCodes[] = $this->cleanResponse($value);
                    }
                }else {
                    $lbrLineCodes[] = $this->cleanResponse($item[$key]['V']);
                }
            }
            if(isset($item[$key]['V']) && ($key === $this->serviceRo->LBRLABORTYPE)){
                if(is_array($item[$key]['V'])){
                    foreach($item[$key]['V'] as $value){
                        $lbrLbrType[] = $this->cleanResponse($value);
                    }
                }else {
                    $lbrLbrType[] = $this->cleanResponse($item[$key]['V']);
                }
            }
            if(isset($item[$key]['V']) && ($key === $this->serviceRo->LBRSEQUENCENO)){
                if(is_array($item[$key]['V'])){
                    foreach($item[$key]['V'] as $value){
                        $lbrSequenceNo[] = $this->cleanResponse($value);
                    }
                }else {
                    $lbrSequenceNo[] = $this->cleanResponse($item[$key]['V']);
                }
            }
        }

        
        foreach($lbrLineCodes as $key => $line){

            $partDataLine = $lbrLineCodes[$key] . $lbrLbrType[$key] . $lbrSequenceNo[$key];

            if(!isset($prtsCosts[$partCostLabel][$partDataLine])){
                $partsCostMap[] = [
                    $partCostLabel => 0,
                    $partSaleLabel => 0
                ];
                continue;
            }

            $partsCostMap[] = [
                $partCostLabel => $prtsCosts[$partCostLabel][$partDataLine],
                $partSaleLabel => $prtsCosts[$partSaleLabel][$partDataLine]
            ];
        }

        return $partsCostMap;

    }

    
    public function parsePartsDataPercent($item){

        $keys = array_keys($item);
        $prtPercentage = [];
        $lbrLines = [];

            foreach($keys as $key){
                if($key === $this->serviceRo->PRTLABORSEQUENCENO){
                    if(isset($item[$key]['V'])){
                        if(is_array($item[$key]['V'])){
                            $count = count($item[$key]['V']);
                            for($i=0;$i<$count;$i++){
                                $prtPercentage[$i] = [ 
                                    $item[$this->serviceRo->PRTLABORSEQUENCENO]['V'][$i] => $item[$this->serviceRo->PRTMCDPERCENTAGE]['V'][$i]
                                ];
                                if(isset($prtPercentage[$i-1][$item[$this->serviceRo->PRTLABORSEQUENCENO]['V'][$i]])){
                                    if(
                                        $prtPercentage[$i-1][$item[$this->serviceRo->PRTLABORSEQUENCENO]['V'][$i]] === $item[$this->serviceRo->PRTMCDPERCENTAGE]['V'][$i]
                                    ){
                                        unset($prtPercentage[$i-1]);
                                    }
                                }
                            }
                        }else {
                            $prtPercentage[0] = [ 
                                $item[$this->serviceRo->PRTLABORSEQUENCENO]['V'][0] => $item[$this->serviceRo->PRTMCDPERCENTAGE]['V']
                            ];
                        }
                    }
                }
                if($key === $this->serviceRo->PRTLABORSEQUENCENO){
                    if(isset($item[$this->serviceRo->PRTLABORSEQUENCENO]['V'])){
                        $lbrLines[] = $item[$this->serviceRo->PRTLABORSEQUENCENO]['V'];
                    }else {
                        $lbrLines[] = $item[$this->serviceRo->PRTLABORSEQUENCENO];
                    }
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
                        $newPartPercentages[$line][$c] = $percentageIndexes[$line][$c] ?? false;
                    }
                    foreach($newPartPercentages[$line] as $key => $partCheck){
                        if(!$partCheck){
                            unset($newPartPercentages[$line][$key]);
                        }
                    }
                }
            }
        }else {
            $lineCounters[$lbrLines] = 1;
            $indexKeys = array_keys($percentageIndexes);
            if(!in_array($lbrLines,$indexKeys)){
                $newPartPercentages[$lbrLines] = [0];
            }else {
                $counter = $lineCounters[$lbrLines];
                for($c=0;$c<$counter;$c++){
                    $newPartPercentages[$lbrLines][$c] = $percentageIndexes[$lbrLines][$c];
                }
            }
        }

        return $newPartPercentages;
        
    }




}