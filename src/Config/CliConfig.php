<?php
namespace App\Config;

use Webmozart\Console\Config\DefaultApplicationConfig;

class CliConfig extends DefaultApplicationConfig
{
    protected function configure()
    {
        parent::configure();

        #$this->configureQueueManager();
        #$this->configureAccountManager();
        #$this->configureCliRoutes();

    }
}
