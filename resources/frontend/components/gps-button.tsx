'use client';

import { useState } from 'react';
import { errorMessage } from '@/lib/api';
import { nearestStop } from '@/lib/location';
import type { Stop } from '@/lib/types';

/** Button that finds the registered stop closest to the device and hands it to the form. */
export function GpsButton({
  disabled,
  onLocate,
  onError,
}: {
  disabled: boolean;
  onLocate: (stop: Stop) => void;
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
          onLocate(await nearestStop());
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
