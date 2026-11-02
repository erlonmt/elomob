import Link from 'next/link';

export default function NotFound() {
  return (
    <div className="content narrow empty">
      <h1>Página não encontrada</h1>
      <p>Este endereço não está disponível.</p>
      <Link className="button" href="/">
        Voltar ao início
      </Link>
    </div>
  );
}
