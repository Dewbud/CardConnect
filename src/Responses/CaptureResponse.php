<?php

namespace Dewbud\CardConnect\Responses;

class CaptureResponse extends Response
{
    protected $_numericFields = [
        'amount',
    ];
}
