<?php
/**
 * Small input validation helpers.
 */

declare(strict_types=1);

namespace App\Core;

final class Validator
{
    public static function slug(string $value): bool
    {
        return (bool) preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $value)
            && strlen($value) <= 100;
    }
}
