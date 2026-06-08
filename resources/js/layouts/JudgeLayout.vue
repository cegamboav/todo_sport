<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import LicenseBanner from '@shared/components/ui/LicenseBanner.vue';
import { useAuth } from '@shared/composables/useAuth';

defineProps<{
  title?: string;
}>();

const { user } = useAuth();
</script>

<template>
  <div class="judge-layout">
    <LicenseBanner />
    <header class="topbar">
      <h2>{{ title ?? 'Juez de esquina' }}</h2>
      <div class="topbar-actions">
        <span v-if="user" class="topbar-user">{{ user.username }}</span>
        <Link href="/logout" method="post" as="button" class="btn btn-ghost btn-sm">Salir</Link>
      </div>
    </header>
    <main class="content">
      <slot />
    </main>
  </div>
</template>

<style scoped>
.topbar-actions {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.topbar-user {
  color: rgba(255, 255, 255, 0.75);
}

.btn-sm {
  padding: 0.35rem 0.65rem;
  font-size: 0.8rem;
}

.judge-layout .btn-ghost {
  color: #fff;
  border-color: rgba(255, 255, 255, 0.3);
}
</style>
