<?php

namespace Dewbud\CardConnect\Responses\Traits;

/**
 * @property boolean $success
 */
trait ChecksSuccess
{
    public function success()
    {
        return $this->respstat == 'A';
    }
}
