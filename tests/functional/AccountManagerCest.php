<?php

namespace tests\functional;

use App\Exception;
use App\Config\AppConfig;
use App\Manager\AccountManager;
use App\Model\ValueObject\Money;
use Ramsey\Uuid\Uuid;

class AccountManagerCest
{
    /** @var AccountManager */
    protected $accountManager;

    public function _before(\FunctionalTester $I)
    {
        $appConfig = new AppConfig(__DIR__.'/../../config.test.ini');
        $this->accountManager = new AccountManager($appConfig->dsn);
    }

    public function _after(\FunctionalTester $I)
    {
    }

    public function testNotExistingAccount(\FunctionalTester $I)
    {
        $I->expectException(Exception\AccountNotExists::class, function () {
            $uuid = Uuid::uuid4();
            $balance = $this->accountManager->getBalance($uuid, 'RUB');
        });

        $I->expectException(Exception\AccountNotExists::class, function () {
            $uuid = Uuid::uuid4();
            $money = Money::RUB(25);
            $balance = $this->accountManager->credit($uuid, $money);
        });

        $I->expectException(Exception\AccountNotExists::class, function () {
            $uuid = Uuid::uuid4();
            $money = Money::RUB(25);
            $balance = $this->accountManager->debit($uuid, $money);
        });
    }

    public function testNotExistingSameCurrencyAccount(\FunctionalTester $I)
    {
        $I->expectException(Exception\AccountNotExists::class, function () {
            $uuid = Uuid::uuid4();
            $balance = $this->accountManager->createAccount($uuid, 'USD');
            $balance = $this->accountManager->getBalance($uuid, 'RUB');
        });
    }

    public function testEmptyAccountBalance(\FunctionalTester $I)
    {
        $uuid = Uuid::uuid4();
        $this->accountManager->createAccount($uuid, 'RUB');
        $balance = $this->accountManager->getBalance($uuid, 'RUB');

        $I->assertEquals($balance->amount, 0);
        $I->assertEquals($balance->currency, 'RUB');
    }

    public function testModifyngAccountBalance(\FunctionalTester $I)
    {
        $uuid = Uuid::uuid4();
        $this->accountManager->createAccount($uuid, 'RUB');

        $money = Money::RUB(15.11);
        $balance = $this->accountManager->credit($uuid, $money);
        $I->assertEquals($balance->amount, 15.11);
        $I->assertEquals($balance->currency, 'RUB');

        $money = Money::RUB(7028.89);
        $balance = $this->accountManager->credit($uuid, $money);
        $I->assertEquals($balance->amount, 7044);
        $I->assertEquals($balance->currency, 'RUB');

        $money = Money::RUB(500.8);
        $balance = $this->accountManager->debit($uuid, $money);
        $I->assertEquals($balance->amount, 6543.2);
        $I->assertEquals($balance->currency, 'RUB');
    }

    public function testNotEnoughtMoney(\FunctionalTester $I)
    {
        $uuid = Uuid::uuid4();
        $this->accountManager->createAccount($uuid, 'EUR');
        $money = Money::EUR(50);
        $this->accountManager->credit($uuid, $money);
        $money = Money::EUR(25);
        $this->accountManager->debit($uuid, $money);

        $I->expectException(Exception\NotEnoughMoney::class, function () use ($uuid) {
            $money = Money::EUR(25.01);
            $this->accountManager->debit($uuid, $money);
        });
    }

    public function testTransferMoney(\FunctionalTester $I)
    {
        $uuid1 = Uuid::uuid4();
        $uuid2 = Uuid::uuid4();
        $money = Money::USD(100);
        $transfer = Money::USD(75);

        $this->accountManager->createAccount($uuid1, 'USD');
        $this->accountManager->createAccount($uuid2, 'USD');

        $this->accountManager->credit($uuid1, $money);
        $this->accountManager->transfer($uuid1, $uuid2, $transfer);

        $balance1 = $this->accountManager->getBalance($uuid1, 'USD');
        $balance2 = $this->accountManager->getBalance($uuid2, 'USD');

        $I->assertEquals($balance1->amount, 25);
        $I->assertEquals($balance1->currency, 'USD');

        $I->assertEquals($balance2->amount, 75);
        $I->assertEquals($balance2->currency, 'USD');

        $I->expectException(
            Exception\NotEnoughMoney::class,
            function () use ($uuid1, $uuid2, $transfer) {
                $this->accountManager->transfer($uuid1, $uuid2, $transfer);
            }
        );
    }

    public function testHoldMoney(\FunctionalTester $I)
    {
        $uuid = Uuid::uuid4();
        $this->accountManager->createAccount($uuid, 'RUB');
        $money = Money::RUB(500);
        $this->accountManager->credit($uuid, $money);
        $holdUUID = $this->accountManager->hold($uuid, $money);
        $balance = $this->accountManager->getBalance($uuid, $money->currency);
        $I->assertEquals($balance->amount, 0);
        $balance = $this->accountManager->rejectHold($holdUUID);
        $I->assertEquals($balance->amount, $money->amount);

        $holdUUID = $this->accountManager->hold($uuid, $money);
        $balance = $this->accountManager->assertHold($holdUUID);
        $I->assertEquals($balance->amount, 0);
        $I->expectException(
            Exception\HoldNotExists::class,
            function () use ($holdUUID) {
                $this->accountManager->rejectHold($holdUUID);
            }
        );
        $I->expectException(
            Exception\HoldNotExists::class,
            function () use ($holdUUID) {
                $this->accountManager->assertHold($holdUUID);
            }
        );

    }
}
