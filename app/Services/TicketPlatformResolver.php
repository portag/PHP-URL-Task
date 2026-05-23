<?php

namespace App\Services;

use InvalidArgumentException;

final class TicketPlatformResolver
{
    public function resolve(string $url): string
    {
        $host = parse_url($url, PHP_URL_HOST);

        if (! is_string($host) || $host === '') {
            throw new InvalidArgumentException('La URL no es valida.');
        }

        $host = strtolower($host);

        if (str_starts_with($host, 'www.')) {
            $host = substr($host, 4);
        }

        return match ($host) {
            'vividseats.com' => 'vividseats',
            'seatgeek.com' => 'seatgeek',
            default => throw new InvalidArgumentException("Plataforma no soportada: {$host}"),
        };
    }
}