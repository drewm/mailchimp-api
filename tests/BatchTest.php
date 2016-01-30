<?php
 
use \DrewM\MailChimp\MailChimp;
use \DrewM\MailChimp\Batch;
 
class BatchTest extends PHPUnit_Framework_TestCase 
{

	public function setUp()
	{
		$env_file_path = __DIR__.'/../';
		
		if (file_exists($env_file_path.'.env')) {
			$dotenv = new Dotenv\Dotenv($env_file_path);
			$dotenv->load();	
		}
	}

	public function testNewBatch()
	{
		$MC_API_KEY = getenv('MC_API_KEY');

		if (!$MC_API_KEY) $this->markTestSkipped('No API key in ENV');

		$MailChimp = new MailChimp($MC_API_KEY);
		$Batch = $MailChimp->new_batch();
		
		$this->assertInstanceOf('DrewM\MailChimp\Batch', $Batch);		
	}

}