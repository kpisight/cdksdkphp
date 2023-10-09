<?php 

class Employee {

    public $ID = 'Id';
    public $NAME = 'Name';

    public function __construct(){}

    public function map($map = []){
        if(!empty($map)){
            return $this->renderCustomMap($map);
        }
        return $this->renderMap();
    }

    public function renderMap(){
        $fields = $this->allFields();
        $map = [];
        foreach($fields as $field){
            $map[$field] = $field;
        }
        return $map;
    }

    public function renderCustomMap($map, $mapAll = false){
        $fields = $this->allFields();
        $keys = array_keys($map);
        $newMap = [];
        foreach($fields as $field){
            if(in_array($field,$keys)){
                $newMap[$map[$field]] = $field;
            }else {
                if($mapAll){
                    $newMap[$field] = $field;
                }
            }
        }
        return $newMap;
    }

    public function allFields(){
        return [
            $this->ID,
            $this->NAME
        ];
    }

}