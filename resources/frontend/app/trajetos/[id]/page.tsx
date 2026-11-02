import { DetailScreen } from '@/components/screens/detail-screen';

export const metadata = { title: 'Detalhes do trajeto' };

export default async function DetailPage({ params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  return <DetailScreen id={id} />;
}
