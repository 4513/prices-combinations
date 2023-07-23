<?php

declare(strict_types=1);

namespace MiBo\Prices\Exceptions;

use RuntimeException;

/**
 * Class NegativePriceException
 *
 * @package MiBo\Prices\Exceptions
 *
 * @author Michal Boris <michal.boris27@gmail.com>
 *
 * @since 0.1
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise.
 */
class NegativePriceException extends RuntimeException
{
}
