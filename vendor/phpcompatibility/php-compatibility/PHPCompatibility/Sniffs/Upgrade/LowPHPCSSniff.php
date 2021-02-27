<?php
/**
 * \PHPCompatibility\Sniffs\Upgrade\LowPHPCSSniff.
 *
 * @category Upgrade
 * @package  PHPCompatibility
 * @author   Juliette Reinders Folmer <phpcompatibility_nospam@adviesenzo.nl>
 */

namespace PHPCompatibility\Sniffs\Upgrade;

use PHPCompatibility\Sniff;
use PHPCompatibility\PHPCSHelper;
use PHP_CodeSniffer_File as File;

/**
 * \PHPCompatibility\Sniffs\Upgrade\LowPHPCSSniff.
 *
 * Add a notification for users of low PHPCS versions.
 *
 * Originally PHPCompatibility supported PHPCS 1.5.x, 2.x and since PHPCompatibility 8.0.0, 3.x.
 * Support for PHPCS < 2.3.0 has been dropped in PHPCompatibility 9.0.0.
 *
 * The standard will - up to a point - still work for users of lower
 * PHPCS versions, but will give less accurate results and may throw
 * notices and warnings (or even fatal out).
 *
 * This sniff adds an explicit error/warning for users of the standard
 * using a PHPCS version below the recommended version.
 *
 * @category Upgrade
 * @package  PHPCompatibility
 * @author   Juliette Reinders Folmer <phpcompatibility_nospam@adviesenzo.nl>
 */
class LowPHPCSSniff extends Sniff
{
    /**
     * The minimum supported PHPCS version.
     *
     * Users on PHPCS versions below this will see an ERROR message.
     *
     * @var string
     */
    protected $minSupportedVersion = '2.3.0';

    /**
     * The minimum recommended PHPCS version.
     *
     * Users on PHPCS versions below this will see a WARNING.
     *
     * @var string
     */
    protected $minRecommendedVersion = '2.6.0';

    /**
     * Keep track of whether this sniff needs to actually run.
     *
     * This will be set to `false` when either a high enough PHPCS
     * version is detected or once the error/warning has been thrown,
     * to make sure that the notice will only be thrown once per run.
     *
     * @var bool
     */
    private $examine = true;


    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register()
    {
        return array(
            \T_OPEN_TAG,
        );
    }

    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param \PHP_CodeSniffer_File $phpcsFile The file being scanned.
     * @param int                   $stackPtr  The position of the current token in the
     *                                         stack passed in $tokens.
     *
     * @return int|void Integer stack pointer to skip forward or void to continue
     *                  normal file processing.
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        // Don't do anything if the warning has already been thrown or is not necessary.
        if ($this->examine === false) {
            return ($phpcsFile->numTokens + 1);
        }

        $phpcsVersion = PHPCSHelper::getVersion();

        // Don't do anything if the PHPCS version used is above the minimum recommended version.
        if (version_compare($phpcsVersion, $this->minRecommendedVersion, '>=')) {
            $this->examine = false;
            return ($phpcsFile->numTokens + 1);
        }

        if (version_compare($phpcsVersion, $this->minSupportedVersion, '<')) {
            $isError      = true;
            $message      = "IMPORTANT: Please be advised that the minimum PHP_CodeSniffer version the PHPCompatibility standard supports is %s. You are currently using PHP_CodeSniffer %s. Please upgrade your PHP_CodeSniffer installation. The recommended version of PHP_CodeSniffer for PHPCompatibility is %s or higher.";
            $errorCode    = 'Unsupported_' . $this->stringToErrorCode($this->minSupportedVersion);
            $replacements = array(
                $this->minSupportedVersion,
                $phpcsVersion,
                $this->minRecommendedVersion,
                $errorCode,
            );
        } else {
            $isError      = false;
            $message      = "IMPORTANT: Please be advised that for the most reliable PHPCompatibility results, PHP_CodeSniffer %s or higher should be used. Support for lower versions will be dropped in the foreseeable future. You are currently using PHP_CodeSniffer %s. Please upgrade your PHP_CodeSniffer installation to version %s or higher.";
            $errorCode    = 'BelowRecommended_' . $this->stringToErrorCode($this->minRecommendedVersion);
            $replacements = array(
                $this->minRecommendedVersion,
                $phpcsVersion,
                $this->minRecommendedVersion,
                $errorCode,
            );
        }

        /*
         * Figure out the report width to determine how long the delimiter lines should be.
         *
         * This is not an exact calculation as there are a number of unknowns at the time the
         * notice is thrown (whether there are other notices for the file, whether those are
         * warnings or errors, whether there are auto-fixable issues etc).
         *
         * In other words, this is just an approximation to get a reasonably stable and
         * readable message layout format.
         *
         * {@internal
         * PHPCS has had some changes as to how the messages display over the years.
         * Most significantly in 2.4.0 it was attempted to solve an issue with messages
         * containing new lines. Unfortunately, that solution is buggy.
         * An improved version has been pulled upstream and will hopefully make it
         * into PHPCS 3.3.1/3.4.0.
         *
         * Anyway, this means that instead of new lines, delimiter lines will be used to improved
         * the readability of the (long) message.
         *
         * Also, as of PHPCS 2.2.0, the report width when using the `-s` option is 8 wider than
         * it should be. A patch for that is included in the same upstream PR.
         *
         * If/when the upstream PR has been merged and the minimum supported/recommended version
         * of PHPCompatibility would go beyond that, the below code should be adjusted.}}
         */
        $reportWidth = PHPCSHelper::getCommandLineData($phpcsFile, 'reportWidth');
        $showSources = PHPCSHelper::getCommandLineData($phpcsFile, 'showSources');
        if ($showSources === true && version_compare($phpcsVersion, '2.3.0', '>=')) {
            $reportWidth += 6;
        }

        $messageWidth  = ($reportWidth - 15); // 15 is length of " # | WARNING | ".
        $delimiterLine = str_repeat('-', ($messageWidth));
        $disableNotice = 'To disable this notice, add --exclude=PHPCompatibility.Upgrade.LowPHPCS to your command or add <exclude name="PHPCompatibility.Upgrade.LowPHPCS.%s"/> to your custom ruleset. ';
        $thankYou      = 'Thank you for using PHPCompatibility!';

        $message .= ' ' . $delimiterLine;
        $message .= ' ' . $disableNotice;
        $message .= ' ' . $delimiterLine;
        $message .= ' ' . $thankYou;

        $this->addMessage($phpcsFile, $message, 0, $isError, $errorCode, $replacements);

        $this->examine = false;
    }
}
