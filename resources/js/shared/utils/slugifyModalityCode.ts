/** Genera un código interno a partir del nombre (preview UX; el backend valida y unifica). */
export function slugifyModalityCode(name: string): string {
  const ascii = name
    .normalize('NFD')
    .replace(/\p{M}/gu, '')
    .toLowerCase();
  const slug = ascii
    .trim()
    .replace(/[^a-z0-9]+/g, '_')
    .replace(/^_+|_+$/g, '');

  return slug || 'modality';
}
