<?php
/**
 * \PHPCompatibility\Sniffs\Syntax\NewDynamicAccessToStaticSniff.
 *
 * PHP version 5.3
 *
 * @category PHP
 * @package  PHPCompatibility
 * @author   Juliette Reinders Folmer <phpcompatibility_nospam@adviesenzo.nl>
 */

namespace PHPCompatibility\Sniffs\Syntax;

use PHPCompatibility\Sniff;
use PHP_CodeSniffer_File as File;
use PHP_CodeSniffer_Tokens as Tokens;

/**
 * \PHPCompatibility\Sniffs\Syntax\NewDynamicAccessToStaticSniff.
 *
 * As of PHP 5.3, static properties and methods as well as class constants
 * can be accessed using a dynamic (variable) class name.
 *
 * PHP version 5.3
 *
 * @category PHP
 * @package  PHPCompatibility
 * @author   Juliette Reinders Folmer <phpcompatibility_nospam@adviesenzo.nl>
 */
class NewDynamicAccessToStaticSniff extends Sniff
{

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register()
    {
        return array(
            \T_DOUBLE_COLON,
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
        if ($this->supportsBelow('5.2') === false) {
            return;
        }

        $tokens       = $phpcsFile->getTokens();
        $prevNonEmpty = $phpcsFile->findPrevious(Tokens::$emptyTokens, ($stackPtr - 1), null, true);

        // Disregard `static::` as well. Late static binding is reported by another sniff.
        if ($tokens[$prevNonEmpty]['code'] === \T_SELF
            || $tokens[$prevNonEmpty]['code'] === \T_PARENT
            || $tokens[$prevNonEmpty]['code'] === \T_STATIC
        ) {
            return;
        }

        if ($tokens[$prevNonEmpty]['code'] === \T_STRING) {
            $prevPrevNonEmpty = $phpcsFile->findPrevious(Tokens::$emptyTokens, ($prevNonEmpty - 1), null, true);

            if ($tokens[$prevPrevNonEmpty]['code'] !== \T_OBJECT_OPERATOR) {
                return;
            }
        }

        $phpcsFile->addError(
            'Static class properties and methods, as well as class constants, could not be accessed using a dynamic (variable) classname in PHP 5.2 or earlier.',
            $stackPtr,
            'Found'
        );
    }
}
