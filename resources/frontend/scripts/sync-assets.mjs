import { cp, mkdir, access } from 'node:fs/promises';
import { fileURLToPath } from 'node:url';

const destination = fileURLToPath(new URL('../public/assets/', import.meta.url));
const source = fileURLToPath(new URL('../../../public/assets/', import.meta.url));

try {
  await access(source);
  await mkdir(destination, { recursive: true });
  await Promise.all(
    ['fonts', 'figma'].map(directory =>
      cp(`${source}/${directory}`, `${destination}/${directory}`, { recursive: true }),
    ),
  );
} catch (error) {
  if (error.code !== 'ENOENT') {
    throw error;
  }
  await access(`${destination}/fonts/fonts.css`);
}
