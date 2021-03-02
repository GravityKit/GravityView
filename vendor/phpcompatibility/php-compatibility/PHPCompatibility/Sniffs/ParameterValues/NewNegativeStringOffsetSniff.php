<?php
/**
 * \PHPCompatibility\Sniffs\ParameterValues\NewNegativeStringOffsetSniff.
 *
 * PHP version 7.1
 *
 * @category PHP
 * @package  PHPCompatibility
 * @author   Juliette Reinders Folmer <phpcompatibility_nospam@adviesenzo.nl>
 */

namespace PHPCompatibility\Sniffs\ParameterValues;

use PHPCompatibility\AbstractFunctionCallParameterSniff;
use PHP_CodeSniffer_File as File;

/**
 * \PHPCompatibility\Sniffs\ParameterValues\NewNegativeStringOffsetSniff.
 *
 * Detect: negative string offsets as parameters passed to functions where this
 * was not allowed prior to PHP 7.1.
 *
 * PHP version 7.1
 *
 * @category PHP
 * @package  PHPCompatibility
 * @author   Juliette Reinders Folmer <phpcompatibility_nospam@adviesenzo.nl>
 */
class NewNegativeStringOffsetSniff extends AbstractFunctionCallParameterSniff
{

    /**
     * Functions to check for.
     *
     * @var array Function name => 1-based parameter offset of the affected parameters => parameter name.
     */
    protected $targetFunctions = array(
        'file_get_contents'     => array(
            4 => 'offset',
        ),
        'grapheme_extract'      => array(
            4 => 'start',
        ),
        'grapheme_stripos'      => array(
            3 => 'offset',
        ),
        'grapheme_strpos'       => array(
            3 => 'offset',
        ),
        'iconv_strpos'          => array(
            3 => 'offset',
        ),
        'mb_ereg_search_setpos' => array(
            1 => 'position',
        ),
        'mb_strimwidth'         => array(
            2 => 'start',
            3 => 'width',
        ),
        'mb_stripos'            => array(
            3 => 'offset',
        ),
        'mb_strpos'             => array(
            3 => 'offset',
        ),
        'stripos'               => array(
            3 => 'offset',
        ),
        'strpos'                => array(
            3 => 'offset',
        ),
        'substr_count'          => array(
            3 => 'offset',
            4 => 'length',
        ),
    );


    /**
     * Do a version check to determine if this sniff needs to run at all.
     *
     * @return bool
     */
    protected function bowOutEarly()
    {
        return ($this->supportsBelow('7.0') === false);
    }

    /**
     * Process the parameters of a matched function.
     *
     * @param \PHP_CodeSniffer_File $phpcsFile    The file being scanned.
     * @param int                   $stackPtr     The position of the current token in the stack.
     * @param string                $functionName The token content (function name) which was matched.
     * @param array                 $parameters   Array with information about the parameters.
     *
     * @return int|void Integer stack pointer to skip forward or void to continue
     *                  normal file processing.
     */
    public function processParameters(File $phpcsFile, $stackPtr, $functionName, $parameters)
    {
        $functionLC = strtolower($functionName);
        foreach ($this->targetFunctions[$functionLC] as $pos => $name) {
            if (isset($parameters[$pos]) === false) {
                continue;
            }

            $targetParam = $parameters[$pos];

            if ($this->isNegativeNumber($phpcsFile, $targetParam['start'], $targetParam['end']) === false) {
                continue;
            }

            $phpcsFile->addError(
                'Negative string offsets were not supported for the $%s parameter in %s() in PHP 7.0 or lower. Found %s',
                $targetParam['start'],
                'Found',
                array(
                    $name,
                    $functionName,
                    $targetParam['raw'],
                )
            );
        }
    }
}
