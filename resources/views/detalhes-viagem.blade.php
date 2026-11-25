@extends('layouts.site')

@section('title', 'Detalhes da Viagem')

@section('content')
    <style>
        .checkout-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; max-width: 1000px; margin: 0 auto; }
        
        /* MAPA DE RECIFE AQUI */
        .map-placeholder {
            background: url('https://staticmap.openstreetmap.de/staticmap.php?center=-8.0476,-34.8770&zoom=12&size=600x600&maptype=mapnik') center/cover;
            border-radius: 24px; height: 100%; min-height: 400px; position: relative; overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .map-placeholder::after {
            content: '🗺️ Mapa do Trajeto (Recife)'; position: absolute; bottom: 20px; left: 20px;
            background: #fff; padding: 10px 20px; border-radius: 20px; font-weight: bold; color: #0f172a;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .trip-summary { background: #fff; border-radius: 24px; padding: 40px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; }
        .trip-summary h2 { font-size: 28px; color: #0f172a; font-weight: 900; margin-bottom: 24px; }
        
        .route-info { display: flex; flex-direction: column; gap: 15px; margin-bottom: 30px; }
        .route-point { display: flex; align-items: flex-start; gap: 15px; }
        .route-icon { width: 20px; height: 20px; border-radius: 50%; border: 3px solid #2563eb; margin-top: 4px; position: relative; }
        .route-icon.end { border-color: #ef4444; }
        .route-line { position: absolute; top: 20px; left: 8px; width: 2px; height: 30px; background: #cbd5e1; }
        .route-point h4 { margin: 0; font-size: 16px; color: #0f172a; }
        .route-point p { margin: 0; font-size: 14px; color: #64748b; }

        .divider { height: 1px; background: #e2e8f0; margin: 20px 0; }

        .detail-row { display: flex; justify-content: space-between; margin-bottom: 12px; font-size: 15px; }
        .detail-row span:first-child { color: #64748b; }
        .detail-row span:last-child { font-weight: 700; color: #0f172a; }

        .total-row { display: flex; justify-content: space-between; align-items: center; margin-top: 24px; padding-top: 24px; border-top: 2px dashed #e2e8f0; }
        .total-row span:first-child { font-size: 18px; font-weight: 700; color: #0f172a; }
        .total-row span:last-child { font-size: 28px; font-weight: 900; color: #2563eb; }

        .btn-pay { display: block; width: 100%; padding: 18px; background: #10b981; color: #fff; text-align: center; border-radius: 12px; font-weight: 800; font-size: 18px; text-decoration: none; margin-top: 30px; border: none; cursor: pointer; transition: background 0.2s; box-shadow: 0 4px 15px rgba(16, 185, 129, 0.4); }
        .btn-pay:hover { background: #059669; transform: translateY(-2px); }

        @media (max-width: 768px) {
            .checkout-grid { grid-template-columns: 1fr; }
            .map-placeholder { min-height: 250px; }
        }
    </style>

    <div style="padding: 40px 8%;">
        <h1 style="font-size: 36px; color: #0f172a; font-weight: 900; text-align: center; margin-bottom: 40px; letter-spacing: -1px;">Detalhes da Viagem</h1>

        <div class="checkout-grid">
            
            <!-- Lado Esquerdo: Mapa de Recife -->
            <div class="map-placeholder"></div>

            <!-- Lado Direito: Resumo e Pagamento -->
            <div class="trip-summary">
                <h2>Resumo do Trajeto</h2>

                <div class="route-info">
                    <div class="route-point">
                        <div class="route-icon"></div>
                        <div>
                            <h4>Origem</h4>
                            <p>Praça Central, 10:30</p>
                        </div>
                    </div>
                    <div class="route-point">
                        <div class="route-icon end"></div>
                        <div>
                            <h4>Destino</h4>
                            <p>Aeroporto Internacional, 11:15</p>
                        </div>
                    </div>
                </div>

                <div class="divider"></div>

                <div class="detail-row">
                    <span>Transporte</span>
                    <span>Metro + Autocarro</span>
                </div>
                <div class="detail-row">
                    <span>Duração</span>
                    <span>45 minutos</span>
                </div>
                <div class="detail-row">
                    <span>Data</span>
                    <span>Hoje, 24 de Novembro</span>
                </div>

                <div class="total-row">
                    <span>Total a Pagar</span>
                    <span>R$ 4,50</span>
                </div>

                <button class="btn-pay" onclick="alert('Pagamento simulado com sucesso!')">Confirmar e Pagar</button>
            </div>

        </div>
    </div>
@endsection