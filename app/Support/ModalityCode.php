<?php

namespace App\Support;

use Illuminate\Support\Str;

class ModalityCode
{
    public static function fromName(string $name): string
    {
        $ascii = Str::ascii($name);
        $slug = Str::slug($ascii, '_');
        $code = strtolower((string) preg_replace('/[^a-z0-9_]+/', '', str_replace('-', '_', $slug)));
        $code = trim($code, '_');

        return $code !== '' ? $code : 'modality';
    }

    public static function normalize(string $code): string
    {
        $normalized = strtolower((string) preg_replace('/[^a-z0-9_]+/', '', str_replace('-', '_', $code)));
        $normalized = trim($normalized, '_');

        return $normalized !== '' ? $normalized : 'modality';
    }
}
