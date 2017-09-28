<?php

namespace App\Manager;

use App\Model\ValueObject\Money;
use App\Contract\AccountManagament;
use App\Exception;
use Ramsey\Uuid\Uuid;

/**
 * Класс управления аккаунтами пользователей
 */
class AccountManager implements AccountManagament
{
    /** @var string строка подключения к БД */
    protected $dsn;

    /** @var PDO объект соединения с базой данных */
    protected $connection;

    /**
     * Конструктор объекта. Не создает подключения к БД.
     */
    public function __construct(string $dsn)
    {
        $this->dsn = $dsn;
    }

    /**
     * Возвращает соединение с базой данных
     * @return PDO
     */
    protected function getConnection()
    {
        if (!$this->connection) {
            $this->connection = new \PDO($this->dsn);
            $this->connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        }
        return $this->connection;
    }

    /**
     * Создать аккаунт в указанной валюте
     * @param string $userUUID идентификатор пользователя
     * @param string Валюта счета
     * @return bool
     */
    public function createAccount(string $userUUID, string $currency)
    {
        $dbh = $this->getConnection();
        $sql = '
            INSERT INTO account (user_uuid, currency)
            VALUES ( :user_uuid, :currency )
            ON CONFLICT DO NOTHING
        ';
        $sth = $dbh->prepare($sql);
        $sth->execute([
            ':user_uuid' => $userUUID,
            ':currency' => $currency,
        ]);
        return true;
    }

    /**
     * Узнать балланс в указанной валюте
     * @param string $userUUID идентификатор пользователя
     * @param string $currency Валюта счета
     * @return Money
     * @throws Exception\AccountNotExists ошибка отсутствия указанного аккаунта
     */
    public function getBalance(string $userUUID, string $currency)
    {
        return $this->requestBalance($userUUID, $currency);
    }

    /**
     * Зачислить деньги на счет
     * @param string $userUUID идентификатор пользователя
     * @param Money $money сумма к зачислению
     * @return Money балланс счета
     * @throws Exception\AccountNotExists ошибка отсутствия указанного аккаунта
     */
    public function credit(string $userUUID, Money $money)
    {
        $dbh = $this->getConnection();
        $balance = $this->changeBalance('credit', $userUUID, $money);
        return $balance;
    }

    /**
     * Списать деньги со счета
     * @param string $userUUID идентификатор пользователя
     * @param Money $money сумма к списанию
     * @return Money балланс счета
     * @throws Exception\NotEnoughtMoney
     * @throws Exception\AccountNotExists ошибка отсутствия указанного аккаунта
     */
    public function debit(string $userUUID, Money $money)
    {
        $dbh = $this->getConnection();
        $dbh->beginTransaction();
        try {
            $balance = $this->requestBalance($userUUID, $money->currency, true);

            if ($balance->lessThan($money)) {
                throw new Exception\NotEnoughMoney($userUUID, $money);
            }

            $balance = $this->changeBalance('debit', $userUUID, $money);
        } catch (\Exception $e) {
            $dbh->rollback();
            throw $e;
        }
        $dbh->commit();
        return $balance;
    }

    /**
     * Заморозить деньги на счету
     * @param string $userUUID идентификатор пользователя
     * @param Money $money сумма к заморозке
     * @return string идентификатор замороженных средств
     * @throws Exception\NotEnoughtMoney
     * @throws Exception\AccountNotExists ошибка отсутствия указанного аккаунта
     */
    public function hold(string $userUUID, Money $money)
    {
        $dbh = $this->getConnection();
        $dbh->beginTransaction();
        try {
            $balance = $this->requestBalance($userUUID, $money->currency, true);

            if ($balance->lessThan($money)) {
                throw new Exception\NotEnoughMoney($userUUID, $money);
            }

            $this->changeBalance('debit', $userUUID, $money);
            $holdUUID = $this->createHold($userUUID, $money);

        } catch (\Exception $e) {
            $dbh->rollback();
            throw $e;
        }
        $dbh->commit();
        return $holdUUID;
    }

    /**
     * Подтвердить списание замороженных средств
     * @param string $holdUUID идентификатор замороженных средств
     * @return Money баланс счета
     */
    public function assertHold(string $holdUUID)
    {
        $dbh = $this->getConnection();
        $sql = "
            UPDATE hold set status = 'accepted'
            WHERE hold_uuid = :hold_uuid
                AND status = 'new'
            RETURNING user_uuid, currency
        ";
        $sth = $dbh->prepare($sql);
        $sth->execute([
            ':hold_uuid' => $holdUUID,
        ]);
        $result = $sth->fetchAll(\PDO::FETCH_ASSOC);
        if (sizeof($result) == 0) {
            throw new Exception\HoldNotExists($holdUUID);
        } else {
            return $this->requestBalance($result[0]['user_uuid'], $result[0]['currency']);
        }
    }

    /**
     * Отклонить списание замороженных средств
     * @param string $holdUUID идентификатор замороженных средств
     * @return Money баланс счета
     */
    public function rejectHold(string $holdUUID)
    {
        $dbh = $this->getConnection();
        $sql = "
            UPDATE hold set status = 'rejected'
            WHERE hold_uuid = :hold_uuid
                AND status = 'new'
            RETURNING user_uuid, currency, amount
        ";
        $sth = $dbh->prepare($sql);
        $sth->execute([
            ':hold_uuid' => $holdUUID,
        ]);
        $result = $sth->fetchAll(\PDO::FETCH_ASSOC);
        if (sizeof($result) == 0) {
            throw new Exception\HoldNotExists($holdUUID);
        } else {
            $money = Money::createFromRawData($result[0]['amount'], $result[0]['currency']);
            return $this->credit($result[0]['user_uuid'], $money);
        }
    }

    /**
     * Создает запись о замороженных средствах
     * @param string $userUUID
     * @param Money $money
     * @return string Идентификатор замороженных средств
     */
    protected function createHold(string $userUUID, Money $money)
    {
        $holdUUID = Uuid::uuid1();
        $dbh = $this->getConnection();
        $sql = '
            INSERT INTO hold (hold_uuid, user_uuid, currency, amount)
            VALUES ( :hold_uuid, :user_uuid, :currency, :amount )
        ';
        $sth = $dbh->prepare($sql);
        $sth->execute([
            ':hold_uuid' => $holdUUID,
            ':user_uuid' => $userUUID,
            ':currency' => $money->currency,
            ':amount' => $money->rawAmount,
        ]);
        return $holdUUID;
    }

    /**
     * Перевести деньги с одного аккаунта на другой
     * @param string $fromUserUUID идентификатор пользователя, у которого списываются средства
     * @param string $toUserUUID идентификатор пользователя, которому зачисляются средства
     * @param Money $money сумма к списанию
     * @return array массив балансов аккантов, участвующих в операции трансфера
     * @throws Exception\NotEnoughtMoney
     * @throws Exception\AccountNotExists ошибка отсутствия указанного аккаунта
     */
    public function transfer(string $fromUserUUID, string $toUserUUID, Money $money)
    {
        $dbh = $this->getConnection();
        $dbh->beginTransaction();
        try {
            // Сортируем идентификаторы, чтобы не напороться на взаимную блокировку
            $uuids = [$fromUserUUID, $toUserUUID];
            sort($uuids);
            $balance = [];
            $balance[$uuids[0]] = $this->requestBalance($uuids[0], $money->currency, true);
            $balance[$uuids[1]] = $this->requestBalance($uuids[1], $money->currency, true);

            if ($balance[$fromUserUUID]->lessThan($money)) {
                throw new Exception\NotEnoughMoney($fromUserUUID, $money);
            }
            $balance[$fromUserUUID] = $this->changeBalance('debit', $fromUserUUID, $money);
            $balance[$toUserUUID] = $this->changeBalance('credit', $toUserUUID, $money);
        } catch (\Exception $e) {
            $dbh->rollback();
            throw $e;
        }
        $dbh->commit();
        return $balance;
    }

    /**
     * Запрос баланса пользователя
     * @param string $userUUID идентификатор пользователя
     * @param string $currency Валюта счета
     * @param bool $forUpdate флаг блокировки записи для последующего изменения значения
     * @return Money
     */
    protected function requestBalance($userUUID, $currency, $forUpdate = false)
    {
        $dbh = $this->getConnection();
        $sql = '
            SELECT balance_amount, currency FROM account
            WHERE  user_uuid = :user_uuid
                AND currency = :currency
        ';
        if ($forUpdate) {
            $sql .= 'FOR UPDATE';
        }
        $sth = $dbh->prepare($sql);
        $sth->execute([
            ':user_uuid' => $userUUID,
            ':currency' => $currency,
        ]);
        $result = $this->fetchMoney($sth);
        if (sizeof($result) == 0) {
            throw new Exception\AccountNotExists($userUUID, $currency);
        } else {
            return $result[0];
        }
    }


    /**
     * Внутренняя операция изменения балланса
     * @param $operation enum(credit,debit) направление движения денег
     * @param string $userUUID идентификатор пользователя
     * @param Money $money количество денег
     * @return Money текущий баланс пользователя
     * @throws Exception\AccountNotExists ошибка отсутствия указанного аккаунта
     */
    protected function changeBalance($operation, $userUUID, Money $money)
    {
        $dbh = $this->getConnection();

        $sign = ($operation == 'credit') ? '+' : '-';

        $sql = '
            UPDATE account
            SET balance_amount = balance_amount '.$sign.' :amount
            WHERE user_uuid = :user_uuid
                AND currency = :currency
            RETURNING balance_amount, currency
        ';
        $sth = $dbh->prepare($sql);
        $sth->execute([
            ':amount' => $money->rawAmount,
            ':user_uuid' => $userUUID,
            ':currency' => $money->currency,
        ]);

        $result = $this->fetchMoney($sth);
        if (sizeof($result) == 0) {
            throw new Exception\AccountNotExists($userUUID, $money->currency);
        } else {
            return $result[0];
        }
    }

    /**
     * Конвертирует ответ в объекты денег
     * @return Money[]
     */
    protected function fetchMoney(\PDOStatement $sth)
    {
        return $sth->fetchAll(\PDO::FETCH_FUNC, [Money::class, 'createFromRawData']);
    }
}
