<?php 

class TestSuite {

    public function setTestSuiteConfig($config){
        $this->verbose = $config['verbose'] ?? false;
        $this->storeLog = $config['store-log'] ?? false;
        $this->investigate = $config['investigate'] ?? [];
        $this->logDir = $config['store-dir'] ?? '';
    }

    /**
     *   @ Build All Test Suite Here
     */
    public function runTestSuite($items){
        if($this->verbose){
            if(isset($items)){
                $count = count($items);
                $randNums = $this->setRandNums(4,$count);
                for($i=0;$i<$count;$i++){
                    $this->outputRoTest($items[$i]);
                    $this->saveRandomOutputTest($items[$i],$i,$randNums);
                }
            }
        }
    }

    public function runTestSuite2($item,$ro){
        if($this->verbose){
            if(in_array($ro, $this->investigate)){
                echo json_encode($item, JSON_PRETTY_PRINT);
                echo "\n\n";
            }
        }
    }


    public function setRandNums($count,$total){
        $nums = [];
        for($i=0;$i<$count;$i++){
            $nums[$i] = rand(0,$total);
        }
        return $nums;
    }

    /**
     *  @ Tests ::
     */
    private function outputRoTest($item){
        if(in_array($item['RONumber'], $this->investigate)){
    
            if($this->storeLog){
                file_put_contents(__DIR__ . $this->logDir . $item['RONumber'] . '.txt', json_encode($item, JSON_PRETTY_PRINT));
            }

            if(is_array($item['prtExtendedCost']['V'])){
                echo 'SUM: ' . array_sum($item['prtExtendedCost']['V']) . "\n\n";
                foreach($item['prtExtendedCost']['V'] as $prtCost){
                    echo 'PARTCOST: ' . $this->cleanResponse($prtCost) . "\n";
                }
            }else {
                echo 'PARTCOST: ' . $this->cleanResponse($item['prtExtendedCost']['V']) . "\n";
            }

            echo "\n\n";

            if(is_array($item['prtExtendedSale']['V'])){
                echo 'SUM: ' . array_sum($item['prtExtendedSale']['V']) . "\n\n";
                foreach($item['prtExtendedSale']['V'] as $prtSale){
                    echo 'PARTSALE: ' . $this->cleanResponse($prtSale) . "\n";
                }
            }else {
                echo 'PARTCOST: ' . $this->cleanResponse($item['prtExtendedSale']['V']) . "\n";
            }

            echo "\n\n";
        
        }
    }

    private function saveRandomOutputTest($item,$i,$randNums){
        foreach($randNums as $rand){
            if($i===$rand){
                if($this->storeLog){
                    file_put_contents(__DIR__ . $this->logDir . 'rand-' . $rand . '.txt', json_encode($item, JSON_PRETTY_PRINT));
                }
            }
        }
    }

    private function cleanResponse($value){
        if(is_numeric($value)){
            $value = number_format($value, 2, '.');
            if($value<0){
                return (string)$value;
            }
            return $value;
        }
        return $value;
    }   

}