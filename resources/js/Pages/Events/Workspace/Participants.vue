<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { computed, onMounted, ref, watch } from 'vue';
import EventWorkspaceLayout from '@layouts/EventWorkspaceLayout.vue';
import AsyncSearchSelect, { type AsyncSearchOption } from '@shared/components/ui/AsyncSearchSelect.vue';
import EventQuickCompetitorForm from '@shared/components/events/EventQuickCompetitorForm.vue';
import EventQuickRegistrationModal from '@shared/components/events/EventQuickRegistrationModal.vue';
import EventPendingAddModal from '@shared/components/events/EventPendingAddModal.vue';
import EventRegisterModal from '@shared/components/events/EventRegisterModal.vue';
import {
  useEventWorkspacePage,
  type EventWorkspaceParticipant,
} from '@shared/composables/useEventWorkspacePage';

defineOptions({ layout: EventWorkspaceLayout });

const {
  event,
  page: inertiaPage,
  canManage,
  canEnrollParticipants,
  isAdmin,
  schoolOptions,
  gradeOptions,
  searchUrls,
  registrationStatusOptions,
} = useEventWorkspacePage();

const metrics = computed(() => inertiaPage.props.participantMetrics);

const showQuickCreate = ref(false);
const quickCreateQuery = ref('');
const expandedId = ref<number | null>(null);
const listFilter = ref<'all' | 'pending_payment' | 'no_items'>('all');
const listSearch = ref('');
const searchSelectKey = ref(0);
const quickRegisterTarget = ref<AsyncSearchOption | null>(null);
const showPendingModal = ref(false);
const withdrawTarget = ref<EventWorkspaceParticipant | null>(null);

const enabledModalities = computed(() => event.value.event_modalities.filter((m) => m.enabled));
const enabledCombos = computed(() => event.value.combos.filter((c) => c.enabled));

const registerModal = ref<{
  participantId: number;
  itemType: 'modality' | 'combo';
  itemId: number;
  label: string;
  price: string | number;
  is_billable: boolean;
  allow_duplicate_override: boolean;
} | null>(null);

const filteredParticipants = computed(() => {
  let rows = event.value.event_competitors;

  if (listFilter.value === 'pending_payment') {
    rows = rows.filter((p) => participantHasPendingPayment(p));
  } else if (listFilter.value === 'no_items') {
    rows = rows.filter((p) => p.registration_items.length === 0);
  }

  const q = listSearch.value.trim().toLowerCase();
  if (q === '') {
    return rows;
  }

  return rows.filter((p) => participantMatchesSearch(p, q));
});

const listSearchActive = computed(() => listSearch.value.trim().length > 0);

function clearListSearch() {
  listSearch.value = '';
}

function participantMatchesSearch(p: EventWorkspaceParticipant, q: string) {
  const first = p.competitor.first_name.toLowerCase();
  const last = p.competitor.last_name.toLowerCase();
  const full = `${first} ${last}`;
  const school = schoolLabel(p).toLowerCase();
  const schoolName = p.competitor.school?.name?.toLowerCase() ?? '';
  const schoolAbbr = p.competitor.school?.abbreviation?.toLowerCase() ?? '';
  const modalities = modalitiesSummary(p).toLowerCase();
  const charge = chargeSummary(p).toLowerCase();
  const status = statusSummary(p).toLowerCase();

  return (
    first.includes(q)
    || last.includes(q)
    || full.includes(q)
    || school.includes(q)
    || schoolName.includes(q)
    || schoolAbbr.includes(q)
    || modalities.includes(q)
    || charge.includes(q)
    || status.includes(q)
  );
}

function participantName(p: EventWorkspaceParticipant) {
  return `${p.competitor.first_name} ${p.competitor.last_name}`;
}

function schoolLabel(p: EventWorkspaceParticipant) {
  const school = p.competitor.school;
  if (!school) {
    return '—';
  }

  return school.abbreviation || school.name;
}

function modalitiesSummary(p: EventWorkspaceParticipant) {
  if (p.registration_items.length === 0) {
    return '—';
  }

  return p.registration_items.map((i) => i.label).join(', ');
}

function chargeSummary(p: EventWorkspaceParticipant) {
  const pending = p.registration_items.filter((i) => i.is_billable && i.status === 'pending');
  if (pending.length === 0) {
    return p.registration_items.some((i) => i.is_billable) ? 'Al día' : 'Sin cobro';
  }

  const total = pending.reduce((sum, i) => sum + Number(i.amount), 0);

  return `${pending.length} pend. ($${total})`;
}

function statusSummary(p: EventWorkspaceParticipant) {
  if (p.registration_items.length === 0) {
    return 'Sin inscripciones';
  }
  if (participantHasPendingPayment(p)) {
    return 'Pago pendiente';
  }

  return 'Inscrito';
}

function participantHasPendingPayment(p: EventWorkspaceParticipant) {
  return p.registration_items.some((i) => i.is_billable && i.status === 'pending');
}

function statusClass(p: EventWorkspaceParticipant) {
  const s = statusSummary(p);
  if (s === 'Pago pendiente') {
    return 'status-pill status-pill--warn';
  }
  if (s === 'Sin inscripciones') {
    return 'status-pill status-pill--muted';
  }

  return 'status-pill status-pill--ok';
}

function toggleExpand(id: number) {
  expandedId.value = expandedId.value === id ? null : id;
}

function onCompetitorSelected(option: AsyncSearchOption) {
  quickRegisterTarget.value = option;
  searchSelectKey.value += 1;
}

function openQuickRegisterFromFlash(payload: { id: number; label: string }) {
  quickRegisterTarget.value = { id: payload.id, label: payload.label };
}

type QuickRegisterFlash = { id: number; label: string } | null | undefined;

function flashQuickRegister(): QuickRegisterFlash {
  const flash = inertiaPage.props.flash as { quick_register?: QuickRegisterFlash };
  return flash?.quick_register;
}

onMounted(() => {
  const payload = flashQuickRegister();
  if (payload?.id) {
    openQuickRegisterFromFlash(payload);
  }
});

watch(
  () => flashQuickRegister(),
  (payload) => {
    if (payload?.id) {
      openQuickRegisterFromFlash(payload);
    }
  },
);

function onCompetitorSearchEmpty(query: string) {
  quickCreateQuery.value = query;
  showQuickCreate.value = true;
}

function hasModalityRegistration(p: EventWorkspaceParticipant, eventModalityId: number) {
  return p.registration_items.some((item) => item.event_modality_id === eventModalityId);
}

function hasComboRegistration(p: EventWorkspaceParticipant, eventComboId: number) {
  return p.registration_items.some((item) => item.event_combo_id === eventComboId);
}

function openRegisterModal(
  participantId: number,
  itemType: 'modality' | 'combo',
  itemId: number,
  label: string,
  price: string | number,
) {
  registerModal.value = {
    participantId,
    itemType,
    itemId,
    label,
    price,
    is_billable: true,
    allow_duplicate_override: false,
  };
}

function updateChargeStatus(itemId: number, status: string) {
  router.put(`/events/${event.value.id}/registration-items/${itemId}/status`, { status }, { preserveScroll: true });
}

function removeItem(itemId: number, label: string) {
  if (!confirm(`¿Eliminar inscripción "${label}"?`)) {
    return;
  }

  router.delete(`/events/${event.value.id}/registration-items/${itemId}`, { preserveScroll: true });
}

function openPendingModal() {
  if (metrics.value.pending_to_add > 0) {
    showPendingModal.value = true;
  }
}

function onPendingQuickAdd(option: AsyncSearchOption) {
  showPendingModal.value = false;
  quickRegisterTarget.value = option;
}

function confirmWithdraw(p: EventWorkspaceParticipant) {
  withdrawTarget.value = p;
}

function executeWithdraw() {
  if (!withdrawTarget.value) {
    return;
  }

  router.delete(`/events/${event.value.id}/participants/${withdrawTarget.value.id}`, {
    preserveScroll: true,
    onSuccess: () => {
      withdrawTarget.value = null;
      expandedId.value = null;
    },
  });
}
</script>

<template>
  <div class="participants-page">
    <section class="card card-body metrics-card">
      <h2 class="section-title">Participantes del torneo</h2>
      <p class="hint">Centro operacional: alta, modalidades, cobros y overrides en un solo lugar.</p>
      <div class="metrics-grid">
        <div class="metric-tile">
          <span class="metric-label">Competidores catálogo</span>
          <strong class="metric-value">{{ metrics.catalog_competitors }}</strong>
        </div>
        <div class="metric-tile">
          <span class="metric-label">Inscritos en evento</span>
          <strong class="metric-value">{{ metrics.enrolled }}</strong>
        </div>
        <button
          type="button"
          class="metric-tile metric-tile--highlight metric-tile--clickable"
          :class="{ 'metric-tile--disabled': metrics.pending_to_add === 0 }"
          :disabled="metrics.pending_to_add === 0"
          @click="openPendingModal"
        >
          <span class="metric-label">Pendientes de agregar</span>
          <strong class="metric-value">{{ metrics.pending_to_add }}</strong>
          <span v-if="metrics.pending_to_add > 0" class="metric-action">Ver lista →</span>
        </button>
        <div class="metric-tile metric-tile--warn">
          <span class="metric-label">Cobros pendientes</span>
          <strong class="metric-value">{{ metrics.pending_payment }}</strong>
        </div>
      </div>
    </section>

    <section v-if="canEnrollParticipants && canManage" class="card card-body add-card">
      <h3 class="subsection-title">Inscripción rápida</h3>
      <p class="hint">Busca un competidor y elige modalidades en un solo paso.</p>
      <div class="add-card-actions">
        <button type="button" class="btn btn-ghost btn-inline" @click="showQuickCreate = true">
          + Nuevo competidor
        </button>
      </div>
      <div class="form-grid add-form">
        <div class="form-group" style="grid-column: 1 / -1">
          <label>Buscar competidor</label>
          <AsyncSearchSelect
            :key="searchSelectKey"
            :model-value="null"
            :search-url="searchUrls.competitors"
            placeholder="Nombre (mín. 2 letras)…"
            show-create-action
            @selected="onCompetitorSelected"
            @no-results="onCompetitorSearchEmpty"
          />
        </div>
      </div>
      <EventQuickCompetitorForm
        v-if="showQuickCreate"
        :event-id="event.id"
        :school-options="schoolOptions"
        :grade-options="gradeOptions"
        :initial-query="quickCreateQuery"
        @cancel="showQuickCreate = false"
      />
    </section>

    <p v-else-if="!canEnrollParticipants" class="flash flash--error">
      Este evento no acepta nuevas altas en su estado actual.
    </p>

    <section class="card card-body participants-list-card">
      <div class="list-header">
        <div>
          <h3 class="subsection-title">Lista de participantes</h3>
          <p class="list-header-meta">
            {{ filteredParticipants.length }} de {{ event.event_competitors.length }} visibles
          </p>
        </div>
      </div>

      <div class="ops-search-bar">
        <label class="ops-search-label" for="participant-search">Buscar participante</label>
        <div class="ops-search-wrap" :class="{ 'ops-search-wrap--active': listSearchActive }">
          <svg class="ops-search-icon" viewBox="0 0 20 20" fill="none" aria-hidden="true">
            <path
              d="M9 3.5a5.5 5.5 0 1 1 0 11 5.5 5.5 0 0 1 0-11Z"
              stroke="currentColor"
              stroke-width="1.75"
            />
            <path d="M13.5 13.5 17 17" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" />
          </svg>
          <input
            id="participant-search"
            v-model="listSearch"
            type="search"
            class="ops-search-input"
            placeholder="Nombre, apellido, escuela, modalidad, cobro…"
            autocomplete="off"
          />
          <button
            v-if="listSearchActive"
            type="button"
            class="ops-search-clear"
            aria-label="Limpiar búsqueda"
            @click="clearListSearch"
          >
            ×
          </button>
        </div>
      </div>

      <div class="list-filters">
        <span class="list-filters-label">Vista rápida</span>
        <div class="filter-chips">
          <button
            type="button"
            class="filter-chip"
            :class="{ 'filter-chip--active': listFilter === 'all' }"
            @click="listFilter = 'all'"
          >
            Todos ({{ event.event_competitors.length }})
          </button>
          <button
            type="button"
            class="filter-chip"
            :class="{ 'filter-chip--active': listFilter === 'pending_payment' }"
            @click="listFilter = 'pending_payment'"
          >
            Con pago pendiente
          </button>
          <button
            type="button"
            class="filter-chip"
            :class="{ 'filter-chip--active': listFilter === 'no_items' }"
            @click="listFilter = 'no_items'"
          >
            Sin inscripciones
          </button>
        </div>
      </div>

      <div class="table-wrap">
        <table class="data-table participants-table">
          <thead>
            <tr>
              <th></th>
              <th>Competidor</th>
              <th>Escuela</th>
              <th>Modalidades</th>
              <th>Cobro</th>
              <th>Estado</th>
            </tr>
          </thead>
          <tbody>
            <template v-for="p in filteredParticipants" :key="p.id">
              <tr class="participant-row" @click="toggleExpand(p.id)">
                <td>
                  <button type="button" class="btn btn-ghost btn-sm expand-btn" @click.stop="toggleExpand(p.id)">
                    {{ expandedId === p.id ? '▼' : '▶' }}
                  </button>
                </td>
                <td class="cell-primary">
                  {{ p.competitor.first_name }} {{ p.competitor.last_name }}
                </td>
                <td>{{ schoolLabel(p) }}</td>
                <td class="cell-truncate">{{ modalitiesSummary(p) }}</td>
                <td>{{ chargeSummary(p) }}</td>
                <td><span :class="statusClass(p)">{{ statusSummary(p) }}</span></td>
              </tr>
              <tr v-if="expandedId === p.id" class="participant-detail-row">
                <td colspan="6">
                  <div class="participant-detail">
                    <table class="data-table data-table--nested">
                      <thead>
                        <tr>
                          <th>Ítem</th>
                          <th>Cobro</th>
                          <th>Monto</th>
                          <th>Estado</th>
                          <th></th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-for="item in p.registration_items" :key="item.id">
                          <td>
                            {{ item.label }}
                            <span v-if="item.admin_override" class="override-badge">override</span>
                          </td>
                          <td>{{ item.is_billable ? 'Sí' : 'No' }}</td>
                          <td>{{ item.is_billable ? item.amount : '—' }}</td>
                          <td>
                            <select
                              v-if="canManage"
                              :value="item.status"
                              class="form-input form-input--sm"
                              @change="updateChargeStatus(item.id, ($event.target as HTMLSelectElement).value)"
                            >
                              <option
                                v-for="opt in registrationStatusOptions"
                                :key="opt.value"
                                :value="opt.value"
                              >
                                {{ opt.label }}
                              </option>
                            </select>
                          </td>
                          <td>
                            <button
                              v-if="canManage"
                              type="button"
                              class="btn btn-ghost btn-sm"
                              @click="removeItem(item.id, item.label)"
                            >
                              Quitar
                            </button>
                          </td>
                        </tr>
                        <tr v-if="p.registration_items.length === 0">
                          <td colspan="5" class="hint table-empty">Sin inscripciones — agrega modalidad o combo.</td>
                        </tr>
                      </tbody>
                    </table>
                    <div v-if="canManage && canEnrollParticipants" class="charge-actions">
                      <template v-for="em in event.event_modalities.filter((m) => m.enabled)" :key="em.id">
                        <button
                          type="button"
                          class="btn btn-ghost btn-sm"
                          :class="{ 'btn--registered': hasModalityRegistration(p, em.id) }"
                          @click="openRegisterModal(p.id, 'modality', em.id, em.modality.name, em.price)"
                        >
                          + {{ em.modality.name }} ({{ em.price }})
                        </button>
                      </template>
                      <template v-for="combo in event.combos.filter((c) => c.enabled)" :key="combo.id">
                        <button
                          type="button"
                          class="btn btn-ghost btn-sm"
                          :class="{ 'btn--registered': hasComboRegistration(p, combo.id) }"
                          @click="openRegisterModal(p.id, 'combo', combo.id, combo.name, combo.price)"
                        >
                          + {{ combo.name }} ({{ combo.price }})
                        </button>
                      </template>
                    </div>
                    <div v-if="canManage && canEnrollParticipants" class="participant-advanced-actions">
                      <button
                        type="button"
                        class="btn btn-ghost btn-sm btn-danger-outline"
                        @click="confirmWithdraw(p)"
                      >
                        Desinscribir del evento
                      </button>
                    </div>
                  </div>
                </td>
              </tr>
            </template>
            <tr v-if="filteredParticipants.length === 0">
              <td colspan="6" class="hint table-empty">
                {{ listSearch.trim() ? 'Ningún participante coincide con la búsqueda.' : 'No hay participantes con este filtro.' }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>
  </div>

  <EventPendingAddModal
    v-if="showPendingModal"
    :event-id="event.id"
    :search-url="searchUrls.pendingCompetitors"
    :total-pending="metrics.pending_to_add"
    @close="showPendingModal = false"
    @quick-add="onPendingQuickAdd"
  />

  <EventQuickRegistrationModal
    v-if="quickRegisterTarget"
    :event-id="event.id"
    :competitor="quickRegisterTarget"
    :modalities="enabledModalities"
    :combos="enabledCombos"
    @close="quickRegisterTarget = null"
  />

  <EventRegisterModal
    v-if="registerModal"
    :event-id="event.id"
    :modal="registerModal"
    :is-admin="isAdmin"
    @close="registerModal = null"
  />

  <div v-if="withdrawTarget" class="withdraw-backdrop" @click.self="withdrawTarget = null">
    <div class="withdraw-modal card card-body">
      <h3 class="section-title">Desinscribir competidor</h3>
      <p class="hint">
        ¿Desinscribir a <strong>{{ participantName(withdrawTarget) }}</strong> de este torneo?
      </p>
      <ul class="withdraw-warnings">
        <li>Se eliminarán todas sus modalidades/combos inscritos.</li>
        <li>Se removerán los cobros asociados pendientes.</li>
        <li>El competidor podrá volver a inscribirse después.</li>
      </ul>
      <div class="withdraw-actions">
        <button type="button" class="btn btn-danger btn-inline" @click="executeWithdraw">
          Confirmar desinscripción
        </button>
        <button type="button" class="btn btn-ghost" @click="withdrawTarget = null">Cancelar</button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.section-title {
  font-size: 1rem;
  margin-bottom: 0.5rem;
}

.subsection-title {
  font-size: 0.92rem;
  margin-bottom: 0.65rem;
}

.metrics-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
  gap: 0.75rem;
  margin-top: 1rem;
}

.metric-tile {
  padding: 0.75rem;
  border-radius: 8px;
  background: #f8fafc;
  border: 1px solid var(--color-border);
}

.metric-tile--highlight {
  border-color: #3b82f6;
  background: #eff6ff;
}

.metric-tile--warn {
  border-color: #f59e0b;
  background: #fffbeb;
}

.metric-tile--clickable {
  cursor: pointer;
  text-align: left;
  width: 100%;
  font: inherit;
  transition: box-shadow 0.15s ease;
}

.metric-tile--clickable:not(:disabled):hover {
  box-shadow: 0 2px 8px rgba(59, 130, 246, 0.15);
}

.metric-tile--disabled {
  opacity: 0.65;
  cursor: default;
}

.metric-action {
  display: block;
  margin-top: 0.25rem;
  font-size: 0.72rem;
  color: #2563eb;
  font-weight: 600;
}

.metric-label {
  display: block;
  font-size: 0.72rem;
  color: var(--color-text-muted);
  margin-bottom: 0.2rem;
}

.metric-value {
  font-size: 1.35rem;
  color: var(--color-navy);
}

.add-card {
  margin-top: 1rem;
}

.add-card-actions {
  margin-bottom: 0.65rem;
}

.participants-page > .card + .card {
  margin-top: 1rem;
}

.participants-list-card {
  padding-top: 1.1rem;
}

.list-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 1rem;
  margin-bottom: 1rem;
}

.list-header .subsection-title {
  margin-bottom: 0.15rem;
}

.list-header-meta {
  margin: 0;
  font-size: 0.78rem;
  color: var(--color-text-muted);
}

.ops-search-bar {
  margin-bottom: 1rem;
  padding: 1rem 1.1rem;
  border-radius: 10px;
  border: 2px solid #1e3a5f;
  background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
  box-shadow: 0 2px 8px rgba(15, 39, 68, 0.06);
}

.ops-search-label {
  display: block;
  margin-bottom: 0.5rem;
  font-size: 0.72rem;
  font-weight: 700;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  color: #1e3a5f;
}

.ops-search-wrap {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  padding: 0.55rem 0.85rem;
  border-radius: 8px;
  border: 1px solid #cbd5e1;
  background: #fff;
  transition: border-color 0.15s ease, box-shadow 0.15s ease;
}

.ops-search-wrap:focus-within,
.ops-search-wrap--active {
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
}

.ops-search-icon {
  width: 1.35rem;
  height: 1.35rem;
  flex-shrink: 0;
  color: #64748b;
}

.ops-search-wrap:focus-within .ops-search-icon,
.ops-search-wrap--active .ops-search-icon {
  color: #2563eb;
}

.ops-search-input {
  flex: 1;
  min-width: 0;
  border: none;
  background: transparent;
  font-size: 1.05rem;
  line-height: 1.4;
  padding: 0.35rem 0;
  color: var(--color-navy, #0f2744);
}

.ops-search-input:focus {
  outline: none;
}

.ops-search-input::placeholder {
  color: #94a3b8;
  font-size: 0.95rem;
}

.ops-search-clear {
  flex-shrink: 0;
  width: 1.75rem;
  height: 1.75rem;
  border: none;
  border-radius: 999px;
  background: #e2e8f0;
  color: #475569;
  font-size: 1.1rem;
  line-height: 1;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
}

.ops-search-clear:hover {
  background: #cbd5e1;
  color: #1e293b;
}

.list-filters {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.65rem;
  margin-bottom: 1rem;
  padding-bottom: 0.85rem;
  border-bottom: 1px solid var(--color-border);
}

.list-filters-label {
  font-size: 0.72rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: var(--color-text-muted);
}

.filter-chips {
  display: flex;
  flex-wrap: wrap;
  gap: 0.35rem;
}

.filter-chip {
  border: 1px solid var(--color-border);
  background: #fff;
  padding: 0.3rem 0.65rem;
  border-radius: 999px;
  font-size: 0.78rem;
  cursor: pointer;
}

.filter-chip--active {
  background: var(--color-navy);
  color: #fff;
  border-color: var(--color-navy);
}

.participant-row {
  cursor: pointer;
}

.participant-row:hover {
  background: #f8fafc;
}

.cell-truncate {
  max-width: 220px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.participant-detail {
  padding: 0.75rem 0.25rem;
}

.data-table--nested {
  margin-bottom: 0.75rem;
}

.form-input--sm {
  max-width: 130px;
  padding: 0.3rem 0.5rem;
  font-size: 0.82rem;
}

.charge-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 0.35rem;
}

.override-badge {
  margin-left: 0.35rem;
  font-size: 0.68rem;
  font-weight: 700;
  text-transform: uppercase;
  color: #b45309;
  background: #fef3c7;
  padding: 0.1rem 0.35rem;
  border-radius: 4px;
}

.btn--registered {
  opacity: 0.55;
}

.status-pill {
  display: inline-block;
  font-size: 0.75rem;
  font-weight: 600;
  padding: 0.15rem 0.5rem;
  border-radius: 999px;
}

.status-pill--ok {
  background: #dcfce7;
  color: #166534;
}

.status-pill--warn {
  background: #fef3c7;
  color: #b45309;
}

.status-pill--muted {
  background: #f1f5f9;
  color: #64748b;
}

.expand-btn {
  min-width: 2rem;
}

.participant-advanced-actions {
  margin-top: 0.75rem;
  padding-top: 0.75rem;
  border-top: 1px dashed var(--color-border);
}

.btn-danger-outline {
  color: #dc2626;
  border-color: #fecaca;
}

.btn-danger {
  background: #dc2626;
  color: #fff;
  border: none;
}

.withdraw-backdrop {
  position: fixed;
  inset: 0;
  z-index: 110;
  background: rgba(15, 23, 42, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1rem;
}

.withdraw-modal {
  width: 100%;
  max-width: 420px;
}

.withdraw-warnings {
  margin: 0.75rem 0 1rem;
  padding-left: 1.25rem;
  font-size: 0.85rem;
  color: var(--color-text-muted);
}

.withdraw-actions {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}
</style>
