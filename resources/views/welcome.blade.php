<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Elomob — Mobilidade urbana premium</title>
    
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800,900&display=swap" rel="stylesheet" />

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Figtree', sans-serif; background: #f1f5f9; color: #1e293b; overflow-x: hidden; }

        /* --- HEADER PREMIUM --- */
        header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            padding: 18px 8%;
            display: flex; justify-content: space-between; align-items: center;
            border-bottom: 1px solid #e2e8f0;
            position: sticky; top: 0; z-index: 100;
        }
        .logo { 
            font-size: 32px; font-weight: 900; color: #0f172a; text-decoration: none; 
            display: flex; align-items: center; gap: 10px; letter-spacing: -1px;
        }
        .logo svg { color: #2563eb; width: 34px; height: 34px; }
        
        nav { display: flex; align-items: center; gap: 36px; }
        nav > a { text-decoration: none; color: #475569; font-weight: 700; font-size: 18px; transition: color 0.2s; }
        nav > a:hover { color: #2563eb; }

        .auth-buttons { display: flex; gap: 16px; align-items: center; }
        .btn-outline {
            padding: 12px 24px; border: 2px solid #e2e8f0; color: #0f172a;
            border-radius: 10px; font-weight: 700; text-decoration: none; font-size: 16px; transition: all 0.2s;
            background: #fff;
        }
        .btn-outline:hover { border-color: #2563eb; color: #2563eb; background: #eff6ff; }
        
        .btn-solid {
            padding: 12px 24px; background: #2563eb; color: #fff;
            border-radius: 10px; font-weight: 700; text-decoration: none; font-size: 16px;
            transition: all 0.2s; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        }
        .btn-solid:hover { background: #1d4ed8; transform: translateY(-2px); box-shadow: 0 6px 15px rgba(37, 99, 235, 0.4); }

        .user-menu { display: flex; align-items: center; gap: 20px; }
        .user-profile {
            display: flex; align-items: center; gap: 12px; text-decoration: none;
            padding: 8px 16px 8px 8px; border-radius: 40px; border: 1px solid #e2e8f0;
            background: #fff; transition: all 0.2s;
        }
        .user-profile:hover { background: #f8fafc; border-color: #cbd5e1; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .avatar {
            width: 38px; height: 38px; background: #2563eb; color: #fff;
            border-radius: 50%; display: flex; align-items: center; justify-content: center;
            font-weight: 800; font-size: 16px; text-transform: uppercase;
        }
        .user-name { font-size: 16px; font-weight: 700; color: #0f172a; }
        
        .btn-logout {
            padding: 10px 20px; background: #fef2f2; color: #ef4444;
            border: 1px solid #fecaca; border-radius: 10px; font-weight: 700; font-size: 16px;
            cursor: pointer; transition: all 0.2s; font-family: inherit;
        }
        .btn-logout:hover { background: #fee2e2; color: #dc2626; }

        /* --- HERO PREMIUM --- */
        .hero {
            min-height: 600px; padding: 100px 8%;
            background: #0b1120;
            color: #fff;
            display: flex; flex-direction: column; justify-content: center;
            position: relative; overflow: hidden;
        }
        .hero::before {
            content: ''; position: absolute; top: -20%; left: 50%; transform: translateX(-50%);
            width: 800px; height: 800px; background: radial-gradient(circle, rgba(37,99,235,0.3) 0%, rgba(11,17,32,0) 70%);
            z-index: 0;
        }
        .hero h1 { font-size: 56px; font-weight: 900; line-height: 1.1; max-width: 800px; margin-bottom: 24px; position: relative; z-index: 1; letter-spacing: -1.5px; }
        .hero p  { font-size: 22px; max-width: 650px; margin-bottom: 48px; color: #94a3b8; position: relative; z-index: 1; line-height: 1.6; }

        .search {
            background: #fff; padding: 12px; border-radius: 16px;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5);
            display: grid; grid-template-columns: 1fr 1fr 1fr auto; gap: 12px;
            max-width: 1000px; position: relative; z-index: 1;
        }
        .search input {
            padding: 18px 20px; border: none; background: #f1f5f9;
            border-radius: 10px; font-size: 16px; outline: none;
            font-family: inherit; color: #1e293b; transition: background 0.2s, box-shadow 0.2s;
            font-weight: 500;
        }
        .search input:focus { background: #fff; box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.2); }
        .search button {
            padding: 18px 36px; background: #2563eb; color: #fff;
            border: none; border-radius: 10px; font-weight: 700;
            font-size: 16px; cursor: pointer; font-family: inherit;
            transition: all 0.2s;
        }
        .search button:hover { background: #1d4ed8; }

        /* --- FOOTER --- */
        footer { background: #0b1120; color: #94a3b8; padding: 80px 8% 40px; font-size: 16px; }
        .footer-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 50px; margin-bottom: 60px; max-width: 1200px; margin-left: auto; margin-right: auto; }
        .footer-grid h5 { color: #fff; margin-bottom: 24px; font-size: 20px; font-weight: 800; letter-spacing: -0.5px; }
        .footer-grid a  { display: block; color: #94a3b8; text-decoration: none; margin-bottom: 14px; transition: color 0.2s; }
        .footer-grid a:hover { color: #fff; }
        .footer-bottom { border-top: 1px solid #1e293b; padding-top: 30px; text-align: center; color: #64748b; max-width: 1200px; margin: 0 auto; }

        /* --- MENU MOBILE --- */
        .mobile-menu-btn { display: none; background: none; border: none; cursor: pointer; color: #0f172a; padding: 5px; }
        .menu-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100vh; background: rgba(0,0,0,0.5); z-index: 90; opacity: 0; transition: opacity 0.3s; }
        .menu-overlay.active { display: block; opacity: 1; }

        @media (max-width: 900px) {
            .search { grid-template-columns: 1fr; }
            .hero { padding: 60px 6%; min-height: auto; }
            .hero h1 { font-size: 36px; }
            .hero p { font-size: 18px; margin-bottom: 30px; }
        }

        @media (max-width: 768px) {
            header { padding: 15px 6%; }
            
            /* MOSTRA O BOTÃO HAMBÚRGUER */
            .mobile-menu-btn { display: block !important; }
            
            /* ESCONDE O MENU NORMAL E CRIA A GAVETA */
            nav { 
                position: fixed; top: 0; right: -100%;
                width: 300px; height: 100vh; 
                background: #fff; flex-direction: column; align-items: flex-start;
                padding: 80px 30px 30px; gap: 20px; 
                box-shadow: -5px 0 30px rgba(0,0,0,0.1); 
                z-index: 1000; transition: right 0.3s ease;
            }
            nav.active { right: 0; }
            
            nav > a { font-size: 18px; width: 100%; padding-bottom: 15px; border-bottom: 1px solid #f1f5f9; }
            
            .user-menu, .auth-buttons { flex-direction: column; width: 100%; gap: 15px; margin-top: 20px; }
            .user-profile { width: 100%; justify-content: flex-start; padding: 10px; }
            .btn-outline, .btn-solid, .btn-logout { width: 100%; text-align: center; padding: 15px; }
            .user-name { display: inline; }
            
            .close-menu-btn { display: block; position: absolute; top: 20px; right: 20px; background: none; border: none; font-size: 24px; cursor: pointer; color: #64748b; }
        }
        
        @media (min-width: 769px) {
            .close-menu-btn { display: none; }
        }
    </style>
</head>
<body>

    <!-- Overlay escuro -->
    <div class="menu-overlay" onclick="toggleMenu()"></div>

    <header>
        <a href="/" class="logo">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path>
                <path d="M2 12h20"></path>
            </svg>
            Elomob
        </a>
        
        <!-- BOTÃO HAMBÚRGUER -->
        <button class="mobile-menu-btn" onclick="toggleMenu()">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <line x1="3" y1="12" x2="21" y2="12"></line>
                <line x1="3" y1="6" x2="21" y2="6"></line>
                <line x1="3" y1="18" x2="21" y2="18"></line>
            </svg>
        </button>

        <nav id="mobileNav">
            <button class="close-menu-btn" onclick="toggleMenu()">✕</button>
            
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

    <section class="hero">
        <h1>A nova era da mobilidade urbana</h1>
        <p>Encontre o trajeto perfeito, compare opções em tempo real e viaje com o conforto que você merece.</p>

        <form class="search" method="GET" action="{{ route('resultados') }}">
            <input type="text" name="origem"  placeholder="De onde você está?" value="{{ request('origem') }}" required>
            <input type="text" name="destino" placeholder="Para onde vamos?" value="{{ request('destino') }}" required>
            <input type="date" name="data" value="{{ request('data') }}" required>
            <button type="submit">Pesquisar</button>
        </form>
    </section>

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
                <div>
    <h5>Legal</h5>
    <a href="{{ route('termos') }}">Privacidade</a>
    <a href="{{ route('termos') }}">Termos de Uso</a>
</div>
            </div>
        </div>
        <div class="footer-bottom">
            © {{ date('Y') }} Elomob — Todos os direitos reservados.
        </div>
    </footer>

    <script>
        function toggleMenu() {
            const nav = document.getElementById('mobileNav');
            const overlay = document.querySelector('.menu-overlay');
            nav.classList.toggle('active');
            overlay.classList.toggle('active');
        }
    </script>

</body>
</html>