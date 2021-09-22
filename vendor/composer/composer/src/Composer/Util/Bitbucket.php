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
 * @author Paul Wenke <wenke.paul@gmail.com>
 */
class Bitbucket
{
    /** @var IOInterface */
    private $io;
    /** @var Config */
    private $config;
    /** @var ProcessExecutor */
    private $process;
    /** @var HttpDownloader */
    private $httpDownloader;
    /** @var array{access_token: string, expires_in?: int}|null */
    private $token = null;
    /** @var int|null */
    private $time;

    const OAUTH2_ACCESS_TOKEN_URL = 'https://bitbucket.org/site/oauth2/access_token';

    /**
     * Constructor.
     *
     * @param IOInterface     $io             The IO instance
     * @param Config          $config         The composer configuration
     * @param ProcessExecutor $process        Process instance, injectable for mocking
     * @param HttpDownloader  $httpDownloader Remote Filesystem, injectable for mocking
     * @param int             $time           Timestamp, injectable for mocking
     */
    public function __construct(IOInterface $io, Config $config, ProcessExecutor $process = null, HttpDownloader $httpDownloader = null, $time = null)
    {
        $this->io = $io;
        $this->config = $config;
        $this->process = $process ?: new ProcessExecutor($io);
        $this->httpDownloader = $httpDownloader ?: Factory::createHttpDownloader($this->io, $config);
        $this->time = $time;
    }

    /**
     * @return string
     */
    public function getToken()
    {
        if (!isset($this->token['access_token'])) {
            return '';
        }

        return $this->token['access_token'];
    }

    /**
     * Attempts to authorize a Bitbucket domain via OAuth
     *
     * @param  string $originUrl The host this Bitbucket instance is located at
     * @return bool   true on success
     */
    public function authorizeOAuth($originUrl)
    {
        if ($originUrl !== 'bitbucket.org') {
            return false;
        }

        // if available use token from git config
        if (0 === $this->process->execute('git config bitbucket.accesstoken', $output)) {
            $this->io->setAuthentication($originUrl, 'x-token-auth', trim($output));

            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    private function requestAccessToken()
    {
        try {
            $response = $this->httpDownloader->get(self::OAUTH2_ACCESS_TOKEN_URL, array(
                'retry-auth-failure' => false,
                'http' => array(
                    'method' => 'POST',
                    'content' => 'grant_type=client_credentials',
                ),
            ));

            $token = $response->decodeJson();
            if (!isset($token['expires_in']) || !isset($token['access_token'])) {
                throw new \LogicException('Expected a token configured with expires_in and access_token present, got '.json_encode($token));
            }

            $this->token = $token;
        } catch (TransportException $e) {
            if ($e->getCode() === 400) {
                $this->io->writeError('<error>Invalid OAuth consumer provided.</error>');
                $this->io->writeError('This can have two reasons:');
                $this->io->writeError('1. You are authenticating with a bitbucket username/password combination');
                $this->io->writeError('2. You are using an OAuth consumer, but didn\'t configure a (dummy) callback url');

                return false;
            }
            if (in_array($e->getCode(), array(403, 401))) {
                $this->io->writeError('<error>Invalid OAuth consumer provided.</error>');
                $this->io->writeError('You can also add it manually later by using "composer config --global --auth bitbucket-oauth.bitbucket.org <consumer-key> <consumer-secret>"');

                return false;
            }

            throw $e;
        }

        return true;
    }

    /**
     * Authorizes a Bitbucket domain interactively via OAuth
     *
     * @param  string                        $originUrl The host this Bitbucket instance is located at
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

        $url = 'https://support.atlassian.com/bitbucket-cloud/docs/use-oauth-on-bitbucket-cloud/';
        $this->io->writeError(sprintf('Follow the instructions on %s', $url));
        $this->io->writeError(sprintf('to create a consumer. It will be stored in "%s" for future use by Composer.', $this->config->getAuthConfigSource()->getName()));
        $this->io->writeError('Ensure you enter a "Callback URL" (http://example.com is fine) or it will not be possible to create an Access Token (this callback url will not be used by composer)');

        $consumerKey = trim((string) $this->io->askAndHideAnswer('Consumer Key (hidden): '));

        if (!$consumerKey) {
            $this->io->writeError('<warning>No consumer key given, aborting.</warning>');
            $this->io->writeError('You can also add it manually later by using "composer config --global --auth bitbucket-oauth.bitbucket.org <consumer-key> <consumer-secret>"');

            return false;
        }

        $consumerSecret = trim((string) $this->io->askAndHideAnswer('Consumer Secret (hidden): '));

        if (!$consumerSecret) {
            $this->io->writeError('<warning>No consumer secret given, aborting.</warning>');
            $this->io->writeError('You can also add it manually later by using "composer config --global --auth bitbucket-oauth.bitbucket.org <consumer-key> <consumer-secret>"');

            return false;
        }

        $this->io->setAuthentication($originUrl, $consumerKey, $consumerSecret);

        if (!$this->requestAccessToken()) {
            return false;
        }

        // store value in user config
        $this->storeInAuthConfig($originUrl, $consumerKey, $consumerSecret);

        // Remove conflicting basic auth credentials (if available)
        $this->config->getAuthConfigSource()->removeConfigSetting('http-basic.' . $originUrl);

        $this->io->writeError('<info>Consumer stored successfully.</info>');

        return true;
    }

    /**
     * Retrieves an access token from Bitbucket.
     *
     * @param  string $originUrl
     * @param  string $consumerKey
     * @param  string $consumerSecret
     * @return string
     */
    public function requestToken($originUrl, $consumerKey, $consumerSecret)
    {
        if ($this->token !== null || $this->getTokenFromConfig($originUrl)) {
            return $this->token['access_token'];
        }

        $this->io->setAuthentication($originUrl, $consumerKey, $consumerSecret);
        if (!$this->requestAccessToken()) {
            return '';
        }

        $this->storeInAuthConfig($originUrl, $consumerKey, $consumerSecret);

        if (!isset($this->token['access_token'])) {
            throw new \LogicException('Failed to initialize token above');
        }

        // side effect above caused this, https://github.com/phpstan/phpstan/issues/5129
        // @phpstan-ignore-next-line
        return $this->token['access_token'];
    }

    /**
     * Store the new/updated credentials to the configuration
     * @param string $originUrl
     * @param string $consumerKey
     * @param string $consumerSecret
     */
    private function storeInAuthConfig($originUrl, $consumerKey, $consumerSecret)
    {
        $this->config->getConfigSource()->removeConfigSetting('bitbucket-oauth.'.$originUrl);

        if (null === $this->token || !isset($this->token['expires_in'])) {
            throw new \LogicException('Expected a token configured with expires_in present, got '.json_encode($this->token));
        }

        $time = null === $this->time ? time() : $this->time;
        $consumer = array(
            "consumer-key" => $consumerKey,
            "consumer-secret" => $consumerSecret,
            "access-token" => $this->token['access_token'],
            "access-token-expiration" => $time + $this->token['expires_in'],
        );

        $this->config->getAuthConfigSource()->addConfigSetting('bitbucket-oauth.'.$originUrl, $consumer);
    }

    /**
     * @param  string $originUrl
     * @return bool
     */
    private function getTokenFromConfig($originUrl)
    {
        $authConfig = $this->config->get('bitbucket-oauth');

        if (
            !isset($authConfig[$originUrl]['access-token'], $authConfig[$originUrl]['access-token-expiration'])
            || time() > $authConfig[$originUrl]['access-token-expiration']
        ) {
            return false;
        }

        $this->token = array(
            'access_token' => $authConfig[$originUrl]['access-token'],
        );

        return true;
    }
}
