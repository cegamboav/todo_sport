import { router } from '@inertiajs/vue3';

import type { MasterFilters } from '@shared/types/masters';



function cleanParams(params: MasterFilters): Record<string, string> {

  return Object.fromEntries(

    Object.entries(params).filter(([, value]) => value !== undefined && value !== ''),

  ) as Record<string, string>;

}



export function useMasterIndex(basePath: string, filters: MasterFilters) {

  function reload(extra: MasterFilters = {}) {

    router.get(basePath, cleanParams({ ...filters, ...extra }), {

      preserveState: true,

      replace: true,

      preserveScroll: true,

    });

  }



  function onSearch(search: string) {

    reload({ search: search || undefined, page: undefined });

  }



  function onFilterChange(key: string, value: string) {

    reload({ [key]: value || undefined, page: undefined });

  }



  return { reload, onSearch, onFilterChange };

}

