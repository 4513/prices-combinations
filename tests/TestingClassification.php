<?php

declare(strict_types=1);

namespace MiBo\Prices\Tests;

use MiBo\Taxonomy\Contracts\ProductTaxonomy;

/**
 * Class TestingClassification
 *
 * @package MiBo\Prices\Tests
 *
 * @author Michal Boris <michal.boris27@gmail.com>
 *
 * @since 2.0
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise.
 */
final class TestingClassification implements ProductTaxonomy
{
    private string $code;

    public function __construct(string $code)
    {
        $this->code = $code;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function is(string|ProductTaxonomy $code): bool
    {
        return $this->code === ($code instanceof ProductTaxonomy ? $code->getCode() : $code);
    }

    public function belongsTo(string|ProductTaxonomy $code): bool
    {
        return false;
    }

    public function wraps(string|ProductTaxonomy $code): bool
    {
        return false;
    }

    public static function isValid(string $code): bool
    {
        return true;
    }
}
