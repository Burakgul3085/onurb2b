<?php

namespace App\Enums;

enum VatRate: string
{
    case Zero = '0';
    case One = '1';
    case Ten = '10';
    case Twenty = '20';

    public function label(): string
    {
        return '%'.$this->value;
    }
}
