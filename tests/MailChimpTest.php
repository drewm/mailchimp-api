<?php

namespace DrewM\MailChimp\Tests;

use DrewM\MailChimp\Exception\MailChimpException;
use DrewM\MailChimp\MailChimp;
use PHPUnit\Framework\TestCase;

class MailChimpTest extends TestCase
{
    /**
     * @throws MailChimpException
     */
    public function testInvalidAPIKey()
    {
        $this->expectException(MailChimpException::class);
        new MailChimp('abc');
    }

    public function testTestEnvironment()
    {
        $MC_API_KEY = \getenv('MC_API_KEY');
        $this->assertNotEmpty($MC_API_KEY, 'No environment variables! Copy .env.example -> .env and fill out your MailChimp account details.');
    }

    /**
     * @throws MailChimpException
     */
    public function testInstantiation()
    {
        $MC_API_KEY = \getenv('MC_API_KEY');
        if (!$MC_API_KEY) {
            $this->markTestSkipped('No API key in ENV');
        }

        $MailChimp = new MailChimp($MC_API_KEY, 'https://api.mailchimp.com/3.0');
        $this->assertInstanceOf(MailChimp::class, $MailChimp);

        $apiEndPoint = 'https://api.mailchimp.com/3.0';
        $this->assertSame($apiEndPoint, $MailChimp->getApiEndpoint());

        $this->assertFalse($MailChimp->success());

        $this->assertFalse($MailChimp->getLastError());

        $this->assertSame(['headers' => null, 'body' => null], $MailChimp->getLastResponse());

        $this->assertSame([], $MailChimp->getLastRequest());
    }

    /**
     * @throws MailChimpException
     */
    public function testSubscriberHash()
    {
        $MC_API_KEY = \getenv('MC_API_KEY');
        if (!$MC_API_KEY) {
            $this->markTestSkipped('No API key in ENV');
        }

        $MailChimp = new MailChimp($MC_API_KEY);

        $email = 'Foo@Example.Com';
        $expected = \md5(\mb_strtolower($email));
        $result = $MailChimp->subscriberHash($email);

        $this->assertSame($expected, $result);
    }
}
