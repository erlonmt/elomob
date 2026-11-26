<x-app-layout>
    <div style="max-width: 1000px; margin: 0 auto;">
        
        <!-- Cabeçalho de Boas-Vindas -->
        <div style="background: #0b1120; border-radius: 24px; padding: 50px 40px; color: #fff; margin-bottom: 40px; position: relative; overflow: hidden; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 30px;">
            <div style="position: absolute; top: -50%; right: -10%; width: 400px; height: 400px; background: radial-gradient(circle, rgba(37,99,235,0.4) 0%, rgba(11,17,32,0) 70%); border-radius: 50%;"></div>
            
            <div style="position: relative; z-index: 1; max-width: 500px;">
                <h1 style="font-size: 36px; font-weight: 900; margin-bottom: 10px; letter-spacing: -1px;">Olá, {{ Auth::user()->name }}!</h1>
                <p style="color: #94a3b8; font-size: 18px; margin-bottom: 0;">Para onde vamos hoje? A cidade é o seu limite.</p>
            </div>

            <!-- Barra de Pesquisa Rápida -->
            <form method="GET" action="{{ route('resultados') }}" style="display: flex; flex-direction: column; gap: 10px; background: #fff; padding: 15px; border-radius: 16px; width: 100%; max-width: 400px; position: relative; z-index: 1; box-shadow: 0 10px 30px rgba(0,0,0,0.2);">
                <div style="display: flex; gap: 10px;">
                    <input type="text" name="origem" placeholder="Origem" required style="flex: 1; padding: 14px; border: none; background: #f1f5f9; border-radius: 8px; outline: none; font-family: inherit;">
                    <input type="text" name="destino" placeholder="Destino" required style="flex: 1; padding: 14px; border: none; background: #f1f5f9; border-radius: 8px; outline: none; font-family: inherit;">
                </div>
                <div style="display: flex; gap: 10px;">
                    <input type="date" name="data" required style="flex: 1; padding: 14px; border: none; background: #f1f5f9; border-radius: 8px; outline: none; font-family: inherit; color: #1e293b;">
                    <button type="submit" style="padding: 14px 30px; background: #2563eb; color: #fff; border: none; border-radius: 8px; font-weight: 700; cursor: pointer; transition: background 0.2s;">Pesquisar</button>
                </div>
            </form>
        </div>

        <!-- Título da Secção -->
        <h2 style="font-size: 24px; font-weight: 800; color: #0f172a; margin-bottom: 24px;">Acesso Rápido</h2>

        <!-- Grid de Cartões -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px;">
            
            <!-- Cartão 1 -->
            <div style="background: #fff; border-radius: 20px; padding: 40px; border: 1px solid #e2e8f0; border-top: 4px solid #2563eb; box-shadow: 0 10px 30px rgba(0,0,0,0.03); transition: transform 0.2s;">
                <div style="width: 60px; height: 60px; background: #eff6ff; border-radius: 16px; display: flex; align-items: center; justify-content: center; margin-bottom: 24px;">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                </div>
                <h3 style="font-size: 22px; font-weight: 800; color: #0f172a; margin-bottom: 10px;">Planear Viagem</h3>
                <p style="color: #64748b; line-height: 1.6; margin-bottom: 24px;">Encontre o melhor trajeto com opções de transporte e preços atualizados.</p>
                <a href="{{ route('resultados') }}" style="display: inline-block; background: #2563eb; color: #fff; padding: 12px 24px; border-radius: 8px; text-decoration: none; font-weight: 700;">Nova Pesquisa</a>
            </div>

            <!-- Cartão 2 -->
            <div style="background: #fff; border-radius: 20px; padding: 40px; border: 1px solid #e2e8f0; border-top: 4px solid #10b981; box-shadow: 0 10px 30px rgba(0,0,0,0.03); transition: transform 0.2s;">
                <div style="width: 60px; height: 60px; background: #ecfdf5; border-radius: 16px; display: flex; align-items: center; justify-content: center; margin-bottom: 24px;">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                </div>
                <h3 style="font-size: 22px; font-weight: 800; color: #0f172a; margin-bottom: 10px;">O Meu Perfil</h3>
                <p style="color: #64748b; line-height: 1.6; margin-bottom: 24px;">Mantenha os seus dados atualizados para uma experiência personalizada.</p>
                <a href="{{ route('profile.edit') }}" style="display: inline-block; background: #10b981; color: #fff; padding: 12px 24px; border-radius: 8px; text-decoration: none; font-weight: 700;">Editar Perfil</a>
            </div>

            <!-- Cartão 3 -->
            <div style="background: #fff; border-radius: 20px; padding: 40px; border: 1px solid #e2e8f0; border-top: 4px solid #8b5cf6; box-shadow: 0 10px 30px rgba(0,0,0,0.03); transition: transform 0.2s;">
                <div style="width: 60px; height: 60px; background: #f5f3ff; border-radius: 16px; display: flex; align-items: center; justify-content: center; margin-bottom: 24px;">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#8b5cf6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                </div>
                <h3 style="font-size: 22px; font-weight: 800; color: #0f172a; margin-bottom: 10px;">Histórico</h3>
                <p style="color: #64748b; line-height: 1.6; margin-bottom: 24px;">Consulte as suas viagens anteriores e os comprovativos de pagamento.</p>
                <!-- BOTÃO CORRIGIDO AQUI -->
                <a href="{{ route('historico') }}" style="display: inline-block; background: #8b5cf6; color: #fff; padding: 12px 24px; border-radius: 8px; text-decoration: none; font-weight: 700;">Ver Histórico</a>
            </div>

        </div>
    </div>
</x-app-layout>