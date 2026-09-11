<?php

use App\Support\ListReturn;

if (! function_exists('list_back')) {
    function list_back(string $fallback): string
    {
        return ListReturn::back($fallback);
    }
}

if (! function_exists('list_to')) {
    function list_to(string $name, mixed $params = [], bool $keep = false): string
    {
        return ListReturn::route($name, $params, $keep);
    }
}
