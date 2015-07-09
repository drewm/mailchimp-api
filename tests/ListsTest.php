<?php
 
use \DrewM\MailChimp\MailChimp;
 
class ListsTest extends PHPUnit_Framework_TestCase 
{

	public function setUp()
	{
		$dotenv = new Dotenv\Dotenv(__DIR__.'/../');
		$dotenv->load();
	}

	public function testGetLists()
	{
		$MailChimp = new MailChimp(getenv('MC_API_KEY'));
		$lists = $MailChimp->get('lists');
		
		$this->assertArrayHasKey('lists', $lists);		
	}

}