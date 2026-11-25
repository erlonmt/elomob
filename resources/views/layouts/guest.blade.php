<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Elomob') }}</title>

        <!-- Fontes -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts do Breeze/Vite -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        
        <!-- CSS customizado para o Elomob -->
        <style>
            body {
                background-color: #f5f7fa;
                font-family: 'Figtree', sans-serif;
                color: #222;
                margin: 0;
                padding: 0;
            }
            .elomob-card {
                background: #ffffff;
                border-radius: 14px;
                box-shadow: 0 10px 30px rgba(0,0,0,.08);
                padding: 40px;
                width: 100%;
                max-width: 420px;
                margin-top: 30px;
            }
            
            /* Sobrescrevendo o visual dos inputs do Breeze */
            input[type="text"], 
            input[type="email"], 
            input[type="password"] {
                width: 100%;
                padding: 12px 16px;
                border: 1px solid #cbd5e1;
                border-radius: 8px;
                font-size: 15px;
                outline: none;
                transition: border-color 0.2s ease, box-shadow 0.2s ease;
                box-sizing: border-box;
            }
            input[type="text"]:focus, 
            input[type="email"]:focus, 
            input[type="password"]:focus {
                border-color: #2563eb;
                box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
            }
            label {
                font-weight: 600;
                font-size: 14px;
                color: #333;
                margin-bottom: 6px;
                display: block;
            }
            
            /* Botão primário do Breeze */
            .btn-primary {
                background-color: #2563eb !important;
                color: #ffffff !important;
                padding: 12px 24px !important;
                border-radius: 8px !important;
                font-weight: bold !important;
                font-size: 15px !important;
                border: none !important;
                cursor: pointer !important;
                transition: background-color 0.2s ease !important;
                text-transform: uppercase;
            }
            .btn-primary:hover {
                background-color: #1d4ed8 !important;
            }
            
            /* Links */
            a {
                color: #2563eb;
                text-decoration: none;
                font-size: 14px;
            }
            a:hover {
                text-decoration: underline;
            }
        </style>
    </head>
    <body class="antialiased">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0">
            
            <!-- Logo do Elomob (Substituindo o do Laravel) -->
            <div>
                <a href="/" style="font-size: 32px; font-weight: bold; color: #2563eb; text-decoration: none;">
                    Elomob
                </a>
            </div>

            <!-- Cartão Branco Central -->
            <div class="elomob-card">
                {{ $slot }}
            </div>
            
        </div>
    </body>
</html>