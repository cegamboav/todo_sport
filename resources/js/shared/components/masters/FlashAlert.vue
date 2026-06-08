<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const success = computed(() => page.props.flash?.success as string | undefined);
const error = computed(() => page.props.flash?.error as string | undefined);
const validationError = computed(() => {
  const errors = page.props.errors as Record<string, string> | undefined;
  if (!errors) {
    return undefined;
  }
  const first = Object.values(errors)[0];
  return typeof first === 'string' ? first : undefined;
});
</script>

<template>
  <div v-if="success" class="flash flash--success">{{ success }}</div>
  <div v-if="error" class="flash flash--error">{{ error }}</div>
  <div v-else-if="validationError" class="flash flash--error">{{ validationError }}</div>
</template>
