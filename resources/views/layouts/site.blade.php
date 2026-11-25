<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Elomob — @yield('title')</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800,900&display=swap" rel="stylesheet" />
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Figtree', sans-serif; background: #f1f5f9; color: #1e293b; overflow-x: hidden; }
        
        header { background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); padding: 18px 8%; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0; position: sticky; top: 0; z-index: 100; }
        .logo { font-size: 32px; font-weight: 900; color: #0f172a; text-decoration: none; display: flex; align-items: center; gap: 10px; letter-spacing: -1px; }
        .logo svg { color: #2563eb; width: 34px; height: 34px; }
        
        nav { display: flex; align-items: center; gap: 36px; }
        nav > a { text-decoration: none; color: #475569; font-weight: 700; font-size: 18px; transition: color 0.2s; }
        nav > a:hover { color: #2563eb; }

        .auth-buttons { display: flex; gap: 16px; align-items: center; }
        .btn-outline { padding: 12px 24px; border: 2px solid #e2e8f0; color: #0f172a; border-radius: 10px; font-weight: 700; text-decoration: none; font-size: 16px; transition: all 0.2s; background: #fff; }
        .btn-outline:hover { border-color: #2563eb; color: #2563eb; background: #eff6ff; }
        .btn-solid { padding: 12px 24px; background: #2563eb; color: #fff; border-radius: 10px; font-weight: 700; text-decoration: none; font-size: 16px; transition: all 0.2s; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3); }
        .btn-solid:hover { background: #1d4ed8; transform: translateY(-2px); box-shadow: 0 6px 15px rgba(37, 99, 235, 0.4); }

        .user-menu { display: flex; align-items: center; gap: 20px; }
        .user-profile { display: flex; align-items: center; gap: 12px; text-decoration: none; padding: 8px 16px 8px 8px; border-radius: 40px; border: 1px solid #e2e8f0; background: #fff; transition: all 0.2s; }
        .user-profile:hover { background: #f8fafc; border-color: #cbd5e1; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .avatar { width: 38px; height: 38px; background: #2563eb; color: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 16px; text-transform: uppercase; }
        .user-name { font-size: 16px; font-weight: 700; color: #0f172a; }
        .btn-logout { padding: 10px 20px; background: #fef2f2; color: #ef4444; border: 1px solid #fecaca; border-radius: 10px; font-weight: 700; font-size: 16px; cursor: pointer; transition: all 0.2s; font-family: inherit; }
        .btn-logout:hover { background: #fee2e2; color: #dc2626; }

        /* --- CSS DAS PÁGINAS --- */
        .page-header { padding: 100px 8% 40px; text-align: center; }
        .page-header h1 { font-size: 48px; color: #0f172a; font-weight: 900; margin-bottom: 16px; letter-spacing: -1px; }
        .page-header p { color: #64748b; font-size: 20px; }
        section { padding: 40px 8% 120px; }

        .steps, .service-grid, .why-grid { display: grid; gap: 40px; max-width: 1200px; margin: 0 auto; }
        .steps { grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); }
        .service-grid { grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); }
        .why-grid { grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 30px; }

        .step, .service, .why { background: #fff; border-radius: 24px; border: 1px solid #e2e8f0; box-shadow: 0 10px 40px rgba(0,0,0,0.03); transition: transform 0.3s, box-shadow 0.3s; }
        .step:hover, .service:hover, .why:hover { transform: translateY(-10px); box-shadow: 0 20px 40px rgba(0,0,0,0.08); border-color: #cbd5e1; }
        
        .step { padding: 50px 40px; text-align: center; }
        .step .num { width: 70px; height: 70px; margin: 0 auto 24px; background: #eff6ff; color: #2563eb; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 28px; font-weight: 900; box-shadow: 0 0 0 8px #f8fafc; }
        .step h3 { margin-bottom: 16px; color: #0f172a; font-size: 24px; font-weight: 800; }
        .step p { color: #64748b; line-height: 1.7; font-size: 17px; }

        .service { padding: 50px 40px; }
        .service .icon { font-size: 48px; margin-bottom: 24px; }
        .service h3 { margin-bottom: 16px; color: #0f172a; font-size: 24px; font-weight: 800; }
        .service p { color: #64748b; line-height: 1.7; font-size: 17px; }

        .why { padding: 40px 30px; border-top: 4px solid transparent; }
        .why:hover { border-top-color: #2563eb; }
        .why h4 { color: #0f172a; margin-bottom: 12px; font-size: 20px; font-weight: 800; }
        .why p { color: #64748b; line-height: 1.7; font-size: 16px; }

        footer { background: #0b1120; color: #94a3b8; padding: 80px 8% 40px; font-size: 16px; }
        .footer-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 50px; margin-bottom: 60px; max-width: 1200px; margin-left: auto; margin-right: auto; }
        .footer-grid h5 { color: #fff; margin-bottom: 24px; font-size: 20px; font-weight: 800; letter-spacing: -0.5px; }
        .footer-grid a  { display: block; color: #94a3b8; text-decoration: none; margin-bottom: 14px; transition: color 0.2s; }
        .footer-grid a:hover { color: #fff; }
        .footer-bottom { border-top: 1px solid #1e293b; padding-top: 30px; text-align: center; color: #64748b; max-width: 1200px; margin: 0 auto; }

        /* --- MENU MOBILE --- */
        .mobile-menu-btn { display: none; background: none; border: none; cursor: pointer; color: #0f172a; }
        
        @media (max-width: 768px) {
            header { padding: 15px 6%; }
            .mobile-menu-btn { display: block; }
            
            /* Esconde o menu normal e o transforma em dropdown */
            nav { 
                position: absolute; top: 70px; left: 0; width: 100%; 
                background: #fff; flex-direction: column; gap: 15px; 
                padding: 20px; box-shadow: 0 10px 20px rgba(0,0,0,0.1);
                display: none; /* Escondido por padrão */
            }
            nav.active { display: flex; } /* Mostra quando ativo */
            
            nav > a { font-size: 16px; width: 100%; text-align: center; padding: 10px 0; border-bottom: 1px solid #f1f5f9; }
            .user-menu, .auth-buttons { flex-direction: column; width: 100%; gap: 10px; }
            .user-profile, .btn-outline, .btn-solid, .btn-logout { width: 100%; justify-content: center; text-align: center; }
            .user-name { display: inline; }
            
            .page-header h1 { font-size: 36px; }
        }
    </style>
</head>
<body>

    <header>
        <a href="/" class="logo">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path>
                <path d="M2 12h20"></path>
            </svg>
            Elomob
        </a>
        
        <!-- Botão de Hambúrguer (só aparece no telemóvel) -->
        <button class="mobile-menu-btn" onclick="document.querySelector('nav').classList.toggle('active')">
            <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="3" y1="12" x2="21" y2="12"></line>
                <line x1="3" y1="6" x2="21" y2="6"></line>
                <line x1="3" y1="18" x2="21" y2="18"></line>
            </svg>
        </button>

        <nav>
            <a href="{{ route('como-funciona') }}">Como funciona</a>
            <a href="{{ route('servicos') }}">Serviços</a>
            <a href="{{ route('porque') }}">Porquê</a>
            
            @auth
                <div class="user-menu">
                    <a href="{{ url('/dashboard') }}" class="user-profile">
                        <div class="avatar">{{ substr(Auth::user()->name, 0, 1) }}</div>
                        <span class="user-name">{{ Auth::user()->name }}</span>
                    </a>
                    <form method="POST" action="{{ route('logout') }}" style="margin:0; width: 100%;">
                        @csrf
                        <button type="submit" class="btn-logout">Sair</button>
                    </form>
                </div>
            @else
                <div class="auth-buttons">
                    <a href="{{ route('login') }}" class="btn-outline">Entrar</a>
                    <a href="{{ route('register') }}" class="btn-solid">Criar conta</a>
                </div>
            @endauth
        </nav>
    </header>

    <!-- AQUI ENTRA O CONTEÚDO DE CADA PÁGINA -->
    @yield('content')

    <footer>
        <div class="footer-grid">
            <div>
                <h5>Elomob</h5>
                <p>Mobilidade urbana simples, rápida e acessível para todos.</p>
            </div>
            <div>
                <h5>Navegação</h5>
                <a href="{{ route('como-funciona') }}">Como funciona</a>
                <a href="{{ route('servicos') }}">Serviços</a>
                <a href="{{ route('porque') }}">Porquê</a>
            </div>
            <div>
                <h5>Conta</h5>
                @auth
                    <a href="{{ url('/dashboard') }}">Dashboard</a>
                @else
                    <a href="{{ route('login') }}">Entrar</a>
                    <a href="{{ route('register') }}">Criar conta</a>
                @endauth
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