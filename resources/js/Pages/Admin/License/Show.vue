<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import AdministrativeLayout from '@layouts/AdministrativeLayout.vue';
import type { LicenseState } from '@shared/types';

defineOptions({ layout: AdministrativeLayout });

const props = defineProps<{
  license: LicenseState;
}>();

const form = useForm<{ license_file: File | null }>({
  license_file: null,
});

function onFileChange(event: Event) {
  const input = event.target as HTMLInputElement;
  form.license_file = input.files?.[0] ?? null;
}

function submit() {
  if (!form.license_file) {
    return;
  }
  form.post('/admin/license/import', {
    forceFormData: true,
    onSuccess: () => form.reset(),
  });
}
</script>

<template>
  <div style="max-width: 640px">
    <h1 class="page-title">Licencia del sistema</h1>

    <div
      v-if="license.status === 'active'"
      class="card"
      style="background: #ecfdf5; border-color: #6ee7b7; margin-bottom: 1rem"
    >
      <div class="card-body">
        <strong>Licencia activa</strong>
        <span v-if="license.organization"> — {{ license.organization }}</span>
        <p v-if="license.expires_at" class="hint" style="margin-top: 0.35rem">
          Válida hasta <strong>{{ license.expires_at }}</strong>
          <span v-if="license.days_remaining != null">
            ({{ license.days_remaining }} días restantes)
          </span>
        </p>
      </div>
    </div>

    <div class="card">
      <div class="card-header">Resumen</div>
      <div class="card-body" style="font-size: 0.88rem">
        <p><strong>Estado:</strong> {{ license.status }}</p>
        <p v-if="license.license_id"><strong>ID:</strong> {{ license.license_id }}</p>
        <p v-if="license.max_rings != null">
          <strong>Rings máximo:</strong> {{ license.max_rings }}
        </p>
        <p v-if="license.features.length">
          <strong>Módulos:</strong> {{ license.features.join(', ') }}
        </p>
        <p><strong>Escritura:</strong> {{ license.is_writable ? 'Sí' : 'Solo lectura' }}</p>
      </div>
    </div>

    <div class="card">
      <div class="card-header">Renovar o actualizar</div>
      <div class="card-body">
        <p class="hint" style="margin-bottom: 0.75rem">
          Importa un archivo <code>.license</code> o JSON de desarrollo.
        </p>
        <input
          type="file"
          accept=".license,.json,application/json"
          style="margin-bottom: 0.75rem; width: 100%"
          @change="onFileChange"
        />
        <p v-if="form.errors.license_file" class="form-error">{{ form.errors.license_file }}</p>
        <button type="button" class="btn btn-primary" style="width: auto" @click="submit">
          Importar licencia
        </button>
      </div>
    </div>
  </div>
</template>
