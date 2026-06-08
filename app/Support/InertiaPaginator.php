<?php

namespace App\Support;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class InertiaPaginator
{
    /**
     * Normalize a Laravel paginator for Inertia/Vue ({ data, links, meta }).
     *
     * @return array{data: mixed, links: array<int, array<string, mixed>>, meta: array<string, mixed>}
     */
    public static function present(LengthAwarePaginator $paginator): array
    {
        $array = $paginator->toArray();

        return [
            'data' => $array['data'],
            'links' => $array['links'],
            'meta' => [
                'current_page' => $array['current_page'],
                'from' => $array['from'],
                'last_page' => $array['last_page'],
                'path' => $array['path'],
                'per_page' => $array['per_page'],
                'to' => $array['to'],
                'total' => $array['total'],
            ],
        ];
    }
}
