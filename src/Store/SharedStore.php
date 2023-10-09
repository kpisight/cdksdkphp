<?php 

include_once __DIR__ . '/../Core/Model/Types.php';
include_once __DIR__ . '/../Core/Helpers/Helpers.php';

class SharedStore {

    public function __construct(
        public $storeDir = '',
        public $pull = 'pull',
        public $install = 'install'
    ){
        $this->Types = new Types();
        $this->Helpers = new Helpers();
    }

    public function get($type,$params){
        $storeFile = $this->getFileName($type,$params);
        if(is_file($storeFile)){
            return file_get_contents($storeFile);
        }
        return false;
    }

    public function save($type,$params,$rawFile = ''){
        $cacheFile = $this->getFileName($type,$params);
        file_put_contents(
            $cacheFile, file_get_contents($rawFile)
        );
    }

    private function getFileName($type,$params){
        $storeKey = $this->setStoreKey($type,$params);
        return $this->setStoreDir($type) . '/' . $storeKey . '.ds';
    }

    private function setStoreDir($type){
        if(in_array($type,$this->Types->pullTypes())){
            return __DIR__ . $this->storeDir . $this->pull; 
        }
        return __DIR__ . $this->storeDir . $this->install;
    }

    private function setStoreKey($type,$params){
        $items = array_values($params);
        foreach($items as $p){
            $type .= $p;
        }
        return $this->Helpers->makeKey($type);
    }

}