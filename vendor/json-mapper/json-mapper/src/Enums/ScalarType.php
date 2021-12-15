<?php

declare(strict_types=1);

namespace JsonMapper\Enums;

use MyCLabs\Enum\Enum;

/**
 * @method static ScalarType STRING()
 * @method static ScalarType BOOLEAN()
 * @method static ScalarType BOOL()
 * @method static ScalarType INTEGER()
 * @method static ScalarType INT()
 * @method static ScalarType DOUBLE()
 * @method static ScalarType FLOAT()
 * @method static ScalarType MIXED()
 *
 * @psalm-immutable
 */
class ScalarType extends Enum
{
    protected const STRING = 'string';
    protected const BOOLEAN = 'boolean';
    protected const BOOL = 'bool';
    protected const INTEGER = 'integer';
    protected const INT = 'int';
    protected const DOUBLE = 'double';
    protected const FLOAT = 'float';
    protected const MIXED = 'mixed';

    /**
     * @param string|bool|int|float $input
     * @return string|bool|int|float
     */
    public function cast($input)
    {
        if ($this->value === self::MIXED) {
            return $input;
        }
        if ($this->value === self::STRING) {
            return (string) $input;
        }
        if ($this->value === self::BOOLEAN || $this->value === self::BOOL) {
            return (bool) $input;
        }
        if ($this->value === self::INTEGER || $this->value === self::INT) {
            return (int) $input;
        }
        if ($this->value === self::DOUBLE || $this->value === self::FLOAT) {
            return (float) $input;
        }

        throw new \LogicException("Missing {$this->value} in cast method");
    }
}
