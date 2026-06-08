<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import EventWorkspaceLayout from '@layouts/EventWorkspaceLayout.vue';
import EventConfigNav from '@shared/components/events/EventConfigNav.vue';
import { useEventWorkspacePage } from '@shared/composables/useEventWorkspacePage';

defineOptions({ layout: EventWorkspaceLayout });

const { event, canManage } = useEventWorkspacePage();

const modalityRows = ref(
  event.value.event_modalities.map((row) => ({
    modality_id: row.modality_id,
    enabled: row.enabled,
    price: Number(row.price),
  })),
);

function saveModalities() {
  router.put(`/events/${event.value.id}/modalities`, { modalities: modalityRows.value }, { preserveScroll: true });
}
</script>

<template>
  <div class="card card-body">
    <h2 class="section-title">Configuración del torneo</h2>
    <EventConfigNav />
    <h3 class="subsection-title">Modalidades</h3>
    <p class="hint">Habilita modalidades y define precio para este evento.</p>
    <form v-if="canManage" @submit.prevent="saveModalities">
      <div class="table-wrap">
        <table class="data-table">
          <thead>
            <tr>
              <th>Modalidad</th>
              <th>Habilitada</th>
              <th>Precio</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(row, idx) in modalityRows" :key="row.modality_id">
              <td>{{ event.event_modalities[idx]?.modality.name }}</td>
              <td><input v-model="row.enabled" type="checkbox" /></td>
              <td>
                <input v-model.number="row.price" type="number" min="0" step="0.01" class="form-input" style="max-width: 120px" />
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <button type="submit" class="btn btn-primary btn-inline" style="margin-top: 1rem">Guardar</button>
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
</style>
