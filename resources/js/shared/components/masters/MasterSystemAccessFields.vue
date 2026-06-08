<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
  form: {
    access_username: string;
    access_password: string;
    update_system_password: boolean;
    errors: Record<string, string>;
  };
  existingUsername?: string | null;
  requireCredentials?: boolean;
}>();

const showCreateFields = computed(
  () => props.requireCredentials && !props.existingUsername,
);
const showPasswordUpdate = computed(
  () => Boolean(props.existingUsername) && props.form.update_system_password,
);
</script>

<template>
  <div class="form-group" style="grid-column: 1 / -1">
    <template v-if="existingUsername">
      <p class="hint" style="margin-bottom: 0.75rem">
        Acceso portal: <strong>{{ existingUsername }}</strong>
      </p>
      <label class="hint" style="display: flex; align-items: center; gap: 0.35rem; margin-bottom: 0.5rem">
        <input v-model="form.update_system_password" type="checkbox" />
        Actualizar contraseña
      </label>
      <div v-if="showPasswordUpdate" class="form-group" style="margin: 0">
        <label for="access_password">Nueva contraseña *</label>
        <input
          id="access_password"
          v-model="form.access_password"
          type="password"
          class="form-input"
          autocomplete="new-password"
        />
        <p v-if="form.errors.access_password" class="form-error">{{ form.errors.access_password }}</p>
      </div>
    </template>
    <template v-else-if="showCreateFields">
      <p class="hint" style="margin-bottom: 0.75rem">Credenciales de acceso (obligatorias)</p>
      <div class="form-grid" style="margin-top: 0">
        <div class="form-group">
          <label for="access_username">Usuario *</label>
          <input
            id="access_username"
            v-model="form.access_username"
            type="text"
            class="form-input"
            placeholder="juan.perez"
            autocomplete="off"
            required
          />
          <p v-if="form.errors.access_username" class="form-error">{{ form.errors.access_username }}</p>
        </div>
        <div class="form-group">
          <label for="access_password">Contraseña inicial *</label>
          <input
            id="access_password"
            v-model="form.access_password"
            type="password"
            class="form-input"
            placeholder="juan2026"
            autocomplete="new-password"
            required
          />
          <p v-if="form.errors.access_password" class="form-error">{{ form.errors.access_password }}</p>
        </div>
      </div>
    </template>
  </div>
</template>
