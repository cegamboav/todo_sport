export function formatAge(age: number | null | undefined): string {
  if (age == null || Number.isNaN(age)) {
    return '—';
  }

  return `${age} años`;
}
