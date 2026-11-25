@extends('layouts.site')

@section('title', 'Como Funciona')

@section('content')
    <div class="page-header">
        <h1>Como funciona</h1>
        <p>Em três passos simples chega ao seu destino.</p>
    </div>

    <section>
        <div class="steps">
            <div class="step">
                <div class="num">1</div>
                <h3>Pesquise</h3>
                <p>Indique a origem, o destino e a data da sua viagem.</p>
            </div>
            <div class="step">
                <div class="num">2</div>
                <h3>Compare</h3>
                <p>Veja as opções disponíveis com preços e tempos de chegada.</p>
            </div>
            <div class="step">
                <div class="num">3</div>
                <h3>Viaje</h3>
                <p>Confirme a reserva e aproveite o percurso com tranquilidade.</p>
            </div>
        </div>
    </section>
@endsection