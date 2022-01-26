<?php

use MikeMcLin\WpPassword\WpPassword;

class WpPasswordTest extends PHPUnit_Framework_TestCase
{

    protected $wp_password;
    protected $password_hash_mock;

    public function setUp()
    {
        $this->password_hash_mock = Mockery::mock('Hautelook\Phpass\PasswordHash');
        $this->wp_password = new WpPassword($this->password_hash_mock);
    }

    public function tearDown()
    {
        Mockery::close();
    }

    public function test_service_implements_interface()
    {
        $this->assertInstanceOf('MikeMcLin\WpPassword\Contracts\WpPassword', $this->wp_password);
    }

    public function test_make_method_calls_HashPassword_and_returns_result()
    {
        $this->password_hash_mock->shouldReceive('HashPassword')
            ->once()
            ->withArgs(array('foo'))
            ->andReturn('bar');

        $response = $this->wp_password->make('foo');

        $this->assertEquals('bar', $response);
    }

    public function test_make_method_trims_password_before_hashing()
    {
        $this->password_hash_mock->shouldReceive('HashPassword')
            ->once()
            ->withArgs(array('foo'));

        $this->wp_password->make('           foo     ');
    }

    public function test_check_method_calls_CheckPassword_and_returns_result()
    {
        $this->password_hash_mock->shouldReceive('CheckPassword')
            ->once()
            ->withArgs(array('plain-text-password', 'hashed-password-longer-than-32-chars'))
            ->andReturn('foo');

        $response = $this->wp_password->check('plain-text-password', 'hashed-password-longer-than-32-chars');

        $this->assertEquals('foo', $response);
    }

    public function test_check_method_detects_md5_passwords()
    {
        $password = 'plain-text-password';

        $validates = $this->wp_password->check('wrong-password', md5($password));
        $this->assertFalse($validates, 'incorrect password and md5 hash password should not pass check');

        $validates = $this->wp_password->check($password, md5($password));
        $this->assertTrue($validates, 'password and md5 hash password should pass check');

    }

}
