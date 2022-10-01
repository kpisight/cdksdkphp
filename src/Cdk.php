<?php

include_once __DIR__ . '/Config/Config.php';
include_once __DIR__ . '/Core/Core.php';

class Cdk extends Core {

    public function setConfig(){
        $this->config = new CdkConfig(
            $this->authentication,
            $this->environment
        );
    }

    public function configData(){
        return [
            'username' => $this->config->username(),
            'password' => $this->config->password(),
            'environment' => $this->config->env(),
            'labels' => [
                'dev' => $this->config->sandboxLabel(),
                'live' => $this->config->liveLabel()
            ]
        ];
    }

    

}