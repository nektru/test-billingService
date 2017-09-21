#!/usr/bin/env php
<?php

use App\Config\BillingServiceConfig;
use Webmozart\Console\ConsoleApplication;

require_once __DIR__.'/vendor/autoload.php';

$cli = new ConsoleApplication(new BillingServiceConfig());
$cli->run();
