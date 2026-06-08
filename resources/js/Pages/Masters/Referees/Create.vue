<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import AdministrativeLayout from '@layouts/AdministrativeLayout.vue';
import FlashAlert from '@shared/components/masters/FlashAlert.vue';
import MasterSystemAccessFields from '@shared/components/masters/MasterSystemAccessFields.vue';
import type { SelectOption } from '@shared/types/masters';

defineOptions({ layout: AdministrativeLayout });

interface GradeOption {
  id: number;
  name: string;
}

defineProps<{
  gradeOptions: GradeOption[];
  specialtyOptions: SelectOption[];
}>();

const form = useForm({
  first_name: '',
  last_name: '',
  email: '',
  phone: '',
  grade_id: '' as string | number,
  specialty: 'corner',
  notes: '',
  access_username: '',
  access_password: '',
  update_system_password: false,
});

function submit() {
  form
    .transform((data) => ({
      ...data,
      grade_id: data.grade_id === '' ? null : Number(data.grade_id),
      email: data.email || null,
      phone: data.phone || null,
      notes: data.notes || null,
    }))
    .post('/masters/referees');
}
</script>

<template>
  <FlashAlert />
  <h1 class="page-title">Nuevo árbitro</h1>
  <div class="card">
    <div class="card-body">
      <form @submit.prevent="submit">
        <div class="form-grid">
          <div class="form-group">
            <label for="first_name">Nombre *</label>
            <input id="first_name" v-model="form.first_name" type="text" class="form-input" required />
            <p v-if="form.errors.first_name" class="form-error">{{ form.errors.first_name }}</p>
          </div>
          <div class="form-group">
            <label for="last_name">Apellidos *</label>
            <input id="last_name" v-model="form.last_name" type="text" class="form-input" required />
            <p v-if="form.errors.last_name" class="form-error">{{ form.errors.last_name }}</p>
          </div>
          <div class="form-group">
            <label for="email">Email</label>
            <input id="email" v-model="form.email" type="email" class="form-input" />
            <p v-if="form.errors.email" class="form-error">{{ form.errors.email }}</p>
          </div>
          <div class="form-group">
            <label for="phone">Teléfono</label>
            <input id="phone" v-model="form.phone" type="text" class="form-input" />
            <p v-if="form.errors.phone" class="form-error">{{ form.errors.phone }}</p>
          </div>
          <div class="form-group">
            <label for="grade_id">Grado</label>
            <select id="grade_id" v-model="form.grade_id" class="form-input">
              <option value="">Sin grado</option>
              <option v-for="g in gradeOptions" :key="g.id" :value="g.id">{{ g.name }}</option>
            </select>
            <p v-if="form.errors.grade_id" class="form-error">{{ form.errors.grade_id }}</p>
          </div>
          <div class="form-group">
            <label for="specialty">Función arbitral *</label>
            <select id="specialty" v-model="form.specialty" class="form-input" required>
              <option v-for="opt in specialtyOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
            </select>
            <p v-if="form.errors.specialty" class="form-error">{{ form.errors.specialty }}</p>
          </div>
          <div class="form-group" style="grid-column: 1 / -1">
            <label for="notes">Notas</label>
            <textarea id="notes" v-model="form.notes" class="form-input" rows="3" />
            <p v-if="form.errors.notes" class="form-error">{{ form.errors.notes }}</p>
          </div>
          <MasterSystemAccessFields :form="form" require-credentials />
          <p class="hint" style="grid-column: 1 / -1; margin: 0">
            Mesa → rol <strong>mesa</strong>. Juez de esquina → rol <strong>corner</strong>. El acceso operativo
            (rings / judge) requiere asignación a evento abierto.
          </p>
        </div>
        <div class="form-actions">
          <button type="submit" class="btn btn-primary btn-inline" :disabled="form.processing">
            {{ form.processing ? 'Guardando…' : 'Crear árbitro' }}
          </button>
          <Link href="/masters/referees" class="btn btn-ghost">Cancelar</Link>
        </div>
      </form>
    </div>
  </div>
</template>
