import { api } from './api';
import type { Stop } from './types';

/** Find the registered stop closest to the device, within 5 km, asking the browser for the location. */
export async function nearestStop(): Promise<Stop> {
  if (!navigator.geolocation) {
    throw new Error('Seu navegador não oferece localização. Escolha uma origem na lista.');
  }
  const position = await new Promise<GeolocationPosition>((resolve, reject) =>
    navigator.geolocation.getCurrentPosition(
      resolve,
      () =>
        reject(
          new Error(
            'Não foi possível acessar sua localização. Verifique a permissão do navegador ou escolha uma origem.',
          ),
        ),
      { enableHighAccuracy: true, timeout: 10000, maximumAge: 60000 },
    ),
  );
  const query = new URLSearchParams({
    latitude: String(position.coords.latitude),
    longitude: String(position.coords.longitude),
    radius: '5000',
  });
  const result = await api<Stop[]>(`/paradas?${query}`);
  if (!result.data.length) {
    throw new Error(
      'Não há paradas cadastradas em até 5 km da sua localização. Escolha um local da rede demonstrativa.',
    );
  }
  return result.data[0];
}
