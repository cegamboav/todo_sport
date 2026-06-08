<script setup lang="ts">
import EventWorkspaceLayout from '@layouts/EventWorkspaceLayout.vue';

defineOptions({ layout: EventWorkspaceLayout });
</script>

<template>
  <div class="card card-body">
    <h2 class="section-title">Operaciones en vivo</h2>
    <p class="hint" style="max-width: 42rem">
      Zona preparada para S2C: categorías, rings, cola, check-in y scoring. Mientras tanto, usa el menú lateral del
      torneo para inscripciones y configuración.
    </p>
    <ul class="hint ops-list">
      <li>Categorías y brackets</li>
      <li>Rings y cola operacional</li>
      <li>Check-in y realtime</li>
    </ul>
  </div>
</template>

<style scoped>
.section-title {
  font-size: 1rem;
  margin-bottom: 0.75rem;
}

.ops-list {
  margin-top: 1rem;
  padding-left: 1.25rem;
}
</style>
