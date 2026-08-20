<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <title>Chill Drink - Premium Beverages</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
        <style>
            html { background: #e8eeec; }
            body {
                margin: 0 auto;
                width: 100%;
                max-width: 480px;
                min-height: 100vh;
                overflow-x: hidden;
                background: #f4f7f6;
                box-shadow: 0 0 40px rgba(15, 71, 54, .12);
            }
            @media (max-width: 480px) {
                html { background: #f4f7f6; }
                body { box-shadow: none; }
            }
        </style>
    </head>
    <body class="font-sans antialiased">
        <x-animated-slider />
    </body>
</html>
