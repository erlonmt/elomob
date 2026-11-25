<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ config('app.name', 'Elomob') }}</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800,900&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <style>
            body { 
                font-family: 'Figtree', sans-serif; 
                background: #f1f5f9; 
                color: #1e293b; 
                margin: 0; 
                display: flex; 
                flex-direction: column; 
                min-height: 100vh; 
            }
            
            /* --- HEADER --- */
            header { 
                background: #fff; 
                padding: 18px 8%; 
                display: flex; 
                justify-content: space-between; 
                align-items: center; 
                box-shadow: 0 2px 10px rgba(0,0,0,.05); 
                border-bottom: 1px solid #e2e8f0; 
            }
            .logo { font-size: 32px; font-weight: 900; color: #2563eb; text-decoration: none; letter-spacing: -1px; }
            nav { display: flex; align-items: center; gap: 24px; }
            nav a { text-decoration: none; color: #475569; font-weight: 700; font-size: 16px; transition: color 0.2s; }
            nav a:hover { color: #2563eb; }
            .btn-logout { 
                padding: 10px 20px; 
                background: #fef2f2; 
                color: #ef4444; 
                border: 1px solid #fecaca; 
                border-radius: 10px; 
                font-weight: 700; 
                font-size: 15px; 
                cursor: pointer; 
                transition: all 0.2s; 
                font-family: inherit; 
            }
            .btn-logout:hover { background: #fee2e2; color: #dc2626; }

            /* --- CONTEÚDO PRINCIPAL --- */
            .main-content { 
                padding: 40px 8%; 
                flex: 1; /* Empurra o footer para baixo */
                width: 100%;
                max-width: 1200px;
                margin: 0 auto;
                box-sizing: border-box;
            }

            /* --- FOOTER COMPACTO --- */
            footer { background: #0b1120; color: #94a3b8; padding: 40px 8% 20px; font-size: 14px; margin-top: auto; }
            .footer-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 30px; margin-bottom: 30px; max-width: 1200px; margin-left: auto; margin-right: auto; }
            .footer-grid h5 { color: #fff; margin-bottom: 12px; font-size: 16px; font-weight: 800; }
            .footer-grid p { margin-bottom: 0; line-height: 1.5; color: #94a3b8; }
            .footer-grid a  { display: block; color: #94a3b8; text-decoration: none; margin-bottom: 8px; transition: color 0.2s; font-size: 14px; }
            .footer-grid a:hover { color: #fff; }
            .footer-bottom { border-top: 1px solid #1e293b; padding-top: 20px; text-align: center; color: #64748b; max-width: 1200px; margin: 0 auto; font-size: 13px; }
        </style>
    </head>
    <body class="antialiased">
        
        <!-- HEADER -->
        <header>
            <a href="/" class="logo">Elomob</a>
            <nav>
                <a href="{{ url('/dashboard') }}">Dashboard</a>
                <a href="{{ route('profile.edit') }}">Perfil</a>
                <form method="POST" action="{{ route('logout') }}" style="margin:0;">
                    @csrf
                    <button type="submit" class="btn-logout">Sair</button>
                </form>
            </nav>
        </header>

        <!-- CONTEÚDO (AQUI ENTRA O DASHBOARD E O PERFIL) -->
        <div class="main-content">
            {{ $slot }}
        </div>

        <!-- FOOTER COMPACTO -->
        <footer>
            <div class="footer-grid">
                <div>
                    <h5>Elomob</h5>
                    <p>Mobilidade urbana simples, rápida e acessível.</p>
                </div>
                <div>
                    <h5>Navegação</h5>
                    <a href="{{ route('como-funciona') }}">Como funciona</a>
                    <a href="{{ route('servicos') }}">Serviços</a>
                    <a href="{{ route('porque') }}">Porquê</a>
                </div>
                <div>
                    <h5>Conta</h5>
                    <a href="{{ url('/dashboard') }}">Dashboard</a>
                    <a href="{{ route('profile.edit') }}">O Meu Perfil</a>
                </div>
                <div>
                    <h5>Legal</h5>
                    <a href="#">Privacidade</a>
                    <a href="#">Termos de Uso</a>
                </div>
            </div>
            <div class="footer-bottom">
                © {{ date('Y') }} Elomob — Todos os direitos reservados.
            </div>
        </footer>

    </body>
</html>