<?php
/**
 * \PHPCompatibility\Sniffs\Lists\ForbiddenEmptyListAssignmentSniff.
 *
 * PHP version 7.0
 *
 * @category PHP
 * @package  PHPCompatibility
 * @author   Wim Godden <wim@cu.be>
 */

namespace PHPCompatibility\Sniffs\Lists;

use PHPCompatibility\Sniff;
use PHP_CodeSniffer_File as File;
use PHP_CodeSniffer_Tokens as Tokens;

/**
 * \PHPCompatibility\Sniffs\Lists\ForbiddenEmptyListAssignmentSniff.
 *
 * Empty list() assignments have been removed in PHP 7.0
 *
 * PHP version 7.0
 *
 * @category PHP
 * @package  PHPCompatibility
 * @author   Wim Godden <wim@cu.be>
 */
class ForbiddenEmptyListAssignmentSniff extends Sniff
{

    /**
     * List of tokens to disregard when determining whether the list() is empty.
     *
     * @var array
     */
    protected $ignoreTokens = array();

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register()
    {
        // Set up a list of tokens to disregard when determining whether the list() is empty.
        // Only needs to be set up once.
        $this->ignoreTokens                       = Tokens::$emptyTokens;
        $this->ignoreTokens[\T_COMMA]             = \T_COMMA;
        $this->ignoreTokens[\T_OPEN_PARENTHESIS]  = \T_OPEN_PARENTHESIS;
        $this->ignoreTokens[\T_CLOSE_PARENTHESIS] = \T_CLOSE_PARENTHESIS;

        return array(
            \T_LIST,
            \T_OPEN_SHORT_ARRAY,
        );
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
        if ($this->supportsAbove('7.0') === false) {
            return;
        }

        $tokens = $phpcsFile->getTokens();

        if ($tokens[$stackPtr]['code'] === \T_OPEN_SHORT_ARRAY) {
            if ($this->isShortList($phpcsFile, $stackPtr) === false) {
                return;
            }

            $open  = $stackPtr;
            $close = $tokens[$stackPtr]['bracket_closer'];
        } else {
            // T_LIST.
            $open = $phpcsFile->findNext(\T_OPEN_PARENTHESIS, $stackPtr, null, false, null, true);
            if ($open === false || isset($tokens[$open]['parenthesis_closer']) === false) {
                return;
            }

            $close = $tokens[$open]['parenthesis_closer'];
        }

        $error = true;
        if (($close - $open) > 1) {
            for ($cnt = $open + 1; $cnt < $close; $cnt++) {
                if (isset($this->ignoreTokens[$tokens[$cnt]['code']]) === false) {
                    $error = false;
                    break;
                }
            }
        }

        if ($error === true) {
            $phpcsFile->addError(
                'Empty list() assignments are not allowed since PHP 7.0',
                $stackPtr,
                'Found'
            );
        }
    }
}
