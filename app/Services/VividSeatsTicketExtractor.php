<?php

namespace App\Services;

use App\Data\TicketData;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use RuntimeException;

/**
 * Extrae el inventario de VividSeats y lo adapta al formato comun de la aplicacion.
 */
final class VividSeatsTicketExtractor
{
    /**
     * Convierte la URL de un evento de VividSeats en una lista normalizada de tickets.
     *
     * @return array<TicketData>
     */
    public function fetchTickets(string $eventUrl): array
    {
        $productionId = $this->extractProductionId($eventUrl);

        // Carga la pagina publica para recuperar el contexto que luego usa la API interna.
        $pageResponse = Http::timeout(15)->get($eventUrl);

        if ($pageResponse->failed()) {
            throw new RuntimeException('No se pudo cargar la pagina del evento de VividSeats.');
        }

        $pageHtml = $pageResponse->body();

        // Contextualiza el inventario que devuelve el endpoint interno.
        $priceGroupId = $this->extractPriceGroupId($pageHtml);

        // API interna que la pagina usa para cargar los listings.
        $query = [
            'productionId' => $productionId,
            'includeIpAddress' => 'true',
            'currency' => 'USD',
            'localizeCurrency' => 'true',
        ];

        // Solo enviamos el priceGroupId si realmente aparece en el HTML.
        if ($priceGroupId !== null) {
            $query['priceGroupId'] = $priceGroupId;
        }

        $response = Http::acceptJson()
            ->timeout(15)
            ->get('https://www.vividseats.com/hermes/api/v1/listings', $query);

        if ($response->failed()) {
            throw new RuntimeException('No se pudieron obtener las entradas de VividSeats.');
        }

        $payload = $response->json();

        if (! is_array($payload)) {
            throw new RuntimeException('La respuesta de VividSeats no es un JSON valido.');
        }

        $tickets = $payload['tickets'] ?? null;
        $currency = $payload['i18n']['to'] ?? 'USD';

        if (! is_array($tickets)) {
            throw new RuntimeException('La respuesta de VividSeats no contiene tickets.');
        }

        $normalizedTickets = [];

        foreach ($tickets as $ticket) {
            if (! is_array($ticket)) {
                continue;
            }

            // Adaptamos cada listing al formato comun que consume el controlador.
            $normalizedTickets[] = $this->mapTicket(
                eventUrl: $eventUrl,
                ticket: $ticket,
                currency: (string) $currency,
            );
        }

        return $normalizedTickets;
    }

    private function extractProductionId(string $eventUrl): string
    {
        $path = parse_url($eventUrl, PHP_URL_PATH);

        if (! is_string($path) || $path === '') {
            throw new InvalidArgumentException('La URL de VividSeats no es valida.');
        }

        // En VividSeats el id del evento viene en la ruta: /production/{id}
        if (! preg_match('#/production/(\d+)#', $path, $matches)) {
            throw new InvalidArgumentException('No se pudo extraer el productionId desde la URL.');
        }

        return $matches[1];
    }

    private function extractPriceGroupId(string $pageHtml): ?string
    {
        // Este valor suele aparecer en la telemetria de la propia pagina como ep.price_group=21
        if (preg_match('/ep\.price_group=([0-9]+)/', $pageHtml, $matches) === 1) {
            return $matches[1];
        }

        return null;
    }

    /**
     * El payload puede mezclar nombres largos y claves abreviadas segun la variante
     * del endpoint que haya devuelto VividSeats.
     */
    private function mapTicket(string $eventUrl, array $ticket, string $currency): TicketData
    {
        $section = (string) ($ticket['sectionName'] ?? $ticket['s'] ?? '');
        $row = (string) ($ticket['row'] ?? $ticket['r'] ?? '');
        $price = (float) ($ticket['allInPricePerTicket'] ?? $ticket['p'] ?? 0);
        $quantity = (int) ($ticket['quantity'] ?? $ticket['q'] ?? 0);

        $dealScore = null;

        if (isset($ticket['dealScore']) || isset($ticket['vs'])) {
            $dealScore = (float) ($ticket['dealScore'] ?? $ticket['vs']);
        }

        return new TicketData(
            platform: 'vividseats',
            eventUrl: $eventUrl,
            section: $section,
            row: $row,
            price: $price,
            quantity: $quantity,
            currency: $currency,
            dealScore: $dealScore,
            raw: $ticket,
        );
    }
}