<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AdministrativeLayout from '@layouts/AdministrativeLayout.vue';

defineOptions({ layout: AdministrativeLayout });

interface EventRow {
  id: number;
  name: string;
  status: string;
  created_at: string;
}

interface ActiveEventStaff {
  event_id: number;
  event_name: string;
  event_status: string;
}

defineProps<{
  events: EventRow[];
  role: string;
  canAccessMasters: boolean;
  activeEventStaff: ActiveEventStaff | null;
}>();
</script>

<template>
  <div>
    <h1 class="page-title">Dashboard</h1>

    <div v-if="activeEventStaff" class="flash flash--success" style="margin-bottom: 1rem">
      Evento activo asignado: <strong>{{ activeEventStaff.event_name }}</strong>
    </div>

    <div class="stat-grid">
      <div class="stat-card">
        <div class="label">Eventos recientes</div>
        <div class="value">{{ events.length }}</div>
      </div>
      <div class="stat-card">
        <div class="label">Tu rol</div>
        <div class="value" style="font-size: 1rem; text-transform: capitalize">{{ role }}</div>
      </div>
    </div>

    <div class="card">
      <div class="card-header">Eventos</div>
      <div class="card-body">
        <p v-if="events.length === 0" class="hint">No hay eventos todavía.</p>
        <ul v-else class="event-list">
          <li v-for="event in events" :key="event.id">
            <Link :href="`/events/${event.id}/participants`">
              <strong>{{ event.name }}</strong>
            </Link>
            <span class="hint"> · {{ event.status }}</span>
          </li>
        </ul>
        <Link href="/events" class="btn btn-ghost btn-sm" style="margin-top: 0.75rem">Ver todos los eventos</Link>
      </div>
    </div>

    <div v-if="canAccessMasters" class="card">
      <div class="card-header">Maestros</div>
      <div class="card-body master-quick-links">
        <Link href="/masters/professors" class="btn btn-ghost btn-sm">Profesores</Link>
        <Link href="/masters/schools" class="btn btn-ghost btn-sm">Escuelas</Link>
        <Link href="/masters/competitors" class="btn btn-ghost btn-sm">Competidores</Link>
        <Link href="/masters/referees" class="btn btn-ghost btn-sm">Árbitros</Link>
      </div>
    </div>
  </div>
</template>

<style scoped>
.event-list {
  list-style: none;
}

.event-list li + li {
  margin-top: 0.5rem;
}

.master-quick-links {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}
</style>
