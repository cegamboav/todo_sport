import type { Paginated } from '@shared/types/masters';

type PaginatorInput<T> =
  | Paginated<T>
  | (Partial<Paginated<T>> & Record<string, unknown>)
  | null
  | undefined;

function emptyPaginator<T>(): Paginated<T> {
  return {
    data: [],
    links: [],
    meta: { current_page: 1, last_page: 1, per_page: 15, total: 0 },
  };
}

export function normalizePaginator<T>(input: PaginatorInput<T>): Paginated<T> {
  if (!input || typeof input !== 'object') {
    return emptyPaginator();
  }

  const data = Array.isArray(input.data) ? input.data : [];
  const links = Array.isArray(input.links) ? input.links : [];

  if (input.meta && typeof input.meta === 'object') {
    const meta = input.meta as Paginated<T>['meta'];

    return {
      data,
      links: links as Paginated<T>['links'],
      meta: {
        current_page: Number(meta.current_page ?? 1),
        last_page: Number(meta.last_page ?? 1),
        per_page: Number(meta.per_page ?? 15),
        total: Number(meta.total ?? data.length),
      },
    };
  }

  const raw = input as Record<string, unknown>;

  return {
    data,
    links: links as Paginated<T>['links'],
    meta: {
      current_page: Number(raw.current_page ?? 1),
      last_page: Number(raw.last_page ?? 1),
      per_page: Number(raw.per_page ?? 15),
      total: Number(raw.total ?? data.length),
    },
  };
}
