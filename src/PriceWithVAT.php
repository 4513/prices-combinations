<?php

declare(strict_types=1);

namespace MiBo\Prices;

use DateTime;
use MiBo\Prices\Calculators\PriceCalc;
use MiBo\Prices\Contracts\PriceInterface;
use MiBo\Prices\Taxonomies\AnyTaxonomy;
use MiBo\Properties\Contracts\NumericalProperty;
use MiBo\Properties\Contracts\Unit;
use MiBo\Properties\Value;
use MiBo\VAT\Enums\VATRate;
use MiBo\VAT\VAT;

/**
 * Class PriceWithVAT
 *
 * @package MiBo\Prices
 *
 * @author Michal Boris <michal.boris27@gmail.com>
 *
 * @since 0.1
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise.
 */
class PriceWithVAT extends Price
{
    protected Value $vatAmount;

    protected Value $initialVATAmount;

    public function __construct(float|Value|int $value, Unit $unit, ?VAT $vat = null, ?DateTime $time = null)
    {
        $time          ??= new DateTime();
        $value           = $value instanceof Value ? $value : new Value($value, $unit->getMinorUnitRate() ?? 0, 0);
        $vat           ??= VAT::get('', VATRate::ANY, AnyTaxonomy::get(), $time);
        $vatPercentage   = PriceCalc::getVATManager()->getValueOfVAT($vat);
        $vatAmount       = clone $value;
        $this->vatAmount = $vatAmount;

        if ($vatPercentage === 0) {
            $this->vatAmount->multiply(0);
        } else {
            $this->vatAmount->multiply($vatPercentage * 100)->divide(100 + ($vatPercentage * 100));
        }

        $value->subtract($vatAmount);

        $this->initialVATAmount = clone $this->vatAmount;

        parent::__construct($value, $unit, $vat, $time);
    }

    /**
     * Creates a new price that is initialized with a value with VAT, while the input is a price without VAT.
     *
     * @param \MiBo\Prices\Contracts\PriceInterface $price Price without VAT.
     *
     * @return self Price with VAT.
     */
    public static function createFromPrice(PriceInterface $price): self
    {
        if ($price instanceof self) {
            return $price;
        }

        /** @phpstan-ignore-next-line */
        return new static($price->getValueWithVAT(), $price->getUnit(), $price->getVAT());
    }

    /**
     * @inheritDoc
     */
    public function setNestedPrice(string $category, PriceInterface $price): void
    {
        if (!$price instanceof static) {
            $price = self::createFromPrice($price);
        }

        $this->vatAmount->add($price->getValueOfVAT(), $price->getUnit()->getMinorUnitRate() ?? 0);

        parent::setNestedPrice($category, $price);
    }

    /**
     * @inheritDoc
     */
    public function multiply(NumericalProperty|float|int $value): NumericalProperty
    {
        $this->initialVATAmount->multiply($value instanceof NumericalProperty ? $value->getNumericalValue() : $value);
        $this->vatAmount->multiply($value instanceof NumericalProperty ? $value->getNumericalValue() : $value);

        return parent::multiply($value);
    }

    /**
     * @inheritDoc
     */
    public function getValueOfVAT(): int|float
    {
        return $this->vatAmount->getValue(
            $this->getUnit()->getMinorUnitRate() ?? 0,
            $this->getUnit()->getMinorUnitRate() ?? 0
        );
    }

    /**
     * @inheritDoc
     */
    public function __clone(): void
    {
        $this->initialVATAmount = clone $this->initialVATAmount;
        $this->vatAmount        = clone $this->vatAmount;

        parent::__clone();
    }
}
