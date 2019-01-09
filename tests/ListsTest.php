<?php

use DrewM\MailChimp\MailChimp;
use PHPUnit\Framework\TestCase;

class ListsTest extends TestCase
{
    public function testGetLists()
    {
        $MC_API_KEY = getenv('MC_API_KEY');

        if (!$MC_API_KEY) {
            $this->markTestSkipped('No API key in ENV');
        }

        $MailChimp = new MailChimp($MC_API_KEY);
        $lists     = $MailChimp->get('lists');

        $this->assertArrayHasKey('lists', $lists);
    }
}
