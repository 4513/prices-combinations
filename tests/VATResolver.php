<?php

declare(strict_types=1);

namespace MiBo\Prices\Tests;

use DateTime;
use DateTimeInterface;
use MiBo\Taxonomy\Contracts\ProductTaxonomy;
use MiBo\VAT\Contracts\Convertor;
use MiBo\VAT\Contracts\ValueResolver;
use MiBo\VAT\Enums\VATRate;
use MiBo\VAT\VAT;
use Stringable;

/**
 * Class VATResolver
 *
 * @package MiBo\Prices\Tests
 *
 * @author Michal Boris <michal.boris27@gmail.com>
 *
 * @since 0.1
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise.
 */
class VATResolver implements \MiBo\VAT\Contracts\VATResolver, Convertor, ValueResolver
{
    public function convert(VAT $vat, ?string $countryCode = null, ?DateTimeInterface $date = null): VAT
    {
        return self::convertForCountry($vat, $countryCode ?? $vat->getCountryCode());
    }

    public function retrieveVAT(
        ProductTaxonomy $classification,
        Stringable|string $countryCode,
        ?DateTimeInterface $date
    ): VAT
    {
        return self::retrieveByCategory($classification->getCode(), (string) $countryCode);
    }

    public function getValueOfVAT(VAT $vat): float|int
    {
        return self::getPercentageOf($vat);
    }

    public static function convertForCountry(VAT $vat, string $countryCode): VAT
    {
        return self::retrieveByCategory($vat->getClassification()->getCode(), $countryCode);
    }

    public static function retrieveByCategory(string $category, string $countryCode): VAT
    {
        if (empty(self::getVATs()[$countryCode][$category])) {
            return VAT::get($countryCode, VATRate::STANDARD, new TestingClassification($category), new DateTime());
        }

        return VAT::get(
            $countryCode,
            self::getVATs()[$countryCode][$category],
            new TestingClassification($category),
            new DateTime()
        );
    }

    public static function getPercentageOf(VAT $vat): float|int
    {
        return self::getPercentages()[$vat->getCountryCode()][$vat->getRate()->name] ?? 0;
    }

    /**
     * @return array<string, array<string, \MiBo\VAT\Enums\VATRate>>
     */
    private static function getVATs(): array
    {
        return [
            "CZE" => [
                "9705 00 00" => VATRate::REDUCED,
                "9704 00 00" => VATRate::REDUCED,
                "2201"       => VATRate::SECOND_REDUCED,
                "06"         => VATRate::NONE,
                "07"         => VATRate::NONE,
                "08"         => VATRate::NONE,
                "09"         => VATRate::NONE,
                "10"         => VATRate::NONE,
                "1"          => VATRate::STANDARD,
                "2"          => VATRate::STANDARD,
            ],
            "SVK" => [
                "07" => VATRate::REDUCED,
                "08" => VATRate::REDUCED, // 36.75
                "1"  => VATRate::STANDARD,
                "2"  => VATRate::STANDARD,
                "0"  => VATRate::NONE,
            ],
        ];
    }

    /**
     * @return array<string, array<value-of<\MiBo\VAT\Enums\VATRate>, float>>
     */
    private static function getPercentages(): array
    {
        return [
            "CZE" => [
                VATRate::STANDARD->name       => 0.21,
                VATRate::SECOND_REDUCED->name => 0.10,
                VATRate::REDUCED->name        => 0.15,
                VATRate::NONE->name           => 0,
            ],
            "SVK" => [
                VATRate::STANDARD->name       => 0.20,
                VATRate::REDUCED->name        => 0.10,
                VATRate::NONE->name           => 0,
                VATRate::SECOND_REDUCED->name => 0.20,
            ],
        ];
    }
}
