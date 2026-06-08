<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import AdministrativeLayout from '@layouts/AdministrativeLayout.vue';
import FlashAlert from '@shared/components/masters/FlashAlert.vue';
import type { SelectOption } from '@shared/types/masters';

defineOptions({ layout: AdministrativeLayout });

interface SchoolOption {
  id: number;
  name: string;
  abbreviation: string;
}

interface GradeOption {
  id: number;
  name: string;
}

interface Competitor {
  id: number;
  first_name: string;
  last_name: string;
  gender: string;
  birth_date: string;
  school_id: number;
  grade_id: number;
  weight_kg: string | number | null;
  height_cm: number | null;
  medical_notes: string | null;
}

const props = defineProps<{
  competitor: Competitor;
  schoolOptions: SchoolOption[];
  gradeOptions: GradeOption[];
  genderOptions: SelectOption[];
}>();

const form = useForm({
  first_name: props.competitor.first_name,
  last_name: props.competitor.last_name,
  gender: props.competitor.gender,
  birth_date: props.competitor.birth_date?.slice(0, 10) ?? '',
  school_id: props.competitor.school_id,
  grade_id: props.competitor.grade_id,
  weight_kg: props.competitor.weight_kg ?? '',
  height_cm: props.competitor.height_cm ?? '',
  medical_notes: props.competitor.medical_notes ?? '',
});

function submit() {
  form
    .transform((data) => ({
      ...data,
      weight_kg: data.weight_kg === '' ? null : Number(data.weight_kg),
      height_cm: data.height_cm === '' ? null : Number(data.height_cm),
      medical_notes: data.medical_notes || null,
    }))
    .put(`/masters/competitors/${props.competitor.id}`);
}
</script>

<template>
  <FlashAlert />
  <h1 class="page-title">Editar competidor</h1>
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
            <label for="gender">Género *</label>
            <select id="gender" v-model="form.gender" class="form-input" required>
              <option v-for="opt in genderOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
            </select>
            <p v-if="form.errors.gender" class="form-error">{{ form.errors.gender }}</p>
          </div>
          <div class="form-group">
            <label for="birth_date">Fecha de nacimiento *</label>
            <input id="birth_date" v-model="form.birth_date" type="date" class="form-input" required />
            <p v-if="form.errors.birth_date" class="form-error">{{ form.errors.birth_date }}</p>
          </div>
          <div class="form-group">
            <label for="school_id">Escuela *</label>
            <select id="school_id" v-model="form.school_id" class="form-input" required>
              <option v-for="s in schoolOptions" :key="s.id" :value="s.id">
                {{ s.name }} ({{ s.abbreviation }})
              </option>
            </select>
            <p v-if="form.errors.school_id" class="form-error">{{ form.errors.school_id }}</p>
          </div>
          <div class="form-group">
            <label for="grade_id">Grado *</label>
            <select id="grade_id" v-model="form.grade_id" class="form-input" required>
              <option v-for="g in gradeOptions" :key="g.id" :value="g.id">{{ g.name }}</option>
            </select>
            <p v-if="form.errors.grade_id" class="form-error">{{ form.errors.grade_id }}</p>
          </div>
          <div class="form-group">
            <label for="weight_kg">Peso (kg)</label>
            <input id="weight_kg" v-model="form.weight_kg" type="number" step="0.01" min="0" class="form-input" />
            <p v-if="form.errors.weight_kg" class="form-error">{{ form.errors.weight_kg }}</p>
          </div>
          <div class="form-group">
            <label for="height_cm">Altura (cm)</label>
            <input id="height_cm" v-model="form.height_cm" type="number" min="0" class="form-input" />
            <p v-if="form.errors.height_cm" class="form-error">{{ form.errors.height_cm }}</p>
          </div>
          <div class="form-group" style="grid-column: 1 / -1">
            <label for="medical_notes">Notas médicas</label>
            <textarea id="medical_notes" v-model="form.medical_notes" class="form-input" rows="3" />
            <p v-if="form.errors.medical_notes" class="form-error">{{ form.errors.medical_notes }}</p>
          </div>
        </div>
        <div class="form-actions">
          <button type="submit" class="btn btn-primary btn-inline" :disabled="form.processing">
            {{ form.processing ? 'Guardando…' : 'Guardar cambios' }}
          </button>
          <Link href="/masters/competitors" class="btn btn-ghost">Cancelar</Link>
        </div>
      </form>
    </div>
  </div>
</template>
