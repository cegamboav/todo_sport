<script setup lang="ts">
import { computed, onBeforeUnmount, ref, watch } from 'vue';

export interface AsyncSearchOption {
  id: number;
  label: string;
  school?: string | null;
  grade?: string | null;
  age?: number | null;
  role?: string | null;
  subtitle?: string | null;
}

const props = withDefaults(
  defineProps<{
    modelValue: number | null;
    searchUrl: string;
    placeholder?: string;
    disabled?: boolean;
    minChars?: number;
    showCreateAction?: boolean;
  }>(),
  {
    placeholder: 'Buscar…',
    disabled: false,
    minChars: 2,
    showCreateAction: false,
  },
);

const emit = defineEmits<{
  'update:modelValue': [value: number | null];
  selected: [option: AsyncSearchOption];
  'no-results': [query: string];
}>();

const query = ref('');
const open = ref(false);
const loading = ref(false);
const results = ref<AsyncSearchOption[]>([]);
const selectedOption = ref<AsyncSearchOption | null>(null);
const errorMessage = ref<string | null>(null);

let debounceTimer: ReturnType<typeof setTimeout> | null = null;

const displayLabel = computed(() => {
  if (selectedOption.value) {
    return selectedOption.value.label;
  }
  return '';
});

function clearSelection() {
  selectedOption.value = null;
  emit('update:modelValue', null);
  query.value = '';
  results.value = [];
}

async function fetchResults(term: string) {
  if (term.length < props.minChars) {
    results.value = [];
    return;
  }

  loading.value = true;
  errorMessage.value = null;

  try {
    const url = `${props.searchUrl}${props.searchUrl.includes('?') ? '&' : '?'}q=${encodeURIComponent(term)}`;
    const response = await fetch(url, {
      headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      credentials: 'same-origin',
    });

    if (!response.ok) {
      throw new Error('Error de búsqueda');
    }

    const data = (await response.json()) as { results: AsyncSearchOption[] };
    results.value = data.results ?? [];
    open.value = true;
  } catch {
    errorMessage.value = 'No se pudo buscar. Intenta de nuevo.';
    results.value = [];
  } finally {
    loading.value = false;
  }
}

function onInput() {
  selectedOption.value = null;
  emit('update:modelValue', null);

  if (debounceTimer) {
    clearTimeout(debounceTimer);
  }

  debounceTimer = setTimeout(() => {
    fetchResults(query.value.trim());
  }, 280);
}

function pick(option: AsyncSearchOption) {
  selectedOption.value = option;
  emit('update:modelValue', option.id);
  emit('selected', option);
  query.value = option.label;
  open.value = false;
  results.value = [];
}

function onBlur() {
  setTimeout(() => {
    open.value = false;
  }, 180);
}

watch(
  () => props.modelValue,
  (id) => {
    if (id === null) {
      selectedOption.value = null;
    }
  },
);

onBeforeUnmount(() => {
  if (debounceTimer) {
    clearTimeout(debounceTimer);
  }
});
</script>

<template>
  <div class="async-search" :class="{ 'async-search--disabled': disabled }">
    <div class="async-search-input-wrap">
      <input
        v-model="query"
        type="search"
        class="form-input"
        :placeholder="placeholder"
        :disabled="disabled"
        autocomplete="off"
        @input="onInput"
        @focus="onInput"
        @blur="onBlur"
      />
      <button
        v-if="query || modelValue"
        type="button"
        class="async-search-clear"
        tabindex="-1"
        @click="clearSelection"
      >
        ×
      </button>
    </div>
    <p v-if="loading" class="hint async-search-meta">Buscando…</p>
    <p v-else-if="errorMessage" class="form-error">{{ errorMessage }}</p>
    <p v-else-if="displayLabel && !open" class="hint async-search-meta">Seleccionado: {{ displayLabel }}</p>

    <ul v-if="open && results.length > 0" class="async-search-results">
      <li v-for="opt in results" :key="opt.id">
        <button type="button" class="async-search-result" @mousedown.prevent="pick(opt)">
          <span class="async-search-result-label">{{ opt.label }}</span>
          <span class="async-search-result-meta">
            <template v-if="opt.school">{{ opt.school }}</template>
            <template v-if="opt.grade"> · {{ opt.grade }}</template>
            <template v-if="opt.age != null"> · {{ opt.age }} años</template>
            <template v-if="opt.role"> · {{ opt.role }}</template>
            <template v-if="opt.subtitle && !opt.role"> · {{ opt.subtitle }}</template>
          </span>
        </button>
      </li>
    </ul>
    <div
      v-else-if="open && query.length >= minChars && !loading && results.length === 0"
      class="async-search-empty"
    >
      <p class="hint async-search-meta">No existe competidor con «{{ query }}».</p>
      <button
        v-if="showCreateAction"
        type="button"
        class="btn btn-primary btn-sm async-search-create"
        @mousedown.prevent="emit('no-results', query.trim())"
      >
        Crear rápido
      </button>
    </div>
  </div>
</template>

<style scoped>
.async-search {
  position: relative;
  width: 100%;
}

.async-search-input-wrap {
  position: relative;
}

.async-search-clear {
  position: absolute;
  right: 0.35rem;
  top: 50%;
  transform: translateY(-50%);
  border: none;
  background: transparent;
  font-size: 1.25rem;
  line-height: 1;
  cursor: pointer;
  color: var(--color-text-muted);
}

.async-search-results {
  position: absolute;
  z-index: 40;
  left: 0;
  right: 0;
  margin: 0.25rem 0 0;
  padding: 0.25rem 0;
  list-style: none;
  background: #fff;
  border: 1px solid var(--color-border);
  border-radius: 8px;
  box-shadow: 0 8px 24px rgba(15, 23, 42, 0.12);
  max-height: 280px;
  overflow-y: auto;
}

.async-search-result {
  display: block;
  width: 100%;
  text-align: left;
  padding: 0.55rem 0.75rem;
  border: none;
  background: transparent;
  cursor: pointer;
}

.async-search-result:hover {
  background: #f1f5f9;
}

.async-search-result-label {
  display: block;
  font-weight: 500;
  color: var(--color-navy);
}

.async-search-result-meta {
  display: block;
  font-size: 0.78rem;
  color: var(--color-text-muted);
  margin-top: 0.15rem;
}

.async-search-meta {
  margin-top: 0.35rem;
  font-size: 0.8rem;
}

.async-search--disabled {
  opacity: 0.65;
}

.async-search-empty {
  position: absolute;
  z-index: 40;
  left: 0;
  right: 0;
  margin-top: 0.25rem;
  padding: 0.65rem 0.75rem;
  background: #fff;
  border: 1px solid var(--color-border);
  border-radius: 8px;
  box-shadow: 0 8px 24px rgba(15, 23, 42, 0.12);
}

.async-search-create {
  margin-top: 0.5rem;
  width: 100%;
}
</style>
