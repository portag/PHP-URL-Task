<?php

namespace App\Http\Controllers;

use App\Data\TicketData;
use App\Services\TicketCatalogService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use InvalidArgumentException;
use RuntimeException;

final class TicketCatalogController extends Controller
{
    public function index(Request $request, TicketCatalogService $ticketCatalogService): View
    {
        // Valores preparados para que la pagina pueda cargarse vacia
        $eventUrl = (string) $request->query('url', '');
        $groupedTickets = collect();
        $error = null;

        // Si todavia no han enviado una URL, solo muestra el formulario
        if ($eventUrl === '') {
            return view('tickets.tickets', [
                'eventUrl' => $eventUrl,
                'groupedTickets' => $groupedTickets,
                'error' => $error,
            ]);
        }

        // Valida URL 
        $validated = $request->validate([
            'url' => ['required', 'url'],
        ]);

        $eventUrl = $validated['url'];

        try {
            $tickets = $ticketCatalogService->fetchTicketsFromUrl($eventUrl);

            // Orden por sector, fila y precio.
            $sortedTickets = collect($tickets)
                ->sort(function (TicketData $left, TicketData $right): int {
                    return [$left->section, $left->row, $left->price]
                        <=> [$right->section, $right->row, $right->price];
                })
                ->values();

            $groupedTickets = $sortedTickets
                ->groupBy(fn (TicketData $ticket) => $ticket->section)
                ->map(fn ($sectionTickets) => $sectionTickets
                    ->groupBy(fn (TicketData $ticket) => $ticket->row)
                    ->map(fn ($rowTickets) => $rowTickets
                        ->groupBy(fn (TicketData $ticket) => number_format($ticket->price, 2, '.', ''))
                    )
                );
        } catch (RuntimeException | InvalidArgumentException $exception) {
            $error = $exception->getMessage();
        }

        return view('tickets.tickets', [
            'eventUrl' => $eventUrl,
            'groupedTickets' => $groupedTickets,
            'error' => $error,
        ]);
    }
}