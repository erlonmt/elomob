@extends('layouts.site')

@section('title', 'Serviços')

@section('content')
    <div class="page-header">
        <h1>Os nossos serviços</h1>
        <p>Soluções pensadas para o dia a dia na cidade.</p>
    </div>

    <section>
        <div class="service-grid">
            <div class="service">
                <div class="icon">🚗</div>
                <h3>Viagens urbanas</h3>
                <p>Deslocações rápidas dentro da cidade com preços acessíveis e motoristas avaliados.</p>
            </div>
            <div class="service">
                <div class="icon">📦</div>
                <h3>Entregas</h3>
                <p>Envie encomendas de forma segura e acompanhe o trajeto em tempo real no mapa.</p>
            </div>
            <div class="service">
                <div class="icon">📅</div>
                <h3>Agendamento</h3>
                <p>Programe viagens com antecedência e garanta o seu transporte sem estresse.</p>
            </div>
        </div>
    </section>
@endsection