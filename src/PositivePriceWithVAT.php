<?php

declare(strict_types=1);

namespace MiBo\Prices;

use MiBo\Prices\Traits\PositivePriceHelper;

/**
 * Class PositivePriceWithVAT
 *
 * @package MiBo\Prices
 *
 * @author Michal Boris <michal.boris27@gmail.com>
 *
 * @since 0.1
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise.
 */
class PositivePriceWithVAT extends PriceWithVAT
{
    use PositivePriceHelper;

    /**
     * @inheritDoc
     */
    protected function compute(): void
    {
        parent::compute();
        $this->check();
    }
}
