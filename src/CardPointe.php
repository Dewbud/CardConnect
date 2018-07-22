<?php

namespace Dewbud\CardConnect;

use Dewbud\CardConnect\Exceptions\CardConnectException;
use Dewbud\CardConnect\Responses\AuthorizationResponse;
use Dewbud\CardConnect\Responses\CaptureResponse;
use Dewbud\CardConnect\Responses\InquireResponse;
use Dewbud\CardConnect\Responses\RefundResponse;
use Dewbud\CardConnect\Responses\Response;
use Dewbud\CardConnect\Responses\SettlementResponse;
use Dewbud\CardConnect\Responses\VoidResponse;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Response as GuzzleResponse;

class CardPointe
{
    // Credentials
    public $merchant_id      = '';
    public $gateway_user     = '';
    public $gateway_pass     = '';
    public $gateway_endpoint = '';

    const AUTH_TEXT       = 'CardConnect REST Servlet';
    const NO_BATCHES_TEXT = 'Null batches';
    const CLIENT_NAME     = 'PHP CARDCONNECT';
    const CLIENT_VERSION  = '1.0.2';

    public $last_request = null;

    /**
     * @var \GuzzleHttp\Client
     */
    protected $http;

    /**
     * CardConnect Client.
     *
     * @param string $merchant Merchant ID
     * @param string $user     Gateway username
     * @param string $pass     Gateway password
     * @param string $endpoint Gateway endpoint
     */
    public function __construct($merchant, $user, $pass, $endpoint)
    {
        $this->merchant_id      = $merchant;
        $this->gateway_user     = $user;
        $this->gateway_pass     = $pass;
        $this->gateway_endpoint = $endpoint;

        if (substr($this->gateway_endpoint, -1) != '/') {
            $this->gateway_endpoint .= '/';
        }
        $this->gateway_endpoint .= 'cardconnect/rest/';

        $this->http = new Client(['base_uri' => $this->gateway_endpoint]);
    }

    /**
     * Test credentials against gateway.
     *
     * @return boolean
     */
    public function testAuth()
    {
        $params = [
            'merchid' => $this->merchant_id,
        ];

        try {
            $res = $this->send('GET', '', $params);
        } catch (ClientException $e) {
            return false;
        }

        preg_match('/<h1>(.*?)<\/h1>/', $res->getBody(), $matches);
        return strtolower($matches[1]) == strtolower(self::AUTH_TEXT);
    }

    /**
     * Authorize a transaction.
     *
     * @param array $request Array of request parameters
     *
     * @return \Dewbud\CardConnect\Responses\AuthorizationResponse|\Dewbud\CardConnect\Responses\CaptureResponse
     */
    public function authorize(array $request)
    {
        $params = [
            'merchid'  => $this->merchant_id,
            'currency' => 'USD',
        ];

        $request = array_merge($params, $request);

        $res = $this->send('PUT', 'auth', $request);

        $res = $this->parseResponse($res);

        if (array_key_exists('capture', $request)) {
            return new CaptureResponse($res);
        }

        return new AuthorizationResponse($res);
    }

    /**
     * Capture a previously authorized transaction
     *
     * @param string $retref transaction id
     * @param array $request request parameters
     * @return \Dewbud\CardConnect\Responses\CaptureResponse
     */
    public function capture(string $retref, $request = [])
    {
        $params = [
            'merchid' => $this->merchant_id,
            'retref'  => $retref,
        ];

        $request = array_merge($params, $request);

        $res = $this->send('PUT', 'capture', $request);

        $res = $this->parseResponse($res);

        return new CaptureResponse($res);
    }

    /**
     * Void a previously authorized transaction
     *
     * @param string $retref transaction id
     * @param array $request request parameters
     * @return \Dewbud\CardConnect\Responses\VoidResponse
     */
    public function void(string $retref, $request = [])
    {
        $params = [
            'merchid' => $this->merchant_id,
            'retref'  => $retref,
        ];

        $request = array_merge($params, $request);

        $res = $this->send('PUT', 'void', $request);

        $res = $this->parseResponse($res);

        return new VoidResponse($res);
    }

    /**
     * Refund a settled transaction
     *
     * @param array $request request parameters
     * @return \Dewbud\CardConnect\Responses\RefundResponse
     */
    public function refund($request = [])
    {
        $params = [
            'merchid' => $this->merchant_id,
        ];

        $request = array_merge($params, $request);

        $res = $this->send('PUT', 'refund', $request);

        $res = $this->parseResponse($res);

        return new RefundResponse($res);
    }

    /**
     * Get status of a transaction
     *
     * @param array $retref transaction id
     * @return \Dewbud\CardConnect\Responses\InquireResponse
     */
    public function inquire(string $retref)
    {
        $params = [
            'merchid' => $this->merchant_id,
        ];

        $res = $this->send('GET', "inquire/{$retref}/{$this->merchant_id}", $params);

        $res = $this->parseResponse($res);

        return new InquireResponse($res);
    }

    /**
     * Get settlement status for a given day
     *
     * @param array $day MMDD
     * @return array|null
     */
    public function settleStat(string $day)
    {
        $params = [
            'merchid' => $this->merchant_id,
        ];

        $res = $this->send('GET', "settlestat?merchid={$this->merchant_id}&date={$day}", $params);

        if (strtolower($res->getBody()) == strtolower(self::NO_BATCHES_TEXT)) {
            return null;
        }

        $res = $this->parseResponse($res);

        $settlements = [];

        foreach ($res as $settle) {
            $settlements[] = new SettlementResponse($settle);
        }

        return $settlements;
    }

    protected function validateInput(array $required, array $input)
    {
        foreach ($required as $field) {
            if (!array_key_exists($field, $input)) {
                throw new CardConnectException("Invalid request, the '{$field}' field is required.");
            }
        }
    }

    /**
     * @param \GuzzleHttp\Psr7\Response $res
     * @return array
     */
    protected function parseResponse(GuzzleResponse $res)
    {
        return \json_decode($res->getBody(), true);
    }

    /**
     * Send request via Guzzle
     *
     * @param string $verb      HTTP verb
     * @param string $resource  API resource
     * @param array $request    Request Data
     * @param array $options    Guzzle Options
     *
     * @return \GuzzleHttp\Psr7\Response
     */
    protected function send($verb, $resource, $request = [], $options = [])
    {
        $res = null;

        $default_options = [
            'allow_redirects' => false,
            'auth'            => [$this->gateway_user, $this->gateway_pass],
            'headers'         => [
                'User-Agent'   => self::CLIENT_NAME . ' v' . self::CLIENT_VERSION,
                'Content-Type' => 'application/json',
            ],
            'verify'          => false, // self signed certs
        ];

        $options         = array_merge($default_options, $options);
        $options['json'] = $request;

        $this->last_request = $options;

        try {
            // Send request
            $res = $this->http->request($verb, $resource, $options);
        } catch (ClientException $e) {
            throw $e;
            // $err = $e->getResponse();
            // throw new CardConnectException("CardConnect HTTP Client {$err->getStatusCode()}: {$err->getReasonPhrase()}");
        }

        return $res;
    }
}
