'use client';

import Link from 'next/link';
import { usePathname } from 'next/navigation';
import type { ReactNode } from 'react';
import { useApplication } from './providers';

const icons: Record<string, [string, number, number]> = {
  pin: ['b3f4f.svg', 18, 18],
  destination: ['63f91.svg', 18, 18],
  brand: ['576ec.svg', 16, 16],
  avatar: ['e6355.svg', 20, 20],
  bus: ['1fbea.svg', 22, 22],
  metro: ['e5d10.svg', 22, 22],
  bike: ['f6272.svg', 22, 22],
  taxi: ['554de.svg', 22, 22],
  app: ['88459.svg', 22, 22],
  home: ['546fd.svg', 22, 22],
  'home-active': ['3cf86.svg', 22, 22],
  route: ['12d2e.svg', 22, 22],
  'route-active': ['663cd.svg', 22, 22],
  map: ['3b15c.svg', 22, 22],
  'map-active': ['8e75c.svg', 22, 22],
  clock: ['505df.svg', 22, 22],
  'clock-active': ['206a7.svg', 22, 22],
  user: ['be78f.svg', 22, 22],
  'user-active': ['a6e2b.svg', 22, 22],
  'bus-small': ['9077a.svg', 14, 14],
  'metro-small': ['c9185.svg', 14, 14],
  'bike-small': ['d6387.svg', 14, 14],
  'taxi-small': ['ebfef.svg', 14, 14],
  'app-small': ['ebfef.svg', 14, 14],
  'walk-small': ['69c03.svg', 14, 14],
  'bus-card': ['2aa3d.svg', 20, 20],
  'metro-card': ['ad10f.svg', 20, 20],
  'bike-card': ['b7c70.svg', 20, 20],
  search: ['88faa.svg', 16, 16],
  arrow: ['eaae0.svg', 18, 18],
  back: ['0548a.svg', 18, 18],
  recent: ['2cb38.svg', 16, 16],
  time: ['6717a.svg', 16, 16],
  transfer: ['6df2a.svg', 14, 14],
  heart: ['04b51.svg', 20, 20],
  location: ['f7ef3.svg', 12, 12],
  help: ['74726.svg', 18, 18],
  info: ['6e2de.svg', 18, 18],
  logout: ['bef17.svg', 18, 18],
  accessible: ['b809a.svg', 18, 18],
  stairs: ['89a9d.svg', 18, 18],
  'need-visual': ['need-visual.svg', 18, 18],
  'need-hearing': ['need-hearing.svg', 18, 18],
  fastest: ['3bfd5.svg', 20, 20],
  cheapest: ['638fd.svg', 20, 20],
  sustainable: ['d2142.svg', 20, 20],
  saved: ['ceee6.svg', 16, 16],
  integration: ['85751.svg', 20, 20],
  'bus-preference': ['5767f.svg', 34, 34],
  'metro-preference': ['8268c.svg', 34, 34],
  'bike-preference': ['25afc.svg', 34, 34],
  'taxi-preference': ['d5185.svg', 34, 34],
  'app-preference': ['f2bb3.svg', 34, 34],
};

export function Icon({ name, className = '' }: { name: string; className?: string }) {
  const [file, width, height] = icons[name] ?? icons.pin;
  return (
    // Local SVG icons, which next/image would serve unchanged.
    // eslint-disable-next-line @next/next/no-img-element
    <img
      className={`icon ${className}`}
      src={`/assets/figma/${file}`}
      width={width}
      height={height}
      alt=""
    />
  );
}

export function Brand({ dark = false }: { dark?: boolean }) {
  return (
    <Link className={`brand ${dark ? 'brand-dark' : ''}`} href="/" aria-label="ELOMOB, início">
      <span className="brand-badge">
        <Icon name="brand" />
      </span>
      <span>
        Elo<span className="brand-accent">mob</span>
      </span>
    </Link>
  );
}

const navigation = [
  { href: '/', label: 'Início', icon: 'home' },
  { href: '/rotas', label: 'Rotas', icon: 'route' },
  { href: '/mapa', label: 'Mapa', icon: 'map' },
  { href: '/horarios', label: 'Horários', icon: 'clock' },
  { href: '/perfil', label: 'Perfil', icon: 'user' },
];

export function AppShell({ children }: { children: ReactNode }) {
  const pathname = usePathname();
  const { user } = useApplication();
  const focusJourney =
    pathname === '/buscar' ||
    pathname.startsWith('/resultados/') ||
    pathname.startsWith('/trajetos/');

  return (
    <div className={`app-shell ${focusJourney ? 'focus-journey' : ''}`}>
      <a href="#main-content" className="skip-link">
        Ir para o conteúdo
      </a>
      <header className="desktop-header">
        <div className="desktop-header-inner">
          <Brand dark />
          <nav aria-label="Navegação principal">
            {navigation.slice(0, 4).map(item => (
              <Link
                key={item.href}
                className={pathname === item.href ? 'active' : ''}
                href={item.href}
              >
                <Icon name={`${item.icon}${pathname === item.href ? '-active' : ''}`} />
                {item.label}
              </Link>
            ))}
          </nav>
          <Link href="/perfil" className="desktop-account">
            <span className="avatar-button">
              <Icon name="avatar" />
            </span>
            <span>{user?.name.split(' ')[0] ?? 'Minha conta'}</span>
          </Link>
        </div>
      </header>
      <main id="main-content">{children}</main>
      <nav className="bottom-nav" aria-label="Navegação principal">
        {navigation.map(item => (
          <Link
            key={item.href}
            href={item.href}
            className={pathname === item.href ? 'active' : ''}
            aria-current={pathname === item.href ? 'page' : undefined}
          >
            <Icon name={`${item.icon}${pathname === item.href ? '-active' : ''}`} />
            <span>{item.label}</span>
          </Link>
        ))}
      </nav>
      <footer className="desktop-footer">
        <Brand dark />
        <p>Mobilidade que conecta o Recife.</p>
        <Link href="/integracoes">Pontos de integração</Link>
        <Link href="/sobre">Sobre o ELOMOB</Link>
        <span>Projeto acadêmico · dados demonstrativos</span>
      </footer>
    </div>
  );
}

export function PageHeader({
  title,
  subtitle,
  back = '/',
}: {
  title: string;
  subtitle?: string;
  back?: string;
}) {
  return (
    <header className="page-header">
      <Link href={back} className="circle-button" aria-label="Voltar">
        <Icon name="back" />
      </Link>
      <div>
        <h1>{title}</h1>
        {subtitle && <p>{subtitle}</p>}
      </div>
    </header>
  );
}

export function Loading({ label = 'Carregando...' }: { label?: string }) {
  return (
    <div className="loading" role="status">
      <span className="spinner" />
      {label}
    </div>
  );
}

export function Notice({ children, success = false }: { children: ReactNode; success?: boolean }) {
  return children ? (
    <div className={`notice ${success ? 'success' : ''}`} role={success ? 'status' : 'alert'}>
      {children}
    </div>
  ) : null;
}

export function Empty({
  title,
  children,
  action,
}: {
  title: string;
  children?: ReactNode;
  action?: ReactNode;
}) {
  return (
    <div className="empty">
      <span className="empty-icon">
        <Icon name="route" />
      </span>
      <h2>{title}</h2>
      {children && <p>{children}</p>}
      {action}
    </div>
  );
}

export function LoginPrompt() {
  return (
    <div className="content narrow">
      <Empty
        title="Seus trajetos, do seu jeito"
        action={
          <Link className="button" href="/entrar">
            Entrar ou criar conta
          </Link>
        }
      >
        Entre para salvar locais, consultar seu histórico e definir suas preferências de transporte.
      </Empty>
    </div>
  );
}

export function DemoNote() {
  return (
    <p className="demo-note">
      Dados demonstrativos · horários, tarifas e disponibilidade estimados, sem atualização em tempo
      real.
    </p>
  );
}
