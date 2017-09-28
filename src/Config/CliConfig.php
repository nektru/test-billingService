<?php
namespace App\Config;

use App\Handler\CliHandler;
use Webmozart\Console\Config\DefaultApplicationConfig;
use Webmozart\Console\Api\Args\Format\Argument;

class CliConfig extends DefaultApplicationConfig
{
    /**
     * @var CliHandler
     */
    protected $cliHandler;

    /**
     * @var string дефолтная валюта рассчетов
     */
    protected $defaultCurrency = "RUB";

    public function setCliHandler(CliHandler $cliHandler)
    {
        $this->cliHandler = $cliHandler;
    }

    public function getCliHandler()
    {
        return $this->cliHandler;
    }

    protected function configure()
    {
        parent::configure();

        $this->beginCommand('daemon')
            ->setDescription('Запустить обработчик событий из очереди')
            ->setHandler([$this, 'getCliHandler'])
            ->setHandlerMethod('startDaemon');

        $this->beginCommand('query')
            ->setDescription('Операции запросов к сервису биллина')
            ->beginSubCommand('credit')
                ->setDescription('Зачислить средства на указанный аккаунт')
                ->addArgument(
                    'userUUID',
                    Argument::REQUIRED,
                    'Идентификатор пользователя'
                )
                ->addArgument(
                    'amount',
                    Argument::REQUIRED,
                    'Сумма к зачислению'
                )
                ->addArgument(
                    'currency',
                    Argument::OPTIONAL,
                    'Трехбуквенный код валюты',
                    $this->defaultCurrency
                )
                ->setHandler([$this, 'getCliHandler'])
                ->setHandlerMethod('credit')
            ->end()

            ->beginSubCommand('debit')
                ->setDescription('Зачислить средства на указанный аккаунт')
                ->addArgument(
                    'userUUID',
                    Argument::REQUIRED,
                    'Идентификатор пользователя'
                )
                ->addArgument(
                    'amount',
                    Argument::REQUIRED,
                    'Сумма к зачислению'
                )
                ->addArgument(
                    'currency',
                    Argument::OPTIONAL,
                    'Трехбуквенный код валюты',
                    $this->defaultCurrency
                )
                ->setHandler([$this, 'getCliHandler'])
                ->setHandlerMethod('debit')
            ->end()
            ->beginSubCommand('transfer')
                ->setDescription('Зачислить средства на указанный аккаунт')
                ->addArgument(
                    'fromUserUUID',
                    Argument::REQUIRED,
                    'Идентификатор пользователя от которого переводятся деньги'
                )
                ->addArgument(
                    'toUserUUID',
                    Argument::REQUIRED,
                    'Идентификатор пользователя к которому переводятся деньги'
                )
                ->addArgument(
                    'amount',
                    Argument::REQUIRED,
                    'Сумма к трансферу'
                )
                ->addArgument(
                    'currency',
                    Argument::OPTIONAL,
                    'Трехбуквенный код валюты',
                    $this->defaultCurrency
                )
                ->setHandler([$this, 'getCliHandler'])
                ->setHandlerMethod('transfer')
            ->end()
        ->end();
    }
}
