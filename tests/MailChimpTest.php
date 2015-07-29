<?php
 
use \DrewM\MailChimp\MailChimp;
 
class MailChimpTest extends PHPUnit_Framework_TestCase 
{

	public function setUp()
	{
		$dotenv = new Dotenv\Dotenv(__DIR__.'/../');
		$dotenv->load();
	}

	public function testTestEnvironment()
	{
		$this->assertNotEmpty(getenv('MC_API_KEY'), 'No environment variables! Copy .env.example -> .env and fill out your MailChimp account details.');
	}

	public function testInstantiation()
	{
		$MailChimp = new MailChimp(getenv('MC_API_KEY'));
		$this->assertInstanceOf('\DrewM\MailChimp\MailChimp', $MailChimp);
	}

}