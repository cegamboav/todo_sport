<script setup lang="ts">
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import EventWorkspaceLayout from '@layouts/EventWorkspaceLayout.vue';
import { useEventWorkspacePage } from '@shared/composables/useEventWorkspacePage';
import { formatWeightKg } from '@shared/utils/formatWeight';

defineOptions({ layout: EventWorkspaceLayout });

interface CategoryRow {
  id: number;
  internal_code: string;
  name: string;
  competition_order: number;
  status: string;
  modality: { id: number; code: string; name: string };
  ring?: { id: number; name: string } | null;
  category_competitors_count: number;
  matches_count: number;
  category_competitors: {
    id: number;
    event_competitor: {
      id: number;
      competitor: {
        first_name: string;
        last_name: string;
        gender: string;
        age?: number | null;
        weight_kg?: string | number | null;
        school?: { name: string; abbreviation?: string | null } | null;
        grade?: { name: string } | null;
      };
    };
  }[];
  reference_age?: string | null;
  reference_grade?: string | null;
  reference_weight?: string | null;
}

interface ModalityChip {
  modality_id: number;
  name: string;
}

interface AssignedModalityChip extends ModalityChip {
  category_name: string;
}

interface PendingCategorizationRow {
  event_competitor_id: number;
  competitor: { first_name: string; last_name: string; school?: string | null };
  enrolled_modalities: ModalityChip[];
  assigned_modalities: AssignedModalityChip[];
  pending_modalities: ModalityChip[];
}

interface PendingCategorization {
  total_with_pending: number;
  summary_by_modality: Array<{ modality_id: number; name: string; count: number }>;
  participants: PendingCategorizationRow[];
}

const { event, canManage } = useEventWorkspacePage();

const page = usePage<{
  categories: CategoryRow[];
  rings: { id: number; name: string }[];
  categoryStatusOptions: { value: string; label: string }[];
  categoryGenderOptions: { value: string; label: string }[];
  categoryMetrics: { total: number; without_ring: number; draft: number; bracket_pending: number };
  pendingCategorization: PendingCategorization;
}>();

const categories = computed(() => page.props.categories);
const rings = computed(() => page.props.rings);
const metrics = computed(() => page.props.categoryMetrics);
const pendingCategorization = computed(() => page.props.pendingCategorization);
const statusOptions = computed(() => page.props.categoryStatusOptions);
const genderOptions = computed(() => page.props.categoryGenderOptions);

const listSearch = ref('');
const pendingSearch = ref('');
const showPendingPanel = ref(true);
const showCreate = ref(false);
const previewCategoryId = ref<number | null>(null);
const previewTimer = ref<ReturnType<typeof setTimeout> | null>(null);
const previewCloseTimer = ref<ReturnType<typeof setTimeout> | null>(null);
const previewPosition = ref<{ top: number; left: number } | null>(null);

const previewCategory = computed(() =>
  categories.value.find((c) => c.id === previewCategoryId.value) ?? null,
);

const createForm = useForm({
  name: '',
  modality_id: '' as string | number,
  gender_scope: '' as string,
  notes: '',
  reference_age: '',
  reference_grade: '',
  reference_weight: '',
});

const orderForm = useForm({
  rows: [] as { id: number; competition_order: number }[],
});

const enabledModalities = computed(() => event.value.event_modalities.filter((m) => m.enabled));

const filteredCategories = computed(() => {
  const q = listSearch.value.trim().toLowerCase();
  if (!q) {
    return categories.value;
  }

  return categories.value.filter((c) => {
    const ring = c.ring?.name?.toLowerCase() ?? '';
    return (
      c.internal_code.toLowerCase().includes(q)
      || c.name.toLowerCase().includes(q)
      || c.modality.name.toLowerCase().includes(q)
      || c.status.toLowerCase().includes(q)
      || ring.includes(q)
    );
  });
});

const filteredPendingParticipants = computed(() => {
  const q = pendingSearch.value.trim().toLowerCase();
  const rows = pendingCategorization.value.participants;

  if (!q) {
    return rows;
  }

  return rows.filter((row) => {
    const name = `${row.competitor.first_name} ${row.competitor.last_name}`.toLowerCase();
    const school = row.competitor.school?.toLowerCase() ?? '';
    const enrolled = row.enrolled_modalities.map((m) => m.name).join(' ').toLowerCase();
    const assigned = row.assigned_modalities.map((m) => m.name).join(' ').toLowerCase();
    const pending = row.pending_modalities.map((m) => m.name).join(' ').toLowerCase();

    return name.includes(q) || school.includes(q) || enrolled.includes(q) || assigned.includes(q) || pending.includes(q);
  });
});

function participantName(row: PendingCategorizationRow) {
  return `${row.competitor.first_name} ${row.competitor.last_name}`;
}

function statusLabel(status: string) {
  return statusOptions.value.find((o) => o.value === status)?.label ?? status;
}

function statusClass(status: string) {
  if (status === 'draft') {
    return 'status-pill status-pill--muted';
  }
  if (status === 'bracket_pending') {
    return 'status-pill status-pill--pending';
  }
  if (status === 'ready' || status === 'assigned') {
    return 'status-pill status-pill--ok';
  }
  if (status === 'in_progress') {
    return 'status-pill status-pill--warn';
  }

  return 'status-pill status-pill--done';
}

function submitCreate() {
  createForm.post(`/events/${event.value.id}/categories`, {
    preserveScroll: true,
    onSuccess: () => {
      createForm.reset();
      showCreate.value = false;
    },
  });
}

function saveOrder() {
  orderForm.rows = categories.value.map((c) => ({
    id: c.id,
    competition_order: c.competition_order,
  }));

  orderForm.put(`/events/${event.value.id}/categories/order`, { preserveScroll: true });
}

function deleteCategory(category: CategoryRow) {
  if (!confirm(`¿Eliminar categoría ${category.internal_code} — ${category.name}?`)) {
    return;
  }

  router.delete(`/events/${event.value.id}/categories/${category.id}`, { preserveScroll: true });
}

function canDeleteCategory(category: CategoryRow) {
  return category.status === 'draft' && category.category_competitors_count === 0 && category.matches_count === 0;
}

function competitorMeta(row: CategoryRow['category_competitors'][number]) {
  const c = row.event_competitor.competitor;
  const school = c.school?.abbreviation || c.school?.name || '—';
  const grade = c.grade?.name || '—';
  const gender = c.gender === 'male' ? 'M' : 'F';
  const weight = formatWeightKg(c.weight_kg)?.replace(' kg', 'kg') ?? 's/p';
  return `${school} · ${grade} · ${gender} · ${weight}`;
}

function openPreviewWithDelay(categoryId: number, event: MouseEvent) {
  cancelClosePreview();
  if (previewTimer.value) {
    clearTimeout(previewTimer.value);
  }
  const target = event.currentTarget as HTMLElement | null;
  if (target) {
    const rect = target.getBoundingClientRect();
    previewPosition.value = {
      top: rect.bottom + 8,
      left: Math.max(12, rect.left - 120),
    };
  }
  previewTimer.value = setTimeout(() => {
    previewCategoryId.value = categoryId;
  }, 220);
}

function scheduleClosePreview() {
  cancelClosePreview();
  previewCloseTimer.value = setTimeout(() => {
    previewCategoryId.value = null;
    previewPosition.value = null;
  }, 180);
}

function cancelClosePreview() {
  if (previewCloseTimer.value) {
    clearTimeout(previewCloseTimer.value);
    previewCloseTimer.value = null;
  }
}

function closePreview() {
  if (previewTimer.value) {
    clearTimeout(previewTimer.value);
    previewTimer.value = null;
  }
  cancelClosePreview();
  previewCategoryId.value = null;
  previewPosition.value = null;
}
</script>

<template>
  <div class="categories-page">
    <section class="card card-body">
      <div class="page-header">
        <div>
          <h2 class="section-title">Categorías del torneo</h2>
          <p class="hint">Organización manual — el operador crea, ordena y asigna participantes.</p>
        </div>
        <button v-if="canManage" type="button" class="btn btn-primary btn-inline" @click="showCreate = !showCreate">
          {{ showCreate ? 'Cancelar' : '+ Nueva categoría' }}
        </button>
      </div>

      <div class="metrics-grid">
        <div class="metric-tile">
          <span class="metric-label">Total categorías</span>
          <strong class="metric-value">{{ metrics.total }}</strong>
        </div>
        <div class="metric-tile metric-tile--warn">
          <span class="metric-label">Sin ring</span>
          <strong class="metric-value">{{ metrics.without_ring }}</strong>
        </div>
        <div class="metric-tile">
          <span class="metric-label">En borrador</span>
          <strong class="metric-value">{{ metrics.draft }}</strong>
        </div>
        <div class="metric-tile metric-tile--pending">
          <span class="metric-label">Llave pendiente</span>
          <strong class="metric-value">{{ metrics.bracket_pending }}</strong>
        </div>
        <button
          type="button"
          class="metric-tile metric-tile--highlight metric-tile--clickable"
          :class="{ 'metric-tile--disabled': pendingCategorization.total_with_pending === 0 }"
          :disabled="pendingCategorization.total_with_pending === 0"
          @click="showPendingPanel = true"
        >
          <span class="metric-label">Pendientes categorizar</span>
          <strong class="metric-value">{{ pendingCategorization.total_with_pending }}</strong>
          <span v-if="pendingCategorization.total_with_pending > 0" class="metric-action">Ver panel →</span>
        </button>
      </div>
    </section>

    <section v-if="pendingCategorization.total_with_pending > 0" class="card card-body pending-categorization-card">
      <div class="page-header pending-panel-header">
        <div>
          <h3 class="subsection-title">Participantes pendientes de categorizar</h3>
          <p class="hint">
            Modalidades inscritas menos modalidades ya asignadas a categorías.
            Incluye cobertura por combo.
          </p>
        </div>
        <button type="button" class="btn btn-ghost btn-sm" @click="showPendingPanel = !showPendingPanel">
          {{ showPendingPanel ? 'Ocultar' : 'Mostrar' }}
        </button>
      </div>

      <template v-if="showPendingPanel">
        <div v-if="pendingCategorization.summary_by_modality.length > 0" class="pending-summary">
          <span class="pending-summary-label">Pendientes por modalidad</span>
          <div class="pending-summary-chips">
            <span
              v-for="item in pendingCategorization.summary_by_modality"
              :key="item.modality_id"
              class="pending-summary-chip"
            >
              {{ item.name }}: <strong>{{ item.count }}</strong>
            </span>
          </div>
        </div>

        <div class="ops-search-bar pending-search-bar">
          <label class="ops-search-label" for="pending-categorization-search">Buscar participante</label>
          <div class="ops-search-wrap" :class="{ 'ops-search-wrap--active': pendingSearch.trim().length > 0 }">
            <svg class="ops-search-icon" viewBox="0 0 20 20" fill="none" aria-hidden="true">
              <path d="M9 3.5a5.5 5.5 0 1 1 0 11 5.5 5.5 0 0 1 0-11Z" stroke="currentColor" stroke-width="1.75" />
              <path d="M13.5 13.5 17 17" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" />
            </svg>
            <input
              id="pending-categorization-search"
              v-model="pendingSearch"
              type="search"
              class="ops-search-input"
              placeholder="Nombre, escuela, modalidad…"
            />
          </div>
        </div>

        <div class="table-wrap">
          <table class="data-table pending-table">
            <thead>
              <tr>
                <th>Competidor</th>
                <th>Inscrito</th>
                <th>Asignado</th>
                <th>Pendiente</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in filteredPendingParticipants" :key="row.event_competitor_id">
                <td class="cell-primary">
                  <strong>{{ participantName(row) }}</strong>
                  <span v-if="row.competitor.school" class="pending-school">{{ row.competitor.school }}</span>
                </td>
                <td>
                  <span
                    v-for="modality in row.enrolled_modalities"
                    :key="`enrolled-${row.event_competitor_id}-${modality.modality_id}`"
                    class="modality-chip modality-chip--enrolled"
                  >
                    {{ modality.name }}
                  </span>
                </td>
                <td>
                  <template v-if="row.assigned_modalities.length > 0">
                    <span
                      v-for="modality in row.assigned_modalities"
                      :key="`assigned-${row.event_competitor_id}-${modality.modality_id}`"
                      class="modality-chip modality-chip--assigned"
                      :title="modality.category_name"
                    >
                      {{ modality.name }}
                    </span>
                  </template>
                  <span v-else class="hint">—</span>
                </td>
                <td>
                  <span
                    v-for="modality in row.pending_modalities"
                    :key="`pending-${row.event_competitor_id}-${modality.modality_id}`"
                    class="modality-chip modality-chip--pending"
                  >
                    {{ modality.name }}
                  </span>
                </td>
              </tr>
              <tr v-if="filteredPendingParticipants.length === 0">
                <td colspan="4" class="hint table-empty">
                  {{ pendingSearch.trim() ? 'Sin coincidencias.' : 'Todos los participantes están categorizados.' }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </template>
    </section>

    <section v-if="showCreate && canManage" class="card card-body">
      <h3 class="subsection-title">Crear categoría</h3>
      <form class="form-grid" @submit.prevent="submitCreate">
        <div class="form-group">
          <label>Nombre visible *</label>
          <input v-model="createForm.name" type="text" class="form-input" required />
        </div>
        <div class="form-group">
          <label>Modalidad *</label>
          <select v-model="createForm.modality_id" class="form-input" required>
            <option value="">Seleccionar…</option>
            <option v-for="m in enabledModalities" :key="m.id" :value="m.modality_id">
              {{ m.modality.name }}
            </option>
          </select>
        </div>
        <div class="form-group">
          <label>Sexo competitivo *</label>
          <select v-model="createForm.gender_scope" class="form-input" required>
            <option value="">Seleccionar…</option>
            <option v-for="opt in genderOptions" :key="opt.value" :value="opt.value">
              {{ opt.label }}
            </option>
          </select>
        </div>
        <div class="form-group">
          <label>Ref. edad (info)</label>
          <input v-model="createForm.reference_age" type="text" class="form-input" placeholder="Ej. 8-9 años" />
        </div>
        <div class="form-group">
          <label>Ref. grado (info)</label>
          <input v-model="createForm.reference_grade" type="text" class="form-input" placeholder="Ej. Cinta verde" />
        </div>
        <div class="form-group">
          <label>Ref. peso (info)</label>
          <input v-model="createForm.reference_weight" type="text" class="form-input" placeholder="Ej. 52-57 kg" />
        </div>
        <div class="form-group" style="grid-column: 1 / -1">
          <label>Notas</label>
          <textarea v-model="createForm.notes" class="form-input" rows="2" />
        </div>
        <div class="form-group form-actions-inline" style="grid-column: 1 / -1">
          <button type="submit" class="btn btn-primary btn-inline" :disabled="createForm.processing">
            Crear categoría
          </button>
        </div>
      </form>
    </section>

    <section class="card card-body categories-list-card">
      <div class="ops-search-bar">
        <label class="ops-search-label" for="category-search">Buscar categoría</label>
        <div class="ops-search-wrap" :class="{ 'ops-search-wrap--active': listSearch.trim().length > 0 }">
          <svg class="ops-search-icon" viewBox="0 0 20 20" fill="none" aria-hidden="true">
            <path d="M9 3.5a5.5 5.5 0 1 1 0 11 5.5 5.5 0 0 1 0-11Z" stroke="currentColor" stroke-width="1.75" />
            <path d="M13.5 13.5 17 17" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" />
          </svg>
          <input
            id="category-search"
            v-model="listSearch"
            type="search"
            class="ops-search-input"
            placeholder="Nombre, modalidad, ring, estado…"
          />
        </div>
      </div>

      <div class="table-wrap">
        <table class="data-table">
          <thead>
            <tr>
              <th>Orden</th>
              <th>Categoría</th>
              <th>Modalidad</th>
              <th>Ring</th>
              <th>Competidores</th>
              <th>Estado</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="c in filteredCategories" :key="c.id">
              <td>
                <input
                  v-if="canManage"
                  v-model.number="c.competition_order"
                  type="number"
                  min="0"
                  class="form-input form-input--sm order-input"
                />
                <span v-else>{{ c.competition_order }}</span>
              </td>
              <td class="cell-primary">
                <Link :href="`/events/${event.id}/categories/${c.id}`" class="cell-link">{{ c.name }}</Link>
              </td>
              <td>{{ c.modality.name }}</td>
              <td>{{ c.ring?.name ?? '—' }}</td>
              <td>
                <div class="competitors-cell">
                  <span class="competitors-count" title="Participantes inscritos">
                    👥 {{ c.category_competitors_count }}
                  </span>
                  <div
                    class="preview-wrap"
                    @mouseenter="openPreviewWithDelay(c.id, $event)"
                    @mouseleave="scheduleClosePreview"
                  >
                    <button type="button" class="btn btn-ghost btn-sm btn-icon" aria-label="Ver participantes">
                      👁
                    </button>
                  </div>
                </div>
              </td>
              <td><span :class="statusClass(c.status)">{{ statusLabel(c.status) }}</span></td>
              <td class="cell-actions">
                <Link :href="`/events/${event.id}/categories/${c.id}`" class="btn btn-ghost btn-sm">Abrir</Link>
                <button
                  v-if="canManage && canDeleteCategory(c)"
                  type="button"
                  class="btn btn-ghost btn-sm btn-danger-text"
                  @click="deleteCategory(c)"
                >
                  Eliminar
                </button>
              </td>
            </tr>
            <tr v-if="filteredCategories.length === 0">
              <td colspan="7" class="hint table-empty">
                {{ listSearch.trim() ? 'Sin coincidencias.' : 'Aún no hay categorías — crea la primera.' }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="canManage && categories.length > 1" class="order-actions">
        <button type="button" class="btn btn-primary btn-inline" :disabled="orderForm.processing" @click="saveOrder">
          Guardar orden de competencia
        </button>
      </div>
    </section>

    <Teleport to="body">
      <div
        v-if="previewCategory && previewPosition"
        class="category-preview-portal"
        :style="{ top: `${previewPosition.top}px`, left: `${previewPosition.left}px` }"
        @mouseenter="cancelClosePreview"
        @mouseleave="scheduleClosePreview"
      >
        <p class="preview-title">{{ previewCategory.category_competitors_count }} inscritos</p>
        <ul class="preview-list">
          <li v-for="row in previewCategory.category_competitors" :key="row.id">
            <strong>
              {{ row.event_competitor.competitor.first_name }}
              {{ row.event_competitor.competitor.last_name }}
            </strong>
            <span class="preview-meta">{{ competitorMeta(row) }}</span>
          </li>
          <li v-if="previewCategory.category_competitors.length === 0" class="hint">Sin inscritos aún.</li>
        </ul>
      </div>
    </Teleport>
  </div>
</template>

<style scoped>
.section-title {
  font-size: 1rem;
  margin-bottom: 0.35rem;
}

.subsection-title {
  font-size: 0.92rem;
  margin-bottom: 0.75rem;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 1rem;
  flex-wrap: wrap;
}

.metrics-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
  gap: 0.75rem;
  margin-top: 1rem;
}

.metric-tile {
  padding: 0.75rem;
  border-radius: 8px;
  background: #f8fafc;
  border: 1px solid var(--color-border);
}

.metric-tile--warn {
  border-color: #f59e0b;
  background: #fffbeb;
}

.metric-tile--pending {
  border-color: #c084fc;
  background: #faf5ff;
}

.metric-tile--highlight {
  border-color: #6366f1;
  background: #eef2ff;
  text-align: left;
}

.metric-tile--clickable {
  cursor: pointer;
  transition: border-color 0.15s, box-shadow 0.15s;
}

.metric-tile--clickable:hover:not(:disabled) {
  border-color: #4f46e5;
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
}

.metric-tile--disabled {
  opacity: 0.65;
  cursor: default;
}

.metric-action {
  display: block;
  margin-top: 0.25rem;
  font-size: 0.72rem;
  color: #4338ca;
  font-weight: 600;
}

.pending-categorization-card {
  border-color: #c4b5fd;
  background: linear-gradient(180deg, #faf5ff 0%, #fff 40%);
}

.pending-panel-header {
  margin-bottom: 0.75rem;
}

.pending-summary {
  margin-bottom: 1rem;
  padding: 0.75rem 0.85rem;
  border-radius: 8px;
  background: #fff;
  border: 1px solid #ddd6fe;
}

.pending-summary-label {
  display: block;
  margin-bottom: 0.45rem;
  font-size: 0.72rem;
  font-weight: 700;
  letter-spacing: 0.05em;
  text-transform: uppercase;
  color: #6d28d9;
}

.pending-summary-chips {
  display: flex;
  flex-wrap: wrap;
  gap: 0.45rem;
}

.pending-summary-chip {
  display: inline-flex;
  align-items: center;
  gap: 0.2rem;
  padding: 0.25rem 0.55rem;
  border-radius: 999px;
  font-size: 0.78rem;
  background: #ede9fe;
  color: #5b21b6;
}

.pending-search-bar {
  border-color: #7c3aed;
}

.pending-school {
  display: block;
  font-size: 0.72rem;
  color: var(--color-text-muted);
  font-weight: 400;
}

.modality-chip {
  display: inline-block;
  margin: 0.12rem 0.25rem 0.12rem 0;
  padding: 0.15rem 0.45rem;
  border-radius: 999px;
  font-size: 0.72rem;
  font-weight: 600;
  white-space: nowrap;
}

.modality-chip--enrolled {
  background: #e2e8f0;
  color: #334155;
}

.modality-chip--assigned {
  background: #dcfce7;
  color: #166534;
}

.modality-chip--pending {
  background: #fef3c7;
  color: #b45309;
}

.pending-table td {
  vertical-align: top;
}

.metric-label {
  display: block;
  font-size: 0.72rem;
  color: var(--color-text-muted);
}

.metric-value {
  font-size: 1.25rem;
  color: var(--color-navy);
}

.categories-page > .card + .card {
  margin-top: 1rem;
}

.ops-search-bar {
  margin-bottom: 1rem;
  padding: 1rem 1.1rem;
  border-radius: 10px;
  border: 2px solid #1e3a5f;
  background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
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
}

.ops-search-wrap--active {
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
}

.ops-search-icon {
  width: 1.35rem;
  height: 1.35rem;
  color: #64748b;
}

.ops-search-input {
  flex: 1;
  border: none;
  background: transparent;
  font-size: 1.05rem;
  padding: 0.35rem 0;
}

.ops-search-input:focus {
  outline: none;
}

.cell-code {
  font-family: ui-monospace, monospace;
  font-size: 0.82rem;
}

.cell-link {
  font-weight: 600;
  color: var(--color-navy);
  text-decoration: none;
}

.cell-link:hover {
  text-decoration: underline;
}

.order-input {
  width: 4.5rem;
}

.form-input--sm {
  padding: 0.3rem 0.45rem;
  font-size: 0.85rem;
}

.cell-actions {
  white-space: nowrap;
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

.competitors-cell {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
}

.competitors-count {
  font-weight: 600;
  font-size: 0.88rem;
  white-space: nowrap;
}

.btn-icon {
  padding: 0.15rem 0.35rem;
  min-width: unset;
  line-height: 1;
}

.btn-danger-text {
  color: #dc2626;
}

.order-actions {
  margin-top: 1rem;
}

.status-pill {
  display: inline-block;
  font-size: 0.72rem;
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

.status-pill--pending {
  background: #f3e8ff;
  color: #7c3aed;
}

.status-pill--done {
  background: #dbeafe;
  color: #1e40af;
}

.form-actions-inline {
  display: flex;
  gap: 0.5rem;
}

.categories-list-card {
  overflow: visible;
}

.table-wrap {
  overflow: visible;
}

.preview-wrap {
  position: relative;
  display: inline-flex;
}

.category-preview-portal {
  position: fixed;
  z-index: 10050;
  width: min(320px, calc(100vw - 24px));
  max-height: min(70vh, 480px);
  overflow-y: auto;
  background: #fff;
  border: 1px solid var(--color-border);
  border-radius: 8px;
  box-shadow: 0 16px 40px rgba(15, 23, 42, 0.22);
  padding: 0.65rem 0.75rem;
  pointer-events: auto;
}

.preview-title {
  margin: 0 0 0.4rem;
  font-size: 0.75rem;
  font-weight: 700;
  color: #334155;
}

.preview-list {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 0.28rem;
}

.preview-list li {
  font-size: 0.78rem;
}

.preview-meta {
  display: block;
  font-size: 0.72rem;
  color: #64748b;
}
</style>
