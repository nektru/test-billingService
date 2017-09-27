<?php

namespace App;

use App\Config\AppConfig;
use App\Config\CliConfig;
use App\Handler\CliHandler;
use App\Handler\QueueHandler;
use App\Manager\QueueManager;
use App\Manager\AccountManager;
use Webmozart\Console\ConsoleApplication;
use Invoker\Invoker;

/**
 * Класс сервислокатора
 *
 * @property CliHandler $cliHandler
 * @property AppConfig $appConfig
 */
class ServiceLocator
{
    /**
     * @var []
     */
    protected $services = [];

    /**
     * @param string $configFilename путь к конфиуграции
     */
    public function __construct($configFilename)
    {
        $this->services = [
            // Конфигурация приложения
            'config' => new AppConfig($configFilename),
            // Обработчик консольного ввода-вывода
            'cliApp' => function () {
                return new ConsoleApplication($this->cliConfig);
            },
            // Конфигурация консольного ввода-вывода
            'cliConfig' => function () {
                $config = new CliConfig($this->config->name, $this->config->version);
                $config->setCliHandler($this->cliHandler);
                return $config;
            },
            // Обработчик консольных команд
            'cliHandler' => function () {
                return new CliHandler(
                    $this->queueManager,
                    $this->queueHandler,
                    new Invoker()
                );
            },
            'queueHandler' => function () {
                return new QueueHandler($this->accountManager);
            },
            // Менеджер очередей
            'queueManager' => function () {
                return new QueueManager(
                    $this->config->rabbitHost,
                    $this->config->rabbitPort,
                    $this->config->rabbitUser,
                    $this->config->rabbitPass,
                    $this->config->rabbitQueue
                );
            },
            'accountManager' => function () {
                return new AccountManager(
                    $this->config->dsn
                );
            }
        ];
    }

    /**
     * Магический метод для доступа к сервисам
     */
    public function __get($name)
    {
        if (isset($this->services[$name])) {
            if (is_callable($this->services[$name])) {
                $this->services[$name] = $this->services[$name]();
            }
            if (is_object($this->services[$name]))
            {
                return $this->services[$name];
            }
        }
        throw new Exception\ServiceNotFound('Service "'.$name.'" not found');
    }
}
