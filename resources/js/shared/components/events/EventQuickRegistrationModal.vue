<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import type { AsyncSearchOption } from '@shared/components/ui/AsyncSearchSelect.vue';

export interface QuickRegistrationModality {
  id: number;
  price: string | number;
  modality: { name: string; code: string };
}

export interface QuickRegistrationCombo {
  id: number;
  name: string;
  price: string | number;
  modalities: { name: string }[];
}

const props = defineProps<{
  eventId: number;
  competitor: AsyncSearchOption;
  modalities: QuickRegistrationModality[];
  combos: QuickRegistrationCombo[];
}>();

const emit = defineEmits<{
  close: [];
}>();

const form = useForm({
  competitor_id: props.competitor.id,
  event_modality_ids: [] as number[],
  event_combo_ids: [] as number[],
});

const selectionCount = computed(
  () => form.event_modality_ids.length + form.event_combo_ids.length,
);

function toggleModality(id: number) {
  const idx = form.event_modality_ids.indexOf(id);
  if (idx >= 0) {
    form.event_modality_ids.splice(idx, 1);
  } else {
    form.event_modality_ids.push(id);
  }
}

function toggleCombo(id: number) {
  const idx = form.event_combo_ids.indexOf(id);
  if (idx >= 0) {
    form.event_combo_ids.splice(idx, 1);
  } else {
    form.event_combo_ids.push(id);
  }
}

function isModalityChecked(id: number) {
  return form.event_modality_ids.includes(id);
}

function isComboChecked(id: number) {
  return form.event_combo_ids.includes(id);
}

function submit() {
  form.post(`/events/${props.eventId}/participants/quick-register`, {
    preserveScroll: true,
    onSuccess: () => emit('close'),
  });
}
</script>

<template>
  <div class="quick-reg-backdrop" @click.self="emit('close')">
    <div class="quick-reg-modal card card-body">
      <header class="quick-reg-header">
        <h3 class="section-title">Inscripción rápida</h3>
        <p class="hint">
          <strong>{{ competitor.label }}</strong>
          <span v-if="competitor.school"> · {{ competitor.school }}</span>
        </p>
      </header>

      <form class="quick-reg-form" @submit.prevent="submit">
        <div v-if="modalities.length" class="quick-reg-group">
          <h4 class="quick-reg-group-title">Modalidades</h4>
          <ul class="quick-reg-options">
            <li v-for="m in modalities" :key="m.id">
              <label class="quick-reg-option">
                <input
                  type="checkbox"
                  :checked="isModalityChecked(m.id)"
                  :disabled="form.processing"
                  @change="toggleModality(m.id)"
                />
                <span class="quick-reg-option-label">{{ m.modality.name }}</span>
                <span class="quick-reg-option-meta">${{ m.price }}</span>
              </label>
            </li>
          </ul>
        </div>

        <div v-if="combos.length" class="quick-reg-group">
          <h4 class="quick-reg-group-title">Combos</h4>
          <ul class="quick-reg-options">
            <li v-for="c in combos" :key="c.id">
              <label class="quick-reg-option">
                <input
                  type="checkbox"
                  :checked="isComboChecked(c.id)"
                  :disabled="form.processing"
                  @change="toggleCombo(c.id)"
                />
                <span class="quick-reg-option-label">{{ c.name }}</span>
                <span class="quick-reg-option-meta">${{ c.price }}</span>
              </label>
              <p v-if="c.modalities.length" class="quick-reg-combo-hint">
                {{ c.modalities.map((x) => x.name).join(' + ') }}
              </p>
            </li>
          </ul>
        </div>

        <p v-if="modalities.length === 0 && combos.length === 0" class="hint hint--warn">
          No hay modalidades ni combos habilitados en este evento. Configúralos antes de inscribir.
        </p>

        <p v-else class="hint quick-reg-footnote">
          Casos especiales (sin cobro, duplicados, overrides) en la edición avanzada de cada participante.
        </p>

        <p v-if="form.errors.competitor_id" class="form-error">{{ form.errors.competitor_id }}</p>
        <p v-if="form.errors.event_modality_ids" class="form-error">{{ form.errors.event_modality_ids }}</p>
        <p v-if="form.errors.event_combo_ids" class="form-error">{{ form.errors.event_combo_ids }}</p>

        <div class="quick-reg-actions">
          <button
            type="submit"
            class="btn btn-primary btn-inline"
            :disabled="form.processing || (modalities.length === 0 && combos.length === 0)"
          >
            {{
              form.processing
                ? 'Guardando…'
                : selectionCount > 0
                  ? `Inscribir (${selectionCount})`
                  : 'Agregar al evento'
            }}
          </button>
          <button type="button" class="btn btn-ghost" :disabled="form.processing" @click="emit('close')">
            Cancelar
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<style scoped>
.section-title {
  font-size: 1.05rem;
  margin: 0 0 0.25rem;
}

.quick-reg-backdrop {
  position: fixed;
  inset: 0;
  z-index: 100;
  background: rgba(15, 23, 42, 0.45);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1rem;
}

.quick-reg-modal {
  width: 100%;
  max-width: 480px;
  max-height: min(90vh, 640px);
  overflow-y: auto;
}

.quick-reg-header {
  margin-bottom: 1rem;
}

.quick-reg-group {
  margin-bottom: 1rem;
}

.quick-reg-group-title {
  font-size: 0.8rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: var(--color-text-muted, #64748b);
  margin: 0 0 0.5rem;
}

.quick-reg-options {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.quick-reg-option {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  cursor: pointer;
  padding: 0.4rem 0.5rem;
  border-radius: 6px;
  border: 1px solid var(--color-border, #e2e8f0);
}

.quick-reg-option:hover {
  background: var(--color-surface-muted, #f8fafc);
}

.quick-reg-option-label {
  flex: 1;
  font-weight: 500;
}

.quick-reg-option-meta {
  font-size: 0.85rem;
  color: var(--color-text-muted, #64748b);
}

.quick-reg-combo-hint {
  margin: 0.15rem 0 0 1.75rem;
  font-size: 0.8rem;
  color: var(--color-text-muted, #64748b);
}

.quick-reg-footnote {
  margin-bottom: 0.75rem;
}

.hint--warn {
  color: #b45309;
}

.quick-reg-actions {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
  padding-top: 0.25rem;
}

.form-error {
  color: #dc2626;
  font-size: 0.875rem;
  margin: 0 0 0.5rem;
}
</style>
