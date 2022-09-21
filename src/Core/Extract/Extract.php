<?php 

class Extract {

    public $dealerId = 'dealerId';
    public $queryId = 'queryId';
    public $startDate = 'qparamStartDate';
    public $endDate = 'qparamEndDate';
    public $reportType = 'reportType';

    public function queryBuilder($query, $data = []){
        $keys = $this->extractKeys($data);
        foreach($data as $key => $value){
            if(in_array($key, $this->defaultParams())){
                $query = $query + $this->setData($key,$value);
            }
        }
        return $query;
    }

    public function setDealerId($value){
        return [
            $this->dealerId => $value
        ];
    }

    public function setQueryId($value){
        return [
            $this->queryId => $value
        ];
    }

    public function setStartDate($value){
        return [
            $this->startDate => $value
        ];
    }

    public function setEndDate($value){
        return [
            $this->endDate => $value
        ];
    }

    public function setReportType($value){
        return [
            $this->reportType => $value
        ];
    }

    public function defaultParams(){
        return [
            $this->dealerId,
            $this->queryId,
            $this->startDate,
            $this->endDate,
            $this->reportType
        ];
    }

    private function setData($key,$value){
        switch($key){
            case $this->dealerId : return $this->setDealerId($value); break;
            case $this->queryId : return $this->setQueryId($value); break;
            case $this->startDate : return $this->setStartDate($value); break;
            case $this->endDate : return $this->setEndDate($value); break;
            case $this->reportType : return $this->setReportType($value); break;
        }
    }

    private function extractKeys($data){
        return array_keys($data);
    }

    private function extractValues($data){
        return array_values($data);
    }

}