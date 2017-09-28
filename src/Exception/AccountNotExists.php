<?php

namespace App\Exception;

class AccountNotExists extends AppException
{
    protected $errorCode = "AccountNotExists";

    public function __construct(string $userUUID, string $currency)
    {
        $this->args = [
            'userUUID' => $userUUID,
            'currency' => $currency,
        ];
        $msg = 'Account "'.$currency.'" for "'.$userUUID.'" not exists';
        parent::__construct($msg);
    }
}
