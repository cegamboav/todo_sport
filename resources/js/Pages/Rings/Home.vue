<script setup lang="ts">
import RingOperationalLayout from '@layouts/RingOperationalLayout.vue';

defineOptions({ layout: RingOperationalLayout });

interface RingRow {
  id: number;
  event_id: number;
  name: string;
  status: string;
  event?: { id: number; name: string; status: string };
}

defineProps<{
  rings: RingRow[];
}>();
</script>

<template>
  <div>
    <h1 class="page-title">Rings</h1>
    <p class="hint" style="margin-bottom: 1rem">
      Selecciona un ring para operar (workspace en slice S5).
    </p>
    <div v-if="rings.length === 0" class="card">
      <div class="card-body">
        <p class="hint">No hay rings configurados.</p>
      </div>
    </div>
    <div v-else class="stat-grid">
      <div v-for="ring in rings" :key="ring.id" class="stat-card">
        <div class="label">{{ ring.event?.name ?? 'Evento' }}</div>
        <div class="value" style="font-size: 1.1rem">{{ ring.name }}</div>
        <p class="hint">{{ ring.status }}</p>
      </div>
    </div>
  </div>
</template>
