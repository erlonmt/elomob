'use client';

import Link from 'next/link';
import { useApplication } from '@/components/providers';
import { DemoNote, Icon, Loading, Notice, PageHeader } from '@/components/ui';

export function IntegrationsScreen() {
  const { catalog, loading, catalogError } = useApplication();
  return (
    <>
      <PageHeader
        title="Pontos de Integração"
        subtitle="Troque de transporte e siga seu caminho."
      />
      <div className="content">
        <Notice>{catalogError}</Notice>
        {loading ? (
          <Loading />
        ) : (
          <div className="integration-grid">
            {catalog.integrations.map(integration => (
              <article key={integration.id} className="integration-card">
                <div className="split">
                  <span className="inline">
                    <span className="icon-tile">
                      <Icon name={`${integration.from_mode}-small`} />
                    </span>
                    <Icon name="transfer" />
                    <span className="icon-tile">
                      <Icon name={`${integration.to_mode}-small`} />
                    </span>
                  </span>
                  <span className="badge teal">Demonstração</span>
                </div>
                <h2>
                  {integration.from_name} → {integration.to_name}
                </h2>
                <Link href={`/mapa?q=${encodeURIComponent(integration.name)}`}>
                  {integration.name}
                </Link>
                <p className="inline">
                  <Icon name="time" />
                  {integration.transfer_minutes} min de transferência
                </p>
                {integration.fare_integrated && (
                  <p className="inline">
                    <Icon name="cheapest" />
                    Tarifa integrada: paga só a diferença
                  </p>
                )}
                <small>{integration.description}</small>
                <Link
                  className="integration-link"
                  href={`/buscar?origin_id=${integration.stop_id}`}
                >
                  Planejar trajeto <Icon name="arrow" />
                </Link>
              </article>
            ))}
          </div>
        )}
        <DemoNote />
      </div>
    </>
  );
}
