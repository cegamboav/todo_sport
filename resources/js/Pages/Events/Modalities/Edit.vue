<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import AdministrativeLayout from '@layouts/AdministrativeLayout.vue';
import FlashAlert from '@shared/components/masters/FlashAlert.vue';

defineOptions({ layout: AdministrativeLayout });

const props = defineProps<{
  modality: {
    id: number;
    code: string;
    name: string;
    description: string | null;
    is_active: boolean;
  };
}>();

const showAdvanced = ref(false);

const form = useForm({
  name: props.modality.name,
  code: props.modality.code,
  description: props.modality.description ?? '',
  is_active: props.modality.is_active,
});

function submit() {
  form.transform((data) => ({
    ...data,
    code: data.code?.trim() || null,
  })).put(`/config/modalities/${props.modality.id}`);
}
</script>

<template>
  <FlashAlert />
  <h1 class="page-title">Editar modalidad</h1>
  <div class="card card-body">
    <form @submit.prevent="submit">
      <div class="form-grid">
        <div class="form-group" style="grid-column: 1 / -1">
          <label>Nombre *</label>
          <input v-model="form.name" type="text" class="form-input" required />
          <p v-if="form.errors.name" class="form-error">{{ form.errors.name }}</p>
        </div>
        <div class="form-group">
          <label class="hint" style="display: flex; gap: 0.35rem; align-items: center">
            <input v-model="form.is_active" type="checkbox" />
            Activa en catálogo
          </label>
        </div>
      </div>

      <button type="button" class="btn btn-ghost btn-sm advanced-toggle" @click="showAdvanced = !showAdvanced">
        {{ showAdvanced ? 'Ocultar opciones avanzadas' : 'Opciones avanzadas' }}
      </button>

      <div v-if="showAdvanced" class="form-grid advanced-panel">
        <div class="form-group">
          <label>Código interno</label>
          <input v-model="form.code" type="text" class="form-input" />
          <p class="hint">Identificador técnico único. No cambiar sin necesidad operativa.</p>
          <p v-if="form.errors.code" class="form-error">{{ form.errors.code }}</p>
        </div>
        <div class="form-group" style="grid-column: 1 / -1">
          <label>Descripción</label>
          <textarea v-model="form.description" class="form-input" rows="2" />
        </div>
      </div>

      <div class="form-actions">
        <button type="submit" class="btn btn-primary btn-inline" :disabled="form.processing">
          {{ form.processing ? 'Guardando…' : 'Guardar' }}
        </button>
        <Link href="/config/modalities" class="btn btn-ghost">Volver</Link>
      </div>
    </form>
  </div>
</template>

<style scoped>
.advanced-toggle {
  margin: 1rem 0 0.5rem;
}

.advanced-panel {
  margin-top: 0.5rem;
  padding-top: 0.75rem;
  border-top: 1px dashed var(--color-border);
}
</style>
