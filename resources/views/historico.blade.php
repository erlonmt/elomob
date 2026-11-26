@extends('layouts.site')

@section('title', 'Histórico de Viagens')

@section('content')
    <style>
        .historico-header { padding: 60px 8% 30px; text-align: center; }
        .historico-header h1 { font-size: 42px; color: #0f172a; font-weight: 900; letter-spacing: -1px; margin-bottom: 10px; }
        .historico-header p { color: #64748b; font-size: 18px; }

        .historico-container { max-width: 900px; margin: 0 auto 80px; padding: 0 8%; }
        
        .viagem-card {
            background: #fff; border-radius: 20px; padding: 30px; margin-bottom: 20px;
            border: 1px solid #e2e8f0; box-shadow: 0 10px 30px rgba(0,0,0,0.03);
            display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px;
            transition: transform 0.2s;
        }
        .viagem-card:hover { transform: translateY(-5px); box-shadow: 0 15px 40px rgba(0,0,0,0.06); }

        .viagem-info { display: flex; align-items: center; gap: 20px; }
        
        .icone-status {
            width: 50px; height: 50px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 20px; font-weight: bold;
        }
        .icone-status.concluida { background: #ecfdf5; color: #10b981; }
        .icone-status.cancelada { background: #fef2f2; color: #ef4444; }

        .detalhes h3 { font-size: 20px; color: #0f172a; margin-bottom: 6px; font-weight: 800; }
        .detalhes p { color: #64748b; font-size: 15px; margin-bottom: 4px; }
        .detalhes span { font-size: 13px; color: #94a3b8; }

        .viagem-preco { text-align: right; }
        .viagem-preco .valor { font-size: 26px; font-weight: 900; color: #0f172a; }
        .viagem-preco .status { font-size: 13px; font-weight: 700; padding: 4px 10px; border-radius: 20px; margin-top: 8px; display: inline-block; }
        .viagem-preco .status.concluida { background: #ecfdf5; color: #10b981; }
        .viagem-preco .status.cancelada { background: #fef2f2; color: #ef4444; }

        @media (max-width: 768px) {
            .viagem-card { flex-direction: column; align-items: flex-start; }
            .viagem-preco { text-align: left; width: 100%; border-top: 1px solid #e2e8f0; padding-top: 15px; display: flex; justify-content: space-between; align-items: center; }
        }
    </style>

    <div class="historico-header">
        <h1>Histórico de Viagens</h1>
        <p>Acompanhe todas as suas deslocações com o Elomob.</p>
    </div>

    <div class="historico-container">
        
        <!-- Viagem 1 -->
        <div class="viagem-card">
            <div class="viagem-info">
                <div class="icone-status concluida">✔</div>
                <div class="detalhes">
                    <h3>Centro -> Aeroporto Internacional</h3>
                    <p>Metro + Autocarro • 45 min</p>
                    <span>Hoje, 14:30</span>
                </div>
            </div>
            <div class="viagem-preco">
                <div class="valor">R$ 4,50</div>
                <span class="status concluida">Concluída</span>
            </div>
        </div>

        <!-- Viagem 2 -->
        <div class="viagem-card">
            <div class="viagem-info">
                <div class="icone-status concluida">✔</div>
                <div class="detalhes">
                    <h3>Praça Central -> Shopping Recife</h3>
                    <p>Autocarro Direto • 30 min</p>
                    <span>Ontem, 09:15</span>
                </div>
            </div>
            <div class="viagem-preco">
                <div class="valor">R$ 3,20</div>
                <span class="status concluida">Concluída</span>
            </div>
        </div>

        <!-- Viagem 3 -->
        <div class="viagem-card" style="opacity: 0.7;">
            <div class="viagem-info">
                <div class="icone-status cancelada">✕</div>
                <div class="detalhes">
                    <h3>Boa Viagem -> Marco Zero</h3>
                    <p>Bicicleta • 20 min</p>
                    <span>12 de Novembro, 18:00</span>
                </div>
            </div>
            <div class="viagem-preco">
                <div class="valor" style="text-decoration: line-through; color: #94a3b8;">R$ 0,00</div>
                <span class="status cancelada">Cancelada</span>
            </div>
        </div>

        <!-- Viagem 4 -->
        <div class="viagem-card">
            <div class="viagem-info">
                <div class="icone-status concluida">✔</div>
                <div class="detalhes">
                    <h3>Universidade -> Centro</h3>
                    <p>Metro Direto • 25 min</p>
                    <span>10 de Novembro, 17:45</span>
                </div>
            </div>
            <div class="viagem-preco">
                <div class="valor">R$ 4,50</div>
                <span class="status concluida">Concluída</span>
            </div>
        </div>

    </div>
@endsection