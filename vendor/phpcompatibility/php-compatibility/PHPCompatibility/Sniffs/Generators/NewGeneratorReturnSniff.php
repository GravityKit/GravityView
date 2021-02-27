<?php
/**
 * \PHPCompatibility\Sniffs\Generators\NewGeneratorReturnSniff.
 *
 * PHP version 7.0
 *
 * @category PHP
 * @package  PHPCompatibility
 * @author   Juliette Reinders Folmer <phpcompatibility_nospam@adviesenzo.nl>
 */

namespace PHPCompatibility\Sniffs\Generators;

use PHPCompatibility\Sniff;
use PHPCompatibility\PHPCSHelper;
use PHP_CodeSniffer_File as File;

/**
 * \PHPCompatibility\Sniffs\Generators\NewGeneratorReturnSniff.
 *
 * As of PHP 7.0, a return statement can be used within a generator for a final expression to be returned.
 *
 * PHP version 7.0
 *
 * @category PHP
 * @package  PHPCompatibility
 * @author   Juliette Reinders Folmer <phpcompatibility_nospam@adviesenzo.nl>
 */
class NewGeneratorReturnSniff extends Sniff
{
    /**
     * Scope conditions within which a yield can exist.
     *
     * @var array
     */
    private $validConditions = array(
        \T_FUNCTION => \T_FUNCTION,
        \T_CLOSURE  => \T_CLOSURE,
    );


    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register()
    {
        $targets = array(
            \T_YIELD,
        );

        /*
         * The `yield` keyword was introduced in PHP 5.5 with the token T_YIELD.
         * The `yield from` keyword was introduced in PHP 7.0 and tokenizes as
         * "T_YIELD T_WHITESPACE T_STRING".
         *
         * Pre-PHPCS 3.1.0, the T_YIELD token was not correctly back-filled for PHP < 5.5.
         * Also, as of PHPCS 3.1.0, the PHPCS tokenizer adds a new T_YIELD_FROM
         * token.
         *
         * So for PHP 5.3-5.4 icw PHPCS < 3.1.0, we need to look for T_STRING with content "yield".
         * For PHP 5.5+ we need to look for T_YIELD.
         * For PHPCS 3.1.0+, we also need to look for T_YIELD_FROM.
         */
        if (version_compare(\PHP_VERSION_ID, '50500', '<') === true
            && version_compare(PHPCSHelper::getVersion(), '3.1.0', '<') === true
        ) {
            $targets[] = \T_STRING;
        }

        if (\defined('T_YIELD_FROM')) {
            $targets[] = \T_YIELD_FROM;
        }

        return $targets;
    }

    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param \PHP_CodeSniffer_File $phpcsFile The file being scanned.
     * @param int                   $stackPtr  The position of the current token in the
     *                                         stack passed in $tokens.
     *
     * @return void|int Void or a stack pointer to skip forward.
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        if ($this->supportsBelow('5.6') !== true) {
            return;
        }

        $tokens = $phpcsFile->getTokens();

        if ($tokens[$stackPtr]['code'] === \T_STRING
            && $tokens[$stackPtr]['content'] !== 'yield'
        ) {
            return;
        }

        if (empty($tokens[$stackPtr]['conditions']) === true) {
            return;
        }

        // Walk the condition from inner to outer to see if we can find a valid function/closure scope.
        $conditions = array_reverse($tokens[$stackPtr]['conditions'], true);
        foreach ($conditions as $ptr => $type) {
            if (isset($this->validConditions[$type]) === true) {
                $function = $ptr;
                break;
            }
        }

        if (isset($function) === false) {
            // Yield outside function scope, fatal error, but not our concern.
            return;
        }

        if (isset($tokens[$function]['scope_opener'], $tokens[$function]['scope_closer']) === false) {
            // Can't reliably determine start/end of function scope.
            return;
        }

        $targets = array(\T_RETURN, \T_CLOSURE, \T_FUNCTION, \T_CLASS);
        if (\defined('T_ANON_CLASS')) {
            $targets[] = \T_ANON_CLASS;
        }

        $current = $tokens[$function]['scope_opener'];

        while (($current = $phpcsFile->findNext($targets, ($current + 1), $tokens[$function]['scope_closer'])) !== false) {
            if ($tokens[$current]['code'] === \T_RETURN) {
                $phpcsFile->addError(
                    'Returning a final expression from a generator was not supported in PHP 5.6 or earlier',
                    $current,
                    'ReturnFound'
                );

                return $tokens[$function]['scope_closer'];
            }

            // Found a nested scope in which return can exist without problems.
            if (isset($tokens[$current]['scope_closer'])) {
                // Skip past the nested scope.
                $current = $tokens[$current]['scope_closer'];
            }
        }

        // Don't examine this function again.
        return $tokens[$function]['scope_closer'];
    }
}
