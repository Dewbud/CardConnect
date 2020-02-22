<?php

namespace Dewbud\CardConnect;

use Dewbud\CardConnect\Exceptions\CardConnectException;
use Dewbud\CardConnect\Requests\AuthorizationRequest;
use Dewbud\CardConnect\Responses\AuthorizationResponse;
use Dewbud\CardConnect\Responses\CaptureResponse;
use Dewbud\CardConnect\Responses\InquireResponse;
use Dewbud\CardConnect\Responses\RefundResponse;
use Dewbud\CardConnect\Responses\SettlementResponse;
use Dewbud\CardConnect\Responses\VoidResponse;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Response as GuzzleResponse;

class CardPointe
{
    /**
     * Merchant ID.
     *
     * @var string
     */
    protected $merchant_id = null;

    /**
     * API username.
     *
     * @var string
     */
    protected $user = null;

    /**
     * API password.
     *
     * @var string
     */
    protected $password = null;

    /**
     * Servlet endpoint.
     *
     * https://sub.domain.tld:port/.
     *
     * @var string
     */
    protected $endpoint = '';

    /**
     * Currency code.
     *
     * @var string
     */
    protected $currency = 'USD';

    const AUTH_TEXT       = 'CardConnect REST Servlet';
    const NO_BATCHES_TEXT = 'Null batches';
    const CLIENT_NAME     = 'PHP CARDCONNECT';
    const CLIENT_VERSION  = '1.1';

    /**
     * Last request.
     *
     * @var array
     */
    public $last_request = null;

    /**
     * Last response.
     *
     * @var array
     */
    public $last_response = null;

    /**
     * @var \GuzzleHttp\Client
     */
    protected $http;

    /**
     * CardConnect Client.
     *
     * @param string $merchant Merchant ID
     * @param string $user     API username
     * @param string $pass     API password
     * @param string $endpoint API endpoint
     * @param string $currency USD, CAD, etc
     */
    public function __construct($merchant, $user, $pass, $endpoint, $currency = 'USD')
    {
        $this->merchant_id = $merchant;
        $this->user        = $user;
        $this->password    = $pass;
        $this->currency    = $currency;
        $this->setEndpoint($endpoint);
    }

    /**
     * Test credentials against gateway.
     *
     * @return bool
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
     * @return \Dewbud\CardConnect\Responses\AuthorizationResponse|\Dewbud\CardConnect\Responses\CaptureResponse
     */
    public function authorize(AuthorizationRequest $request)
    {
        $params = [
            'merchid'  => $this->merchant_id,
            'currency' => $this->currency,
        ];

        $payload = array_merge($params, $request->toArray());

        $res = $this->send('PUT', 'auth', $payload);

        $res = $this->parseResponse($res);

        if ($request->capture) {
            return new CaptureResponse($res);
        }

        return new AuthorizationResponse($res);
    }

    /**
     * Capture a previously authorized transaction.
     *
     * @param string $retref  transaction id
     * @param array  $request request parameters
     *
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
     * Void a previously authorized transaction.
     *
     * @param string $retref  transaction id
     * @param array  $request request parameters
     *
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
     * Refund a settled transaction.
     *
     * @param array $request request parameters
     *
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
     * Get status of a transaction.
     *
     * @param array $retref transaction id
     *
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
     * Get settlement status for a given day.
     *
     * @param array $day MMDD
     *
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

    /**
     * Create a profile.
     *
     * @see https://developer.cardconnect.com/cardconnect-api?lang=php#create-update-profile-request
     *
     * @return array
     */
    public function createProfile(array $request = [])
    {
        $params = [
            'merchid' => $this->merchant_id,
        ];

        $request = array_merge($params, $request);
        $res     = $this->send('PUT', 'profile', $request);

        return $this->parseResponse($res);
    }

    /**
     * Get profile(s).
     *
     * @param string      $profile_id profile id
     * @param string|null $account_id account id
     *
     * @see https://developer.cardconnect.com/cardconnect-api?lang=php#get-profile-request
     *
     * @return object
     */
    public function profile(string $profile_id, $account_id = null)
    {
        $res = $this->send('GET', "profile/$profile_id/$account_id/{$this->merchant_id}", null);

        return $this->parseResponse($res);
    }

    /**
     * Delete profile(s).
     *
     * @param string      $profile_id profile id
     * @param string|null $account_id account id
     *
     * @see https://developer.cardconnect.com/cardconnect-api?lang=php#delete-profile-request
     *
     * @return object
     */
    public function deleteProfile(string $profile_id, $account_id = null)
    {
        $res = $this->send('DELETE', "profile/$profile_id/$account_id/{$this->merchant_id}", null);

        return $this->parseResponse($res);
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
     * @return array
     */
    protected function parseResponse(GuzzleResponse $res)
    {
        return \json_decode($res->getBody(), true);
    }

    /**
     * Send request via Guzzle.
     *
     * @param string $verb     HTTP verb
     * @param string $resource API resource
     * @param array  $request  Request Data
     * @param array  $options  Guzzle Options
     *
     * @return \GuzzleHttp\Psr7\Response
     */
    protected function send($verb, $resource, $request = [], $options = [])
    {
        $res = null;

        $default_options = [
            'allow_redirects' => false,
            'auth'            => [$this->user, $this->password],
            'headers'         => [
                'User-Agent'   => self::CLIENT_NAME.' v'.self::CLIENT_VERSION,
                'Content-Type' => 'application/json',
            ],
            'verify' => false, // self signed certs
        ];

        $options         = array_merge_recursive($default_options, $options);
        $options['json'] = $request;

        $this->last_request = $options;

        try {
            // Send request
            $res                 = $this->http->request($verb, $resource, $options);
            $this->last_response = \json_decode($res->getBody(), true);
        } catch (ClientException $e) {
            throw $e;
            // $err = $e->getResponse();
            // throw new CardConnectException("CardConnect HTTP Client {$err->getStatusCode()}: {$err->getReasonPhrase()}");
        }

        return $res;
    }

    /**
     * Getters / Setters.
     */

    /**
     * Get merchant ID.
     *
     * @return string
     */
    public function getMerchantId()
    {
        return $this->merchant_id;
    }

    /**
     * Set merchant ID.
     *
     * @param string $merchant_id merchant ID
     *
     * @return self
     */
    public function setMerchantId(string $merchant_id)
    {
        $this->merchant_id = $merchant_id;

        return $this;
    }

    /**
     * Get gateway username.
     *
     * @return string
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set username.
     *
     * @param string $user API username
     *
     * @return self
     */
    public function setUser(string $user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get password.
     *
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Set password.
     *
     * @param string $password API password
     *
     * @return self
     */
    public function setPassword(string $password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Get endpoint.
     *
     * @return string
     */
    public function getEndpoint()
    {
        return $this->endpoint;
    }

    /**
     * Set endpoint.
     *
     * @param string $endpoint https://sub.domain.tld:port/
     *
     * @return self
     */
    public function setEndpoint(string $endpoint)
    {
        $this->endpoint = $endpoint;

        if ('/' != substr($this->endpoint, -1)) {
            $this->endpoint .= '/';
        }
        $this->endpoint .= 'cardconnect/rest/';

        $this->http = new Client(['base_uri' => $this->endpoint]);

        return $this;
    }

    /**
     * Get currency code.
     *
     * @return string
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * Set currency code.
     *
     * @param string $currency currency code
     *
     * @return self
     */
    public function setCurrency(string $currency)
    {
        $this->currency = $currency;

        return $this;
    }
}
