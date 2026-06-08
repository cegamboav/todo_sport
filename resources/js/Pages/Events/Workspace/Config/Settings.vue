<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import EventWorkspaceLayout from '@layouts/EventWorkspaceLayout.vue';
import EventConfigNav from '@shared/components/events/EventConfigNav.vue';
import { useEventWorkspacePage } from '@shared/composables/useEventWorkspacePage';

defineOptions({ layout: EventWorkspaceLayout });

const { event, canManage, thirdPlaceOptions } = useEventWorkspacePage();

const settingsForm = useForm({
  third_place_mode: event.value.settings?.third_place_mode ?? 'no_bronze',
  allow_team_forms: event.value.settings?.allow_team_forms ?? false,
});

const selectedThirdPlace = computed(() =>
  thirdPlaceOptions.value.find((o) => o.value === settingsForm.third_place_mode),
);
</script>

<template>
  <div class="card card-body">
    <h2 class="section-title">Configuración del torneo</h2>
    <EventConfigNav />
    <h3 class="subsection-title">Configuración general</h3>
    <p class="hint">Reglas estructurales (tercer lugar, formas equipo). Cobros globales — próximamente.</p>
    <form v-if="canManage" class="form-grid" @submit.prevent="settingsForm.put(`/events/${event.id}/settings`)">
      <div class="form-group" style="grid-column: 1 / -1">
        <label for="third_place_mode">Modo de bronce *</label>
        <select id="third_place_mode" v-model="settingsForm.third_place_mode" class="form-input">
          <option v-for="opt in thirdPlaceOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
        </select>
        <p v-if="selectedThirdPlace?.description" class="hint" style="margin-top: 0.5rem">
          {{ selectedThirdPlace.description }}
        </p>
      </div>
      <div class="form-group" style="grid-column: 1 / -1">
        <label class="hint" style="display: flex; gap: 0.35rem; align-items: center">
          <input v-model="settingsForm.allow_team_forms" type="checkbox" />
          Permitir formas por equipo
        </label>
      </div>
      <button type="submit" class="btn btn-primary btn-inline" :disabled="settingsForm.processing">Guardar</button>
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
