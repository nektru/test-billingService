#!/usr/bin/env php
<?php

use App\Config\AppConfig;
use App\Config\CliConfig;
use App\Manager\AccountManager;
use App\Manager\QueueManager;
use Webmozart\Console\ConsoleApplication;

require_once __DIR__.'/vendor/autoload.php';

// Чтение настроек приложения
$appConfig = new AppConfig(__DIR__.'/config.ini');

// Работа с аккаунтами пользователей
$accountManager = new AccountManager($appConfig->dsn);

// Работа с очередями
$queueManager = new QueueManager();
$queueManager->setAccountManager($accountManager);

// Формирование настроек роутинга для cli
$cliConfig = new CliConfig($appConfig->name, $appConfig->version);
$cliConfig->setQueueManager($queueManager);

// Создание и запуск приложения
$cli = new ConsoleApplication($cliConfig);
$cli->run();
