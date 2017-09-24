<?php

namespace App\Manager;

/**
 * Класс управления аккаунтами пользователей
 */
class AccountManager
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
        if (!$connection) {
            $this->connection = new PDO($this->dsn);
        }
        return $this->connection;
    }
}
