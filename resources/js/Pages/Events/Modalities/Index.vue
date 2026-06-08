<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AdministrativeLayout from '@layouts/AdministrativeLayout.vue';
import FlashAlert from '@shared/components/masters/FlashAlert.vue';
import PaginationLinks from '@shared/components/masters/PaginationLinks.vue';
import type { Paginated } from '@shared/types/masters';
import { normalizePaginator } from '@shared/utils/normalizePaginator';

defineOptions({ layout: AdministrativeLayout });

interface ModalityRow {
  id: number;
  name: string;
  is_active: boolean;
}

const props = defineProps<{
  modalities: Paginated<ModalityRow>;
  filters: { search?: string };
}>();

const search = ref(props.filters.search ?? '');
const rows = computed(() => normalizePaginator(props.modalities).data);

function applyFilters() {
  router.get('/config/modalities', { search: search.value || undefined }, { preserveState: true, replace: true });
}
</script>

<template>
  <FlashAlert />
  <div class="page-header-row">
    <h1 class="page-title">Catálogo de modalidades</h1>
    <Link href="/config/modalities/create" class="btn btn-primary" style="width: auto">+ Modalidad</Link>
  </div>
  <p class="hint" style="margin-bottom: 1rem; max-width: 40rem">
    Configuración global reutilizable. Los eventos solo habilitan modalidades y asignan precio.
  </p>
  <form class="master-toolbar-filters" style="margin-bottom: 1rem" @submit.prevent="applyFilters">
    <input v-model="search" type="search" class="form-input" placeholder="Buscar por nombre…" style="max-width: 220px" />
    <button type="submit" class="btn btn-ghost">Buscar</button>
  </form>
  <div class="card">
    <div class="table-wrap">
      <table class="data-table">
        <thead>
          <tr>
            <th>Nombre</th>
            <th>Estado</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="m in rows" :key="m.id">
            <td>{{ m.name }}</td>
            <td>
              <span class="status-pill" :class="m.is_active ? 'status-pill--active' : 'status-pill--inactive'">
                {{ m.is_active ? 'Activa' : 'Inactiva' }}
              </span>
            </td>
            <td class="table-actions">
              <Link :href="`/config/modalities/${m.id}/edit`" class="btn btn-ghost btn-sm">Editar</Link>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
  <PaginationLinks :paginator="modalities" />
</template>

<style scoped>
.status-pill {
  display: inline-block;
  font-size: 0.78rem;
  font-weight: 600;
  padding: 0.2rem 0.55rem;
  border-radius: 999px;
}

.status-pill--active {
  background: #dcfce7;
  color: #166534;
}

.status-pill--inactive {
  background: #f1f5f9;
  color: #64748b;
}
</style>
