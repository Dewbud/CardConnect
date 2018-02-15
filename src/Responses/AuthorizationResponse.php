<?php

namespace Dewbud\CardConnect\Responses;

class AuthorizationResponse extends Response
{
    protected $_numericFields = [
        'amount',
    ];
}
