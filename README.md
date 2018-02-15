# PHP CardConnect Adapter

## Initializing
```php
$merchant_id = '123456123456';
$user        = 'username';
$pass        = 'password';
$server      = 'https://sub.domain.tld:1111/';

$client = new \Dewbud\CardPointe($merchant_id, $user, $pass, $server);
```

## Testing Credentials
```php
$boolean = $client->testAuth();
```

## Tweaks
Requests done to the API require an amount in cents but return a response in dollars.
ex: 100 = '1.00'

Responses are parsed and their amount fields are returned in cents like the requests for sanity.

## Response Objects
Responses are returned as objects and can be accessed as arrays.
```php
$response = $this->client->inquire($retref);
$response->amount; // returns int
$response->toJSON(); // Returns JSON encoded string
$response->toArray(); // Returns array of attributes
```

## Authorizing Transactions
```php
$authorization_response = $this->client->authorize([
    'account' => '4242424242424242',
    'amount'  => '100',
    'expiry'  => '0120',
]);
```

**You can also authorize and capture in the same request like so**
```php
$capture_response = $this->client->authorize([
    'account' => '4242424242424242',
    'amount'  => '100',
    'expiry'  => '0120',
    'capture' => 'Y',
]);
```
These are the minimum required fields.

To view all available fields see [Authorization Request](https://developer.cardconnect.com/cardconnect-api#authorization-request)

All returned fields [Authorization Response](https://developer.cardconnect.com/cardconnect-api#authorization-response)

## Capturing Transactions
```php
$auth_retref = '123456654321';
$params = []; // optional
$capture_response = $this->client->capture($auth_retref, $params);
```
To view all available fields see [Capture Request](https://developer.cardconnect.com/cardconnect-api#capture-request)

All returned fields [Capture Response](https://developer.cardconnect.com/cardconnect-api#capture-response)

## Voiding Transactions
```php
$auth_retref = '123456654321';
$params = []; // optional
$void_response = $this->client->void($auth_retref, $params);
```
To view all available fields see [Void Request](https://developer.cardconnect.com/cardconnect-api#void-request)

All returned fields [Void Response](https://developer.cardconnect.com/cardconnect-api#void-response)

## Refunding Transactions
```php
$capture_retref = '123456654321';
$params = []; // optional
$void_response = $this->client->void($capture_retref, $params);
```
To view all available fields see [Refund Request](https://developer.cardconnect.com/cardconnect-api#refund-request)

All returned fields [Refund Response](https://developer.cardconnect.com/cardconnect-api#refund-response)

## Transaction Status
```php
$retref = '123456654321';
$inquire_response = $this->client->inquire($retref);
```
All returned fields [Inquire Response](https://developer.cardconnect.com/cardconnect-api#inquire-response)

## Settlement Status
```php
$date = '0118';
$settlements = $this->client->settleStat($date);
$first_settlement = $settlements[0];
```
All returned fields [Settlement Response](https://developer.cardconnect.com/cardconnect-api#settlement-response)

## Future stuff
Implement remains service endpoints
    - Funding Service
    - Profile Service
    - Signature Capture Service (probably never)
    - Open Batch Service
    - Close Batch Service
    - BIN Service


