<?php

namespace App\Handler;

use App\Manager\AccountManager;

class QueueHandler
{
    /**
     * @var AccountManager
     */
    protected $accountManager;

    public function __construct(AccountManager $accountManager)
    {
        $this->accountManager = $accountManager;
    }

    /**
     * Обрабатывает сообщение из очереди
     *
     * @param array $message
     */
    public function execute($message)
    {
        return true;
    }

    /**
     */
    public function createAccount($userUUID, $currency)
    {
        return [$userUUID, $currency];
    }

}
