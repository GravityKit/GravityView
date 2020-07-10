<?php
/**
 * \PHPCompatibility\Sniffs\Syntax\RemovedNewReferenceSniff.
 *
 * PHP version 5.4
 *
 * @category  PHP
 * @package   PHPCompatibility
 * @author    Wim Godden <wim.godden@cu.be>
 * @copyright 2012 Cu.be Solutions bvba
 */

namespace PHPCompatibility\Sniffs\Syntax;

use PHPCompatibility\Sniff;
use PHP_CodeSniffer_File as File;
use PHP_CodeSniffer_Tokens as Tokens;

/**
 * \PHPCompatibility\Sniffs\Syntax\RemovedNewReferenceSniff.
 *
 * Discourages the use of assigning the return value of new by reference
 *
 * PHP version 5.4
 *
 * @category  PHP
 * @package   PHPCompatibility
 * @author    Wim Godden <wim.godden@cu.be>
 * @copyright 2012 Cu.be Solutions bvba
 */
class RemovedNewReferenceSniff extends Sniff
{

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register()
    {
        return array(\T_NEW);
    }

    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param \PHP_CodeSniffer_File $phpcsFile The file being scanned.
     * @param int                   $stackPtr  The position of the current token in the
     *                                         stack passed in $tokens.
     *
     * @return void
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        if ($this->supportsAbove('5.3') === false) {
            return;
        }

        $tokens       = $phpcsFile->getTokens();
        $prevNonEmpty = $phpcsFile->findPrevious(Tokens::$emptyTokens, ($stackPtr - 1), null, true);
        if ($prevNonEmpty === false || $tokens[$prevNonEmpty]['type'] !== 'T_BITWISE_AND') {
            return;
        }

        $error     = 'Assigning the return value of new by reference is deprecated in PHP 5.3';
        $isError   = false;
        $errorCode = 'Deprecated';

        if ($this->supportsAbove('7.0') === true) {
            $error    .= ' and has been removed in PHP 7.0';
            $isError   = true;
            $errorCode = 'Removed';
        }

        $this->addMessage($phpcsFile, $error, $stackPtr, $isError, $errorCode);
    }
}
