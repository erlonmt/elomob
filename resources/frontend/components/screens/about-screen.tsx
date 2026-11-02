'use client';

import Link from 'next/link';
import { Icon, PageHeader } from '@/components/ui';

export function AboutScreen() {
  return (
    <>
      <PageHeader title="Sobre o ELOMOB" subtitle="Mobilidade que conecta o Recife." />
      <article className="content prose narrow">
        <div className="about-mark">
          <Icon name="integration" />
        </div>
        <h2>Uma cidade, muitos caminhos.</h2>
        <p>
          O ELOMOB conecta modos de transporte para ajudar você a comparar trajetos no Recife.
          Escolha origem, destino e suas preferências para encontrar opções por tempo, custo,
          transferências e sustentabilidade.
        </p>
        <h2>Como usar</h2>
        <ol>
          <li>Escolha origem e destino entre os locais cadastrados.</li>
          <li>Selecione os transportes e o horário de saída.</li>
          <li>Compare as opções e confira cada etapa do percurso no mapa.</li>
          <li>Crie uma conta para salvar locais e ajustar acessibilidade.</li>
        </ol>
        <h2>Sobre as informações</h2>
        <p>
          Este é um projeto acadêmico com rede de demonstração. Linhas, coordenadas, horários,
          tarifas e disponibilidade de bicicletas são dados de teste, sem atualização em tempo real.
          Consulte fontes oficiais antes de uma viagem real.
        </p>
        <p>
          O cálculo considera as conexões, a operação e a frequência das linhas cadastradas, além da
          disponibilidade de bicicletas e vagas nos trechos de bicicleta. Os mapas mostram as
          coordenadas dessa rede sobre o mapa de fundo do OpenStreetMap e conectam seus pontos de
          forma esquemática; não oferecem navegação por ruas.
        </p>
        <p>
          O mapa de fundo é carregado dos servidores do OpenStreetMap, que recebem o endereço IP de
          quem abre o mapa. ©{' '}
          <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noreferrer">
            Colaboradores do OpenStreetMap
          </a>
          .
        </p>
        <Link className="button" href="/buscar">
          Buscar um trajeto
        </Link>
      </article>
    </>
  );
}
