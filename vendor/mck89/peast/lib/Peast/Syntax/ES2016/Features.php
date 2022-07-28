<?php
/**
 * This file is part of the Peast package
 *
 * (c) Marco Marchiò <marco.mm89@gmail.com>
 *
 * For the full copyright and license information refer to the LICENSE file
 * distributed with this source code
 */
namespace Peast\Syntax\ES2016;

/**
 * ES2016 features class
 * 
 * @author Marco Marchiò <marco.mm89@gmail.com>
 * 
 * @codeCoverageIgnore
 */
class Features extends \Peast\Syntax\ES2015\Features
{
    /**
     * Exponentiation operator
     *
     * @var bool
     */
    public $exponentiationOperator = true;
}