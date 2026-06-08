<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLogo from '@shared/components/ui/AppLogo.vue';
import LicenseBanner from '@shared/components/ui/LicenseBanner.vue';
import FlashAlert from '@shared/components/masters/FlashAlert.vue';
import { useAuth } from '@shared/composables/useAuth';

interface OperationsMeta {
  title: string;
  status: string;
  status_label: string;
  event_date: string | null;
  venue: string | null;
  summary: { participants: number; pending_charges: number };
}

const page = usePage<{
  event: { id: number; name: string };
  operations: OperationsMeta;
  canAccessEventAdmin: boolean;
}>();

const { user } = useAuth();

const eventId = computed(() => page.props.event.id);
const base = computed(() => `/events/${eventId.value}`);
const operations = computed(() => page.props.operations);

const navItems = [
  { id: 'overview', label: 'Centro operativo', href: () => `${base.value}/operations` },
];
</script>

<template>
  <div class="app-layout event-operations-layout">
    <aside class="sidebar event-operations-sidebar">
      <div class="sidebar-brand">
        <AppLogo tagline="Operaciones" :light="true" />
        <p class="event-operations-name">{{ operations.title }}</p>
        <p class="event-operations-meta">
          <span class="event-operations-status">{{ operations.status_label }}</span>
        </p>
      </div>

      <nav class="sidebar-nav">
        <p class="sidebar-section">Torneo en vivo</p>
        <Link
          v-for="item in navItems"
          :key="item.id"
          :href="item.href()"
          class="is-active"
        >
          {{ item.label }}
        </Link>

        <p class="sidebar-section">Próximamente</p>
        <span class="sidebar-link sidebar-link--disabled">Caja / POS</span>
        <span class="sidebar-link sidebar-link--disabled">Check-in</span>
        <span class="sidebar-link sidebar-link--disabled">Pagos en cola</span>
        <span class="sidebar-link sidebar-link--disabled">Validación acceso</span>
      </nav>

      <div class="sidebar-footer">
        <Link
          v-if="canAccessEventAdmin"
          :href="base"
          class="sidebar-switch"
        >
          ← Administración del evento
        </Link>
        <Link href="/events" class="sidebar-exit">← Salir del torneo</Link>
        <Link href="/logout" method="post" as="button">Cerrar sesión</Link>
      </div>
    </aside>

    <div class="main-area">
      <LicenseBanner />
      <header class="topbar event-operations-topbar">
        <div>
          <p class="topbar-eyebrow">Operación en vivo</p>
          <h2>{{ operations.title }}</h2>
        </div>
        <div class="topbar-right">
          <span class="topbar-stat">{{ operations.summary.participants }} participantes</span>
          <span class="topbar-stat topbar-stat--warn">{{ operations.summary.pending_charges }} cobros pend.</span>
          <span v-if="user" class="topbar-user">{{ user.username }}</span>
        </div>
      </header>
      <main class="content">
        <FlashAlert />
        <slot />
      </main>
    </div>
  </div>
</template>

<style scoped>
.event-operations-sidebar {
  border-right: 3px solid #7c2d12;
  background: linear-gradient(180deg, #431407 0%, #5c1d0e 100%);
}

.event-operations-name {
  margin: 0.5rem 0 0.15rem;
  font-size: 0.82rem;
  line-height: 1.35;
  color: rgba(255, 255, 255, 0.92);
  font-weight: 600;
}

.event-operations-meta {
  margin: 0;
  font-size: 0.72rem;
  color: rgba(255, 255, 255, 0.6);
}

.event-operations-status {
  display: inline-block;
  padding: 0.1rem 0.4rem;
  border-radius: 4px;
  background: rgba(255, 255, 255, 0.12);
}

.sidebar-link--disabled {
  display: block;
  padding: 0.45rem 1rem;
  font-size: 0.84rem;
  color: rgba(255, 255, 255, 0.4);
}

.sidebar-switch {
  display: block;
  margin-bottom: 0.5rem;
  font-size: 0.85rem;
  font-weight: 600;
  color: rgba(255, 255, 255, 0.9);
  text-decoration: none;
}

.sidebar-switch:hover {
  color: #fff;
}

.sidebar-exit {
  display: block;
  margin-bottom: 0.5rem;
  font-size: 0.85rem;
  font-weight: 600;
  color: rgba(255, 255, 255, 0.85);
  text-decoration: none;
}

.sidebar-exit:hover {
  color: #fff;
}

.event-operations-topbar {
  align-items: flex-start;
}

.topbar-eyebrow {
  margin: 0 0 0.15rem;
  font-size: 0.72rem;
  font-weight: 700;
  letter-spacing: 0.05em;
  text-transform: uppercase;
  color: var(--color-text-muted);
}

.topbar-right {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
  align-items: center;
}

.topbar-stat {
  font-size: 0.82rem;
  color: var(--color-text-muted);
  background: #f1f5f9;
  padding: 0.25rem 0.55rem;
  border-radius: 6px;
}

.topbar-stat--warn {
  background: #fffbeb;
  color: #b45309;
}
</style>
