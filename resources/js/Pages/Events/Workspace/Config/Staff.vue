<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import EventWorkspaceLayout from '@layouts/EventWorkspaceLayout.vue';
import EventConfigNav from '@shared/components/events/EventConfigNav.vue';
import AsyncSearchSelect from '@shared/components/ui/AsyncSearchSelect.vue';
import { useEventWorkspacePage } from '@shared/composables/useEventWorkspacePage';

defineOptions({ layout: EventWorkspaceLayout });

const { event, canManageStaff, searchUrls } = useEventWorkspacePage();

const staffForm = useForm({
  user_id: null as number | null,
});

function assignStaff() {
  if (!staffForm.user_id) {
    staffForm.setError('user_id', 'Selecciona un usuario.');
    return;
  }

  staffForm.post(`/events/${event.value.id}/staff`, { preserveScroll: true });
}

function removeStaff(userId: number) {
  router.delete(`/events/${event.value.id}/staff/${userId}`, { preserveScroll: true });
}
</script>

<template>
  <div class="card card-body">
    <h2 class="section-title">Configuración del torneo</h2>
    <EventConfigNav />
    <h3 class="subsection-title">Staff</h3>
    <ul v-if="event.event_staff.length" class="staff-list">
      <li v-for="row in event.event_staff" :key="row.id">
        <span>{{ row.user.username }} <span class="hint">({{ row.user.role }})</span></span>
        <button v-if="canManageStaff" type="button" class="btn btn-ghost btn-sm" @click="removeStaff(row.user.id)">
          Quitar
        </button>
      </li>
    </ul>
    <form v-if="canManageStaff" class="form-grid" @submit.prevent="assignStaff">
      <div class="form-group" style="grid-column: 1 / -1">
        <label>Buscar usuario operativo</label>
        <AsyncSearchSelect
          v-model="staffForm.user_id"
          :search-url="searchUrls.staffUsers"
          placeholder="Staff, mesa, corner…"
        />
        <p v-if="staffForm.errors.user_id" class="form-error">{{ staffForm.errors.user_id }}</p>
      </div>
      <button type="submit" class="btn btn-primary btn-inline" :disabled="staffForm.processing">
        {{ staffForm.processing ? 'Asignando…' : 'Asignar' }}
      </button>
    </form>
    <p v-if="event.event_staff.length === 0" class="hint">Sin staff asignado.</p>
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

.staff-list {
  list-style: none;
  margin: 0 0 1rem;
  padding: 0;
}

.staff-list li {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.35rem 0;
  border-bottom: 1px solid var(--color-border);
}
</style>
