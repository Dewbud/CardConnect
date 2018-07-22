<?php

namespace Tests;

use Dewbud\CardConnect\CardPointe;
use Dewbud\CardConnect\Responses\AuthorizationResponse;
use Dewbud\CardConnect\Responses\CaptureResponse;
use Dewbud\CardConnect\Responses\InquireResponse;
use Dewbud\CardConnect\Responses\SettlementTransaction;
use Dewbud\CardConnect\Responses\VoidResponse;
use PHPUnit\Framework\TestCase;

class CardPointeTest extends TestCase
{
    const MERCHANT = '496160873888';
    const USER     = 'testing';
    const PASS     = 'testing123';
    const SERVER   = 'https://fts.cardconnect.com:6443/';

    /**
     * @var \Dewbud\CardConnect\CardPointe
     */
    private $client;

    public function setUp()
    {
        parent::setUp();
        $this->client = new CardPointe(self::MERCHANT, self::USER, self::PASS, self::SERVER);
    }

    public function tearDown()
    {
        unset($this->client);
        parent::tearDown();
    }

    public function log($text)
    {
        fwrite(STDERR, $text . "\n");
    }

    /**
     * Tests
     */

    /** @test */
    public function detectsValidCredentials()
    {
        $this->assertTrue($this->client->testAuth());
    }

    /** @test */
    public function detectsInvalidCredentials()
    {
        $client               = $this->client;
        $client->gateway_pass = 'bad_password';
        $this->assertFalse($client->testAuth());
    }

    /** @test */
    public function authorizesTransactions()
    {
        $res = $this->client->authorize([
            'account' => '4242424242424242',
            'amount'  => 500,
            'expiry'  => '0120',
        ]);

        $this->assertInstanceOf(AuthorizationResponse::class, $res, get_class($res));
        if (!$res->success()) {
            fwrite(STDERR, print_r(json_encode($this->client->last_request, JSON_PRETTY_PRINT), true));
        }
        $this->assertTrue($res->success(), $res->toJSON(JSON_PRETTY_PRINT));
        $this->assertEquals(500, $res->amount, $res->toJSON());
    }

    /** @test */
    public function returnsCaptureResponseFromAuthorizeAndCapture()
    {
        $res = $this->client->authorize([
            'account' => '4242424242424242',
            'amount'  => 500,
            'expiry'  => '0120',
            'capture' => 'Y',
        ]);

        $this->assertInstanceOf(CaptureResponse::class, $res);
        $this->assertTrue($res->success(), $res->toJSON());
        $this->assertEquals(500, $res->amount, $res->toJSON());
    }

    /** @test */
    public function capturesAuthorizations()
    {
        $auth = $this->client->authorize([
            'account' => '4242424242424242',
            'amount'  => 500,
            'expiry'  => '0120',
        ]);

        $cap = $this->client->capture($auth->retref);

        $this->assertInstanceOf(CaptureResponse::class, $cap);
        $this->assertTrue($cap->success(), $cap->toJSON());
        $this->assertEquals(500, $cap->amount, $cap->toJSON());
    }

    /** @test */
    public function voidsTransactions()
    {
        $auth = $this->client->authorize([
            'account' => '4242424242424242',
            'amount'  => 500,
            'expiry'  => '0120',
        ]);

        $void = $this->client->void($auth->retref);

        $this->assertInstanceOf(VoidResponse::class, $void);
        $this->assertTrue($void->success(), $void->toJSON());
        $this->assertEquals('REVERS', $void->authcode, $void->toJSON());
    }

    /** @test */
    public function inquiresAboutTransactions()
    {
        $auth = $this->client->authorize([
            'account' => '4242424242424242',
            'amount'  => 500,
            'expiry'  => '0120',
        ]);

        $inquire = $this->client->inquire($auth->retref);

        $this->assertInstanceOf(InquireResponse::class, $inquire);
        $this->assertTrue($inquire->success(), $inquire->toJSON());
        $this->assertEquals($auth->retref, $inquire->retref, $inquire->toJSON());
    }

    /** @test */
    public function getsSettlements()
    {
        $settlements = $this->client->settleStat(date('md', strtotime('yesterday')));

        $this->assertTrue($settlements != null, json_encode($settlements));
        $this->assertTrue(is_array($settlements[0]->txns), $settlements[0]->txns);
        $this->assertInstanceOf(SettlementTransaction::class, $settlements[0]->txns[0]);
        $this->assertTrue(is_int($settlements[0]->txns[0]->setlamount), $settlements[0]->txns[0]->setlamount);
    }

    /** @test */
    public function returnsNullForNoSettlements()
    {
        $settlements = $this->client->settleStat(date('md', strtotime('tomorrow')));

        $this->assertEquals(null, $settlements, $settlements);
    }
}
