<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import AdministrativeLayout from '@layouts/AdministrativeLayout.vue';
import FlashAlert from '@shared/components/masters/FlashAlert.vue';

defineOptions({ layout: AdministrativeLayout });

interface DirectorOption {
  id: number;
  first_name: string;
  last_name: string;
}

defineProps<{
  directorOptions: DirectorOption[];
}>();

const form = useForm({
  name: '',
  abbreviation: '',
  country: '',
  city: '',
  director_id: '' as string | number,
  notes: '',
});

function submit() {
  form
    .transform((data) => ({
      ...data,
      abbreviation: data.abbreviation.trim().toUpperCase(),
      director_id: Number(data.director_id),
    }))
    .post('/masters/schools');
}
</script>

<template>
  <FlashAlert />
  <h1 class="page-title">Nueva escuela</h1>
  <div class="card">
    <div class="card-body">
      <form @submit.prevent="submit">
        <div class="form-grid">
          <div class="form-group">
            <label for="name">Nombre *</label>
            <input id="name" v-model="form.name" type="text" class="form-input" required />
            <p v-if="form.errors.name" class="form-error">{{ form.errors.name }}</p>
          </div>
          <div class="form-group">
            <label for="abbreviation">Abreviatura *</label>
            <input
              id="abbreviation"
              v-model="form.abbreviation"
              type="text"
              class="form-input"
              maxlength="15"
              placeholder="DEMO"
              required
              style="text-transform: uppercase"
            />
            <p class="hint">Mayúsculas, máx. 15 caracteres. Se usa en brackets y listados de ring.</p>
            <p v-if="form.errors.abbreviation" class="form-error">{{ form.errors.abbreviation }}</p>
          </div>
          <div class="form-group">
            <label for="country">País *</label>
            <input id="country" v-model="form.country" type="text" class="form-input" required />
            <p v-if="form.errors.country" class="form-error">{{ form.errors.country }}</p>
          </div>
          <div class="form-group">
            <label for="city">Ciudad *</label>
            <input id="city" v-model="form.city" type="text" class="form-input" required />
            <p v-if="form.errors.city" class="form-error">{{ form.errors.city }}</p>
          </div>
          <div class="form-group">
            <label for="director_id">Director *</label>
            <select id="director_id" v-model="form.director_id" class="form-input" required>
              <option value="" disabled>Seleccionar…</option>
              <option v-for="d in directorOptions" :key="d.id" :value="d.id">
                {{ d.first_name }} {{ d.last_name }}
              </option>
            </select>
            <p v-if="form.errors.director_id" class="form-error">{{ form.errors.director_id }}</p>
          </div>
          <div class="form-group" style="grid-column: 1 / -1">
            <label for="notes">Notas</label>
            <textarea id="notes" v-model="form.notes" class="form-input" rows="3" />
            <p v-if="form.errors.notes" class="form-error">{{ form.errors.notes }}</p>
          </div>
        </div>
        <div class="form-actions">
          <button type="submit" class="btn btn-primary btn-inline" :disabled="form.processing">
            {{ form.processing ? 'Guardando…' : 'Crear escuela' }}
          </button>
          <Link href="/masters/schools" class="btn btn-ghost">Cancelar</Link>
        </div>
      </form>
    </div>
  </div>
</template>
