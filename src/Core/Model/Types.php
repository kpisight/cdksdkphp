<?php 

class Types {

    public $SERVICE_RO_HISTORY_QUERYID = 'SROD_History_DateRange';
    public $SERVICE_RO_HISTORY = 'service-ro-history/extract';
    public $SERVICE_RO_HISTORY_OBJ = 'service-repair-order-history';

    public $SERVICE_RO_CLOSED_QUERYID = 'SROD_Closed_DateRange';
    public $SERVICE_RO_CLOSED = 'service-ro-closed/extract';
    public $SERVICE_RO_CLOSED_OBJ = 'service-repair-order-closed';

    public $HELP_EMPLOYEE_QUERYID = 'HEMPL_Bulk_Service';
    public $HELP_EMPLOYEE = 'help-employee/extract';
    public $HELP_EMPLOYEE_OBJ = 'HelpEmployee';

    public $HELP_EMPLOYEE_DELTA_QUERYID = 'HEMPL_Delta_Service';
    public $HELP_EMPLOYEE_DELTA = 'help-employee/extract';
    public $HELP_EMPLOYEE_DELTA_OBJ = 'HelpEmployee';

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

    public function helpEmployeeExtract(){
        return $this->HELP_EMPLOYEE;
    }

    public function helpEmployeeExtractQueryId(){
        return $this->HELP_EMPLOYEE_QUERYID;
    }

    public function helpEmployeeDeltaExtract(){
        return $this->HELP_EMPLOYEE_DELTA;
    }

    public function helpEmployeeDeltaExtractQueryId(){
        return $this->HELP_EMPLOYEE_DELTA_QUERYID;
    }

    public function roServiceTypes(){
        return [
            $this->SERVICE_RO_HISTORY,
            $this->SERVICE_RO_CLOSED
        ];
    }

    public function installTypes(){
        return [
            $this->SERVICE_RO_HISTORY
        ];
    }
    public function pullTypes(){
        return [
            $this->SERVICE_RO_CLOSED
        ];
    }
    public function renderTypeObj($type){
        switch($type){
            case $this->SERVICE_RO_HISTORY : return $this->SERVICE_RO_HISTORY_OBJ; break;
            case $this->SERVICE_RO_CLOSED : return $this->SERVICE_RO_CLOSED_OBJ; break;
            case $this->HELP_EMPLOYEE : return $this->HELP_EMPLOYEE_OBJ; break;
            case $this->HELP_EMPLOYEE_DELTA : return $this->HELP_EMPLOYEE_DELTA_OBJ; break;
        }
    }


}