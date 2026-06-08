<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLogo from '@shared/components/ui/AppLogo.vue';
import LicenseBanner from '@shared/components/ui/LicenseBanner.vue';
import { useAuth } from '@shared/composables/useAuth';
import type { SharedPageProps } from '@shared/types';

defineProps<{
  title?: string;
}>();

const { user } = useAuth();
const page = usePage<SharedPageProps>();

const access = computed(() => page.props.auth.access);
const isAdmin = computed(() => user.value?.role === 'admin');
const canAccessMasters = computed(() => access.value?.can_access_masters ?? false);
const canAccessEvents = computed(() => access.value?.can_access_events ?? false);
</script>

<template>
  <div class="app-layout">
    <aside class="sidebar">
      <div class="sidebar-brand">
        <AppLogo tagline="" :light="true" />
      </div>
      <nav class="sidebar-nav">
        <Link href="/dashboard" :class="{ 'is-active': page.url.startsWith('/dashboard') }">
          Dashboard
        </Link>
        <Link
          v-if="canAccessEvents"
          href="/events"
          :class="{
            'is-active': page.url.startsWith('/events') && !page.url.includes('/operations'),
          }"
        >
          Eventos
        </Link>
        <Link
          v-if="isAdmin"
          href="/admin/license"
          :class="{ 'is-active': page.url.startsWith('/admin/license') }"
        >
          Licencia
        </Link>
        <template v-if="isAdmin">
          <p class="sidebar-section">Configuración</p>
          <Link
            href="/config/modalities"
            :class="{ 'is-active': page.url.startsWith('/config/modalities') }"
          >
            Modalidades
          </Link>
        </template>
        <template v-if="canAccessMasters">
          <p class="sidebar-section">Catálogo base</p>
          <Link
            href="/masters/schools"
            :class="{ 'is-active': page.url.startsWith('/masters/schools') }"
          >
            Escuelas
          </Link>
          <Link
            href="/masters/professors"
            :class="{ 'is-active': page.url.startsWith('/masters/professors') }"
          >
            Profesores
          </Link>
          <Link
            href="/masters/competitors"
            :class="{ 'is-active': page.url.startsWith('/masters/competitors') }"
          >
            Competidores
          </Link>
          <Link
            href="/masters/referees"
            :class="{ 'is-active': page.url.startsWith('/masters/referees') }"
          >
            Árbitros
          </Link>
        </template>
      </nav>
      <div class="sidebar-footer">
        <Link href="/logout" method="post" as="button">Cerrar sesión</Link>
      </div>
    </aside>
    <div class="main-area">
      <LicenseBanner />
      <header class="topbar">
        <h2>{{ title ?? 'Administración' }}</h2>
        <span v-if="user" class="topbar-user">{{ user.username }} · {{ user.role }}</span>
      </header>
      <main class="content">
        <slot />
      </main>
    </div>
  </div>
</template>

<style scoped>
.sidebar-brand :deep(h1) {
  font-size: 1rem;
}
</style>
