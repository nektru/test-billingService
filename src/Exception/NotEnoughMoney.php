<?php

namespace App\Exception;

use App\Model\ValueObject\Money;

class NotEnoughMoney extends AppException
{
    protected $errorCode = "NotEnoughMoney";

    public function __construct(string $userUUID, Money $money)
    {
        $this->args = [
            'userUUID' => $userUUID,
            'money' => $money,
        ];
        $msg = 'User "'.$userUUID.'" have not enough money to withdraw "'.$money->format().'"';
        parent::__construct($msg);
    }
}
