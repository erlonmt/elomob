@extends('layouts.site')

@section('title', 'Termos de Uso e Privacidade')

@section('content')
    <style>
        .terms-header { padding: 80px 8% 40px; text-align: center; background: #fff; border-bottom: 1px solid #e2e8f0; }
        .terms-header h1 { font-size: 42px; color: #0f172a; font-weight: 900; letter-spacing: -1px; margin-bottom: 10px; }
        .terms-header p { color: #64748b; font-size: 18px; }

        .terms-content { max-width: 800px; margin: 60px auto; padding: 0 8%; }
        .terms-content h2 { font-size: 24px; color: #0f172a; font-weight: 800; margin: 40px 0 15px; padding-bottom: 10px; border-bottom: 2px solid #f1f5f9; }
        .terms-content h2:first-child { margin-top: 0; }
        .terms-content p { color: #475569; line-height: 1.8; font-size: 16px; margin-bottom: 15px; }
        .terms-content ul { margin-left: 20px; margin-bottom: 20px; color: #475569; line-height: 1.8; font-size: 16px; }
        .terms-content li { margin-bottom: 8px; }
        
        .terms-footer { text-align: center; margin-top: 60px; color: #94a3b8; font-size: 14px; }
    </style>

    <div class="terms-header">
        <h1>Termos e Privacidade</h1>
        <p>Última atualização: 25 de Novembro de 2026</p>
    </div>

    <div class="terms-content">
        <h2>1. Termos de Uso</h2>
        <p>Bem-vindo ao <strong>Elomob</strong>. Ao acessar e utilizar a nossa plataforma, você concorda em cumprir e estar vinculado aos seguintes termos e condições de uso.</p>
        <p>O Elomob é uma plataforma de mobilidade urbana que tem como objetivo facilitar a sua rotina, oferecendo opções de trajetos, transportes públicos, bicicletas e serviços de entrega. Ao utilizar os nossos serviços, você concorda que:</p>
        <ul>
            <li>As informações fornecidas no registo são verdadeiras e precisas.</li>
            <li>Você é responsável por manter a confidencialidade da sua palavra-passe.</li>
            <li>O uso da plataforma para fins ilegais ou não autorizados é estritamente proibido.</li>
            <li>Os preços e horários apresentados são simulados para fins deste projeto académico.</li>
        </ul>

        <h2>2. Política de Privacidade</h2>
        <p>A sua privacidade é muito importante para nós. Esta Política descreve como recolhemos, usamos e protegemos as suas informações pessoais.</p>
        <p><strong>Recolha de Dados:</strong> Recolhemos apenas as informações necessárias para o funcionamento do serviço, como nome, e-mail e preferências de trajeto (acessibilidade, rotas favoritas).</p>
        <p><strong>Uso dos Dados:</strong> Os seus dados são utilizados para personalizar a sua experiência, sugerir rotas mais rápidas ou económicas e melhorar a nossa plataforma.</p>
        <p><strong>Partilha de Dados:</strong> Não vendemos nem partilhamos os seus dados pessoais com terceiros para fins de marketing. Os dados podem ser partilhados apenas com operadores de transporte para a realização da viagem.</p>
        <p><strong>Segurança:</strong> Utilizamos protocolos de segurança modernos para proteger os seus dados contra acessos não autorizados.</p>

        <h2>3. Cancelamentos e Reembolsos</h2>
        <p>Como a nossa plataforma agrega dados de múltiplos operadores, as políticas de cancelamento podem variar. Viagens canceladas com mais de 1 hora de antecedência podem ser elegíveis para reembolso total (simulação).</p>

        <div class="terms-footer">
            © {{ date('Y') }} Elomob — Todos os direitos reservados.
        </div>
    </div>
@endsection