<?php

namespace App\Config;

/**
 * Класс конфигурации приложения
 *
 * @property name
 * @property version
 */
class AppConfig
{
    /**
     * @var array Дефолтный конфиг приложения
     */
    protected $config = [
        // Название приложения
        'name' => 'BillingService',
        // Версия приложения
        'version' => '0.0.1',
        'dsn' => 'pgsql:host=192.168.33.10;dbname=billingservice;user=postgres;password=postgres',
    ];

    /**
     * Создает объект конфигурации на основе ini-файла
     *
     * @param string $filename путь к конфигурационному файлу
     */
    public function __construct($filename)
    {
        $this->config = array_merge($this->config, parse_ini_file($filename));
    }

    public function __get($arg)
    {
        return $this->config[$arg] ?: null;
    }
}
