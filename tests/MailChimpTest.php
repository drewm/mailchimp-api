<?php

use \DrewM\MailChimp\MailChimp;
use PHPUnit\Framework\TestCase;

class MailChimpTest extends TestCase
{

    public function setUp()
    {
        $env_file_path = __DIR__ . '/../';

        if (file_exists($env_file_path . '.env')) {
            $dotenv = new Dotenv\Dotenv($env_file_path);
            $dotenv->load();
        }

    }

    public function testInvalidAPIKey()
    {
        $this->expectException('\Exception');
        $MailChimp = new MailChimp('abc');
    }

    public function testTestEnvironment()
    {
        $MC_API_KEY = getenv('MC_API_KEY');
        $message    = 'No environment variables! Copy .env.example -> .env and fill out your MailChimp account details.';
        $this->assertNotEmpty($MC_API_KEY, $message);
    }

    public function testInstantiation()
    {
        $MC_API_KEY = getenv('MC_API_KEY');

        if (!$MC_API_KEY) {
            $this->markTestSkipped('No API key in ENV');
        }

        $MailChimp = new MailChimp($MC_API_KEY);
        $this->assertInstanceOf('\DrewM\MailChimp\MailChimp', $MailChimp);
    }

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
