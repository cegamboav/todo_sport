<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import AdministrativeLayout from '@layouts/AdministrativeLayout.vue';
import FlashAlert from '@shared/components/masters/FlashAlert.vue';

defineOptions({ layout: AdministrativeLayout });

interface SchoolOption {
  id: number;
  name: string;
  abbreviation: string;
}

defineProps<{
  schoolOptions: SchoolOption[];
}>();

const form = useForm({
  name: '',
  event_date: '',
  venue: '',
  host_school_id: '' as string | number,
  notes: '',
});

function submit() {
  form
    .transform((data) => ({
      ...data,
      host_school_id: data.host_school_id === '' ? null : Number(data.host_school_id),
      event_date: data.event_date || null,
      notes: data.notes || null,
    }))
    .post('/events');
}
</script>

<template>
  <FlashAlert />
  <h1 class="page-title">Nuevo evento</h1>
  <div class="card">
    <div class="card-body">
      <form @submit.prevent="submit">
        <div class="form-grid">
          <div class="form-group" style="grid-column: 1 / -1">
            <label for="name">Nombre *</label>
            <input id="name" v-model="form.name" type="text" class="form-input" required />
            <p v-if="form.errors.name" class="form-error">{{ form.errors.name }}</p>
          </div>
          <div class="form-group">
            <label for="event_date">Fecha</label>
            <input id="event_date" v-model="form.event_date" type="date" class="form-input" />
          </div>
          <div class="form-group">
            <label for="venue">Sede</label>
            <input id="venue" v-model="form.venue" type="text" class="form-input" />
          </div>
          <div class="form-group">
            <label for="host_school_id">Escuela anfitriona</label>
            <select id="host_school_id" v-model="form.host_school_id" class="form-input">
              <option value="">Sin escuela</option>
              <option v-for="s in schoolOptions" :key="s.id" :value="s.id">
                {{ s.name }} ({{ s.abbreviation }})
              </option>
            </select>
          </div>
          <div class="form-group" style="grid-column: 1 / -1">
            <label for="notes">Notas</label>
            <textarea id="notes" v-model="form.notes" class="form-input" rows="3" />
          </div>
        </div>
        <p class="hint">El evento inicia en borrador. Configura modalidades y precios en el hub.</p>
        <div class="form-actions">
          <button type="submit" class="btn btn-primary btn-inline" :disabled="form.processing">Crear evento</button>
          <Link href="/events" class="btn btn-ghost">Cancelar</Link>
        </div>
      </form>
    </div>
  </div>
</template>
