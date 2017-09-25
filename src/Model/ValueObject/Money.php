<?php

namespace App\Model\ValueObject;

use NumberFormatter;
use Money\Currency;
use Money\Money as ParentMoney;

/**
 * Обертка для представления денежных величин.
 * Я вляется прокси-классом для библиотеки moneyphp/money
 *
 * Примеры использования:
 * ```php
 * $oneDollar = Money::USD(1);
 * echo $oneDollar; // $1
 *
 * $tenEuros = Money::create(10, "EUR");
 * echo $tenEuros; // €10
 * ```
 *
 * @property string $amount сумма
 * @property string $currency валюта в ISO 4217 alpha-3
 *
 * Проксируемые методы:
 * @method bool isSameCurrency(Money $other)
 * @method bool equals(Money $other)
 * @method int compare(Money $other)
 * @method bool greaterThan(Money $other)
 * @method bool greaterThanOrEqual(Money $other)
 * @method bool lessThan(Money $other)
 * @method bool lessThanOrEqual(Money $other)
 * @method Money add(Money $addend)
 * @method Money subtract(Money $subtrahend)
 * @method Money multiply(float|int|string $multiplier, $roundingMode = \Money\Money::ROUND_HALF_UP)
 * @method Money divide(float|int|string $divisor, $roundingMode = \Money\Money::ROUND_HALF_UP)
 * @method Money[] allocate(array $ratios)
 * @method Money[] allocateTo($n)
 * @method Money absolute()
 * @method bool isZero()
 * @method bool isPositive()
 * @method bool isNegative()
 * @method void registerCalculator($calculator)
 */
class Money implements \JsonSerializable
{
    const ROUND_HALF_UP = PHP_ROUND_HALF_UP;
    const ROUND_HALF_DOWN = PHP_ROUND_HALF_DOWN;
    const ROUND_HALF_EVEN = PHP_ROUND_HALF_EVEN;
    const ROUND_HALF_ODD = PHP_ROUND_HALF_ODD;
    const ROUND_UP = 5;
    const ROUND_DOWN = 6;
    const ROUND_HALF_POSITIVE_INFINITY = 7;
    const ROUND_HALF_NEGATIVE_INFINITY = 8;

    /**
     *  @var integer количество учитываемых минорных единиц валюты
     */
    protected static $minorCounts = 100;

    /**
     *  @var string дефолтная локаль отображения для форматированных денежных величин
     */
    protected static $defaultLanguage = 'ru_RU';

    /**
     *  @var array массив переопределяемых символов валют
     */
    protected static $symbols = [
        'RUB' => 'Ք',
    ];

    /**
     *  @var \Money\Money
     */
    protected $money;

    /** Ограничение прямого использования конструктора */
    protected function __construct() {}

    /**
     * @param float $amount сумма
     * @param string $currency валюта в ISO 4217 alpha-3
     * @return Money
     */
    public static function create($amount, $currency)
    {
        $amount = intval($amount * self::$minorCounts);
        $money = new ParentMoney($amount, new Currency($currency));

        return self::createWrapper($money);
    }

    /**
     * Создание объекта из хранимых данных.
     *
     * !NB используйте с осторожностью
     *
     * @param float $amount сумма минорных единиц
     * @param string $currency валюта в ISO 4217 alpha-3
     * @return Money
     */
    public static function createFromRawData($amount, $currency)
    {
        $money = new ParentMoney($amount, new Currency($currency));

        return self::createWrapper($money);
    }

    /**
     * Магический метод для сокращенного синтаксиса для доступа к геттерам
     */
    public function __get($name)
    {
        $getter = 'get' . $name;
        if (method_exists($this, $getter)) {
            // read property, e.g. getName()
            return $this->$getter();
        }
        /// @todo throw Exception;
        return null;
    }

    /**
     *  Позволяет создание объектов следующим образом:
     *  ```php
     *  $tenEuro = Money::EUR(10);
     *  ```
     * @param string $name
     * @param array $args
     * @return Money
     */
    public static function __callStatic($name, $args)
    {
        $amount = intval($args[0] * self::$minorCounts);
        $money = ParentMoney::$name($amount);

        return self::createWrapper($money);
    }

    /**
     * @param \Money\Money $money
     * @return Money
     */
    protected static function createWrapper(ParentMoney $money)
    {
        $moneyWrapper = new self();
        $moneyWrapper->money = $money;

        return $moneyWrapper;
    }

    /**
     *  Получить сумму
     *  @return string
     */
    public function getAmount()
    {
        return bcdiv($this->money->getAmount(), self::$minorCounts, 2);
    }

    /**
     *  Получить сумму для сохранения в БД
     *
     *  !NB используйте с осторожностью
     *
     *  @return string
     */
    public function getRawAmount()
    {
        return $this->money->getAmount();
    }

    /**
     *  Получить код валюты
     *  @return string валюта в ISO 4217 alpha-3
     */
    public function getCurrency()
    {
        return $this->money->getCurrency()->getCode();
    }

    /**
     * Формирует данные для сериализации в JSON
     * @return array
     */
    public function jsonSerialize()
    {
        return [
            'amount' => $this->amount,
            'currency' => $this->currency,
        ];
    }

    /**
     * Форматирует сумму в соответствии с настройками локализации.
     * Использует php-intl и подставляет требуемые символы валют
     *
     * @return string форматированный результат
     */
    public function format()
    {
        $fmt = NumberFormatter::create(self::$defaultLanguage, NumberFormatter::CURRENCY);
        $fmt->setTextAttribute(NumberFormatter::CURRENCY_CODE, $this->currency);

        // Получаем и устанавливаем в форматтер знак валюты
        if (isset(self::$symbols[$this->currency])) {
            $symbol = self::$symbols[$this->currency];
            $fmt->setSymbol(NumberFormatter::CURRENCY_SYMBOL, $symbol);
        }

        // Отбрасываем дробную часть, если она равна нулю
        $amount = $this->amount;
        if ($amount - (int)$amount == 0) {
            $fmt->setAttribute(NumberFormatter::FRACTION_DIGITS, 0);
        }

        return $fmt->format($this->amount);
    }

    /**
     * Магический метод, преобразует объект в строку при выводе
     *
     * @return string
     */
    public function __toString()
    {
        return $this->format();
    }

    /**
     * Магия для проксирования методов библиотеки Money
     *
     * @param string $name
     * @param array $params
     * @return mixed
     */
    public function __call($name, $params)
    {
        if (method_exists($this->money, $name)) {
            $result = call_user_func_array([$this->money, $name], $this->convertArray($params));
            return $this->convert($result);
        }

        return parent::__call($name, $params);
    }

    /**
     * Конвертит туда-сюда объекты Money
     * для прозрачного использования параметров и результатов в проксировании методов
     *
     * @param $object
     * @return array|Money|\Money\Money|mixed
     */
    private function convert($object)
    {
        if (is_array($object)) {
            return $this->convertArray($object);
        } elseif ($object instanceof ParentMoney) {
            return self::createWrapper($object);
        } elseif ($object instanceof self) {
            return $object->money;
        } else {
            return $object;
        }
    }

    /**
     * Конвертит массивы
     *
     * @param array $array
     * @return Money[]|\Money\Money[]|array
     */
    private function convertArray(array $array)
    {
        return array_map(
            function ($object) {
                return $this->convert($object);
            },
            $array
        );
    }
}
