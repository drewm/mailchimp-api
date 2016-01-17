<?php
 
use \DrewM\MailChimp\MailChimp;
 
class MailChimpTest extends PHPUnit_Framework_TestCase 
{

	public function setUp()
	{
		$env_file_path = __DIR__.'/../';
		
		if (file_exists($env_file_path.'.env')) {
			$dotenv = new Dotenv\Dotenv($env_file_path);
			$dotenv->load();	
		}
		
	}

	public function testTestEnvironment()
	{
		$MC_API_KEY = getenv('MC_API_KEY');
		$this->assertNotEmpty($MC_API_KEY, 'No environment variables! Copy .env.example -> .env and fill out your MailChimp account details.');
	}

	public function testInstantiation()
	{
		$MC_API_KEY = getenv('MC_API_KEY');
		if (!$MC_API_KEY) $this->markTestSkipped('No API key in ENV');
		$MailChimp = new MailChimp($MC_API_KEY);
		$this->assertInstanceOf('\DrewM\MailChimp\MailChimp', $MailChimp);
	}

	public function testSubscriberHash()
	{
		$MC_API_KEY = getenv('MC_API_KEY');
		if (!$MC_API_KEY) $this->markTestSkipped('No API key in ENV');
		$MailChimp = new MailChimp($MC_API_KEY);

		$email    = 'Foo@Example.Com';
		$expected = md5(strtolower($email));
		$result   = $MailChimp->subscriberHash($email);

		$this->assertEquals($expected, $result);
	}

}