<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import AppLogo from '@shared/components/ui/AppLogo.vue';
import LicenseBanner from '@shared/components/ui/LicenseBanner.vue';
import { useAuth } from '@shared/composables/useAuth';

defineProps<{
  title?: string;
}>();

const { user } = useAuth();
const page = usePage();
</script>

<template>
  <div class="app-layout ring-layout">
    <aside class="sidebar">
      <div class="sidebar-brand">
        <AppLogo tagline="Mesa" :light="true" />
      </div>
      <nav class="sidebar-nav">
        <Link href="/rings" :class="{ 'is-active': page.url.startsWith('/rings') }">Rings</Link>
      </nav>
      <div class="sidebar-footer">
        <Link href="/logout" method="post" as="button">Salir</Link>
      </div>
    </aside>
    <div class="main-area">
      <LicenseBanner />
      <header class="topbar">
        <h2>{{ title ?? 'Mesa de ring' }}</h2>
        <span v-if="user" class="topbar-user">{{ user.username }}</span>
      </header>
      <main class="content">
        <slot />
      </main>
    </div>
  </div>
</template>
