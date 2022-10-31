<?php

include_once __DIR__ . '/../Response/Response.php';

class CdkConfig {

    public $sandbox = 'sandbox';
    public $live = 'live';

    public function __construct(
        public $auth,
        public $environment,
        public $testSuite = []
    ){
        $this->Response = new Response;
    }

    public function env(){
        return $this->environment;
    }

    public function password(){
        return $this->verify('password');
    }

    public function username(){
        return $this->verify('username');
    }

    public function endpoint(){
        return $this->verify('endpoint');
    }

    public function environments(){
        return [
            $this->sandbox, 
            $this->live
        ];
    }

    public function sandboxLabel(){
        return $this->sandbox;
    }

    public function liveLabel(){
        return $this->live;
    }

    public function testSuite(){
        return $this->testSuite;
    }

    public function global(){
        return [
            'username' => $this->username(),
            'password' => $this->password(),
            'environment' => $this->env(),
            'labels' => [
                'dev' => $this->sandboxLabel(),
                'live' => $this->liveLabel()
            ]
        ];
    }

    private function verify($type){
        try {
            return $this->auth[$type];
        }catch (\Exception $e){
            $this->Response->errorResponse('Missing configuration dependency: "' . $type . '"', true);
        }
    }


}