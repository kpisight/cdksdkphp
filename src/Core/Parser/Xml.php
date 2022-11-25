<?php

class XmlHandler {

    public function __construct(){
        $this->reader = new XmlReader();
    }

    public function readXml($file,$dir,$responseObj,$parentTag,$parserType = 'v2',$maxChunk = 250){
        return $this->{$parserType}($file,$dir,$responseObj,$parentTag,$maxChunk);
    }

    public function v2($file,$dir,$responseObj,$parentTag,$maxChunk){

        $openingTag = '<' . $parentTag . '>';
        $closingTag = '</' . $parentTag . '>';

        $num = 0;
        $files = 0;
        $nodes = 0;
        $xmlData = '';

        /**
         *  @ Parse All Data Nodes ::
         */
        $this->reader->open($file);
        while ($this->reader->read()){
            $fileName = $dir . '/' . str_pad($files, 5, '0', STR_PAD_LEFT) . '.cdk';
            if ($this->reader->nodeType == XMLReader::ELEMENT){
                switch ($this->reader->name) {
                    case $parentTag: break;
                    case $responseObj:
                        $this->saveToFile($fileName, $this->reader->readOuterXml());
                        $num++;
                        $nodes++;
                        break;
                }
            }
            if($num===$maxChunk){
                $this->saveToFile($fileName,$closingTag);
                file_put_contents($fileName, $openingTag . file_get_contents($fileName));
                $xmlData = '';
                $num = 0;
                $nodes = 0;
                $files++;
            }
        }

        // -- Save the last file ::
        if($nodes!==$maxChunk){
            $this->saveToFile($fileName,$closingTag);
            file_put_contents($fileName, $openingTag . file_get_contents($fileName));
        }
        
        $this->reader->close();

        return true;
    }


    public function v1($file,$dir,$responseObj,$parentTag,$maxChunk){

        $openingTag = '<' . $parentTag . '>';
        $closingTag = '</' . $parentTag . '>';

        $num = 0;
        $files = 0;
        $nodes = 0;
        $xmlData = '';

        /**
         *  @ Count Total Number of Nodes ::
         */
        $this->reader->open($file);
        while ($this->reader->read()){
            if ($this->reader->nodeType == XMLReader::ELEMENT){
                switch ($this->reader->name){
                    case $responseObj: $nodes++; break;
                }
            }
        }
        $this->reader->close();

        /**
         *  @ Parse All Data Nodes ::
         */
        $this->reader->open($file);
        while ($this->reader->read()){
            if ($this->reader->nodeType == XMLReader::ELEMENT){
                switch ($this->reader->name) {
                    case $parentTag: break;
                    case $responseObj:
                        $xmlData .=  $this->reader->readOuterXml();
                        $num++;
                        break;
                }
            }
            if($num===$maxChunk||$num===$nodes){
            
                file_put_contents(
                    $dir . '/' . str_pad($files, 5, '0', STR_PAD_LEFT) . '.cdk', 
                    $openingTag . $xmlData . $closingTag
                );

                $xmlData = '';
                $num = 0;

                $files++;

            }
        }
        $this->reader->close();

        return true;
    }



    private function saveToFile($file,$data){
        $fp = fopen($file, 'a');
              fwrite($fp,$data);  
              fclose($fp);  
    }
 

}