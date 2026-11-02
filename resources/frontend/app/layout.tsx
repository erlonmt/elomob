import type { Metadata } from 'next';
import { Suspense } from 'react';
import { Providers } from '@/components/providers';
import { AppShell, Loading } from '@/components/ui';
import './globals.css';

export const metadata: Metadata = {
  title: { default: 'ELOMOB · Mobilidade no Recife', template: '%s · ELOMOB' },
  description:
    'Compare trajetos de ônibus, metrô, bicicleta, táxi e aplicativo no Recife em um só lugar.',
};

export default function RootLayout({ children }: { children: React.ReactNode }) {
  return (
    <html lang="pt-BR">
      <head>
        <link rel="stylesheet" href="/assets/fonts/fonts.css" />
      </head>
      <body>
        <Providers>
          <AppShell>
            <Suspense fallback={<Loading />}>{children}</Suspense>
          </AppShell>
        </Providers>
      </body>
    </html>
  );
}
