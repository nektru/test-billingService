<?php

namespace tests\unit\models;

use App\Model\ValueObject\Money;

class MoneyTest extends \Codeception\TestCase\Test
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    public function formatDataProvider()
    {
        return [
            [123, 'USD', '123 $'],
            [-123, 'EUR', '-123 €'],
            [123.123, 'RUB', '123,12 Ք'],
        ];
    }

    /**
     * @dataProvider formatDataProvider
     */
    public function testFormat($amount, $currency, $stringResult)
    {
        $money = Money::create($amount, $currency);

        $this->tester->assertEquals($stringResult, $money->format());
        $this->tester->assertEquals($stringResult, (string)$money);
    }

    public function testInstanceProperties()
    {
        $money = Money::create(50, 'EUR');

        $this->assertSame('50.00', $money->amount);
        $this->assertSame('EUR', $money->currency);
    }

    /**
     * Тест конвертации параметров и результатов при проксировании методов либы Money
     */
    public function testProxyMethodsDataConversion()
    {
        $money1 = Money::create(50, 'EUR');
        $money2 = Money::create(10, 'EUR');

        // тест прокси метода с параметром Money и результатом bool
        $this->assertFalse($money1->equals($money2));

        // тест прокси метода с параметром Money и результатом Money
        $this->assertEquals(Money::create(60, 'EUR'), $money1->add($money2));

        // тест прокси метода с параметром массив и результатом массив
        $this->assertEquals([Money::create(20, 'EUR'), Money::create(30, 'EUR')], $money1->allocate([2, 3]));
    }

    public function testCreateByStaticMethods()
    {
        $money = Money::EUR(5);
        $this->assertInstanceOf(Money::class, $money);
    }

    public function testSerialization()
    {
        $money = Money::create(50, 'EUR');
        $this->assertSame(['amount' => '50.00', 'currency' => 'EUR'], $money->jsonSerialize());
    }
}
