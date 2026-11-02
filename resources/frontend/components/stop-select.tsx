'use client';

import { useApplication } from './providers';

export function StopSelect({
  value,
  onChange,
  label,
  placeholder,
}: {
  value: string;
  onChange: (value: string) => void;
  label: string;
  placeholder: string;
}) {
  const { catalog, loading } = useApplication();
  return (
    <select
      aria-label={label}
      value={value}
      onChange={event => onChange(event.target.value)}
      required
      disabled={loading}
    >
      <option value="">{loading ? 'Carregando locais...' : placeholder}</option>
      {catalog.stops.map(stop => (
        <option key={stop.id} value={stop.id}>
          {stop.name}
        </option>
      ))}
    </select>
  );
}
