<?php

namespace DrewM\MailChimp\Tests;

use DrewM\MailChimp\MailChimp;
use PHPUnit\Framework\TestCase;

class MailChimpTest extends TestCase
{
    /**
     * @throws \Exception
     */
    public function testInvalidAPIKey()
    {
        $this->expectException('\Exception');
        new MailChimp('abc');
    }

    public function testTestEnvironment()
    {
        $MC_API_KEY = getenv('MC_API_KEY');
        $this->assertNotEmpty($MC_API_KEY, 'No environment variables! Copy .env.example -> .env and fill out your MailChimp account details.');
    }

    /**
     * @throws \Exception
     */
    public function testInstantiation()
    {
        $MC_API_KEY = getenv('MC_API_KEY');

        if (!$MC_API_KEY) {
            $this->markTestSkipped('No API key in ENV');
        }

        $MailChimp = new MailChimp($MC_API_KEY, 'https://api.mailchimp.com/3.0');
        $this->assertInstanceOf('\DrewM\MailChimp\MailChimp', $MailChimp);

        $this->assertSame('https://api.mailchimp.com/3.0', $MailChimp->getApiEndpoint());

        $this->assertFalse($MailChimp->success());

        $this->assertFalse($MailChimp->getLastError());

        $this->assertSame(array('headers' => null, 'body' => null), $MailChimp->getLastResponse());

        $this->assertSame(array(), $MailChimp->getLastRequest());
    }

    /**
     * @throws \Exception
     */
    public function testSubscriberHash()
    {
        $MC_API_KEY = getenv('MC_API_KEY');

        if (!$MC_API_KEY) {
            $this->markTestSkipped('No API key in ENV');
        }

        $MailChimp = new MailChimp($MC_API_KEY);

        $email    = 'Foo@Example.Com';
        $expected = md5(strtolower($email));
        $result   = $MailChimp->subscriberHash($email);

        $this->assertEquals($expected, $result);
    }

    public function testResponseState()
    {
        $MC_API_KEY = getenv('MC_API_KEY');

        if (!$MC_API_KEY) {
            $this->markTestSkipped('No API key in ENV');
        }

        $MailChimp = new MailChimp($MC_API_KEY);

        $MailChimp->get('lists');

        $this->assertTrue($MailChimp->success());
    }

    /* This test requires that your test list have:
     * a) a list
     * b) enough entries that the curl request will timeout after 1 second.
     * How many this is may depend on your network connection to the Mailchimp servers.
     */
    /*
    public function testRequestTimeout()
    {
        $this->markTestSkipped('CI server too fast to realistically test.');


        $MC_API_KEY = getenv('MC_API_KEY');

        if (!$MC_API_KEY) {
            $this->markTestSkipped('No API key in ENV');
        }

        $MailChimp = new MailChimp($MC_API_KEY);
        $result = $MailChimp->get('lists');
        $list_id = $result['lists'][0]['id'];

        $args = array( 'count' => 1000 );
        $timeout = 1;
        $result = $MailChimp->get("lists/$list_id/members", $args, $timeout );
        $this->assertFalse( $result );

        $error = $MailChimp->getLastError();
        $this->assertRegExp( '/Request timed out after 1.\d+ seconds/', $error );
    }
    */
}
