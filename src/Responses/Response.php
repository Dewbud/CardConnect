<?php

namespace Dewbud\CardConnect\Responses;

use Dewbud\CardConnect\Responses\Traits\ChecksSuccess;
use Dewbud\CardConnect\Responses\Traits\ConvertsNumbers;

class Response extends Fluent
{
    use ChecksSuccess, ConvertsNumbers;
    public function __construct(array $res)
    {
        parent::__construct($res);
        $this->convertNumbers();
    }
}
