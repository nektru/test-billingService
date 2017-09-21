<?php
namespace App\Config;

use Webmozart\Console\Config\DefaultApplicationConfig;

class BillingServiceConfig extends DefaultApplicationConfig
{
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('BillingService')
            ->setVersion('1.0.0')
        ;
    }
}
