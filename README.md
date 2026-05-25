## Requisitos

- Linux o WSL
- PHP 8.3 o superior
- Composer
- Node.js y npm

## Configuracion del entorno

Este proyecto no incluye el archivo `.env` real. Para configurarlo:

```bash
cp .env.example .env
```

Despues, genera la clave de la aplicacion:

```bash
php artisan key:generate
```

Notas sobre `.env`:

- La variable `SEATGEEK_CLIENT_ID` solo es necesaria si quieres consultar URLs de SeatGeek. Debido a que para acceder a la API se precisa de cuenta y no me han verificado a día de hoy este apartado no está testeado.
- Para URLs de VividSeats no hace falta ninguna credencial adicional.


## Comandos necesarios para que el proyecto funcione

Instalacion completa paso a paso:

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan serve
```

Despues abre la aplicacion en:

```text
http://127.0.0.1:8000
```

