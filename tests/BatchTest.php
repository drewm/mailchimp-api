<?php

namespace DrewM\MailChimp\Tests;

use DrewM\MailChimp\MailChimp;
use PHPUnit\Framework\TestCase;

class BatchTest extends TestCase
{
    /**
     * @throws \Exception
     */
    public function testNewBatch()
    {
        $MC_API_KEY = getenv('MC_API_KEY');

        if (!$MC_API_KEY) {
            $this->markTestSkipped('No API key in ENV');
        }

        $MailChimp = new MailChimp($MC_API_KEY);
        $Batch     = $MailChimp->new_batch('1');

        $this->assertInstanceOf('\DrewM\MailChimp\Batch', $Batch);

        $this->assertSame(array(), $Batch->get_operations());
    }

}
