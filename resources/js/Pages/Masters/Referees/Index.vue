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

interface RefereeRow {
  id: number;
  first_name: string;
  last_name: string;
  email: string | null;
  phone: string | null;
  specialty: string;
  status: string;
  deleted_at: string | null;
  grade?: { name: string };
}

const props = defineProps<{
  referees: Paginated<RefereeRow>;
  filters: MasterFilters;
  specialtyOptions: SelectOption[];
  gradeOptions: GradeOption[];
}>();

const { onSearch, onFilterChange } = useMasterIndex('/masters/referees', props.filters);

const rows = computed(() => normalizePaginator(props.referees).data);

const extraFilters = computed(() => [
  {
    key: 'specialty',
    label: 'Función',
    options: props.specialtyOptions ?? [],
  },
  {
    key: 'grade_id',
    label: 'Grado',
    options: (props.gradeOptions ?? []).map((g) => ({ value: g.id, label: g.name })),
  },
]);

function specialtyLabel(value: string) {
  return props.specialtyOptions.find((o) => o.value === value)?.label ?? value;
}

function deactivate(id: number) {
  if (confirm('¿Desactivar este árbitro?')) {
    router.delete(`/masters/referees/${id}`);
  }
}

function restore(id: number) {
  router.post(`/masters/referees/${id}/restore`);
}
</script>

<template>
  <FlashAlert />
  <h1 class="page-title">Árbitros</h1>
  <MasterToolbar
    :filters="filters"
    :extra-filters="extraFilters"
    create-href="/masters/referees/create"
    create-label="Árbitro"
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
            <th>Función</th>
            <th>Estado</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="referee in rows" :key="referee.id">
            <td>{{ referee.first_name }} {{ referee.last_name }}</td>
            <td>{{ referee.email ?? '—' }}</td>
            <td>{{ referee.phone ?? '—' }}</td>
            <td>{{ referee.grade?.name ?? '—' }}</td>
            <td>{{ specialtyLabel(referee.specialty) }}</td>
            <td><StatusBadge :status="referee.status" /></td>
            <td class="table-actions">
              <template v-if="referee.deleted_at">
                <button type="button" class="btn btn-ghost btn-sm" @click="restore(referee.id)">Restaurar</button>
              </template>
              <template v-else>
                <Link :href="`/masters/referees/${referee.id}/edit`" class="btn btn-ghost btn-sm">Editar</Link>
                <button type="button" class="btn btn-ghost btn-sm" @click="deactivate(referee.id)">Desactivar</button>
              </template>
            </td>
          </tr>
          <tr v-if="rows.length === 0">
            <td colspan="7" class="hint" style="padding: 1rem">Sin resultados.</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
  <PaginationLinks :paginator="referees" />
</template>
