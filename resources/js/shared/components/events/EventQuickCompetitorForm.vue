<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';
import type { SelectOption } from '@shared/types/masters';

const props = defineProps<{
  eventId: number;
  schoolOptions: SelectOption[];
  gradeOptions: SelectOption[];
  initialQuery?: string;
}>();

const emit = defineEmits<{
  cancel: [];
}>();

function splitQuery(query: string): { first: string; last: string } {
  const parts = query.trim().split(/\s+/).filter(Boolean);
  if (parts.length === 0) {
    return { first: '', last: '' };
  }
  if (parts.length === 1) {
    return { first: parts[0], last: '' };
  }

  return { first: parts[0], last: parts.slice(1).join(' ') };
}

const parsed = splitQuery(props.initialQuery ?? '');

const form = useForm({
  first_name: parsed.first,
  last_name: parsed.last,
  school_id: '' as string | number,
  gender: '',
  birth_date: '',
  grade_id: '' as string | number,
});

watch(
  () => props.initialQuery,
  (query) => {
    if (!query) {
      return;
    }
    const next = splitQuery(query);
    form.first_name = next.first;
    form.last_name = next.last;
  },
);

function submit() {
  form.post(`/events/${props.eventId}/participants/quick-create`, {
    preserveScroll: true,
    onSuccess: () => emit('cancel'),
  });
}
</script>

<template>
  <div class="quick-competitor card card-body">
    <h3 class="quick-competitor-title">Crear competidor rápido</h3>
    <p class="hint">Se guarda en catálogo y abre inscripción rápida sin salir del torneo.</p>
    <form class="form-grid" @submit.prevent="submit">
      <div class="form-group">
        <label>Nombre *</label>
        <input v-model="form.first_name" type="text" class="form-input" required />
        <p v-if="form.errors.first_name" class="form-error">{{ form.errors.first_name }}</p>
      </div>
      <div class="form-group">
        <label>Apellido *</label>
        <input v-model="form.last_name" type="text" class="form-input" required />
        <p v-if="form.errors.last_name" class="form-error">{{ form.errors.last_name }}</p>
      </div>
      <div class="form-group" style="grid-column: 1 / -1">
        <label>Escuela *</label>
        <select v-model="form.school_id" class="form-input" required>
          <option value="">Seleccionar…</option>
          <option v-for="opt in schoolOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
        </select>
        <p v-if="form.errors.school_id" class="form-error">{{ form.errors.school_id }}</p>
      </div>
      <div class="form-group">
        <label>Grado</label>
        <select v-model="form.grade_id" class="form-input">
          <option value="">Opcional</option>
          <option v-for="opt in gradeOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
        </select>
      </div>
      <div class="form-group">
        <label>Fecha nacimiento</label>
        <input v-model="form.birth_date" type="date" class="form-input" />
      </div>
      <div class="form-group form-actions-inline" style="grid-column: 1 / -1">
        <button type="submit" class="btn btn-primary btn-inline" :disabled="form.processing">
          {{ form.processing ? 'Creando…' : 'Crear e inscribir' }}
        </button>
        <button type="button" class="btn btn-ghost" @click="emit('cancel')">Cancelar</button>
      </div>
    </form>
  </div>
</template>

<style scoped>
.quick-competitor {
  margin-top: 1rem;
  border: 1px dashed var(--color-navy);
  background: #f8fafc;
}

.quick-competitor-title {
  font-size: 0.95rem;
  margin-bottom: 0.35rem;
}

.form-actions-inline {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}
</style>
