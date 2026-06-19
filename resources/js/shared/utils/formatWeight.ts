export function formatWeightKg(value: string | number | null | undefined): string | null {
  if (value === null || value === undefined || value === '') {
    return null;
  }

  const numeric = Number(value);
  if (Number.isNaN(numeric)) {
    return `${value} kg`;
  }

  const formatted = Number.isInteger(numeric) ? String(Math.trunc(numeric)) : String(numeric);

  return `${formatted} kg`;
}
