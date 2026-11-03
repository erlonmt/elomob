import { defineConfig, globalIgnores } from 'eslint/config';
import nextVitals from 'eslint-config-next/core-web-vitals';
import nextTypeScript from 'eslint-config-next/typescript';

// The Next.js rules for React, hooks, accessibility (jsx-a11y), imports and TypeScript.
export default defineConfig([
  ...nextVitals,
  ...nextTypeScript,
  globalIgnores([
    '.next/**',
    'next-env.d.ts',
    'public/assets/**',
    'playwright-report/**',
    'test-results/**',
    'blob-report/**',
  ]),
]);
