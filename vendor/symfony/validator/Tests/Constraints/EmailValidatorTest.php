<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Validator\Tests\Constraints;

use Symfony\Bridge\PhpUnit\DnsMock;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\EmailValidator;
use Symfony\Component\Validator\Validation;

/**
 * @group dns-sensitive
 */
class EmailValidatorTest extends AbstractConstraintValidatorTest
{
    protected function getApiVersion()
    {
        return Validation::API_VERSION_2_5;
    }

    protected function createValidator()
    {
        return new EmailValidator(false);
    }

    public function testNullIsValid()
    {
        $this->validator->validate(null, new Email());

        $this->assertNoViolation();
    }

    public function testEmptyStringIsValid()
    {
        $this->validator->validate('', new Email());

        $this->assertNoViolation();
    }

    /**
     * @expectedException \Symfony\Component\Validator\Exception\UnexpectedTypeException
     */
    public function testExpectsStringCompatibleType()
    {
        $this->validator->validate(new \stdClass(), new Email());
    }

    /**
     * @dataProvider getValidEmails
     */
    public function testValidEmails($email)
    {
        $this->validator->validate($email, new Email());

        $this->assertNoViolation();
    }

    public function getValidEmails()
    {
        return array(
            array('fabien@symfony.com'),
            array('example@example.co.uk'),
            array('fabien_potencier@example.fr'),
        );
    }

    /**
     * @dataProvider getInvalidEmails
     */
    public function testInvalidEmails($email)
    {
        $constraint = new Email(array(
            'message' => 'myMessage',
        ));

        $this->validator->validate($email, $constraint);

        $this->buildViolation('myMessage')
            ->setParameter('{{ value }}', '"'.$email.'"')
            ->setCode(Email::INVALID_FORMAT_ERROR)
            ->assertRaised();
    }

    public function getInvalidEmails()
    {
        return array(
            array('example'),
            array('example@'),
            array('example@localhost'),
            array('foo@example.com bar'),
        );
    }

    public function testStrict()
    {
        $constraint = new Email(array('strict' => true));

        $this->validator->validate('example@localhost', $constraint);

        $this->assertNoViolation();
    }

    /**
     * @dataProvider getDnsChecks
     * @requires function Symfony\Bridge\PhpUnit\DnsMock::withMockedHosts
     */
    public function testDnsChecks($type, $violation)
    {
        DnsMock::withMockedHosts(array('example.com' => array(array('type' => $violation ? false : $type))));

        $constraint = new Email(array(
            'message' => 'myMessage',
            'MX' === $type ? 'checkMX' : 'checkHost' => true,
        ));

        $this->validator->validate('foo@example.com', $constraint);

        if (!$violation) {
            $this->assertNoViolation();
        } else {
            $this->buildViolation('myMessage')
                ->setParameter('{{ value }}', '"foo@example.com"')
                ->setCode($violation)
                ->assertRaised();
        }
    }

    public function getDnsChecks()
    {
        return array(
            array('MX', false),
            array('MX', Email::MX_CHECK_FAILED_ERROR),
            array('A', false),
            array('A', Email::HOST_CHECK_FAILED_ERROR),
            array('AAAA', false),
            array('AAAA', Email::HOST_CHECK_FAILED_ERROR),
        );
    }

    /**
     * @requires function Symfony\Bridge\PhpUnit\DnsMock::withMockedHosts
     */
    public function testHostnameIsProperlyParsed()
    {
        DnsMock::withMockedHosts(array('baz.com' => array(array('type' => 'MX'))));

        $this->validator->validate(
            '"foo@bar"@baz.com',
            new Email(array('checkMX' => true))
        );

        $this->assertNoViolation();
    }

    /**
     * @dataProvider provideCheckTypes
     */
    public function testEmptyHostIsNotValid($checkType, $violation)
    {
        $this->validator->validate(
            'foo@bar.fr@',
            new Email(array(
                'message' => 'myMessage',
                $checkType => true,
            ))
        );

        $this
            ->buildViolation('myMessage')
            ->setParameter('{{ value }}', '"foo@bar.fr@"')
            ->setCode($violation)
            ->assertRaised();
    }

    public function provideCheckTypes()
    {
        return array(
            array('checkMX', Email::MX_CHECK_FAILED_ERROR),
            array('checkHost', Email::HOST_CHECK_FAILED_ERROR),
        );
    }
}
