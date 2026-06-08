<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AppLogo from '@shared/components/ui/AppLogo.vue';
import LicenseBanner from '@shared/components/ui/LicenseBanner.vue';
import { useAuth } from '@shared/composables/useAuth';

defineProps<{
  title?: string;
}>();

const { user } = useAuth();
</script>

<template>
  <div class="app-layout">
    <aside class="sidebar">
      <div class="sidebar-brand">
        <AppLogo tagline="Portal escuela" :light="true" />
      </div>
      <nav class="sidebar-nav">
        <Link href="/school" class="is-active">Inicio</Link>
      </nav>
      <div class="sidebar-footer">
        <Link href="/logout" method="post" as="button">Cerrar sesión</Link>
      </div>
    </aside>
    <div class="main-area">
      <LicenseBanner />
      <header class="topbar">
        <h2>{{ title ?? 'Portal profesor' }}</h2>
        <span v-if="user" class="topbar-user">{{ user.username }}</span>
      </header>
      <main class="content">
        <slot />
      </main>
    </div>
  </div>
</template>
