<?php

namespace App\Handler;

use App\Model\ValueObject\Money;
use App\Manager\QueueManager;
use App\Exception;
use Webmozart\Console\Api\Args\Args;
use Webmozart\Console\IO\ConsoleIO as IO;

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

    public function __construct(
        QueueManager $queueManager,
        QueueHandler $queueHandler
    ) {
        $this->queueManager = $queueManager;
        $this->queueHandler = $queueHandler;
    }

    public function startDaemon()
    {
        // Подписываемся на сообщения
        $this->queueManager->setListener([$this->queueHandler, 'execute']);
        // Основной цикл работы
        while (true) {
            $this->queueManager->waitForMessages(10);
        }
    }

    public function balance(Args $args, IO $io)
    {
        $userUUID = $args->getArgument('userUUID');
        $currency = $args->getArgument('currency');

        $io->writeLine("<b>Getting ballance for $userUUID with $currency</b>");
        $response = $this->queueManager->getBalance($userUUID, $currency);
        $this->processResponse($response, $io);
    }

    public function credit(Args $args, IO $io)
    {
        $userUUID = $args->getArgument('userUUID');
        $money = Money::create($args->getArgument('amount'), $args->getArgument('currency'));

        $io->writeLine("<b>Credit account $userUUID with $money</b>");
        $response = $this->queueManager->credit($userUUID, $money);
        $this->processResponse($response, $io);
        if ($this->createAccountIfNotExists($response, $io)) {
            $io->writeLine("<b>Credit account $userUUID with $money again</b>");
            $response = $this->queueManager->credit($userUUID, $money);
            $this->processResponse($response, $io);
        }
    }

    public function debit(Args $args, IO $io)
    {
        $userUUID = $args->getArgument('userUUID');
        $money = Money::create($args->getArgument('amount'), $args->getArgument('currency'));

        $io->writeLine("<b>Debit account $userUUID with $money</b>");
        $response = $this->queueManager->debit($userUUID, $money);
        $this->processResponse($response, $io);
    }

    public function hold(Args $args, IO $io)
    {
        $userUUID = $args->getArgument('userUUID');
        $money = Money::create($args->getArgument('amount'), $args->getArgument('currency'));

        $io->writeLine("<b>Hold account $userUUID with $money</b>");
        $response = $this->queueManager->hold($userUUID, $money);
        $this->processResponse($response, $io);
    }

    public function assertHold(Args $args, IO $io)
    {
        $holdUUID = $args->getArgument('holdUUID');

        $io->writeLine("<b>Asserting hold $holdUUID</b>");
        $response = $this->queueManager->assertHold($holdUUID);
        $this->processResponse($response, $io);
    }

    public function rejectHold(Args $args, IO $io)
    {
        $holdUUID = $args->getArgument('holdUUID');

        $io->writeLine("<b>Rejecting hold $holdUUID</b>");
        $response = $this->queueManager->rejectHold($holdUUID);
        $this->processResponse($response, $io);
    }

    public function transfer(Args $args, IO $io)
    {
        $fromUserUUID = $args->getArgument('fromUserUUID');
        $toUserUUID = $args->getArgument('toUserUUID');
        $money = Money::create($args->getArgument('amount'), $args->getArgument('currency'));

        $io->writeLine("<b>Transfer $money from $fromUserUUID to $toUserUUID</b>");
        $response = $this->queueManager->transfer($fromUserUUID, $toUserUUID, $money);
        $this->processResponse($response, $io);
    }

    /**
     * Создает аккаунт, если в ответе указано, что он еще не создан
     * @return bool true - если аккаунт не существовал и был создан
     */
    protected function createAccountIfNotExists(array $response, IO $io)
    {
        if ($response['status'] == 'error' && $response['error']['code'] == 'AccountNotExists')
        {
            $userUUID = $response['error']['args']['userUUID'];
            $currency = $response['error']['args']['currency'];
            $io->writeLine("<b>Create account $userUUID as $currency</b>");
            $response = $this->queueManager->createAccount($userUUID, $currency);
            $this->processResponse($response, $io);
            return $response['status'] == "ok";
        }
        return false;
    }

    /**
     * Проверяет ответ на ошибки и исправляет их, если может. Результат выводит в консоль
     */
    protected function processResponse(array $response, IO $io)
    {
        if ($response['status'] == 'ok') {
            $io->writeLine("<c1>".json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)."</c1>");
        } else {
            $io->writeLine("<c2>".json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)."</c2>");
        }
    }
}
