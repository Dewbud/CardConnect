# 2-22-2020
## 1.1.0
CardPointe client public credential/settings attributes are now protected and behind setters/getters, 
if you were using the the public attributes you should now use the setters/getters

```php
// old
$client->merchant_id = '12345';
$client->gateway_user = 'user';

$user = $client->gateway_user;

// new
$client->setMerchantId('12345')
    ->setUser('user');

$user = $client->getUser();
```