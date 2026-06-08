<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import type { MasterFilters, SelectOption } from '@shared/types/masters';

const props = defineProps<{
  filters: MasterFilters;
  extraFilters?: { key: string; label: string; options: SelectOption[] }[];
  createHref: string;
  createLabel?: string;
}>();

const emit = defineEmits<{
  search: [value: string];
  filter: [key: string, value: string];
}>();

const search = ref(props.filters.search ?? '');

watch(
  () => props.filters.search,
  (v) => {
    search.value = v ?? '';
  },
);

function submitSearch() {
  emit('search', search.value);
}
</script>

<template>
  <div class="master-toolbar">
    <form class="master-toolbar-filters" @submit.prevent="submitSearch">
      <input
        v-model="search"
        type="search"
        class="form-input"
        placeholder="Buscar…"
        style="max-width: 220px"
      />
      <template v-for="ef in extraFilters ?? []" :key="ef.key">
        <select
          class="form-input"
          style="max-width: 160px"
          :value="filters[ef.key] ?? ''"
          @change="emit('filter', ef.key, ($event.target as HTMLSelectElement).value)"
        >
          <option value="">{{ ef.label }}</option>
          <option v-for="opt in ef.options" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
        </select>
      </template>
      <label class="hint" style="display: flex; align-items: center; gap: 0.35rem">
        <input
          type="checkbox"
          :checked="filters.only_trashed === '1'"
          @change="emit('filter', 'only_trashed', ($event.target as HTMLInputElement).checked ? '1' : '')"
        />
        Solo desactivados
      </label>
      <button type="submit" class="btn btn-ghost">Buscar</button>
    </form>
    <Link :href="createHref" class="btn btn-primary" style="width: auto">+ {{ createLabel ?? 'Nuevo' }}</Link>
  </div>
</template>
