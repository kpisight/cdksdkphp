<?php

include_once __DIR__ . '/Config/Config.php';
include_once __DIR__ . '/Core/Core.php';

class Cdk extends Core {

    public function setConfig(){
        
        $this->config = new CdkConfig(
            $this->authentication,
            $this->environment,
            $this->testSuiteConfig
        );

        $this->setTestSuiteConfig(
            $this->config->testSuite()
        );

    }

}