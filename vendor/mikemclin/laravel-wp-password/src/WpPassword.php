<?php namespace MikeMcLin\WpPassword;

use Hautelook\Phpass\PasswordHash;
use MikeMcLin\WpPassword\Contracts\WpPassword as WpPasswordContract;

class WpPassword implements WpPasswordContract
{

    /**
     * @var \Hautelook\Phpass\PasswordHash
     */
    protected $wp_hasher;

    /**
     * @param \Hautelook\Phpass\PasswordHash $wp_hasher
     */
    function __construct(PasswordHash $wp_hasher)
    {
        $this->wp_hasher = $wp_hasher;
    }

    /**
     * Create a hash (encrypt) of a plain text password.
     *
     * For integration with other applications, this function can be overwritten to
     * instead use the other package password checking algorithm.
     *
     * @uses PasswordHash::HashPassword
     *
     * @param string $password Plain text user password to hash
     *
     * @return string The hash string of the password
     */
    public function make($password)
    {
        return $this->wp_hasher->HashPassword(trim($password));
    }

    /**
     * Checks the plaintext password against the encrypted Password.
     *
     * Maintains compatibility between old version and the new cookie authentication
     * protocol using PHPass library. The $hash parameter is the encrypted password
     * and the function compares the plain text password when encrypted similarly
     * against the already encrypted password to see if they match.
     *
     * @uses PasswordHash::CheckPassword
     *
     * @param string $password Plaintext user's password
     * @param string $hash     Hash of the user's password to check against.
     *
     * @return bool False, if the $password does not match the hashed password
     */
    public function check($password, $hash)
    {
        // If the hash is still md5...
        if (strlen($hash) <= 32) {
            return ($hash == md5($password));
        }

        // If the stored hash is longer than an MD5, presume the
        // new style phpass portable hash.
        return $this->wp_hasher->CheckPassword($password, $hash);
    }

}