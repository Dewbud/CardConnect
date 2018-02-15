<?php

namespace Dewbud\CardConnect\Responses;

class VoidResponse extends Response
{
    protected $_numericFields = [
        'amount',
    ];
}
