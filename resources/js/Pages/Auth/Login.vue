<script setup lang="ts">
import { computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AppLogo from '@shared/components/ui/AppLogo.vue';

const props = withDefaults(
  defineProps<{
    portal?: 'admin' | 'professor' | 'judge';
  }>(),
  {
    portal: 'admin',
  },
);

const form = useForm({
  username: '',
  password: '',
  remember: false,
});

const tagline = computed(() => {
  if (props.portal === 'professor') return 'Portal de profesores';
  if (props.portal === 'judge') return 'App juez de esquina';
  return 'Todo Sport Admin · local';
});

const loginUrl = computed(() => {
  if (props.portal === 'professor') return '/school/login';
  if (props.portal === 'judge') return '/judge/login';
  return '/login';
});

function submit() {
  form.post(loginUrl.value, {
    onFinish: () => form.reset('password'),
  });
}
</script>

<template>
  <div class="login-page">
    <div class="login-card">
      <div class="login-logo">
        <AppLogo :tagline="tagline" />
      </div>
      <form @submit.prevent="submit">
        <div class="form-group">
          <label for="username">Usuario</label>
          <input
            id="username"
            v-model="form.username"
            type="text"
            class="form-input"
            placeholder="nombre.usuario"
            autocomplete="username"
            required
          />
          <p v-if="form.errors.username" class="form-error">{{ form.errors.username }}</p>
        </div>
        <div class="form-group">
          <label for="password">Contraseña</label>
          <input
            id="password"
            v-model="form.password"
            type="password"
            class="form-input"
            placeholder="••••••••"
            autocomplete="current-password"
            required
          />
        </div>
        <button type="submit" class="btn btn-primary" :disabled="form.processing">
          {{ form.processing ? 'Entrando…' : 'Iniciar sesión' }}
        </button>
      </form>
      <template v-if="portal === 'admin'">
        <p class="hint" style="text-align: center; margin-top: 1rem">
          <a href="/school/login">Portal profesores</a>
          ·
          <a href="/judge/login">App juez</a>
        </p>
      </template>
      <template v-else>
        <p class="hint" style="text-align: center; margin-top: 1rem">
          <a href="/login">Todo Sport Admin</a>
        </p>
      </template>
    </div>
  </div>
</template>
