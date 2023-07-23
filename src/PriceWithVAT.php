<?php

declare(strict_types=1);

namespace MiBo\Prices;

use DateTime;
use MiBo\Prices\Contracts\PriceInterface;
use MiBo\Properties\Contracts\NumericalProperty;
use MiBo\Properties\Contracts\Unit;
use MiBo\Properties\Value;
use MiBo\VAT\Enums\VATRate;
use MiBo\VAT\Resolvers\ProxyResolver;
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
        $value           = $value instanceof Value ?
            $value :
            new Value($value, $unit->getMinorUnitRate() ?? 0, 0);
        $vat           ??= VAT::get("", VATRate::NONE);
        $vatPercentage   = ProxyResolver::getPercentageOf($vat, $time);
        $vatAmount       = clone $value;
        $this->vatAmount = $vatAmount;

        $this->vatAmount->divide(100 + $vatPercentage, -2);
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

        $this->vatAmount->add($price->getValueOfVAT());
        parent::setNestedPrice($category, $price);
    }

    /**
     * @inheritDoc
     */
    public function multiply(NumericalProperty|float|int $value): static
    {
        $this->initialVATAmount->multiply($value instanceof NumericalProperty ? $value->getNumericalValue() : $value);

        return parent::multiply($value);
    }

    /**
     * @inheritDoc
     */
    protected function compute(): void
    {
        $this->vatAmount->multiply(0)->add($this->initialVATAmount);
        parent::compute();
    }

    /**
     * @inheritDoc
     */
    public function getValueOfVAT(): int|float
    {
        return $this->vatAmount->getValue();
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
