<?php 

class Helpers {

    public function convertBlankArrayData($data){
        $isArray = is_array($data);
        if(!$isArray){
            return $data;
        }
        if(empty($data)){
            return '';
        }
        return $data;
    }

    public function cleanResponse($value,$numeric_money = false){
        if(is_numeric($value)){
            if($numeric_money){
                if($value<0){
                    return (string)number_format((float)$value, 2, '.', '');
                }
                return (string)number_format((float)$value, 2, '.', '');
            }
            return (float)$value;
        }
        return $value;
    }   

    
}