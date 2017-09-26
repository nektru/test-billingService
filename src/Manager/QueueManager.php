<?php

namespace App\Manager;

use App\Contract\AccountManagament;
use App\Model\ValueObject\Money;
use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class QueueManager implements AccountManagament
{
    /** var AccountManager */
    protected $accountManager;

    /**
     * @var AMQPConnection Класс соединения с менеджером очередей
     */
    protected $connect;

    /**
     * @var AMQPChannel
     */
    protected $channel;

    /**
     * var array конфигурация подключения к rabbitmq
     */
    protected $config = [];

    public function __construct(
        string $host = null,
        string $port = null,
        string $user = null,
        string $pass = null,
        string $queue = null
    )
    {
        $this->config = [
            'host' => $host ?: 'localhost',
            'port' => $port ?: '5672',
            'user' => $user ?: 'guest',
            'pass' => $pass ?: 'guest',
            'queue' => $queue ?: 'billingService'
        ];
    }

    /**
     *  Возвращает объект подключения к брокеру
     *  @return AMQPConnection
     */
    protected function connect()
    {
        if (!$this->connect) {
            $this->connect = new AMQPConnection(
                $this->config['host'],
                $this->config['port'],
                $this->config['user'],
                $this->config['pass']
            );
        }
        return $this->connect;
    }

    /**
     *  @return AMQPChannel
     */
    protected function channel()
    {
        if (!$this->channel) {
            $this->channel = $this->connect()->channel();
        }
        return $this->channel;
    }

    public function setAccountManager(AccountManager $accountManager)
    {
        $this->accountManager = $accountManager;
    }

    /**
     * Запускает обработчик событий
     */
    public function runListener()
    {
        $this->preparePersistentQueue();
        echo "it's work!";
    }

    public function createAccount(string $userUUID, string $currency)
    {

    }

    public function getBalance(string $userUUID, string $currency)
    {

    }

    public function credit(string $userUUID, Money $money)
    {

    }

    public function debit(string $userUUID, Money $money)
    {

    }

    public function transfer(string $fromUserUUID, string $toUserUUID, Money $money)
    {


    }

    /**
     * Вызов метода на клиенте через очереди
     * @param string $operation название вызываемого метода
     * @param array $params аргументы вызова
     * @return mixed
     */
    protected function remoteCall(string $operation, array $args)
    {
        $replyTo = $this->randomQueueName();
    }

    /**
     * генерирует рандомное имя для временной очереди
     */
    protected function getRandomQueueName()
    {
        return "tmp_" . strtr(base64_encode(random_bytes(12)), '+/', '-_');
    }


    /**
     * Внутренний метод отправки сообщения в очередь для обработки
     * @see QueueManager::listener()
     *
     * @param array $msg сообщение к отправке
     * @return void
     */
    protected function sendMessage(array $msg, string $replyTo = null)
    {
        $ch = $this->channel();
        $message = [
            'msg' => $msg, // Тело сообщения
            'replyTo' => $replyTo, // Имя очереди для получения ответа
        ];

        $message = new AMQPMessage(
            json_encode($message, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
        $ch->basic_publish($message, '', $this->config['queue']);
    }

    /**
     *  Подготовка потоянных очередей
     */
    protected function preparePersistentQueue()
    {
        /*
            name: $queue
            passive: false
            durable: true // the queue will survive server restarts
            exclusive: false // the queue can be accessed in other channels
            auto_delete: false //the queue won't be deleted once the channel is closed.
        */
        $this->channel()->queue_declare($this->config['queue'], false, true, false, false);
    }

}