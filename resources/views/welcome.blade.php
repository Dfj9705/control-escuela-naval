<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Control Escuela Naval</title>
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            min-height: 100svh;
            display: grid;
            place-items: center;
            background: #f4f6fa;
            font-family: system-ui, sans-serif;
        }

        main {
            display: grid;
            justify-items: center;
            gap: 2rem;
            padding: 2rem;
        }

        img {
            width: min(13rem, 65vw);
            height: auto;
        }

        a {
            padding: .8rem 1.4rem;
            border-radius: .5rem;
            background: #000080;
            color: #fff;
            font-weight: 600;
            text-decoration: none;
        }

        a:hover {
            background: #2525a0;
        }

        a:focus-visible {
            outline: 3px solid #000080;
            outline-offset: 4px;
        }
    </style>
</head>
<body>
    <main>
        <img src="{{ asset('images/eng_logo.png') }}" alt="Escudo de la Escuela Naval de Guatemala">
        <a href="{{ route('filament.admin.pages.dashboard') }}">Ingresar al sistema</a>
    </main>
</body>
</html>
