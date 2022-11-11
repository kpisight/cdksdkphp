<?php 

include_once __DIR__ . '/../Helpers/Helpers.php';

class Parser extends Helpers {

    public function parsePartsData($item,$prtsMap){
        
        $keys = array_keys($item);

        $prtsExtendedCost = [];
        $prtsExtendedSale = [];
        $prtsLineCode = [];
        $prtsSequenceNo = [];

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

           
            if(isset($item[$key]['V']) && ($key === $this->serviceRo->PRTLABORSEQUENCENO)){
                if(is_array($item[$key]['V'])){
                    foreach($item[$key]['V'] as $key => $value){
                        $prtsSequenceNo[] = $this->cleanResponse($value);
                    }
                }else {
                    $prtsSequenceNo[] = $this->cleanResponse($item[$key]['V']);
                }
            }
        }



        $partsLineCodeList = [];
        foreach($prtsSequenceNo as $key => $lineCode){
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


    public function mapToPartsCost($item,$prtsCosts = [], $extractPartsPercent = [], $prtsMap = []){

        $lineCodes = [];
        $lineCodeMap = [];
        $sequenceNoMap = [];
        $sequences = [];

        $prtsMap = array_flip($prtsMap);
        $partCostLabel = $prtsMap[$this->serviceRo->PRTEXTENDEDCOST];
        $partSaleLabel = $prtsMap[$this->serviceRo->PRTEXTENDEDSALE];

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
        $percentMap = [];
        $extractPartsPercentageCount = [];
        $debug = [];

        foreach($lineCodes as $value){

            if(
                !isset($sequenceNoMap[$item[$this->serviceRo->LBRSEQUENCENO]['V'][$key]])
            ){
                
                $partsCostMap[] = [
                    $partCostLabel => 0,
                    $partSaleLabel => 0
                ];

            }else {

                $partIdentifier = $item[$this->serviceRo->LBRSEQUENCENO]['V'][$key];
                
                $partCost = $prtsCosts[$partCostLabel][$partIdentifier] ?? 0;
                $partSale = $prtsCosts[$partSaleLabel][$partIdentifier] ?? 0;

                $extractPartsPercentageCount[$item[$this->serviceRo->LBRSEQUENCENO]['V'][$key]] = (
                    $extractPartsPercentageCount[$item[$this->serviceRo->LBRSEQUENCENO]['V'][$key]] ?? 0
                );

                $extractPartsPrcnt = $extractPartsPercent[$item[$this->serviceRo->LBRSEQUENCENO]['V'][$key]][
                    $extractPartsPercentageCount[$item[$this->serviceRo->LBRSEQUENCENO]['V'][$key]]
                ] ?? 0;
                
                $extractPartsPercentageCount[$item[$this->serviceRo->LBRSEQUENCENO]['V'][$key]] = (
                    $extractPartsPercentageCount[$item[$this->serviceRo->LBRSEQUENCENO]['V'][$key]]+1
                );

                $partsCostMap[] = [
                    $partCostLabel => $partCost*((int)$extractPartsPrcnt/100),
                    $partSaleLabel => $partSale*((int)$extractPartsPrcnt/100)
                ];

            }

            $key++;
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