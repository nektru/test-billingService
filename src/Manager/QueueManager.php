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
     * @var array конфигурация подключения к rabbitmq
     */
    protected $config = [];

    public function __construct(
        string $host = null,
        string $port = null,
        string $user = null,
        string $pass = null,
        string $queue = null
    ) {
        $this->config = [
            'host' => $host ?: 'localhost',
            'port' => $port ?: '5672',
            'user' => $user ?: 'guest',
            'pass' => $pass ?: 'guest',
            'queue' => $queue ?: 'defaultQueue'
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

    /**
     * Запускает обработчик событий на дефолтной очереди
     * @param callable $callback метод принимает тело сообщения
     */
    public function setListener(callable $callback)
    {
        $this->prepareQueue($this->config['queue'], true);
        $this->channel()->basic_consume(
            $this->config['queue'], // queue name
            '',     // consumer tag
            false,  // no_local
            false,  // no_ack
            false,  // exclusive
            false,  // nowait
            function ($msg) use ($callback) { // callback
                return $this->processQueueMessage($msg, $callback);
            }
        );
    }

    /**
     *  Запустить ожидание сообщений из очередей
     *
     *  Этот метод ожидает любого события, на который была осуществлена подписка черед self::subscribe
     *  @param int $timeout максимальное время ожидания сообщения
     *  @return void
     */
    public function waitForMessages($timeout = 0)
    {
        $ch = $this->channel();
        try {
            $ch->wait(null, true, $timeout);
        } catch (\PhpAmqpLib\Exception\AMQPTimeoutException $e) {
            return; // Все хорошо, дождались таймаута
        } catch (\Exception $e) {
            if (strpos($e->getMessage(), 'Interrupted system call') !== false) {
                /// @bug AMQPT не умеет корректно обрабатывать сигналы прерываний
                return;
            }
            throw $e;
        }
        return;
    }

    /**
     * Обработка сообщения из очереди
     *
     * В зависимости от успешности обработки отправляет ack/nack
     * @param AMQPMessage $msg сообщение из очереди
     * @param callable $action обработчик сообщения
     */
    protected function processQueueMessage(AMQPMessage $msg, callable $action)
    {
        $ch = $this->channel();
        if (isset($msg->delivery_info)) {
            $data = json_decode($msg->body, true);
            $result = $action($data['operation'], $data['args']);
            if (isset($data['replyTo'])) {
                $this->sendRequest($data['replyTo'], $result);
            }
            $ch->basic_ack($msg->delivery_info['delivery_tag']);
        }
        return $result;
    }

    public function createAccount(string $userUUID, string $currency)
    {
        $args = [
            'userUUID' => $userUUID,
            'currency' => $currency,
        ];
        $responce = $this->remoteCall(__FUNCTION__, $args);
        return $responce;
    }

    public function getBalance(string $userUUID, string $currency)
    {
        $args = [
            'userUUID' => $userUUID,
            'currency' => $currency,
        ];
        $responce = $this->remoteCall(__FUNCTION__, $args);
        return $responce;
    }

    public function credit(string $userUUID, Money $money)
    {
        $args = [
            'userUUID' => $userUUID,
            'money' => $money,
        ];
        $responce = $this->remoteCall(__FUNCTION__, $args);
        return $responce;
    }

    public function debit(string $userUUID, Money $money)
    {
        $args = [
            'userUUID' => $userUUID,
            'money' => $money,
        ];
        $responce = $this->remoteCall(__FUNCTION__, $args);
        return $responce;
    }

    public function transfer(string $fromUserUUID, string $toUserUUID, Money $money)
    {
        $args = [
            'fromUserUUID' => $fromUserUUID,
            'toUserUUID' => $toUserUUID,
            'money' => $money,
        ];
        $responce = $this->remoteCall(__FUNCTION__, $args);
        return $responce;
    }

    /**
     * Вызов метода на клиенте через очереди
     * @param string $operation название вызываемого метода
     * @param array $params аргументы вызова
     * @return mixed
     */
    public function remoteCall(string $operation, array $args)
    {
        $replyTo = $this->getRandomQueueName();
        $this->prepareQueue($replyTo);
        $message = [
            'operation' => $operation,
            'args' => $args,
            'replyTo' => $replyTo
        ];
        $this->sendRequest($this->config['queue'], $message);
        $response = $this->waitResponse($replyTo);
        return $response;
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
     *
     * @param string $queue имя очереди
     * @param array $message сообщение к отправке
     * @return void
     */
    protected function sendRequest(string $queue, array $message)
    {
        $ch = $this->channel();
        $message = new AMQPMessage(
            json_encode($message, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
        $ch->basic_publish($message, '', $queue);
    }

    /**
     * Метод дожидается ответа из указанной очереди
     *
     * @property string $queueName
     * @return array
     */
    protected function waitResponse(string $queueName)
    {
        $response = null;

        $ch = $this->channel();
        $receiveCallback = function ($msg) use (&$response, $ch) {
            $response = json_decode($msg->body, true);
            $ch->basic_ack($msg->delivery_info['delivery_tag']);
        };

        $ch->basic_consume($queueName, '', false, false, false, false, $receiveCallback);
        while (!$response) {
            $ch->wait();
        }
        return $response;
    }

    /**
     *  Подготовка потоянных очередей
     */
    protected function prepareQueue(string $queue, $persistent = false)
    {
        /*
            name: $queue
            passive: false
            durable: true // the queue will survive server restarts
            exclusive: false // the queue can be accessed in other channels
            auto_delete: false //the queue won't be deleted once the channel is closed.
        */
        $this->channel()->queue_declare($queue, false, $persistent, !$persistent, !$persistent);
    }
}
