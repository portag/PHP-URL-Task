<?php

namespace App\Services;

use App\Data\TicketData;
use RuntimeException;

final class TicketCatalogService
{
    public function __construct(
        private TicketPlatformResolver $platformResolver,
        private VividSeatsTicketExtractor $vividSeatsTicketExtractor,
        private SeatGeekTicketExtractor $seatGeekTicketExtractor,
    ) {}

    /**
     * Punto de entrada unico para obtener tickets desde una URL.
     *
     * @return array<TicketData>
     */
    public function fetchTicketsFromUrl(string $eventUrl): array
    {
        // Detecta de que plataforma es la URL.
        $platform = $this->platformResolver->resolve($eventUrl);

        // Distincion de plataformas.
        return match ($platform) {
            'vividseats' => $this->vividSeatsTicketExtractor->fetchTickets($eventUrl),

            // SeatGeek queda conectado igual que VividSeats.
            'seatgeek' => $this->seatGeekTicketExtractor->fetchTickets($eventUrl),

            default => throw new RuntimeException("No hay extractor para la plataforma: {$platform}"),
        };
    }
}