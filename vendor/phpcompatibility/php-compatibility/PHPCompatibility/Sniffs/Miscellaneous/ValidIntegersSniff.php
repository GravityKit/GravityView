<?php
/**
 * \PHPCompatibility\Sniffs\Miscellaneous\ValidIntegersSniff.
 *
 * @category PHP
 * @package  PHPCompatibility
 * @author   Juliette Reinders Folmer <phpcompatibility_nospam@adviesenzo.nl>
 */

namespace PHPCompatibility\Sniffs\Miscellaneous;

use PHPCompatibility\Sniff;
use PHP_CodeSniffer_File as File;

/**
 * \PHPCompatibility\Sniffs\Miscellaneous\ValidIntegersSniff.
 *
 * @category PHP
 * @package  PHPCompatibility
 * @author   Juliette Reinders Folmer <phpcompatibility_nospam@adviesenzo.nl>
 */
class ValidIntegersSniff extends Sniff
{

    /**
     * Whether PHPCS is run on a PHP < 5.4.
     *
     * @var bool
     */
    protected $isLowPHPVersion = false;

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register()
    {
        $this->isLowPHPVersion = version_compare(\PHP_VERSION_ID, '50400', '<');

        return array(
            \T_LNUMBER, // Binary, octal integers.
            \T_CONSTANT_ENCAPSED_STRING, // Hex numeric string.
        );
    }


    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param \PHP_CodeSniffer_File $phpcsFile The file being scanned.
     * @param int                   $stackPtr  The position of the current token in
     *                                         the stack.
     *
     * @return void
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();
        $token  = $tokens[$stackPtr];

        if ($this->couldBeBinaryInteger($tokens, $stackPtr) === true) {
            if ($this->supportsBelow('5.3')) {
                $error = 'Binary integer literals were not present in PHP version 5.3 or earlier. Found: %s';
                if ($this->isLowPHPVersion === false) {
                    $data = array($token['content']);
                } else {
                    $data = array($this->getBinaryInteger($phpcsFile, $tokens, $stackPtr));
                }
                $phpcsFile->addError($error, $stackPtr, 'BinaryIntegerFound', $data);
            }

            if ($this->isInvalidBinaryInteger($tokens, $stackPtr) === true) {
                $error = 'Invalid binary integer detected. Found: %s';
                $data  = array($this->getBinaryInteger($phpcsFile, $tokens, $stackPtr));
                $phpcsFile->addWarning($error, $stackPtr, 'InvalidBinaryIntegerFound', $data);
            }
            return;
        }

        $isError = $this->supportsAbove('7.0');
        $data    = array( $token['content'] );

        if ($this->isInvalidOctalInteger($tokens, $stackPtr) === true) {
            $this->addMessage(
                $phpcsFile,
                'Invalid octal integer detected. Prior to PHP 7 this would lead to a truncated number. From PHP 7 onwards this causes a parse error. Found: %s',
                $stackPtr,
                $isError,
                'InvalidOctalIntegerFound',
                $data
            );
            return;
        }

        if ($this->isHexidecimalNumericString($tokens, $stackPtr) === true) {
            $this->addMessage(
                $phpcsFile,
                'The behaviour of hexadecimal numeric strings was inconsistent prior to PHP 7 and support has been removed in PHP 7. Found: %s',
                $stackPtr,
                $isError,
                'HexNumericStringFound',
                $data
            );
            return;
        }
    }


    /**
     * Could the current token an potentially be a binary integer ?
     *
     * @param array $tokens   Token stack.
     * @param int   $stackPtr The current position in the token stack.
     *
     * @return bool
     */
    private function couldBeBinaryInteger($tokens, $stackPtr)
    {
        $token = $tokens[$stackPtr];

        if ($token['code'] !== \T_LNUMBER) {
            return false;
        }

        if ($this->isLowPHPVersion === false) {
            return (preg_match('`^0b[0-1]+$`D', $token['content']) === 1);
        }
        // Pre-5.4, binary strings are tokenized as T_LNUMBER (0) + T_STRING ("b01010101").
        // At this point, we don't yet care whether it's a valid binary int, that's a separate check.
        else {
            return($token['content'] === '0' && $tokens[$stackPtr + 1]['code'] === \T_STRING && preg_match('`^b[0-9]+$`D', $tokens[$stackPtr + 1]['content']) === 1);
        }
    }

    /**
     * Is the current token an invalid binary integer ?
     *
     * @param array $tokens   Token stack.
     * @param int   $stackPtr The current position in the token stack.
     *
     * @return bool
     */
    private function isInvalidBinaryInteger($tokens, $stackPtr)
    {
        if ($this->couldBeBinaryInteger($tokens, $stackPtr) === false) {
            return false;
        }

        if ($this->isLowPHPVersion === false) {
            // If it's an invalid binary int, the token will be split into two T_LNUMBER tokens.
            return ($tokens[$stackPtr + 1]['code'] === \T_LNUMBER);
        } else {
            return (preg_match('`^b[0-1]+$`D', $tokens[$stackPtr + 1]['content']) === 0);
        }
    }

    /**
     * Retrieve the content of the tokens which together look like a binary integer.
     *
     * @param \PHP_CodeSniffer_File $phpcsFile The file being scanned.
     * @param array                 $tokens    Token stack.
     * @param int                   $stackPtr  The position of the current token in
     *                                         the stack.
     *
     * @return string
     */
    private function getBinaryInteger(File $phpcsFile, $tokens, $stackPtr)
    {
        $length = 2; // PHP < 5.4 T_LNUMBER + T_STRING.

        if ($this->isLowPHPVersion === false) {
            $i = $stackPtr;
            while ($tokens[$i]['code'] === \T_LNUMBER) {
                $i++;
            }
            $length = ($i - $stackPtr);
        }

        return $phpcsFile->getTokensAsString($stackPtr, $length);
    }

    /**
     * Is the current token an invalid octal integer ?
     *
     * @param array $tokens   Token stack.
     * @param int   $stackPtr The current position in the token stack.
     *
     * @return bool
     */
    private function isInvalidOctalInteger($tokens, $stackPtr)
    {
        $token = $tokens[$stackPtr];

        if ($token['code'] === \T_LNUMBER && preg_match('`^0[0-7]*[8-9]+[0-9]*$`D', $token['content']) === 1) {
            return true;
        }

        return false;
    }

    /**
     * Is the current token a hexidecimal numeric string ?
     *
     * @param array $tokens   Token stack.
     * @param int   $stackPtr The current position in the token stack.
     *
     * @return bool
     */
    private function isHexidecimalNumericString($tokens, $stackPtr)
    {
        $token = $tokens[$stackPtr];

        if ($token['code'] === \T_CONSTANT_ENCAPSED_STRING && preg_match('`^0x[a-f0-9]+$`iD', $this->stripQuotes($token['content'])) === 1) {
            return true;
        }

        return false;
    }
}
