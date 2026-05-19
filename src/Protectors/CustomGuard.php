<?php

namespace Tenseg\CascadingEntryProtection\Protectors;
use Illuminate\Support\Facades\Log;

class CustomGuard
{
    protected string|null $pagePassword;

    public function __construct(string|null $pagePassword)
    {
        $this->pagePassword = $pagePassword;
    }

    public function check(string|null $password)
    {
        $allowed = $this->pagePassword;
        Log::debug("CEP guard checking $password == $allowed");
        if ($password === $allowed) {
            return true;
        }
    }
}
