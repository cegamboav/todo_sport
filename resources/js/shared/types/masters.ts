export interface SelectOption {
  value: string | number;
  label: string;
}

export interface Paginated<T> {
  data: T[];
  links: { url: string | null; label: string; active: boolean }[];
  meta: { current_page: number; last_page: number; per_page: number; total: number };
}

export interface MasterFilters {
  search?: string;
  status?: string;
  gender?: string;
  school_id?: string;
  grade_id?: string;
  only_trashed?: string;
  [key: string]: string | undefined;
}
