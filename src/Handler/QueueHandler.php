<?php

namespace App\Handler;

use App\Model\ValueObject\Money;
use App\Manager\AccountManager;
use App\Exception;
use Invoker\Invoker;

/**
 * Обработчик событий из очередей
 *
 * Транслирует запросы из очереди в БД
 */
class QueueHandler
{
    /**
     * @var AccountManager
     */
    protected $accountManager;

    /**
     * @var Invoker
     */
    protected $invoker;

    public function __construct(AccountManager $accountManager, Invoker $invoker)
    {
        $this->accountManager = $accountManager;
        $this->invoker = $invoker;
    }

    /**
     * Обрабатывает сообщение из очереди
     *
     * @param array $message
     */
    public function execute($operation, $arguments)
    {
        try {
            /// @todo заменить на нормальные логи
            echo 'REQUEST: '.$operation."\n".json_encode($arguments, JSON_PRETTY_PRINT)."\n\n";
            $response = $this->invoker->call(
                [$this, $operation],
                $arguments
            );
            $out = [
                'status' => 'ok',
                'response' => $response
            ];
        } catch (Exception\AppException $e) {
            $out = [
                'status' => 'error',
                'error' => $e
            ];
        } catch (\Exception $e) {
            /// @todo заменить на нормальные логи
            fwrite(STDERR, $e);
            $out = [
                'status' => 'error',
                'error' => ['code' => 'unknownError']
            ];
        }
        /// @todo заменить на нормальные логи
        echo "RESPONSE: ".json_encode($out, JSON_PRETTY_PRINT)."\n\n";
        return $out;
    }

    public function createAccount($userUUID, $currency)
    {
        return $this->accountManager->createAccount($userUUID, $currency);
    }

    public function credit($userUUID, $money)
    {
        return $this->accountManager->credit(
            $userUUID,
            Money::create($money['amount'], $money['currency'])
        );
    }

    public function debit($userUUID, $money)
    {
        return $this->accountManager->debit(
            $userUUID,
            Money::create($money['amount'], $money['currency'])
        );
    }

    public function transfer($fromUserUUID, $toUserUUID, $money)
    {
        return $this->accountManager->transfer(
            $fromUserUUID,
            $toUserUUID,
            Money::create($money['amount'], $money['currency'])
        );
    }
}
