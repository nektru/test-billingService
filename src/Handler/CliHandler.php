<?php

namespace App\Handler;

use App\Manager\QueueManager;
use Ramsey\Uuid\Uuid;
use Invoker\Invoker;

class CliHandler
{
    /**
     * @var QueueManager
     */
    protected $queueManager;

    /**
     * @var QueueManager
     */
    protected $queueHandler;

    /**
     * @var Invoker
     */
    protected $invoker;

    public function __construct(
        QueueManager $queueManager,
        QueueHandler $queueHandler,
        Invoker $invoker
    ) {
        $this->queueManager = $queueManager;
        $this->queueHandler = $queueHandler;
        $this->invoker = $invoker;
    }

    public function startDaemon()
    {
        // Обработка прерываний
        $stopSignal = false;
        pcntl_signal(SIGTERM, function () use ($stopSignal) {
            $stopSignal = true;
        });

        // Подписываемся на сообщения
        $exetuteMessage = function ($operation, $arguments) {
            try {
                $response = $this->invoker->call(
                    [$this->queueHandler, $operation],
                    $arguments
                );
                return [
                    'status' => 'ok',
                    'responce' => $response
                ];
            } catch (\Exception $e) {
                return [
                    'status' => 'error',
                    'error' => $e
                ];
            }
        };
        $this->queueManager->setListener($exetuteMessage);

        // Основной цикл работы
        while (!$stopSignal) {
            $this->queueManager->waitForMessages(10);
            // Обработать системные сигналы
            pcntl_signal_dispatch();
            /// @todo добавить проверку на утечку памяти
        }
    }

    public function create()
    {
        $userUUID = Uuid::uuid4();
        $currency = 'RUB';
        $out = $this->queueManager->createAccount($userUUID, $currency);
        var_dump($out);
    }
}
