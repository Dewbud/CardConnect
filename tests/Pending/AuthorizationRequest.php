<?php

namespace Tests;

use Dewbud\CardConnect\CardPointe;
use PHPUnit\Framework\TestCase;

class AuthorizationRequest extends TestCase
{
    const MERCHANT = '496160873888';
    const USER     = 'testing';
    const PASS     = 'testing123';
    const SERVER   = 'https://fts-uat.cardconnect.com/';

    /**
     * @var \Dewbud\CardConnect\CardPointe
     */
    private $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = new CardPointe(self::MERCHANT, self::USER, self::PASS, self::SERVER);
    }

    protected function tearDown(): void
    {
        unset($this->client);
        parent::tearDown();
    }

    // /** @test */
    // public function detectsValidCredentials()
    // {
    //     $this->assertTrue($this->client->testAuth());
    // }
}
