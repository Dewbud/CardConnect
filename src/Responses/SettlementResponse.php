<?php

namespace Dewbud\CardConnect\Responses;

class SettlementResponse extends Response
{
    protected $_numericFields = [
        'refundtotal',
        'chargetotal',
    ];

    public function __construct(array $res)
    {
        parent::__construct($res);

        $txns = [];
        foreach ($this->txns as $t) {
            $txns[] = new SettlementTransaction($t);
        }
        $this->txns = $txns;
    }
}
