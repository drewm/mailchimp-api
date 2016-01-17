<?php
 
use \DrewM\MailChimp\MailChimp;
 
class ListsTest extends PHPUnit_Framework_TestCase 
{

	public function setUp()
	{
		$env_file_path = __DIR__.'/../';
		
		if (file_exists($env_file_path.'.env')) {
			$dotenv = new Dotenv\Dotenv($env_file_path);
			$dotenv->load();	
		}
	}

	public function testGetLists()
	{
		$MC_API_KEY = getenv('MC_API_KEY');

		if (!$MC_API_KEY) $this->markTestSkipped('No API key in ENV');

		$MailChimp = new MailChimp($MC_API_KEY);
		$lists = $MailChimp->get('lists');
		
		$this->assertArrayHasKey('lists', $lists);		
	}

}