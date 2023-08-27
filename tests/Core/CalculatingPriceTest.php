<?php

declare(strict_types=1);

namespace MiBo\Prices\Tests;

use Closure;
use MiBo\Prices\Price;
use MiBo\Prices\PriceWithVAT;
use MiBo\Prices\Units\Price\Currency;
use MiBo\Properties\Calculators\UnitConvertor;
use MiBo\VAT\Resolvers\ProxyResolver;
use PHPUnit\Framework\TestCase;

/**
 * Class CalculatingPriceTest
 *
 * @package MiBo\Prices\Tests
 *
 * @author Michal Boris <michal.boris27@gmail.com>
 *
 * @since 0.1
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise.
 *
 * @coversDefaultClass \MiBo\Prices\PriceWithVAT
 */
class CalculatingPriceTest extends TestCase
{
    /**
     * @small
     *
     * @covers ::setNestedPrice
     * @covers ::createFromPrice
     * @covers ::__clone
     * @covers ::multiply
     *
     * @param \Closure $getInitialPrice
     * @param \Closure $getAddendPrice
     * @param int|float $expectedValueWithVAT
     * @param int|float $expectedValue
     * @param int|float $expectedValueOfVAT
     *
     * @return void
     *
     * @dataProvider provideData
     */
    public function testAdding(
        Closure $getInitialPrice,
        Closure $getAddendPrice,
        int|float $expectedValueWithVAT,
        int|float $expectedValue,
        int|float $expectedValueOfVAT
    ): void
    {
        /** @var \MiBo\Prices\PriceWithVAT $initialPrice */
        $initialPrice = $getInitialPrice();
        /** @var \MiBo\Prices\Contracts\PriceInterface $addendPrice */
        $addendPrice = $getAddendPrice();

        $initialPrice->add($addendPrice);

        $this->assertEquals($expectedValueOfVAT, $initialPrice->getValueOfVAT());
        $this->assertEquals($expectedValue, $initialPrice->getValue());
        $this->assertEquals($expectedValueWithVAT, $initialPrice->getValueWithVAT());

        $this->assertSame(0, $initialPrice->multiply(0)->getValue());
        $this->assertSame(0, $initialPrice->getValueWithVAT());
        $this->assertSame(0, $initialPrice->getValueOfVAT());
    }

    public static function provideData(): array
    {
        return [
            'Prices with VAT (none)'          => [
                function() {
                    return new PriceWithVAT(10, Currency::get('EUR'));
                },
                function() {
                    return new PriceWithVAT(10, Currency::get('EUR'));
                },
                20,
                20,
                0,
            ],
            'Price without VAT (none)'        => [
                function() {
                    return new PriceWithVAT(10, Currency::get('EUR'));
                },
                function() {
                    return new Price(10, Currency::get('EUR'));
                },
                20,
                20,
                0,
            ],
            'Prices with VAT (combined)'      => [
                function() {
                    return new PriceWithVAT(
                        11,
                        Currency::get('EUR'),
                        ProxyResolver::retrieveByCategory('07', 'SVK')
                    );
                },
                function() {
                    return new PriceWithVAT(
                        10,
                        Currency::get('EUR'),
                        ProxyResolver::retrieveByCategory('0', 'SVK')
                    );
                },
                21,
                20,
                1,
            ],
            'Prices with VAT (same)'          => [
                function() {
                    return new PriceWithVAT(
                        11,
                        Currency::get('EUR'),
                        ProxyResolver::retrieveByCategory('07', 'SVK')
                    );
                },
                function() {
                    return new PriceWithVAT(
                        11,
                        Currency::get('EUR'),
                        ProxyResolver::retrieveByCategory('07', 'SVK')
                    );
                },
                22,
                20,
                2,
            ],
            'Price without VAT (same)'        => [
                function() {
                    return new PriceWithVAT(
                        11,
                        Currency::get('EUR'),
                        ProxyResolver::retrieveByCategory('07', 'SVK')
                    );
                },
                function() {
                    return new Price(
                        10,
                        Currency::get('EUR'),
                        ProxyResolver::retrieveByCategory('07', 'SVK')
                    );
                },
                22,
                20,
                2,
            ],
            'Price without VAT (combined)'    => [
                function() {
                    return new PriceWithVAT(
                        11,
                        Currency::get('EUR'),
                        ProxyResolver::retrieveByCategory('07', 'SVK')
                    );
                },
                function() {
                    return new Price(
                        10,
                        Currency::get('EUR'),
                        ProxyResolver::retrieveByCategory('0', 'SVK')
                    );
                },
                21,
                20,
                1,
            ],
            'Price without VAT (combined) #2' => [
                function() {
                    return new PriceWithVAT(
                        11,
                        Currency::get('EUR'),
                        ProxyResolver::retrieveByCategory('0', 'SVK')
                    );
                },
                function() {
                    return new Price(
                        10,
                        Currency::get('EUR'),
                        ProxyResolver::retrieveByCategory('07', 'SVK')
                    );
                },
                22,
                21,
                1,
            ],
        ];
    }

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        ProxyResolver::setResolver(VATResolver::class);

        // Setting conversion rate between CZK and EUR => 1 EUR = 25 CZK
        UnitConvertor::$unitConvertors[\MiBo\Prices\Quantities\Price::class] = function(Price $price, Currency $unit) {
            if ($price->getUnit()->getName() === "Euro" && $unit->getName() === "Czech Koruna") {
                return $price->getNumericalValue()->multiply(25);
            } else if ($price->getUnit()->is($unit)) {
                return $price->getNumericalValue();
            }

            return $price->getNumericalValue()->divide(25);
        };
    }
}
