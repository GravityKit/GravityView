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

namespace Composer\IO;

use Composer\Config;
use Composer\Util\ProcessExecutor;
use Psr\Log\LogLevel;

abstract class BaseIO implements IOInterface
{
    /** @var array<string, array{username: string, password: string}> */
    protected $authentications = array();

    /**
     * {@inheritDoc}
     */
    public function getAuthentications()
    {
        return $this->authentications;
    }

    /**
     * {@inheritDoc}
     */
    public function resetAuthentications()
    {
        $this->authentications = array();
    }

    /**
     * {@inheritDoc}
     */
    public function hasAuthentication($repositoryName)
    {
        return isset($this->authentications[$repositoryName]);
    }

    /**
     * {@inheritDoc}
     */
    public function getAuthentication($repositoryName)
    {
        if (isset($this->authentications[$repositoryName])) {
            return $this->authentications[$repositoryName];
        }

        return array('username' => null, 'password' => null);
    }

    /**
     * {@inheritDoc}
     */
    public function setAuthentication($repositoryName, $username, $password = null)
    {
        $this->authentications[$repositoryName] = array('username' => $username, 'password' => $password);
    }

    /**
     * {@inheritDoc}
     */
    public function writeRaw($messages, $newline = true, $verbosity = self::NORMAL)
    {
        $this->write($messages, $newline, $verbosity);
    }

    /**
     * {@inheritDoc}
     */
    public function writeErrorRaw($messages, $newline = true, $verbosity = self::NORMAL)
    {
        $this->writeError($messages, $newline, $verbosity);
    }

    /**
     * Check for overwrite and set the authentication information for the repository.
     *
     * @param string $repositoryName The unique name of repository
     * @param string $username       The username
     * @param string $password       The password
     */
    protected function checkAndSetAuthentication($repositoryName, $username, $password = null)
    {
        if ($this->hasAuthentication($repositoryName)) {
            $auth = $this->getAuthentication($repositoryName);
            if ($auth['username'] === $username && $auth['password'] === $password) {
                return;
            }

            $this->writeError(
                sprintf(
                    "<warning>Warning: You should avoid overwriting already defined auth settings for %s.</warning>",
                    $repositoryName
                )
            );
        }
        $this->setAuthentication($repositoryName, $username, $password);
    }

    /**
     * {@inheritDoc}
     */
    public function loadConfiguration(Config $config)
    {
        $bitbucketOauth = $config->get('bitbucket-oauth') ?: array();
        $githubOauth = $config->get('github-oauth') ?: array();
        $gitlabOauth = $config->get('gitlab-oauth') ?: array();
        $gitlabToken = $config->get('gitlab-token') ?: array();
        $httpBasic = $config->get('http-basic') ?: array();
        $bearerToken = $config->get('bearer') ?: array();

        // reload oauth tokens from config if available

        foreach ($bitbucketOauth as $domain => $cred) {
            $this->checkAndSetAuthentication($domain, $cred['consumer-key'], $cred['consumer-secret']);
        }

        foreach ($githubOauth as $domain => $token) {
            // allowed chars for GH tokens are from https://github.blog/changelog/2021-03-04-authentication-token-format-updates/
            // plus dots which were at some point used for GH app integration tokens
            if (!preg_match('{^[.A-Za-z0-9_]+$}', $token)) {
                throw new \UnexpectedValueException('Your github oauth token for '.$domain.' contains invalid characters: "'.$token.'"');
            }
            $this->checkAndSetAuthentication($domain, $token, 'x-oauth-basic');
        }

        foreach ($gitlabOauth as $domain => $token) {
            $this->checkAndSetAuthentication($domain, $token, 'oauth2');
        }

        foreach ($gitlabToken as $domain => $token) {
            $username = is_array($token) && array_key_exists("username", $token) ? $token["username"] : $token;
            $password = is_array($token) && array_key_exists("token", $token) ? $token["token"] : 'private-token';
            $this->checkAndSetAuthentication($domain, $username, $password);
        }

        // reload http basic credentials from config if available
        foreach ($httpBasic as $domain => $cred) {
            $this->checkAndSetAuthentication($domain, $cred['username'], $cred['password']);
        }

        foreach ($bearerToken as $domain => $token) {
            $this->checkAndSetAuthentication($domain, $token, 'bearer');
        }

        // setup process timeout
        ProcessExecutor::setTimeout((int) $config->get('process-timeout'));
    }

    /**
     * {@inheritDoc}
     */
    public function emergency($message, array $context = array())
    {
        $this->log(LogLevel::EMERGENCY, $message, $context);
    }

    /**
     * {@inheritDoc}
     */
    public function alert($message, array $context = array())
    {
        $this->log(LogLevel::ALERT, $message, $context);
    }

    /**
     * {@inheritDoc}
     */
    public function critical($message, array $context = array())
    {
        $this->log(LogLevel::CRITICAL, $message, $context);
    }

    /**
     * {@inheritDoc}
     */
    public function error($message, array $context = array())
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }

    /**
     * {@inheritDoc}
     */
    public function warning($message, array $context = array())
    {
        $this->log(LogLevel::WARNING, $message, $context);
    }

    /**
     * {@inheritDoc}
     */
    public function notice($message, array $context = array())
    {
        $this->log(LogLevel::NOTICE, $message, $context);
    }

    /**
     * {@inheritDoc}
     */
    public function info($message, array $context = array())
    {
        $this->log(LogLevel::INFO, $message, $context);
    }

    /**
     * {@inheritDoc}
     */
    public function debug($message, array $context = array())
    {
        $this->log(LogLevel::DEBUG, $message, $context);
    }

    /**
     * {@inheritDoc}
     */
    public function log($level, $message, array $context = array())
    {
        if (in_array($level, array(LogLevel::EMERGENCY, LogLevel::ALERT, LogLevel::CRITICAL, LogLevel::ERROR))) {
            $this->writeError('<error>'.$message.'</error>');
        } elseif ($level === LogLevel::WARNING) {
            $this->writeError('<warning>'.$message.'</warning>');
        } elseif ($level === LogLevel::NOTICE) {
            $this->writeError('<info>'.$message.'</info>', true, self::VERBOSE);
        } elseif ($level === LogLevel::INFO) {
            $this->writeError('<info>'.$message.'</info>', true, self::VERY_VERBOSE);
        } else {
            $this->writeError($message, true, self::DEBUG);
        }
    }
}
