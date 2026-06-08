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
import { formatAge } from '@shared/utils/formatAge';
import { normalizePaginator } from '@shared/utils/normalizePaginator';

defineOptions({ layout: AdministrativeLayout });

interface SchoolOption {
  id: number;
  name: string;
  abbreviation: string;
}

interface GradeOption {
  id: number;
  name: string;
}

interface CompetitorRow {
  id: number;
  first_name: string;
  last_name: string;
  gender: string;
  birth_date: string;
  age: number | null;
  status: string;
  deleted_at: string | null;
  school?: { name: string; abbreviation: string };
  grade?: { name: string };
}

const props = defineProps<{
  competitors: Paginated<CompetitorRow>;
  filters: MasterFilters;
  genderOptions: SelectOption[];
  schoolOptions: SchoolOption[];
  gradeOptions: GradeOption[];
}>();

const { onSearch, onFilterChange } = useMasterIndex('/masters/competitors', props.filters);

const rows = computed(() => normalizePaginator(props.competitors).data);

const extraFilters = computed(() => [
  {
    key: 'gender',
    label: 'Género',
    options: props.genderOptions ?? [],
  },
  {
    key: 'school_id',
    label: 'Escuela',
    options: (props.schoolOptions ?? []).map((s) => ({
      value: s.id,
      label: `${s.name} (${s.abbreviation})`,
    })),
  },
  {
    key: 'grade_id',
    label: 'Grado',
    options: (props.gradeOptions ?? []).map((g) => ({ value: g.id, label: g.name })),
  },
]);

function genderLabel(value: string) {
  return props.genderOptions.find((o) => o.value === value)?.label ?? value;
}

function deactivate(id: number) {
  if (confirm('¿Desactivar este competidor?')) {
    router.delete(`/masters/competitors/${id}`);
  }
}

function restore(id: number) {
  router.post(`/masters/competitors/${id}/restore`);
}
</script>

<template>
  <FlashAlert />
  <h1 class="page-title">Competidores</h1>
  <MasterToolbar
    :filters="filters"
    :extra-filters="extraFilters"
    create-href="/masters/competitors/create"
    create-label="Competidor"
    @search="onSearch"
    @filter="onFilterChange"
  />
  <div class="card">
    <div class="table-wrap">
      <table class="data-table">
        <thead>
          <tr>
            <th>Nombre</th>
            <th>Género</th>
            <th>Edad</th>
            <th>Escuela</th>
            <th>Grado</th>
            <th>Estado</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="competitor in rows" :key="competitor.id">
            <td>{{ competitor.first_name }} {{ competitor.last_name }}</td>
            <td>{{ genderLabel(competitor.gender) }}</td>
            <td>{{ formatAge(competitor.age) }}</td>
            <td>
              <span v-if="competitor.school">
                {{ competitor.school.name }}
                <span class="abbr-tag">{{ competitor.school.abbreviation }}</span>
              </span>
              <span v-else class="hint">—</span>
            </td>
            <td>{{ competitor.grade?.name ?? '—' }}</td>
            <td><StatusBadge :status="competitor.status" /></td>
            <td class="table-actions">
              <template v-if="competitor.deleted_at">
                <button type="button" class="btn btn-ghost btn-sm" @click="restore(competitor.id)">Restaurar</button>
              </template>
              <template v-else>
                <Link :href="`/masters/competitors/${competitor.id}/edit`" class="btn btn-ghost btn-sm">Editar</Link>
                <button type="button" class="btn btn-ghost btn-sm" @click="deactivate(competitor.id)">Desactivar</button>
              </template>
            </td>
          </tr>
          <tr v-if="rows.length === 0">
            <td colspan="7" class="hint table-empty">Sin resultados.</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
  <PaginationLinks :paginator="competitors" />
</template>
