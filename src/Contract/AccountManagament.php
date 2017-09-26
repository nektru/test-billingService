<?php

namespace App\Contract;

use App\Model\ValueObject\Money;

/**
 *  Интерфейс управления аккаунтами
 */
interface AccountManagament
{
    public function createAccount(string $userUUID, string $currency);

    public function getBalance(string $userUUID, string $currency);

    public function credit(string $userUUID, Money $money);

    public function debit(string $userUUID, Money $money);

    public function transfer(string $fromUserUUID, string $toUserUUID, Money $money);
}
