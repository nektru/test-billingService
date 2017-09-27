<?php
namespace App\Config;

use Webmozart\Console\Config\DefaultApplicationConfig;
use App\Handler\CliHandler;

class CliConfig extends DefaultApplicationConfig
{
    /** @var CliHandler */
    protected $cliHandler;

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

        $this->beginCommand('create')
            ->setDescription('Запустить обработчик событий из очереди')
            ->setHandler([$this, 'getCliHandler'])
            ->setHandlerMethod('create');
    }
}
