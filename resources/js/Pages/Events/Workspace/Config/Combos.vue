<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import EventWorkspaceLayout from '@layouts/EventWorkspaceLayout.vue';
import EventConfigNav from '@shared/components/events/EventConfigNav.vue';
import { useEventWorkspacePage } from '@shared/composables/useEventWorkspacePage';

defineOptions({ layout: EventWorkspaceLayout });

const { event, canManage } = useEventWorkspacePage();

const comboForm = useForm({
  name: '',
  price: '' as string | number,
  modality_ids: [] as number[],
});

const enabledModalityOptions = computed(() =>
  event.value.event_modalities.filter((m) => m.enabled).map((m) => m.modality),
);

function createCombo() {
  comboForm.post(`/events/${event.value.id}/combos`, {
    preserveScroll: true,
    onSuccess: () => comboForm.reset(),
  });
}
</script>

<template>
  <div class="card card-body">
    <h2 class="section-title">Configuración del torneo</h2>
    <EventConfigNav />
    <h3 class="subsection-title">Combos</h3>
    <p class="hint">Precios manuales por combo.</p>
    <div class="table-wrap" style="margin-bottom: 1.5rem">
      <table class="data-table">
        <thead>
          <tr>
            <th>Combo</th>
            <th>Incluye</th>
            <th>Precio</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="combo in event.combos" :key="combo.id">
            <td>{{ combo.name }}</td>
            <td>{{ combo.modalities.map((m) => m.name).join(' + ') }}</td>
            <td>{{ combo.price }}</td>
          </tr>
          <tr v-if="event.combos.length === 0">
            <td colspan="3" class="hint table-empty">Sin combos.</td>
          </tr>
        </tbody>
      </table>
    </div>
    <form v-if="canManage" class="form-grid" @submit.prevent="createCombo">
      <div class="form-group">
        <label>Nombre *</label>
        <input v-model="comboForm.name" type="text" class="form-input" required />
      </div>
      <div class="form-group">
        <label>Precio *</label>
        <input v-model="comboForm.price" type="number" min="0" step="0.01" class="form-input" required />
      </div>
      <div class="form-group" style="grid-column: 1 / -1">
        <label>Modalidades incluidas *</label>
        <div class="checkbox-row">
          <label v-for="m in enabledModalityOptions" :key="m.id" class="hint">
            <input v-model="comboForm.modality_ids" type="checkbox" :value="m.id" />
            {{ m.name }}
          </label>
        </div>
      </div>
      <button type="submit" class="btn btn-primary btn-inline">Crear combo</button>
    </form>
  </div>
</template>

<style scoped>
.section-title {
  font-size: 1rem;
  margin-bottom: 0.5rem;
}

.subsection-title {
  font-size: 0.92rem;
  margin-bottom: 0.5rem;
}

.checkbox-row {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
}
</style>
