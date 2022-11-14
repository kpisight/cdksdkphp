<?php 

include_once __DIR__ . '/../Core/Model/Types.php';
include_once __DIR__ . '/../Core/Helpers/Helpers.php';

class HttpCache {

    public function __construct(
        public $cacheDir = '',
        public $pull = 'pull',
        public $install = 'install'
    ){
        $this->Types = new Types();
        $this->Helpers = new Helpers();
    }

    public function get($type,$params){
        $cacheFile = $this->getFileName($type,$params);
        if(is_file($cacheFile)){
            return json_decode(file_get_contents($cacheFile), true);
        }
        return false;
    }

    public function save($type,$params,$data = []){
        $cacheFile = $this->getFileName($type,$params);
        file_put_contents(
            $cacheFile, json_encode($data)
        );
    }

    private function getFileName($type,$params){
        $cacheKey = $this->setCacheKey($type,$params);
        return $this->setCacheDir($type) . '/' . $cacheKey . '.cache';
    }

    private function setCacheDir($type){
        if(in_array($type,$this->Types->pullTypes())){
            return __DIR__ . $this->cacheDir . $this->pull; 
        }
        return __DIR__ . $this->cacheDir . $this->install;
    }

    private function setCacheKey($type,$params){
        $items = array_values($params);
        foreach($items as $p){
            $type .= $p;
        }
        return $this->Helpers->makeKey($type);
    }


}