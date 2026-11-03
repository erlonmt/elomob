'use client';

import Link from 'next/link';
import { ItineraryCard } from '@/components/itinerary-card';
import { useApiData } from '@/components/providers';
import { DemoNote, Empty, Loading, Notice, PageHeader } from '@/components/ui';
import { needTitles } from '@/lib/accessibility';
import { searchQuery } from '@/lib/geo';
import { routeOptions } from '@/lib/itineraries';
import { departureLabel } from '@/lib/schedule';
import type { Search } from '@/lib/types';

export function ResultsScreen({ id }: { id: string }) {
  const { data: search, loading, error, reload } = useApiData<Search>(`/buscas/${id}`);
  const options = routeOptions(search?.itineraries ?? []);
  const departureAt = search?.departure_at ?? search?.created_at;
  const departure = departureAt ? new Date(departureAt) : null;
  const needs = needTitles(search?.filters.accessibility_needs ?? []);
  return (
    <>
      <PageHeader
        title={search ? `${search.origin} → ${search.destination}` : 'Opções de trajeto'}
        subtitle="Compare as opções e escolha como quer ir."
        back={search ? `/buscar?${searchQuery(search)}` : '/buscar'}
      />
      <div className="content">
        <Notice>{error}</Notice>
        {error && (
          <button onClick={reload} className="text-button">
            Tentar novamente
          </button>
        )}
        {loading ? (
          <Loading label="Carregando seus trajetos..." />
        ) : (
          search && (
            <>
              <div className="results-heading">
                <h2>
                  {options.length} {options.length === 1 ? 'opção' : 'opções'} para seu caminho
                </h2>
                <Link href={`/buscar?${searchQuery(search)}`} className="small-outline">
                  Alterar busca
                </Link>
              </div>
              {departure && (
                <p className="departure-note">{departureLabel(departure, new Date())}.</p>
              )}
              {needs.length > 0 && (
                <p className="preference-note">Acessibilidade considerada: {needs.join(', ')}.</p>
              )}
              {options.length ? (
                <div className="results-grid">
                  {options.map(option => (
                    <ItineraryCard
                      key={option.itinerary.id}
                      itinerary={option.itinerary}
                      criteria={option.criteria}
                      departure={departure}
                    />
                  ))}
                </div>
              ) : (
                <Empty
                  title="Nenhum trajeto disponível"
                  action={
                    <Link href={`/buscar?${searchQuery(search)}`} className="button">
                      Alterar busca
                    </Link>
                  }
                >
                  Tente outros modais, horários ou preferências de acessibilidade. Linhas fora de
                  operação e bicicletas sem disponibilidade não entram no cálculo.
                </Empty>
              )}
              <DemoNote />
            </>
          )
        )}
      </div>
    </>
  );
}
