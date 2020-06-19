<?php

namespace Dewbud\CardConnect\Responses\Traits;

/**
 * @property bool $success
 */
trait ChecksSuccess
{
    public function success()
    {
        return 'A' === $this->respstat;
    }
}
