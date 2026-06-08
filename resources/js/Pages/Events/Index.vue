<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AdministrativeLayout from '@layouts/AdministrativeLayout.vue';
import FlashAlert from '@shared/components/masters/FlashAlert.vue';
import PaginationLinks from '@shared/components/masters/PaginationLinks.vue';
import type { Paginated, SelectOption } from '@shared/types/masters';
import { normalizePaginator } from '@shared/utils/normalizePaginator';

defineOptions({ layout: AdministrativeLayout });

interface EventRow {
  id: number;
  name: string;
  status: string;
  event_date: string | null;
  venue: string | null;
  host_school?: { name: string; abbreviation: string };
}

const props = defineProps<{
  events: Paginated<EventRow>;
  filters: { search?: string; status?: string };
  statusOptions: SelectOption[];
}>();

const search = ref(props.filters.search ?? '');
const status = ref(props.filters.status ?? '');

const rows = computed(() => normalizePaginator(props.events).data);

function applyFilters() {
  router.get('/events', {
    search: search.value || undefined,
    status: status.value || undefined,
  }, { preserveState: true, replace: true });
}

function statusLabel(value: string) {
  return props.statusOptions.find((o) => o.value === value)?.label ?? value;
}
</script>

<template>
  <FlashAlert />
  <div class="page-header-row">
    <h1 class="page-title">Eventos</h1>
    <Link href="/events/create" class="btn btn-primary" style="width: auto">+ Nuevo evento</Link>
  </div>

  <form class="master-toolbar-filters" style="margin-bottom: 1rem" @submit.prevent="applyFilters">
    <input v-model="search" type="search" class="form-input" placeholder="Buscar…" style="max-width: 220px" />
    <select v-model="status" class="form-input" style="max-width: 200px">
      <option value="">Estado del evento</option>
      <option v-for="opt in statusOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
    </select>
    <button type="submit" class="btn btn-ghost">Filtrar</button>
  </form>

  <div class="card">
    <div class="table-wrap">
      <table class="data-table">
        <thead>
          <tr>
            <th>Nombre</th>
            <th>Fecha</th>
            <th>Sede</th>
            <th>Estado</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="event in rows" :key="event.id">
            <td>
              <Link :href="`/events/${event.id}/participants`" class="cell-primary">{{ event.name }}</Link>
              <span v-if="event.host_school" class="abbr-tag">{{ event.host_school.abbreviation }}</span>
            </td>
            <td>{{ event.event_date ?? '—' }}</td>
            <td>{{ event.venue ?? '—' }}</td>
            <td><span class="event-status-pill">{{ statusLabel(event.status) }}</span></td>
            <td class="table-actions">
              <Link :href="`/events/${event.id}/participants`" class="btn btn-primary btn-sm">Operar</Link>
              <Link :href="`/events/${event.id}/edit`" class="btn btn-ghost btn-sm">Editar</Link>
            </td>
          </tr>
          <tr v-if="rows.length === 0">
            <td colspan="5" class="hint table-empty">Sin eventos.</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
  <PaginationLinks :paginator="events" />
</template>
