<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLogo from '@shared/components/ui/AppLogo.vue';
import LicenseBanner from '@shared/components/ui/LicenseBanner.vue';
import FlashAlert from '@shared/components/masters/FlashAlert.vue';
import { useAuth } from '@shared/composables/useAuth';

interface WorkspaceMeta {
  title: string;
  status: string;
  status_label: string;
  event_date: string | null;
  venue: string | null;
  summary: { participants: number; pending_charges: number };
}

const page = usePage<{
  event: { id: number; name: string };
  workspace: WorkspaceMeta;
  canAccessEventOperations?: boolean;
}>();

const { user } = useAuth();

const eventId = computed(() => page.props.event.id);
const base = computed(() => `/events/${eventId.value}`);
const workspace = computed(() => page.props.workspace);

const canAccessOperations = computed(() => page.props.canAccessEventOperations ?? true);

const navItems = [
  { id: 'overview', label: 'Resumen', href: () => base.value },
  { id: 'participants', label: 'Participantes', href: () => `${base.value}/participants` },
  { id: 'categories', label: 'Categorías', href: () => `${base.value}/categories` },
  { id: 'config', label: 'Configuración evento', href: () => `${base.value}/config/modalities` },
];

const futureItems = [{ label: 'Rings (S2C)' }];

function isActive(href: string, id: string): boolean {
  const path = page.url.split('?')[0];
  if (id === 'overview') {
    return path === base.value;
  }
  if (id === 'config') {
    return path.includes('/config');
  }
  if (id === 'categories') {
    return path.includes('/categories');
  }

  return path === href || path.startsWith(`${href}/`);
}
</script>

<template>
  <div class="app-layout event-workspace-layout">
    <aside class="sidebar event-workspace-sidebar">
      <div class="sidebar-brand">
        <AppLogo tagline="Torneo" :light="true" />
        <p class="event-workspace-name">{{ workspace.title }}</p>
        <p class="event-workspace-meta">
          <span class="event-workspace-status">{{ workspace.status_label }}</span>
        </p>
      </div>

      <nav class="sidebar-nav">
        <p class="sidebar-section">Administración</p>
        <Link
          v-for="item in navItems"
          :key="item.id"
          :href="item.href()"
          :class="{ 'is-active': isActive(item.href(), item.id) }"
        >
          {{ item.label }}
        </Link>

        <p class="sidebar-section">Próximamente</p>
        <span v-for="item in futureItems" :key="item.label" class="sidebar-link sidebar-link--disabled">
          {{ item.label }}
        </span>
      </nav>

      <div class="sidebar-footer">
        <Link
          v-if="canAccessOperations"
          :href="`${base}/operations`"
          class="sidebar-switch"
        >
          → Operaciones en vivo
        </Link>
        <Link href="/events" class="sidebar-exit">← Salir del torneo</Link>
        <Link href="/logout" method="post" as="button">Cerrar sesión</Link>
      </div>
    </aside>

    <div class="main-area">
      <LicenseBanner />
      <header class="topbar event-workspace-topbar">
        <div>
          <p class="topbar-eyebrow">Administración del torneo</p>
          <h2>{{ workspace.title }}</h2>
        </div>
        <div class="topbar-right">
          <span class="topbar-stat">{{ workspace.summary.participants }} participantes</span>
          <span class="topbar-stat">{{ workspace.summary.pending_charges }} cobros pend.</span>
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
.event-workspace-sidebar {
  border-right: 3px solid #1e3a5f;
  background: linear-gradient(180deg, #0f2744 0%, #132f52 100%);
}

.event-workspace-name {
  margin: 0.5rem 0 0.15rem;
  font-size: 0.82rem;
  line-height: 1.35;
  color: rgba(255, 255, 255, 0.92);
  font-weight: 600;
}

.event-workspace-meta {
  margin: 0;
  font-size: 0.72rem;
  color: rgba(255, 255, 255, 0.6);
}

.event-workspace-status {
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

.event-workspace-topbar {
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
</style>
