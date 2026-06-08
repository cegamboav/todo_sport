<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import EventOperationsLayout from '@layouts/EventOperationsLayout.vue';

defineOptions({ layout: EventOperationsLayout });

interface OperationalModule {
  id: string;
  label: string;
  description: string;
  status: string;
}

const page = usePage<{
  event: { id: number; name: string };
  operations: {
    summary: { participants: number; pending_charges: number };
  };
  operationalModules: OperationalModule[];
  canAccessEventAdmin: boolean;
}>();

const eventId = page.props.event.id;
</script>

<template>
  <div class="operations-index">
    <section class="card card-body">
      <h2 class="section-title">Centro operativo del torneo</h2>
      <p class="hint intro">
        Workspace separado de la administración del evento. Aquí vivirá la operación en vivo:
        caja, check-in, pagos y validación — sin mezclar configuración ni inscripciones masivas.
      </p>
      <div class="ops-summary">
        <span class="ops-stat">{{ operations.summary.participants }} participantes activos</span>
        <span class="ops-stat ops-stat--warn">{{ operations.summary.pending_charges }} cobros pendientes</span>
      </div>
    </section>

    <section class="ops-modules">
      <article v-for="mod in operationalModules" :key="mod.id" class="card card-body ops-module">
        <div class="ops-module-head">
          <h3 class="ops-module-title">{{ mod.label }}</h3>
          <span class="ops-badge">Próximamente</span>
        </div>
        <p class="hint">{{ mod.description }}</p>
      </article>
    </section>

    <section v-if="canAccessEventAdmin" class="card card-body ops-admin-link">
      <p class="hint">
        Inscripciones, modalidades y configuración están en el workspace de administración.
      </p>
      <Link :href="`/events/${eventId}/participants`" class="btn btn-primary btn-inline">
        Ir a Participantes (admin)
      </Link>
    </section>
  </div>
</template>

<style scoped>
.section-title {
  font-size: 1rem;
  margin-bottom: 0.5rem;
}

.intro {
  max-width: 42rem;
}

.ops-summary {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  margin-top: 1rem;
}

.ops-stat {
  font-size: 0.82rem;
  background: #f1f5f9;
  padding: 0.35rem 0.65rem;
  border-radius: 6px;
}

.ops-stat--warn {
  background: #fffbeb;
  color: #b45309;
}

.ops-modules {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 0.75rem;
  margin-top: 1rem;
}

.ops-module-head {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 0.5rem;
  margin-bottom: 0.35rem;
}

.ops-module-title {
  font-size: 0.92rem;
  margin: 0;
}

.ops-badge {
  font-size: 0.68rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: #64748b;
  background: #f1f5f9;
  padding: 0.15rem 0.45rem;
  border-radius: 4px;
  white-space: nowrap;
}

.ops-admin-link {
  margin-top: 1rem;
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 1rem;
}
</style>
