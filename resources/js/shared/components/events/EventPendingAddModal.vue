<script setup lang="ts">
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import type { AsyncSearchOption } from '@shared/components/ui/AsyncSearchSelect.vue';

const props = defineProps<{
  eventId: number;
  searchUrl: string;
  totalPending: number;
}>();

const emit = defineEmits<{
  close: [];
  'quick-add': [option: AsyncSearchOption];
}>();

const query = ref('');
const loading = ref(false);
const results = ref<AsyncSearchOption[]>([]);
const errorMessage = ref<string | null>(null);

let debounceTimer: ReturnType<typeof setTimeout> | null = null;

async function fetchResults(term: string) {
  loading.value = true;
  errorMessage.value = null;

  try {
    const params = new URLSearchParams();
    if (term.length > 0) {
      params.set('q', term);
    }
    params.set('limit', '30');

    const url = `${props.searchUrl}${props.searchUrl.includes('?') ? '&' : '?'}${params.toString()}`;
    const response = await fetch(url, {
      headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      credentials: 'same-origin',
    });

    if (!response.ok) {
      throw new Error('Error de búsqueda');
    }

    const data = (await response.json()) as { results: AsyncSearchOption[] };
    results.value = data.results ?? [];
  } catch {
    errorMessage.value = 'No se pudo cargar la lista. Intenta de nuevo.';
    results.value = [];
  } finally {
    loading.value = false;
  }
}

function onSearchInput() {
  if (debounceTimer) {
    clearTimeout(debounceTimer);
  }

  debounceTimer = setTimeout(() => {
    fetchResults(query.value.trim());
  }, 280);
}

const emptyMessage = computed(() => {
  if (loading.value) {
    return null;
  }
  if (query.value.trim().length > 0 && results.value.length === 0) {
    return 'Sin resultados para esta búsqueda.';
  }
  if (results.value.length === 0) {
    return 'No hay competidores pendientes de agregar.';
  }

  return null;
});

watch(
  () => props.searchUrl,
  () => fetchResults(''),
  { immediate: true },
);

onBeforeUnmount(() => {
  if (debounceTimer) {
    clearTimeout(debounceTimer);
  }
});
</script>

<template>
  <div class="pending-modal-backdrop" @click.self="emit('close')">
    <div class="pending-modal card card-body">
      <header class="pending-modal-header">
        <h3 class="section-title">Pendientes de agregar</h3>
        <p class="hint">
          {{ totalPending }} competidor(es) en catálogo aún no inscritos en este torneo.
        </p>
      </header>

      <div class="pending-search">
        <input
          v-model="query"
          type="search"
          class="form-input"
          placeholder="Filtrar por nombre…"
          autocomplete="off"
          @input="onSearchInput"
        />
      </div>

      <p v-if="loading" class="hint">Cargando…</p>
      <p v-if="errorMessage" class="form-error">{{ errorMessage }}</p>
      <p v-if="emptyMessage" class="hint pending-empty">{{ emptyMessage }}</p>

      <ul v-else class="pending-list">
        <li v-for="row in results" :key="row.id" class="pending-row">
          <div class="pending-row-info">
            <strong>{{ row.label }}</strong>
            <span v-if="row.school" class="pending-row-meta">{{ row.school }}</span>
            <span v-if="row.grade" class="pending-row-meta">{{ row.grade }}</span>
          </div>
          <button type="button" class="btn btn-primary btn-sm" @click="emit('quick-add', row)">
            Inscribir
          </button>
        </li>
      </ul>

      <div class="pending-modal-actions">
        <button type="button" class="btn btn-ghost" @click="emit('close')">Cerrar</button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.section-title {
  font-size: 1.05rem;
  margin: 0 0 0.25rem;
}

.pending-modal-backdrop {
  position: fixed;
  inset: 0;
  z-index: 100;
  background: rgba(15, 23, 42, 0.45);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1rem;
}

.pending-modal {
  width: 100%;
  max-width: 520px;
  max-height: min(85vh, 640px);
  display: flex;
  flex-direction: column;
}

.pending-modal-header {
  margin-bottom: 0.75rem;
}

.pending-search {
  margin-bottom: 0.75rem;
}

.pending-list {
  list-style: none;
  margin: 0;
  padding: 0;
  overflow-y: auto;
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.pending-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  padding: 0.5rem 0.65rem;
  border: 1px solid var(--color-border, #e2e8f0);
  border-radius: 8px;
}

.pending-row-info {
  display: flex;
  flex-direction: column;
  gap: 0.1rem;
  min-width: 0;
}

.pending-row-meta {
  font-size: 0.78rem;
  color: var(--color-text-muted, #64748b);
}

.pending-empty {
  padding: 1rem 0;
  text-align: center;
}

.pending-modal-actions {
  margin-top: 0.75rem;
  padding-top: 0.5rem;
  border-top: 1px solid var(--color-border, #e2e8f0);
}

.form-error {
  color: #dc2626;
  font-size: 0.875rem;
}
</style>
