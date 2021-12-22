<?php

/*
 * This file is part of Composer.
 *
 * (c) Nils Adermann <naderman@naderman.de>
 *     Jordi Boggiano <j.boggiano@seld.be>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Composer\Util;

use Composer\Factory;
use Composer\IO\IOInterface;
use Composer\Config;
use Composer\Downloader\TransportException;

/**
 * @author Jordi Boggiano <j.boggiano@seld.be>
 */
class GitHub
{
    /** @var IOInterface */
    protected $io;
    /** @var Config */
    protected $config;
    /** @var ProcessExecutor */
    protected $process;
    /** @var HttpDownloader */
    protected $httpDownloader;

    /**
     * Constructor.
     *
     * @param IOInterface     $io             The IO instance
     * @param Config          $config         The composer configuration
     * @param ProcessExecutor $process        Process instance, injectable for mocking
     * @param HttpDownloader  $httpDownloader Remote Filesystem, injectable for mocking
     */
    public function __construct(IOInterface $io, Config $config, ProcessExecutor $process = null, HttpDownloader $httpDownloader = null)
    {
        $this->io = $io;
        $this->config = $config;
        $this->process = $process ?: new ProcessExecutor($io);
        $this->httpDownloader = $httpDownloader ?: Factory::createHttpDownloader($this->io, $config);
    }

    /**
     * Attempts to authorize a GitHub domain via OAuth
     *
     * @param  string $originUrl The host this GitHub instance is located at
     * @return bool   true on success
     */
    public function authorizeOAuth($originUrl)
    {
        if (!in_array($originUrl, $this->config->get('github-domains'))) {
            return false;
        }

        // if available use token from git config
        if (0 === $this->process->execute('git config github.accesstoken', $output)) {
            $this->io->setAuthentication($originUrl, trim($output), 'x-oauth-basic');

            return true;
        }

        return false;
    }

    /**
     * Authorizes a GitHub domain interactively via OAuth
     *
     * @param  string                        $originUrl The host this GitHub instance is located at
     * @param  string                        $message   The reason this authorization is required
     * @throws \RuntimeException
     * @throws TransportException|\Exception
     * @return bool                          true on success
     */
    public function authorizeOAuthInteractively($originUrl, $message = null)
    {
        if ($message) {
            $this->io->writeError($message);
        }

        $note = 'Composer';
        if ($this->config->get('github-expose-hostname') === true && 0 === $this->process->execute('hostname', $output)) {
            $note .= ' on ' . trim($output);
        }
        $note .= ' ' . date('Y-m-d Hi');

        $url = 'https://'.$originUrl.'/settings/tokens/new?scopes=&description=' . str_replace('%20', '+', rawurlencode($note));
        $this->io->writeError(sprintf('When working with _public_ GitHub repositories only, head to %s to retrieve a token.', $url));
        $this->io->writeError('This token will have read-only permission for public information only.');

        $url = 'https://'.$originUrl.'/settings/tokens/new?scopes=repo&description=' . str_replace('%20', '+', rawurlencode($note));
        $this->io->writeError(sprintf('When you need to access _private_ GitHub repositories as well, go to %s', $url));
        $this->io->writeError('Note that such tokens have broad read/write permissions on your behalf, even if not needed by Composer.');
        $this->io->writeError(sprintf('Tokens will be stored in plain text in "%s" for future use by Composer.', $this->config->getAuthConfigSource()->getName()));
        $this->io->writeError('For additional information, check https://getcomposer.org/doc/articles/authentication-for-private-packages.md#github-oauth');

        $token = trim($this->io->askAndHideAnswer('Token (hidden): '));

        if (!$token) {
            $this->io->writeError('<warning>No token given, aborting.</warning>');
            $this->io->writeError('You can also add it manually later by using "composer config --global --auth github-oauth.github.com <token>"');

            return false;
        }

        $this->io->setAuthentication($originUrl, $token, 'x-oauth-basic');

        try {
            $apiUrl = ('github.com' === $originUrl) ? 'api.github.com/' : $originUrl . '/api/v3/';

            $this->httpDownloader->get('https://'. $apiUrl, array(
                'retry-auth-failure' => false,
            ));
        } catch (TransportException $e) {
            if (in_array($e->getCode(), array(403, 401))) {
                $this->io->writeError('<error>Invalid token provided.</error>');
                $this->io->writeError('You can also add it manually later by using "composer config --global --auth github-oauth.github.com <token>"');

                return false;
            }

            throw $e;
        }

        // store value in user config
        $this->config->getConfigSource()->removeConfigSetting('github-oauth.'.$originUrl);
        $this->config->getAuthConfigSource()->addConfigSetting('github-oauth.'.$originUrl, $token);

        $this->io->writeError('<info>Token stored successfully.</info>');

        return true;
    }

    /**
     * Extract rate limit from response.
     *
     * @param string[] $headers Headers from Composer\Downloader\TransportException.
     *
     * @return array{limit: int|'?', reset: string}
     */
    public function getRateLimit(array $headers)
    {
        $rateLimit = array(
            'limit' => '?',
            'reset' => '?',
        );

        foreach ($headers as $header) {
            $header = trim($header);
            if (false === strpos($header, 'X-RateLimit-')) {
                continue;
            }
            list($type, $value) = explode(':', $header, 2);
            switch ($type) {
                case 'X-RateLimit-Limit':
                    $rateLimit['limit'] = (int) trim($value);
                    break;
                case 'X-RateLimit-Reset':
                    $rateLimit['reset'] = date('Y-m-d H:i:s', (int) trim($value));
                    break;
            }
        }

        return $rateLimit;
    }

    /**
     * Finds whether a request failed due to rate limiting
     *
     * @param string[] $headers Headers from Composer\Downloader\TransportException.
     *
     * @return bool
     */
    public function isRateLimited(array $headers)
    {
        foreach ($headers as $header) {
            if (preg_match('{^X-RateLimit-Remaining: *0$}i', trim($header))) {
                return true;
            }
        }

        return false;
    }
}
