import { ResultsScreen } from '@/components/screens/results-screen';

export const metadata = { title: 'Opções de trajeto' };

export default async function ResultsPage({ params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  return <ResultsScreen id={id} />;
}
