#!/usr/bin/env php
<?php

use App\ServiceLocator;

require_once __DIR__.'/vendor/autoload.php';

// Чтение настроек приложения и инициализация сервисов
$service = new ServiceLocator(__DIR__.'/config.ini');

// Создание и запуск приложения
$service->cliApp->run();
