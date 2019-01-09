# PHP CardConnect Adapter

## Initializing
```php
$merchant_id = '123456123456';
$user        = 'username';
$pass        = 'password';
$server      = 'https://sub.domain.tld:1111/';

$client = new CardPointe($merchant_id, $user, $pass, $server);
```

## Testing Credentials
```php
$boolean = $client->testAuth();
```

## Tweaks
Responses are parsed and their amount fields are returned in cents ```int```.

The client stores the last request made as an ```array``` ```$client->last_request``` which can be used to debug requests

## Response Objects
Responses are returned as objects and can be accessed as arrays.
```php
$response = $client->inquire($retref);
$response->amount; // returns int
$response->toJSON(); // Returns JSON encoded string, accepts format codes (JSON_PRETTY_PRINT, etc)
$response->toArray(); // Returns array of attributes
```

## Authorizing Transactions
```php
$authorization_response = $client->authorize([
    'account' => '4242424242424242',
    'amount'  => '100',
    'expiry'  => '0120',
]);
```

**You can also authorize and capture in the same request like so**
```php
$capture_response = $client->authorize([
    'account' => '4242424242424242',
    'amount'  => '100',
    'expiry'  => '0120',
    'capture' => 'Y',
]);
```

To view all available fields see [Authorization Request](https://developer.cardconnect.com/cardconnect-api#authorization-request)

All returned fields see [Authorization Response](https://developer.cardconnect.com/cardconnect-api#authorization-response)

## Capturing Transactions
```php
$auth_retref = '123456654321';
$params = []; // optional
$capture_response = $client->capture($auth_retref, $params);
```
To view all available fields see [Capture Request](https://developer.cardconnect.com/cardconnect-api#capture-request)

All returned fields see [Capture Response](https://developer.cardconnect.com/cardconnect-api#capture-response)

## Voiding Transactions
```php
$auth_retref = '123456654321';
$params = []; // optional
$void_response = $client->void($auth_retref, $params);
```
To view all available fields see [Void Request](https://developer.cardconnect.com/cardconnect-api#void-request)

All returned fields see [Void Response](https://developer.cardconnect.com/cardconnect-api#void-response)

## Refunding Transactions
```php
$capture_retref = '123456654321';
$params = []; // optional
$void_response = $client->refund($capture_retref, $params);
```
To view all available fields see [Refund Request](https://developer.cardconnect.com/cardconnect-api#refund-request)

All returned fields see [Refund Response](https://developer.cardconnect.com/cardconnect-api#refund-response)

## Transaction Status
```php
$retref = '123456654321';
$inquire_response = $client->inquire($retref);
```
All returned fields see [Inquire Response](https://developer.cardconnect.com/cardconnect-api#inquire-response)

## Settlement Status
```php
$date = '0118';
$settlements = $client->settleStat($date);
$first_settlement = $settlements[0];
```
All returned fields see [Settlement Response](https://developer.cardconnect.com/cardconnect-api#settlement-response)

## Create/Update Profile
```php
// update a profile by providing 'profile' => $profile_id in the request
$request = [
    'merchid'     => "496400000840",
    'defaultacct' => "Y",
    'account'     => "4444333322221111",
    'expiry'      => "0914",
    'name'        => "Test User",
    'address'     => "123 Test St",
    'city'        => "TestCity",
    'region'      => "TestState",
    'country'     => "US",
    'postal'      => "11111",
];
$res = $client->createProfile($request);
```
All returned fields see [Create/Update Profile Request](https://developer.cardconnect.com/cardconnect-api?lang=php#create-update-profile-response)

## Get Profile
```php
$profile_id = '1023456789';
$account_id = null; // optional
$profile = $client->profile($profile_id, $account_id);
```
All returned fields see [Profile Response](https://developer.cardconnect.com/cardconnect-api?lang=php#get-profile-response)

## Delete Profile
```php
$profile_id = '1023456789';
$account_id = null; // optional
$profile = $client->deleteProfile($profile_id, $account_id);
```
All returned fields see [Delete Profile Response](https://developer.cardconnect.com/cardconnect-api?lang=php#delete-profile-response)

## Tests
```composer test```

Note: small or large authorization/capture amounts don't seem to work with the test merchant credentials.

## Future stuff
Implement remains service endpoints
- Funding Service
- Signature Capture Service (probably never)
- Open Batch Service
- Close Batch Service
- BIN Service


