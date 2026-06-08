<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const eventId = computed(() => {
  const match = page.url.match(/\/events\/(\d+)/);
  return match ? match[1] : '';
});

const base = computed(() => `/events/${eventId.value}/config`);

const items = [
  { label: 'Modalidades', path: '/modalities' },
  { label: 'Combos', path: '/combos' },
  { label: 'Staff', path: '/staff' },
  { label: 'Configuración general', path: '/settings' },
];

function isActive(path: string): boolean {
  const url = page.url.split('?')[0];
  return url === `${base.value}${path}` || url === `${base.value}` && path === '/modalities';
}
</script>

<template>
  <nav class="config-subnav">
    <Link
      v-for="item in items"
      :key="item.path"
      :href="`${base}${item.path}`"
      class="config-subnav-link"
      :class="{ 'config-subnav-link--active': isActive(item.path) }"
    >
      {{ item.label }}
    </Link>
  </nav>
</template>

<style scoped>
.config-subnav {
  display: flex;
  flex-wrap: wrap;
  gap: 0.35rem;
  margin-bottom: 1.25rem;
  padding-bottom: 0.75rem;
  border-bottom: 1px solid var(--color-border);
}

.config-subnav-link {
  padding: 0.4rem 0.75rem;
  border-radius: 8px;
  font-size: 0.85rem;
  text-decoration: none;
  color: var(--color-navy);
  border: 1px solid var(--color-border);
  background: #fff;
}

.config-subnav-link--active {
  background: var(--color-navy);
  color: #fff;
  border-color: var(--color-navy);
}
</style>
