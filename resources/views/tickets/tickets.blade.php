<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Entradas por evento</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            background: #f5f7fb;
            color: #1f2937;
        }

        main {
            max-width: 1100px;
            margin: 0 auto;
            padding: 32px 20px 48px;
        }

        h1, h2, h3, h4, h5 {
            margin-top: 0;
        }

        form {
            display: grid;
            gap: 12px;
            margin-bottom: 24px;
            padding: 20px;
            background: #ffffff;
            border: 1px solid #dbe1ea;
            border-radius: 12px;
        }

        input[type="url"] {
            padding: 12px;
            border: 1px solid #c7d2e0;
            border-radius: 8px;
            font-size: 14px;
        }

        button {
            width: fit-content;
            padding: 12px 18px;
            border: 0;
            border-radius: 8px;
            background: #1d4ed8;
            color: #ffffff;
            cursor: pointer;
        }

        .panel {
            margin-bottom: 20px;
            padding: 16px 18px;
            border-radius: 12px;
            background: #ffffff;
            border: 1px solid #dbe1ea;
        }

        .error {
            background: #fef2f2;
            border-color: #fecaca;
            color: #991b1b;
        }

        .sector {
            margin-bottom: 24px;
            padding: 20px;
            background: #ffffff;
            border: 1px solid #dbe1ea;
            border-radius: 12px;
        }

        .row-block {
            margin-top: 16px;
        }

        .price-block {
            margin-top: 12px;
            padding: 12px;
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th, td {
            padding: 10px 12px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }

        th {
            background: #eef4ff;
        }
    </style>
</head>
<body>
    <main>
        <h1>Consulta de entradas</h1>
        <p>Pega una URL de VividSeats o SeatGeek para ver las entradas agrupadas por sector, fila y precio.</p>

        {{-- El formulario usa GET para que la URL del resultado se pueda compartir facilmente. --}}
        <form method="GET" action="{{ route('tickets.index') }}">
            <label for="url">URL del evento</label>
            <input
                id="url"
                name="url"
                type="url"
                value="{{ old('url', $eventUrl) }}"
                placeholder="https://www.vividseats.com/..."
                required
            >
            <button type="submit">Buscar entradas</button>
        </form>

        @if ($errors->any())
            <div class="panel error">
                {{-- Mensajes de error de validación del Request. --}}
                @foreach ($errors->all() as $message)
                    <p>{{ $message }}</p>
                @endforeach
            </div>
        @endif

        @if ($error)
            <div class="panel error">
                {{-- Mensaje de error en caso de que la plataforma falle. --}}
                <p>{{ $error }}</p>
            </div>
        @endif

        @if ($groupedTickets->isNotEmpty())
            <h2>Resultados</h2>

            @foreach ($groupedTickets as $section => $rows)
                <section class="sector">
                    <h3>Sector: {{ $section }}</h3>

                    @foreach ($rows as $row => $prices)
                        <div class="row-block">
                            <h4>Fila: {{ $row }}</h4>

                            @foreach ($prices as $price => $tickets)
                                <div class="price-block">
                                    <h5>Precio: {{ $price }} {{ $tickets->first()->currency ?? '' }}</h5>

                                    <table>
                                        <thead>
                                            <tr>
                                                <th>Cantidad</th>
                                                <th>Moneda</th>
                                                <th>Deal Score</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($tickets as $ticket)
                                                <tr>
                                                    <td>{{ $ticket->quantity }}</td>
                                                    <td>{{ $ticket->currency }}</td>
                                                    <td>
                                                        {{ $ticket->dealScore !== null ? number_format($ticket->dealScore, 1) : 'N/A' }}
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </section>
            @endforeach
        @elseif ($eventUrl !== '' && ! $error && ! $errors->any())
            <div class="panel">
                <p>No se encontraron entradas para esta URL.</p>
            </div>
        @endif
    </main>
</body>
</html>