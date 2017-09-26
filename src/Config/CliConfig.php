<?php
namespace App\Config;

use Webmozart\Console\Config\DefaultApplicationConfig;
use App\Manager\QueueManager;

class CliConfig extends DefaultApplicationConfig
{
    /** @var QueueManager */
    protected $queueManager;

    public function setQueueManager(QueueManager $queueManager)
    {
        $this->queueManager = $queueManager;
    }

    protected function configure()
    {
        parent::configure();

        $this->beginCommand('daemon')
            ->setDescription('Запустить обработчик событий из очереди')
            ->setHandler(function () {
                return $this->queueManager;
            })
            ->setHandlerMethod('runListener');
    }
}
