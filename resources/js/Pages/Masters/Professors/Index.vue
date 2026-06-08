<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import AdministrativeLayout from '@layouts/AdministrativeLayout.vue';
import FlashAlert from '@shared/components/masters/FlashAlert.vue';
import MasterToolbar from '@shared/components/masters/MasterToolbar.vue';
import PaginationLinks from '@shared/components/masters/PaginationLinks.vue';
import StatusBadge from '@shared/components/masters/StatusBadge.vue';
import { useMasterIndex } from '@shared/composables/useMasterIndex';
import type { MasterFilters, Paginated, SelectOption } from '@shared/types/masters';
import { normalizePaginator } from '@shared/utils/normalizePaginator';

defineOptions({ layout: AdministrativeLayout });

interface GradeOption {
  id: number;
  name: string;
}

interface ProfessorRow {
  id: number;
  first_name: string;
  last_name: string;
  email: string | null;
  phone: string | null;
  status: string;
  deleted_at: string | null;
  grade?: { name: string };
}

const props = defineProps<{
  professors: Paginated<ProfessorRow>;
  filters: MasterFilters;
  gradeOptions: GradeOption[];
}>();

const { onSearch, onFilterChange } = useMasterIndex('/masters/professors', props.filters);

const rows = computed(() => normalizePaginator(props.professors).data);

const extraFilters = computed(() => [
  {
    key: 'grade_id',
    label: 'Grado',
    options: (props.gradeOptions ?? []).map((g) => ({ value: g.id, label: g.name })),
  },
]);

function deactivate(id: number) {
  if (confirm('¿Desactivar este profesor?')) {
    router.delete(`/masters/professors/${id}`);
  }
}

function restore(id: number) {
  router.post(`/masters/professors/${id}/restore`);
}
</script>

<template>
  <FlashAlert />
  <h1 class="page-title">Profesores</h1>
  <MasterToolbar
    :filters="filters"
    :extra-filters="extraFilters"
    create-href="/masters/professors/create"
    create-label="Profesor"
    @search="onSearch"
    @filter="onFilterChange"
  />
  <div class="card">
    <div class="table-wrap">
      <table class="data-table">
        <thead>
          <tr>
            <th>Nombre</th>
            <th>Email</th>
            <th>Teléfono</th>
            <th>Grado</th>
            <th>Estado</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="professor in rows" :key="professor.id">
            <td>{{ professor.first_name }} {{ professor.last_name }}</td>
            <td>{{ professor.email ?? '—' }}</td>
            <td>{{ professor.phone ?? '—' }}</td>
            <td>{{ professor.grade?.name ?? '—' }}</td>
            <td><StatusBadge :status="professor.status" /></td>
            <td class="table-actions">
              <template v-if="professor.deleted_at">
                <button type="button" class="btn btn-ghost btn-sm" @click="restore(professor.id)">Restaurar</button>
              </template>
              <template v-else>
                <Link :href="`/masters/professors/${professor.id}/edit`" class="btn btn-ghost btn-sm">Editar</Link>
                <button type="button" class="btn btn-ghost btn-sm" @click="deactivate(professor.id)">Desactivar</button>
              </template>
            </td>
          </tr>
          <tr v-if="rows.length === 0">
            <td colspan="6" class="hint" style="padding: 1rem">Sin resultados.</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
  <PaginationLinks :paginator="professors" />
</template>
