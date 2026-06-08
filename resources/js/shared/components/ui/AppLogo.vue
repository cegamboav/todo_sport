<script setup lang="ts">
withDefaults(
  defineProps<{
    tagline?: string;
    light?: boolean;
  }>(),
  {
    tagline: 'Gestión de torneos · local',
    light: false,
  },
);
</script>

<template>
  <div class="app-logo" :class="{ 'app-logo--light': light }">
    <h1>Todo Sport</h1>
    <p v-if="tagline">{{ tagline }}</p>
  </div>
</template>

<style scoped>
.app-logo h1 {
  font-size: 1.25rem;
  font-weight: 700;
  background: linear-gradient(90deg, var(--color-navy), var(--color-brand));
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.app-logo--light h1 {
  background: none;
  -webkit-text-fill-color: #fff;
  color: #fff;
}

.app-logo p {
  font-size: 0.8rem;
  color: var(--color-text-muted);
  margin-top: 0.2rem;
}

.app-logo--light p {
  color: rgba(255, 255, 255, 0.75);
}
</style>
