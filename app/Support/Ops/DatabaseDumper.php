<?php

namespace App\Support\Ops;

interface DatabaseDumper
{
    public function dump(string $path): void;
}
