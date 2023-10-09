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
                $randNums = $this->setRandNums(0,$count);
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
            }else {
                return '';
            }
        }
    }


    public function setRandNums($count,$total){
        $nums = [];
        $taken = [];
        for($i=0;$i<$count;$i++){
            if(in_array($i,$nums)){
                $count = $count++;
                continue;
            }
            $nums[$i] = rand(0,$total);
        }
        return $nums;
    }

    public function assoc(array $arr)
    {
        if (array() === $arr) return false;
        return array_keys($arr) !== range(0, count($arr) - 1);
    }

    /**
     *  @ Tests ::
     */
    public function outputRoTest($item){


        echo $item['RONumber'] . ' --- ' . json_encode($this->investigate) . "\n\n";
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