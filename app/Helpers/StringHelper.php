<?php

namespace App\Helpers;

class StringHelper
{
    public static function normalizeWarehouseName($name)
    {
        $name = mb_strtolower($name, 'UTF-8');
        $name = iconv('UTF-8', 'ASCII//TRANSLIT', $name);
        return preg_replace('/[^a-z0-9]/', '', $name);
    }
}
