<?php

declare(strict_types=1);

namespace MiBo\Prices\Tests;

use MiBo\Prices\Calculators\PriceCalc;
use MiBo\Prices\Price;
use MiBo\Prices\PriceWithVAT;
use MiBo\Prices\Units\Price\Currency;
use MiBo\Properties\Calculators\UnitConvertor;
use MiBo\VAT\Manager;
use MiBo\VAT\VAT;
use PHPUnit\Framework\TestCase;

/**
 * Class PriceWithVATTest
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
class PriceWithVATTest extends TestCase
{
    /**
     * @small
     *
     * @covers ::__construct
     *
     * @return void
     */
    public function test(): void
    {
        $price = new PriceWithVAT(10, Currency::get('EUR'));

        $this->assertSame(10, $price->getValue());
        $this->assertSame(0, $price->getValueOfVAT());
        $this->assertSame(10, $price->getValueWithVAT());
    }

    /**
     * @small
     *
     * @covers ::__construct
     * @covers ::compute
     * @covers ::getValueOfVAT
     *
     * @param int|float $initialValue
     * @param array{0: string, 1: \MiBo\VAT\VAT} $vatToUse
     * @param int|float $expectedValueWithVAT
     * @param int|float $expectedValue
     * @param int|float $expectedValueOfVAT
     *
     * @return void
     *
     * @dataProvider provideConstructionData
     */
    public function testCreation(
        int|float $initialValue,
        array $vatToUse,
        int|float $expectedValueWithVAT,
        int|float $expectedValue,
        int|float $expectedValueOfVAT
    ): void
    {
        $price = new PriceWithVAT(
            $initialValue,
            Currency::get('EUR'),
            $this->retrieveVATByCategory($vatToUse[0], $vatToUse[1])
        );

        $this->assertSame(
            $expectedValue,
            $price->getValue(),
            'Value w/o VAT is not correct. A: ' . $price->getValue() . ', E: ' . $expectedValue
        );
        $this->assertSame(
            $expectedValueOfVAT,
            $price->getValueOfVAT(),
            'Value of VAT is not correct. A: ' . $price->getValueOfVAT() . ', E: ' . $expectedValueOfVAT
        );
        $this->assertSame(
            $expectedValueWithVAT,
            $price->getValueWithVAT(),
            'Value with VAT is not correct. A: ' . $price->getValueWithVAT() . ', E: ' . $expectedValueWithVAT
        );
    }

    public static function provideConstructionData(): array
    {
        return [
            'Empty VAT #1' => [
                10,
                ['0', 'SVK'],
                10,
                10,
                0,
            ],
            'Empty VAT #2' => [
                11,
                ['0', 'SVK'],
                11,
                11,
                0,
            ],
            'Empty VAT #3' => [
                1.5,
                ['0', 'SVK'],
                1.5,
                1.5,
                0,
            ],
            'With VAT #1'  => [
                11,
                ['07', 'SVK'],
                11,
                10,
                1,
            ],
            'With VAT #2'  => [
                1.1,
                ['07', 'SVK'],
                1.1,
                1,
                0.1,
            ],
            'With VAT #3'  => [
                12.1,
                ['07', 'SVK'],
                12.1,
                11,
                1.1,
            ],
            'With VAT #4'  => [
                110,
                ['07', 'SVK'],
                110,
                100,
                10,
            ],
        ];
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
}
