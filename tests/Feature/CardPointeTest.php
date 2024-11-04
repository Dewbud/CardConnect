<?php

namespace Tests\Feature;

use Dewbud\CardConnect\CardPointe;
use Dewbud\CardConnect\Requests\AuthorizationRequest;
use Dewbud\CardConnect\Responses\AuthorizationResponse;
use Dewbud\CardConnect\Responses\CaptureResponse;
use Dewbud\CardConnect\Responses\InquireResponse;
use Dewbud\CardConnect\Responses\SettlementTransaction;
use Dewbud\CardConnect\Responses\VoidResponse;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class CardPointeTest extends TestCase
{
    public const MERCHANT = '496160873888';
    public const USER     = 'testing';
    public const PASS     = 'testing123';
    public const SERVER   = 'https://fts-uat.cardconnect.com/';

    private CardPointe $client;

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

    public function log($text)
    {
        fwrite(STDERR, $text."\n");
    }

    /**
     * Tests.
     */
    #[Test]
    public function detectsValidCredentials()
    {
        $this->assertTrue($this->client->testAuth());
    }

    #[Test]
    public function detectsInvalidCredentials()
    {
        $this->client->setPassword('bad_password');
        $this->assertFalse($this->client->testAuth());
    }

    #[Test]
    public function validateMerchantID()
    {
        $this->assertTrue($this->client->validateMerchantId());
    }

    #[Test]
    public function authorizesTransactions()
    {
        $request = new AuthorizationRequest([
            'account' => '4242424242424242',
            'amount'  => 500,
            'expiry'  => date('my', strtotime('next year')),
        ]);

        $res = $this->client->authorize($request);

        $this->assertInstanceOf(AuthorizationResponse::class, $res, get_class($res));
        $this->assertTrue($res->success(), $res->toJSON(JSON_PRETTY_PRINT));
        $this->assertEquals(500, $res->amount, $res->toJSON());
    }

    #[Test]
    public function returnsCaptureResponseFromAuthorizeAndCapture()
    {
        $request = new AuthorizationRequest([
            'account' => '4242424242424242',
            'amount'  => 500,
            'expiry'  => date('my', strtotime('next year')),
            'capture' => true,
        ]);

        $res = $this->client->authorize($request);

        $this->assertInstanceOf(CaptureResponse::class, $res);
        $this->assertTrue($res->success(), $res->toJSON());
        $this->assertEquals(500, $res->amount, $res->toJSON());
    }

    #[Test]
    public function capturesAuthorizations()
    {
        $request = new AuthorizationRequest([
            'account' => '4242424242424242',
            'amount'  => 500,
            'expiry'  => date('my', strtotime('next year')),
            'capture' => true,
        ]);

        $auth = $this->client->authorize($request);
        $cap  = $this->client->capture($auth->retref);

        $this->assertInstanceOf(CaptureResponse::class, $cap);
        $this->assertTrue($cap->success(), $cap->toJSON());
        $this->assertEquals(500, $cap->amount, $cap->toJSON());
    }

    #[Test]
    public function voidsTransactions()
    {
        $request = new AuthorizationRequest([
            'account' => '4242424242424242',
            'amount'  => 500,
            'expiry'  => date('my', strtotime('next year')),
            'capture' => true,
        ]);

        $auth = $this->client->authorize($request);
        $void = $this->client->void($auth->retref);

        $this->assertInstanceOf(VoidResponse::class, $void);
        $this->assertTrue($void->success(), $void->toJSON());
        $this->assertEquals('REVERS', $void->authcode, $void->toJSON());
    }

    #[Test]
    public function inquiresAboutTransactions()
    {
        $request = new AuthorizationRequest([
            'account' => '4242424242424242',
            'amount'  => 500,
            'expiry'  => date('my', strtotime('next year')),
        ]);

        $auth    = $this->client->authorize($request);
        $inquire = $this->client->inquire($auth->retref);

        $this->assertInstanceOf(InquireResponse::class, $inquire);
        $this->assertTrue($inquire->success(), $inquire->toJSON());
        $this->assertEquals($auth->retref, $inquire->retref, $inquire->toJSON());
    }

    #[Test]
    public function getsSettlements()
    {
        $settlements = $this->client->settleStat(date('md', strtotime('yesterday')));

        $this->assertNotNull($settlements, json_encode($settlements));
        $this->assertIsArray($settlements[0]->txns);
        $this->assertInstanceOf(SettlementTransaction::class, $settlements[0]->txns[0]);
        $this->assertIsInt($settlements[0]->txns[0]->setlamount, $settlements[0]->txns[0]->setlamount);
    }

    #[Test]
    public function returnsNullForNoSettlements()
    {
        $settlements = $this->client->settleStat(date('md', strtotime('tomorrow')));
        $this->assertNull($settlements);
    }

    #[Test]
    public function createsProfile()
    {
        $response = $this->client->createProfile([
            'defaultacct' => 'Y',
            'account'     => '4242424242424242',
            'expiry'      => date('my', strtotime('next year')),
            'name'        => 'Test User',
            'address'     => '123 Test St',
            'city'        => 'TestCity',
            'region'      => 'TestState',
            'country'     => 'US',
            'postal'      => '11111',
        ]);

        $this->assertNotEmpty($response['profileid']);
        $this->assertNotEmpty($response['acctid']);
        $this->assertEquals('A', $response['respstat']);
    }

    #[Test]
    public function createsProfileWhenAuthorizeAndCapture()
    {
        $request = new AuthorizationRequest([
            'account' => '4242424242424242',
            'amount'  => 500,
            'expiry'  => date('my', strtotime('next year')),
            'capture' => true,
            'profile' => 'Y',
        ]);

        $res = $this->client->authorize($request);

        $this->assertInstanceOf(CaptureResponse::class, $res);
        $this->assertTrue($res->success(), $res->toJSON());
        $this->assertEquals(500, $res->amount, $res->toJSON());
        $this->assertNotEmpty($res['profileid']);
        $this->assertNotEmpty($res['acctid']);
    }

    #[Test]
    public function returnsCaptureResponseFromAuthorizeAndCaptureUsingSavedCard()
    {
        $res = $this->client->createProfile([
            'defaultacct' => 'Y',
            'account'     => '4242424242424242',
            'expiry'      => date('my', strtotime('next year')),
            'name'        => 'Test User',
            'address'     => '123 Test St',
            'city'        => 'TestCity',
            'region'      => 'TestState',
            'country'     => 'US',
            'postal'      => '11111',
        ]);

        $this->assertNotEmpty($res['profileid']);
        $this->assertNotEmpty($res['acctid']);
        $this->assertEquals('A', $res['respstat']);

        $request = new AuthorizationRequest([
            'amount'  => 500,
            'expiry'  => date('my', strtotime('next year')),
            'capture' => true,
            'profile' => $res['profileid'].'/'.$res['acctid'],
        ]);

        $res = $this->client->authorize($request);

        $this->assertInstanceOf(CaptureResponse::class, $res);
        $this->assertTrue($res->success(), $res->toJSON());
        $this->assertEquals(500, $res->amount, $res->toJSON());
    }

    // @todo more profile service tests (profileGet, profileDelete)
}