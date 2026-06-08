<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import AdministrativeLayout from '@layouts/AdministrativeLayout.vue';
import FlashAlert from '@shared/components/masters/FlashAlert.vue';
import MasterToolbar from '@shared/components/masters/MasterToolbar.vue';
import PaginationLinks from '@shared/components/masters/PaginationLinks.vue';
import StatusBadge from '@shared/components/masters/StatusBadge.vue';
import { useMasterIndex } from '@shared/composables/useMasterIndex';
import type { MasterFilters, Paginated } from '@shared/types/masters';
import { normalizePaginator } from '@shared/utils/normalizePaginator';

defineOptions({ layout: AdministrativeLayout });

interface SchoolRow {
  id: number;
  name: string;
  abbreviation: string;
  city: string | null;
  country: string | null;
  status: string;
  deleted_at: string | null;
  director?: { first_name: string; last_name: string; phone: string | null };
}

const props = defineProps<{
  schools: Paginated<SchoolRow>;
  filters: MasterFilters;
}>();

const { onSearch, onFilterChange } = useMasterIndex('/masters/schools', props.filters);

const rows = computed(() => normalizePaginator(props.schools).data);

function deactivate(id: number) {
  if (confirm('¿Desactivar esta escuela?')) {
    router.delete(`/masters/schools/${id}`);
  }
}

function restore(id: number) {
  router.post(`/masters/schools/${id}/restore`);
}
</script>

<template>
  <FlashAlert />
  <h1 class="page-title">Escuelas</h1>
  <MasterToolbar
    :filters="filters"
    create-href="/masters/schools/create"
    create-label="Escuela"
    @search="onSearch"
    @filter="onFilterChange"
  />
  <div class="card">
    <div class="table-wrap">
      <table class="data-table">
        <thead>
          <tr>
            <th>Nombre</th>
            <th>Ciudad</th>
            <th>Director</th>
            <th>Teléfono</th>
            <th>Estado</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="school in rows" :key="school.id">
            <td>
              <span class="cell-primary">{{ school.name }}</span>
              <span class="abbr-tag">{{ school.abbreviation }}</span>
            </td>
            <td>{{ school.city ?? '—' }}</td>
            <td>
              <span v-if="school.director">
                {{ school.director.first_name }} {{ school.director.last_name }}
              </span>
              <span v-else class="hint">—</span>
            </td>
            <td>{{ school.director?.phone ?? '—' }}</td>
            <td><StatusBadge :status="school.status" /></td>
            <td class="table-actions">
              <template v-if="school.deleted_at">
                <button type="button" class="btn btn-ghost btn-sm" @click="restore(school.id)">Restaurar</button>
              </template>
              <template v-else>
                <Link :href="`/masters/schools/${school.id}/edit`" class="btn btn-ghost btn-sm">Editar</Link>
                <button type="button" class="btn btn-ghost btn-sm" @click="deactivate(school.id)">Desactivar</button>
              </template>
            </td>
          </tr>
          <tr v-if="rows.length === 0">
            <td colspan="6" class="hint table-empty">Sin resultados.</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
  <PaginationLinks :paginator="schools" />
</template>
