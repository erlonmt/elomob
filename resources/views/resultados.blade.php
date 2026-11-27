@extends('layouts.site')

@section('title', 'Resultados da Pesquisa')

@section('content')
    <style>
        .search-bar { background: #0f172a; padding: 30px 8%; display: flex; justify-content: center; }
        .search-inner { background: #fff; padding: 12px; border-radius: 16px; display: flex; gap: 12px; width: 100%; max-width: 1000px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
        
        .search-inner input { 
            flex: 1; padding: 16px; border: 1px solid #cbd5e1; border-radius: 10px; 
            outline: none; font-family: inherit; font-size: 16px; background: #f1f5f9; 
            color: #1e293b; font-weight: 600; 
        }
        
        .search-inner button { padding: 16px 32px; background: #2563eb; color: #fff; border: none; border-radius: 10px; font-weight: 700; cursor: pointer; font-family: inherit; font-size: 16px; transition: background 0.2s; }
        .search-inner button:hover { background: #1d4ed8; }
        
        .main-content { padding: 60px 8%; max-width: 1200px; margin: 0 auto; }
        .page-title { font-size: 36px; color: #0f172a; margin-bottom: 10px; font-weight: 900; letter-spacing: -1px; }
        .page-subtitle { color: #64748b; margin-bottom: 40px; font-size: 18px; }

        .trip-list { display: flex; flex-direction: column; gap: 24px; }
        .trip-card { background: #fff; padding: 30px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,.03); display: flex; justify-content: space-between; align-items: center; border: 1px solid #e2e8f0; border-left: 6px solid #cbd5e1; transition: all 0.3s ease; }
        .trip-card:hover { transform: translateY(-5px); box-shadow: 0 20px 40px rgba(0,0,0,.08); border-color: #cbd5e1; }
        
        .trip-card.fastest { border-left-color: #2563eb; }
        .trip-card.cheapest { border-left-color: #10b981; }
        .trip-card.sustainable { border-left-color: #8b5cf6; }

        .trip-info h3 { font-size: 22px; color: #0f172a; margin-bottom: 12px; font-weight: 800; }
        .trip-details { display: flex; gap: 20px; color: #475569; font-size: 16px; margin-bottom: 16px; font-weight: 500; }
        .trip-tags { display: flex; gap: 10px; }
        .tag { padding: 6px 14px; border-radius: 20px; font-size: 13px; font-weight: 700; }
        .tag.blue { background: #eff6ff; color: #2563eb; }
        .tag.green { background: #ecfdf5; color: #10b981; }
        .tag.purple { background: #f5f3ff; color: #8b5cf6; }

        .trip-price { text-align: right; min-width: 150px; }
        .trip-price .price { font-size: 32px; font-weight: 900; color: #0f172a; margin-bottom: 10px; }
        .trip-price .price span { font-size: 16px; color: #64748b; font-weight: normal; }
        .btn-buy { display: inline-block; background: #2563eb; color: #fff; padding: 14px 30px; border-radius: 10px; text-decoration: none; font-weight: 700; font-size: 16px; transition: all 0.2s; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3); }
        .btn-buy:hover { transform: translateY(-2px); box-shadow: 0 6px 15px rgba(37, 99, 235, 0.4); }

        @media (max-width: 768px) {
            .trip-card { flex-direction: column; align-items: flex-start; gap: 20px; }
            .trip-price { text-align: left; width: 100%; }
            .search-inner { flex-direction: column; }
            .btn-buy { width: 100%; text-align: center; }
        }
    </style>

    <!-- Barra de Pesquisa Rápida -->
    <div class="search-bar">
        <form class="search-inner" method="GET" action="{{ route('resultados') }}">
            <input type="text" name="origem" value="{{ request('origem', 'Origem') }}" placeholder="Origem" required>
            <input type="text" name="destino" value="{{ request('destino', 'Destino') }}" placeholder="Destino" required>
            <input type="date" name="data" value="{{ request('data', date('Y-m-d')) }}" required>
            <button type="submit">Pesquisar</button>
        </form>
    </div>

    <!-- Resultados -->
    <div class="main-content">
        <h1 class="page-title">Resultados da Pesquisa</h1>
        <p class="page-subtitle">
            A mostrar opções de <strong>{{ $origem ?? 'Centro' }}</strong> para <strong>{{ $destino ?? 'Aeroporto' }}</strong> 
            em {{ isset($data) ? date('d/m/Y', strtotime($data)) : date('d/m/Y') }}.
        </p>

        <div class="trip-list">
            {{-- O loop abaixo vai criar os cartões com os dados vindos do Back-end --}}
            @forelse($viagens as $viagem)
                <div class="trip-card {{ $viagem['classe'] }}">
                    <div class="trip-info">
                        <h3>{{ $viagem['tipo'] }}</h3>
                        <div class="trip-details">
                            <span>⏱ {{ $viagem['tempo'] }}</span>
                            <span>🚌 {{ $viagem['transporte'] }}</span>
                        </div>
                        <div class="trip-tags">
                            <span class="tag" style="background: {{ $viagem['cor'] }}20; color: {{ $viagem['cor'] }};">
                                {{ $viagem['transporte'] }}
                            </span>
                        </div>
                    </div>
                    <div class="trip-price">
                        <div class="price"><span>R$</span> {{ $viagem['preco'] }}</div>
                        <a href="{{ route('detalhes-viagem', ['origem' => $viagem['origem'], 'destino' => $viagem['destino'], 'data' => $viagem['data']]) }}" class="btn-buy" style="background: {{ $viagem['cor'] }};">Selecionar</a>
                    </div>
                </div>
            @empty
                <div style="text-align: center; padding: 40px; background: #fff; border-radius: 20px; border: 1px solid #e2e8f0;">
                    <p style="color: #64748b; font-size: 18px;">Nenhuma viagem encontrada para esta pesquisa. Tente outra origem ou destino.</p>
                </div>
            @endforelse
        </div>
    </div>
@endsection