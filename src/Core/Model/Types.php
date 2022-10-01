<?php 

class Types {

    public $SERVICE_RO_HISTORY_QUERYID = 'SROD_History_DateRange';
    public $SERVICE_RO_HISTORY = 'service-ro-history/extract';
    public $SERVICE_RO_HISTORY_OBJ = 'service-repair-order-history';

    public $SERVICE_RO_CLOSED_QUERYID = 'SROD_Closed_DateRange';
    public $SERVICE_RO_CLOSED = 'service-ro-closed/extract';
    public $SERVICE_RO_CLOSED_OBJ = 'service-repair-order-closed';

    public function roHistoryExtract(){
        return $this->SERVICE_RO_HISTORY;
    }

    public function roHistoryExtractQueryId(){
        return $this->SERVICE_RO_HISTORY_QUERYID;
    }

    public function roHistoryObj(){
        return $this->SERVICE_RO_HISTORY_OBJ;
    }

    public function roClosedExtract(){
        return $this->SERVICE_RO_CLOSED;
    }

    public function roClosedExtractQueryId(){
        return $this->SERVICE_RO_CLOSED_QUERYID;
    }

    public function roClosedObj(){
        return $this->SERVICE_RO_CLOSED_OBJ;
    }

    public function renderTypeObj($type){
        switch($type){
            case $this->SERVICE_RO_HISTORY : return $this->SERVICE_RO_HISTORY_OBJ; break;
            case $this->SERVICE_RO_CLOSED : return $this->SERVICE_RO_CLOSED_OBJ; break;
        }
    }


}