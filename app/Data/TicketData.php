<?php

namespace App\Data;

final readonly class TicketData
{
    public function __construct(
        public string $platform,
        public string $eventUrl,
        public string $section,
        public string $row,
        public float $price,
        public int $quantity,
        public string $currency,
        public ?float $dealScore = null,
        public array $raw = [],
    ) {}
}