'use client';

import { useState } from 'react';
import { errorMessage } from '@/lib/api';
import { nearbyStops } from '@/lib/location';
import type { Stop } from '@/lib/types';

/**
 * Button that finds the registered stop closest to the device and hands it to the form, with every stop within
 * 5 km, closest first.
 */
export function GpsButton({
  disabled,
  onLocate,
  onError,
}: {
  disabled: boolean;
  onLocate: (stop: Stop, nearby: Stop[]) => void;
  onError: (message: string) => void;
}) {
  const [locating, setLocating] = useState(false);

  return (
    <button
      type="button"
      className="gps"
      disabled={disabled || locating}
      onClick={async () => {
        setLocating(true);
        onError('');
        try {
          const nearby = await nearbyStops();
          onLocate(nearby[0], nearby);
        } catch (error) {
          onError(errorMessage(error));
        } finally {
          setLocating(false);
        }
      }}
    >
      {locating ? '...' : 'GPS'}
    </button>
  );
}
