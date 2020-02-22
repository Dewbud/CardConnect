<?php

namespace Dewbud\CardConnect\Requests;

/**
 * @property string|null      $merchid   MerchantID
 * @property int              $amount    Amount
 * @property string           $expiry    Card expiration MMYY or YYYYMM
 * @property string           $account   CardSecure token or PAN (CC/BAN)
 * @property string           $currency  USD, CAD, etc @see https://www.iban.com/currency-codes
 * @property string|null      $bankaba   Routing number
 * @property string|null      $track     Track Data
 * @property bool|null        $receipt   Request receipt in response
 * @property bool|string|null $profile   Set true to create profile from this request
 *                                       or supply "$profile_id/$account_id" to use existing account
 * @property bool|null        $capture   Wether to capture payment if authorization succeeds
 * @property bool|null        $tokenize  Wether to return payment method token in response
 * @property string|null      $signature JSON escaped, Base64 encoded, Gzipped, BMP of signature data
 * @property string|null      $cvv2      CVV
 * @property string|null      $name      Name
 * @property string|null      $address   Address
 * @property string|null      $city      City
 * @property string|null      $postal    Postal
 * @property string|null      $region    Region
 * @property string|null      $country   Country (US, CA, etc) @see https://www.worldatlas.com/aatlas/ctycodes.htm
 * @property string|null      $phone     Phone
 * @property string|null      $email     Email
 * @property string|null      $orderid   Order ID
 * @property string|null      $authcode  Authorization code
 * @property bool|null        $taxexempt Tax exempt
 * @property string|null      $termid    Terminal device ID
 * @property string|null      $accttype  PPAL, PAID, GIFT or PDEBIT
 * @property string|null      $ecomind   E-commerce ID (T, R or E)
 */
class AuthorizationRequest extends Request
{
    protected $casts = [
        'taxexempt' => 'bool',
        'tokenize'  => 'bool',
        'capture'   => 'bool',
        'profile'   => 'bool',
        'receipt'   => 'bool',
    ];
}
