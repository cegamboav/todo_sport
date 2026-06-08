<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import EventWorkspaceLayout from '@layouts/EventWorkspaceLayout.vue';
import { useEventWorkspacePage } from '@shared/composables/useEventWorkspacePage';

defineOptions({ layout: EventWorkspaceLayout });

const { event, workspace, canManage, statusOptions } = useEventWorkspacePage();

const statusForm = useForm({ status: event.value.status });
</script>

<template>
  <div class="card card-body">
    <h2 class="section-title">Resumen del torneo</h2>
    <p class="hint">
      Estás operando <strong>{{ workspace.title }}</strong>. El catálogo global del sistema queda fuera de este
      entorno.
    </p>
    <dl class="overview-facts">
      <dt>Estado</dt>
      <dd>{{ workspace.status_label }}</dd>
      <dt v-if="workspace.event_date">Fecha</dt>
      <dd v-if="workspace.event_date">{{ workspace.event_date }}</dd>
      <dt v-if="workspace.venue">Sede</dt>
      <dd v-if="workspace.venue">{{ workspace.venue }}</dd>
      <dt>Participantes</dt>
      <dd>{{ workspace.summary.participants }}</dd>
      <dt>Cobros pendientes</dt>
      <dd>{{ workspace.summary.pending_charges }}</dd>
    </dl>

    <form v-if="canManage" class="form-grid" style="margin-top: 1.5rem" @submit.prevent="statusForm.put(`/events/${event.id}/status`)">
      <div class="form-group">
        <label for="status">Cambiar estado del evento</label>
        <select id="status" v-model="statusForm.status" class="form-input">
          <option v-for="opt in statusOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
        </select>
      </div>
      <div class="form-group" style="align-self: end">
        <button type="submit" class="btn btn-primary btn-inline" :disabled="statusForm.processing">Aplicar</button>
      </div>
    </form>

    <div class="overview-links">
      <Link :href="`/events/${event.id}/participants`" class="btn btn-primary btn-inline">Ir a participantes</Link>
      <Link :href="`/events/${event.id}/config/modalities`" class="btn btn-ghost btn-inline">Configuración evento</Link>
      <Link :href="`/events/${event.id}/edit`" class="btn btn-ghost btn-sm">Editar datos del evento</Link>
    </div>
  </div>
</template>

<style scoped>
.section-title {
  font-size: 1rem;
  margin-bottom: 0.75rem;
}

.overview-facts {
  display: grid;
  grid-template-columns: auto 1fr;
  gap: 0.35rem 1rem;
  margin: 1rem 0 0;
}

.overview-facts dt {
  font-weight: 600;
  color: var(--color-text-muted);
}

.overview-links {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  margin-top: 1.25rem;
}
</style>
