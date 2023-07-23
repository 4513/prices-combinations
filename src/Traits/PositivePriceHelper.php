<?php

declare(strict_types=1);

namespace MiBo\Prices\Traits;

use MiBo\Prices\Exceptions\NegativePriceException;

/**
 * Trait PositivePriceHelper
 *
 * @package MiBo\Prices\Traits
 *
 * @author Michal Boris <michal.boris27@gmail.com>
 *
 * @since 0.1
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise.
 */
trait PositivePriceHelper
{
    /**
     * Check if the price is positive.
     *
     * @return void
     */
    public function check(): void
    {
        $errors        = [];
        $groupedByRate = [];

        foreach ($this->prices as $price) {
            $groupedByRate[$price->getVAT()->getRate()->name][] = $price;
        }

        foreach ($groupedByRate as $rate => $prices) {
            $sum = 0;

            foreach ($prices as $price) {
                $sum += $price->getValue();
            }

            if ($sum < 0) {
                $errors[$rate] = $sum;
            }
        }

        if (!empty($errors)) {
            $message = strtr(
                "The price is invalid!\n" .
                "The sum of prices with VAT rates :rates are defective. The sums of the VATs: :sums.",
                [
                    ":rates" => implode(", ", array_keys($errors)),
                    ":sums"  => implode(", ", $errors),
                ]
            );

            throw new NegativePriceException($message);
        }
    }
}
