<script setup lang="ts">
defineProps<{
  status: string;
}>();
</script>

<template>
  <span class="status-badge" :class="status === 'active' ? 'status-badge--active' : 'status-badge--inactive'">
    {{ status === 'active' ? 'Activo' : 'Inactivo' }}
  </span>
</template>
