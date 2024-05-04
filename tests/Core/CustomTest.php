<?php

declare(strict_types=1);

namespace MiBo\Prices\Tests;

use MiBo\Prices\PositivePrice;
use MiBo\Prices\Price;
use MiBo\Prices\Units\Price\Currency;
use PHPUnit\Framework\TestCase;

/**
 * Class CustomTest
 *
 * @package MiBo\Prices\Tests
 *
 * @author Michal Boris <michal.boris27@gmail.com>
 *
 * @since 2.0.1
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise.
 */
final class CustomTest extends TestCase
{
    /**
     * @small
     *
     * @coversNothing
     *
     * @return void
     */
    public function testNothing(): void
    {
        $price = new PositivePrice(100, Currency::get('EUR'), VATResolver::retrieveByCategory('1', 'SVK'));

        $price->add($price);

        self::assertEquals(200, $price->getValue());
        self::assertEquals(200, $price->getValue());

        $newPrice = new Price(100, Currency::get('EUR'), VATResolver::retrieveByCategory('2', 'SVK'));

        $price->add($newPrice);

        self::assertEquals(300, $price->getValue());
        self::assertEquals(300, $price->getValue());
        self::assertEquals(360, $price->getValueWithVAT());
        self::assertEquals(360, $price->getValueWithVAT());
    }
}
