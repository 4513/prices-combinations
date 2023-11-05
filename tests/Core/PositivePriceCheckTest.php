<?php

declare(strict_types=1);

namespace MiBo\Prices\Tests;

use MiBo\Prices\Calculators\PriceCalc;
use MiBo\Prices\Exceptions\NegativePriceException;
use MiBo\Prices\PositivePrice;
use MiBo\Prices\PositivePriceWithVAT;
use MiBo\Prices\Price;
use MiBo\Prices\Units\Price\Currency;
use MiBo\Properties\Calculators\UnitConvertor;
use MiBo\VAT\Manager;
use MiBo\VAT\VAT;
use PHPUnit\Framework\TestCase;

/**
 * Class PositivePriceCheckTest
 *
 * @package MiBo\Prices\Tests
 *
 * @author Michal Boris <michal.boris27@gmail.com>
 *
 * @since 0.1
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise.
 *
 * @coversDefaultClass \MiBo\Prices\PositivePrice
 */
class PositivePriceCheckTest extends TestCase
{
    /**
     * @small
     *
     * @covers ::subtract
     * @covers ::compute
     * @covers ::__construct
     * @covers ::check
     *
     * @param int|float $initialPrice
     * @param array $subtractions
     *
     * @return void
     *
     * @dataProvider provideDataToNegativeResult
     */
    public function testNegativity(int|float $initialPrice, array $subtractions): void
    {
        $price = new PositivePrice($initialPrice, Currency::get('EUR'));

        $this->expectException(NegativePriceException::class);

        foreach ($subtractions as $subtract) {
            $price->subtract($subtract);
        }

        $price->getValue();
    }

    /**
     * @small
     *
     * @covers \MiBo\Prices\PositivePriceWithVAT::subtract
     * @covers \MiBo\Prices\PositivePriceWithVAT::compute
     * @covers \MiBo\Prices\PositivePriceWithVAT::__construct
     * @covers \MiBo\Prices\PositivePriceWithVAT::check
     *
     * @param int|float $initialPrice
     * @param array $subtractions
     *
     * @return void
     *
     * @dataProvider provideDataToNegativeResult
     */
    public function testNegativityWithVAT(int|float $initialPrice, array $subtractions): void
    {
        $price = new PositivePriceWithVAT($initialPrice, Currency::get('EUR'));

        $this->expectException(NegativePriceException::class);

        foreach ($subtractions as $subtract) {
            $price->subtract($subtract);
        }

        $price->getValue();
    }

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $vatHelper = new VATResolver();

        PriceCalc::setVATManager(new Manager($vatHelper, $vatHelper, $vatHelper));

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

    protected function retrieveVATByCategory(string $category, string $country): VAT
    {
        return PriceCalc::getVATManager()->retrieveVAT(new TestingClassification($category), $country);
    }

    public static function provideDataToNegativeResult(): array
    {
        return [
            'Negative, simple'        => [
                10,
                [
                    2,
                    1,
                    4,
                    6,
                    10,
                ],
            ],
            'Negative, some ignored'  => [
                10,
                [
                    0,
                    0,
                    1,
                    2,
                    5,
                    10,
                ],
            ],
            'Negative, some opposite' => [
                10,
                [
                    0,
                    10,
                    -1,
                    1,
                    12,
                ],
            ],
            'Negative, floats'        => [
                10,
                [
                    0.1,
                    0.9,
                    9,
                    -0.1,
                    2,
                ],
            ],
        ];
    }
}
