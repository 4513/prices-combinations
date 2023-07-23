<?php

declare(strict_types=1);

namespace MiBo\Prices;

use MiBo\Prices\Traits\PositivePriceHelper;

/**
 * Class PositivePrice
 *
 * @package MiBo\Prices
 *
 * @author Michal Boris <michal.boris27@gmail.com>
 *
 * @since 0.1
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise.
 */
class PositivePrice extends Price
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
