<?php

class XmlHandler {

    public function __construct(){
        $this->reader = new XmlReader();
    }

    public function readXml($file,$dir,$responseObj,$parentTag,$maxChunk = 250){

        $openingTag = '<' . $parentTag . '>';
        $closingTag = '</' . $parentTag . '>';

        $num = 0;
        $files = 0;
        $nodes = 0;
        $xmlData = '';
        $xmlChunk = '';

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
                        $xmlData .= '<' . $responseObj . '>' . $this->reader->readInnerXml() . '</' . $responseObj . '>';
                        $num++;
                        break;
                }
            }
            if($num===$maxChunk || $num===$nodes){
                $xmlChunk .= $openingTag . $xmlData . $closingTag;
                file_put_contents($dir . '/' . str_pad($files, 5, '0', STR_PAD_LEFT) . '.cdk', $xmlChunk);
                $xmlData = '';
                $xmlChunk = '';
                $num = 0;
                $files++;
            }
        }
        $this->reader->close();
    }
 

}