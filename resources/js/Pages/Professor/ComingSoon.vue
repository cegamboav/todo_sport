<script setup lang="ts">
import ProfessorLayout from '@layouts/ProfessorLayout.vue';

defineOptions({ layout: ProfessorLayout });

withDefaults(
  defineProps<{
    message?: string;
  }>(),
  {
    message: 'Portal escuela — próximamente',
  },
);
</script>

<template>
  <div class="card" style="max-width: 480px; text-align: center">
    <div class="card-body" style="padding: 2rem">
      <h1 class="page-title">{{ message }}</h1>
      <p class="hint">Resultados y gestión escolar llegarán en una versión futura.</p>
    </div>
  </div>
</template>
