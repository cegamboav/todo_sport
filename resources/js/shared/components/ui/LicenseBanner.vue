<script setup lang="ts">
import { computed } from 'vue';
import { useAuth } from '@shared/composables/useAuth';

const { license } = useAuth();

const message = computed(() => {
  const banner = license.value.banner;
  if (!banner) {
    return null;
  }

  switch (license.value.status) {
    case 'grace':
      return 'Licencia en período de gracia. Renueva pronto para mantener escritura completa.';
    case 'expired':
      return 'Licencia expirada. Solo lectura hasta importar una licencia válida.';
    case 'invalid':
      return 'Licencia inválida. Contacta soporte o importa un archivo válido.';
    case 'missing':
      return 'Sin licencia activa. Importa un archivo de licencia en administración.';
    default:
      return 'Estado de licencia requiere atención.';
  }
});
</script>

<template>
  <div
    v-if="license.banner && message"
    class="license-banner"
    :class="`license-banner--${license.banner}`"
    role="status"
  >
    {{ message }}
  </div>
</template>
