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

    public function makeKey($obj){
        return sha1(base64_encode($obj));
    }
    
    public function createRandPhrase($count = 10){

        $words = [
            'rule','arise','father','applaud','opera','frequency','receipt','coup','manage','response','bench','spider',
            'word','golf','composer','freckle','chin','harsh','wardrobe','still','happy','hover','apparatus','speech',
            'super','wow','nice','mother'
        ];

        $phrase = '';

        for($i=0;$i<$count;$i++){
            $phrase .= $words[array_rand($words)] . '-';
            if($i==($count-1)){
                $phrase .= $words[array_rand($words)];
            }
        }

        return $phrase;

    }
    
}