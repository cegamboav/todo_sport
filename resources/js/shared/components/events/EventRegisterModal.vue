<script setup lang="ts">
import { router } from '@inertiajs/vue3';

const props = defineProps<{
  eventId: number;
  modal: {
    participantId: number;
    itemType: 'modality' | 'combo';
    itemId: number;
    label: string;
    price: string | number;
    is_billable: boolean;
    allow_duplicate_override: boolean;
  };
  isAdmin: boolean;
}>();

const emit = defineEmits<{
  close: [];
}>();

function submit() {
  router.post(
    `/events/${props.eventId}/participants/${props.modal.participantId}/items`,
    {
      item_type: props.modal.itemType,
      event_modality_id: props.modal.itemType === 'modality' ? props.modal.itemId : undefined,
      event_combo_id: props.modal.itemType === 'combo' ? props.modal.itemId : undefined,
      is_billable: props.modal.is_billable,
      allow_duplicate_override: props.modal.allow_duplicate_override,
    },
    {
      preserveScroll: true,
      onSuccess: () => emit('close'),
    },
  );
}
</script>

<template>
  <div class="register-modal-backdrop" @click.self="emit('close')">
    <div class="register-modal card card-body">
      <h3 class="section-title">Registrar: {{ modal.label }}</h3>
      <form class="form-grid" @submit.prevent="submit">
        <div class="form-group" style="grid-column: 1 / -1">
          <label class="hint" style="display: flex; gap: 0.35rem; align-items: center">
            <input v-model="modal.is_billable" type="checkbox" />
            Generar cobro (precio {{ modal.price }})
          </label>
          <p class="hint">Desmarca para inscripción sin cobro.</p>
        </div>
        <div v-if="isAdmin" class="form-group" style="grid-column: 1 / -1">
          <label class="hint hint--warning" style="display: flex; gap: 0.35rem; align-items: center">
            <input v-model="modal.allow_duplicate_override" type="checkbox" />
            Override admin — permitir duplicado
          </label>
        </div>
        <div class="form-group form-actions-inline" style="grid-column: 1 / -1">
          <button type="submit" class="btn btn-primary btn-inline">Confirmar</button>
          <button type="button" class="btn btn-ghost" @click="emit('close')">Cancelar</button>
        </div>
      </form>
    </div>
  </div>
</template>

<style scoped>
.section-title {
  font-size: 1rem;
  margin-bottom: 0.75rem;
}

.register-modal-backdrop {
  position: fixed;
  inset: 0;
  z-index: 100;
  background: rgba(15, 23, 42, 0.45);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1rem;
}

.register-modal {
  width: 100%;
  max-width: 420px;
}

.form-actions-inline {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.hint--warning {
  color: #b45309;
}
</style>
