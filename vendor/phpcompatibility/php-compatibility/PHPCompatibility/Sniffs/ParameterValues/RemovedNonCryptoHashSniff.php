<?php
/**
 * \PHPCompatibility\Sniffs\ParameterValues\RemovedNonCryptoHashSniff.
 *
 * PHP version 7.2
 *
 * @category PHP
 * @package  PHPCompatibility
 * @author   Juliette Reinders Folmer <phpcompatibility_nospam@adviesenzo.nl>
 */

namespace PHPCompatibility\Sniffs\ParameterValues;

use PHPCompatibility\AbstractFunctionCallParameterSniff;
use PHP_CodeSniffer_File as File;

/**
 * \PHPCompatibility\Sniffs\ParameterValues\RemovedNonCryptoHashSniff.
 *
 * Detect: "The hash_hmac(), hash_hmac_file(), hash_pbkdf2(), and hash_init()
 * (with HASH_HMAC) functions no longer accept non-cryptographic hashes."
 *
 * PHP version 7.2
 *
 * @category PHP
 * @package  PHPCompatibility
 * @author   Juliette Reinders Folmer <phpcompatibility_nospam@adviesenzo.nl>
 */
class RemovedNonCryptoHashSniff extends AbstractFunctionCallParameterSniff
{

    /**
     * Functions to check for.
     *
     * @var array
     */
    protected $targetFunctions = array(
        'hash_hmac'      => true,
        'hash_hmac_file' => true,
        'hash_init'      => true,
        'hash_pbkdf2'    => true,
    );

    /**
     * List of the non-cryptographic hashes.
     *
     * @var array
     */
    protected $disabledCryptos = array(
        'adler32' => true,
        'crc32'   => true,
        'crc32b'  => true,
        'fnv132'  => true,
        'fnv1a32' => true,
        'fnv164'  => true,
        'fnv1a64' => true,
        'joaat'   => true,
    );


    /**
     * Do a version check to determine if this sniff needs to run at all.
     *
     * @return bool
     */
    protected function bowOutEarly()
    {
        return ($this->supportsAbove('7.2') === false);
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
        if (isset($parameters[1]) === false) {
            return;
        }

        $targetParam = $parameters[1];

        if (isset($this->disabledCryptos[$this->stripQuotes($targetParam['raw'])]) === false) {
            return;
        }

        if (strtolower($functionName) === 'hash_init'
            && (isset($parameters[2]) === false
            || ($parameters[2]['raw'] !== 'HASH_HMAC'
                && $parameters[2]['raw'] !== (string) \HASH_HMAC))
        ) {
            // For hash_init(), these hashes are only disabled with HASH_HMAC set.
            return;
        }

        $phpcsFile->addError(
            'Non-cryptographic hashes are no longer accepted by function %s() since PHP 7.2. Found: %s',
            $targetParam['start'],
            $this->stringToErrorCode($functionName),
            array(
                $functionName,
                $targetParam['raw'],
            )
        );
    }
}
