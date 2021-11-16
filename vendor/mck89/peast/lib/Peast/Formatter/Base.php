<?php
/**
 * This file is part of the Peast package
 *
 * (c) Marco Marchiò <marco.mm89@gmail.com>
 *
 * For the full copyright and license information refer to the LICENSE file
 * distributed with this source code
 */
namespace Peast\Formatter;

/**
 * Base class for formatters, all the formatters must extend this class.
 * 
 * @author Marco Marchiò <marco.mm89@gmail.com>
 * 
 * @abstract
 */
abstract class Base
{
    /**
     * New line character
     * 
     * @var string
     */
    protected $newLine = "\n";
    
    /**
     * Indentation character
     * 
     * @var string
     */
    protected $indentation = "\t";
    
    /**
     * Boolean that indicates if open curly brackets in code blocks must be
     * on a new line
     * 
     * @var bool
     */
    protected $newLineBeforeCurlyBracket = false;
    
    /**
     * Boolean that indicates if blocks of code must be wrapped in curly
     * brackets also if they contain only one instruction
     * 
     * @var bool
     */
    protected $alwaysWrapBlocks = true;
    
    /**
     * Boolean that indicates if operators must be surrounded by spaces
     * 
     * @var bool
     */
    protected $spacesAroundOperators = true;
    
    /**
     * Boolean that indicates if content inside round brackets must be
     * surrounded by spaces
     * 
     * @var bool
     */
    protected $spacesInsideRoundBrackets = false;
    
    /**
     * Returns the new line character
     * 
     * @return string
     */
    public function getNewLine()
    {
        return $this->newLine;
    }
    
    /**
     * Returns the indentation character
     * 
     * @return string
     */
    public function getIndentation()
    {
        return $this->indentation;
    }
    
    /**
     * Returns a boolean that indicates if open curly brackets in code blocks
     * must be on a new line
     * 
     * @return bool
     */
    public function getNewLineBeforeCurlyBracket()
    {
        return $this->newLineBeforeCurlyBracket;
    }
    
    /**
     * Returns a boolean that indicates if blocks of code must be wrapped in
     * curly brackets also if they contain only one instruction
     * 
     * @return bool
     */
    public function getAlwaysWrapBlocks()
    {
        return $this->alwaysWrapBlocks;
    }
    
    /**
     * Returns a boolean that indicates if operators must be surrounded by
     * spaces
     * 
     * @return bool
     */
    public function getSpacesAroundOperator()
    {
        return $this->spacesAroundOperators;
    }
    
    /**
     * Returns a boolean that indicates if content inside round brackets must be
     * surrounded by spaces
     * 
     * @return bool
     */
    public function getSpacesInsideRoundBrackets()
    {
        return $this->spacesInsideRoundBrackets;
    }
}