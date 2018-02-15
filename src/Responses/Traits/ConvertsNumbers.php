<?php

namespace Dewbud\CardConnect\Responses\Traits;

trait ConvertsNumbers
{
    private function convertNumbers()
    {
        if (!isset($this->_numericFields)) {
            return;
        }

        foreach ($this->_numericFields as $field) {
            if (isset($this->$field) && is_string($this->$field)) {
                $this->$field = (int) preg_replace('/\D/', '', $this->$field);
            }
        }
    }
}
