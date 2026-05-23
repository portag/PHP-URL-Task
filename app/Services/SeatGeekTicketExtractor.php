<?php

namespace App\Services;

use App\Data\TicketData;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use RuntimeException;

/**
 * Extrae el inventario de SeatGeek y lo adapta al formato comun de la aplicacion.
 *
 * Precisa de una clave para acceder a la API
 */
final class SeatGeekTicketExtractor
{
    /**
     * Convierte la URL de un evento de SeatGeek en una lista normalizada de tickets.
     *
     * @return array<TicketData>
     */
    public function fetchTickets(string $eventUrl): array
    {
        $eventId = $this->extractEventId($eventUrl);

        // Pedimos el inventario directamente al endpoint de listings para no depender
        // del HTML renderizado ni de estructuras internas de la pagina publica.
        $response = Http::acceptJson()
            ->timeout(15)
            ->get('https://seatgeek.com/listings', [
                'id' => $eventId,
                'client_id' => $this->resolveClientId(),
            ]);

        if ($response->failed()) {
            throw new RuntimeException(
                'No se pudieron obtener las entradas de SeatGeek. HTTP '.$response->status()
            );
        }

        $payload = $response->json();

        if (! is_array($payload)) {
            throw new RuntimeException('La respuesta de SeatGeek no es un JSON valido.');
        }

        $tickets = $payload['listings'] ?? null;

        if (! is_array($tickets)) {
            throw new RuntimeException('La respuesta de SeatGeek no contiene listings.');
        }

        $currency = (string) (
            $payload['currency']
            ?? $payload['meta']['currency']
            ?? $payload['meta']['currency_code']
            ?? 'USD'
        );

        $normalizedTickets = [];

        foreach ($tickets as $ticket) {
            if (! is_array($ticket)) {
                continue;
            }

            $normalizedTickets[] = $this->mapTicket(
                eventUrl: $eventUrl,
                ticket: $ticket,
                currency: $currency,
            );
        }

        return array_values(array_filter(
            $normalizedTickets,
            fn (TicketData $ticket): bool => $ticket->section !== '' && $ticket->price > 0,
        ));
    }

    
    //SeatGeek suele colocar el identificador numerico del evento al final de la URL.
   
    private function extractEventId(string $eventUrl): string
    {
        $path = parse_url($eventUrl, PHP_URL_PATH);

        if (! is_string($path) || $path === '') {
            throw new InvalidArgumentException('La URL de SeatGeek no es valida.');
        }

        if (! preg_match('#/(\d+)/?$#', $path, $matches)) {
            throw new InvalidArgumentException('No se pudo extraer el eventId desde la URL de SeatGeek.');
        }

        return $matches[1];
    }

    
    // Lee el client_id desde la configuracion para no dejar credenciales
    private function resolveClientId(): string
    {
        $clientId = config('services.seatgeek.client_id');

        if (! is_string($clientId) || $clientId === '') {
            throw new RuntimeException('Falta configurar services.seatgeek.client_id para SeatGeek.');
        }

        return $clientId;
    }

    /**
     * SeatGeek ha usado tanto claves legibles como abreviadas en distintos payloads,
     * por eso soportamos ambas variantes al normalizar el ticket.
     */
    private function mapTicket(string $eventUrl, array $ticket, string $currency): TicketData
    {
        $section = (string) ($ticket['section'] ?? $ticket['sectionName'] ?? $ticket['s'] ?? '');
        $row = (string) ($ticket['row'] ?? $ticket['rowName'] ?? $ticket['r'] ?? '');
        $price = (float) ($ticket['displayPrice'] ?? $ticket['finalPrice'] ?? $ticket['pf'] ?? $ticket['price'] ?? $ticket['p'] ?? 0);
        $quantity = (int) ($ticket['quantity'] ?? $ticket['q'] ?? 0);

        $dealScore = null;

        if (isset($ticket['dealScore']) || isset($ticket['dq'])) {
            $dealScore = (float) ($ticket['dealScore'] ?? $ticket['dq']);
        }

        return new TicketData(
            platform: 'seatgeek',
            eventUrl: $eventUrl,
            section: $section,
            row: $row !== '' ? $row : 'N/A',
            price: $price,
            quantity: $quantity,
            currency: $currency,
            dealScore: $dealScore,
            raw: $ticket,
        );
    }
}