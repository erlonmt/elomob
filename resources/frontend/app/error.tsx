'use client';

import { Empty } from '@/components/ui';

export default function ErrorPage({ reset }: { reset: () => void }) {
  return (
    <div className="content">
      <Empty
        title="Não foi possível abrir esta página"
        action={
          <button className="button" onClick={reset}>
            Tentar novamente
          </button>
        }
      >
        Tente novamente em instantes.
      </Empty>
    </div>
  );
}
