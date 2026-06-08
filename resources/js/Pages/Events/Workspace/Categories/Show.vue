<script setup lang="ts">
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import EventWorkspaceLayout from '@layouts/EventWorkspaceLayout.vue';
import { useEventWorkspacePage } from '@shared/composables/useEventWorkspacePage';

defineOptions({ layout: EventWorkspaceLayout });

interface CompetitorMini {
  first_name: string;
  last_name: string;
  gender: string;
  age?: number | null;
  weight_kg?: string | number | null;
  height_cm?: number | null;
  school?: { name: string; abbreviation?: string | null } | null;
  grade?: { id: number; name: string } | null;
}

interface CategoryAssignment {
  id: number;
  sort_order: number;
  event_competitor: { id: number; competitor: CompetitorMini };
}

interface CategoryMatch {
  id: number;
  bout_order: number;
  stage_label: string;
  red_competitor?: { id: number; competitor?: { first_name: string; last_name: string } | null } | null;
  blue_competitor?: { id: number; competitor?: { first_name: string; last_name: string } | null } | null;
}

interface CategoryDetail {
  id: number;
  internal_code: string;
  name: string;
  competition_order: number;
  status: string;
  modality_id: number;
  gender_scope: string;
  ring_id: number | null;
  notes: string | null;
  reference_age: string | null;
  reference_grade: string | null;
  reference_weight: string | null;
  modality: { id: number; code: string; name: string };
  ring?: { id: number; name: string } | null;
  category_competitors: CategoryAssignment[];
  matches: CategoryMatch[];
}

interface EligibleParticipant {
  id: number;
  competitor: CompetitorMini;
}

type BuilderRow = {
  id: number | null;
  bout_order: number;
  stage_label: string;
  red_event_competitor_id: number | null;
  blue_event_competitor_id: number | null;
};

const { event, canManage } = useEventWorkspacePage();
const page = usePage<{
  category: CategoryDetail;
  eligibleParticipants: EligibleParticipant[];
  categoryCompetitorsForBuilder?: CategoryAssignment[];
  rings: { id: number; name: string }[];
  categoryStatusOptions: { value: string; label: string }[];
  categoryGenderOptions: { value: string; label: string }[];
}>();

const category = computed(() => page.props.category);
const eligibleParticipants = computed(() => page.props.eligibleParticipants);
const rings = computed(() => page.props.rings);
const statusOptions = computed(() => page.props.categoryStatusOptions);
const genderOptions = computed(() => page.props.categoryGenderOptions);

const assignSearch = ref('');
const dragParticipantId = ref<number | null>(null);
const builderRows = ref<BuilderRow[]>([]);

const isDraft = computed(() => category.value.status === 'draft');
const isBracketPending = computed(() => category.value.status === 'bracket_pending');
const isReady = computed(() => category.value.status === 'ready');
const isAdvancedLocked = computed(() => ['assigned', 'in_progress', 'finished', 'awarded'].includes(category.value.status));

const editForm = useForm({
  name: category.value.name,
  modality_id: category.value.modality_id,
  gender_scope: category.value.gender_scope,
  ring_id: category.value.ring_id ?? '',
  competition_order: category.value.competition_order,
  notes: category.value.notes ?? '',
  reference_age: category.value.reference_age ?? '',
  reference_grade: category.value.reference_grade ?? '',
  reference_weight: category.value.reference_weight ?? '',
});

const assignForm = useForm({ event_competitor_id: null as number | null });
const matchesForm = useForm({ rows: [] as BuilderRow[] });
const enabledModalities = computed(() => event.value.event_modalities.filter((m) => m.enabled));

const categoryCompetitorsForBuilder = computed(() => page.props.categoryCompetitorsForBuilder ?? []);

function competitorMatchesScope(competitor: CompetitorMini, scope: string): boolean {
  if (scope === 'male') return competitor.gender === 'male';
  if (scope === 'female') return competitor.gender === 'female';
  return competitor.gender === 'male' || competitor.gender === 'female';
}

const allCategoryParticipants = computed(() => categoryCompetitorsForBuilder.value.map((row) => ({
  id: row.event_competitor.id,
  competitor: row.event_competitor.competitor,
})));

const rosterAssignments = computed(() => category.value.category_competitors.filter((row) =>
  competitorMatchesScope(row.event_competitor.competitor, category.value.gender_scope),
));

const participantMap = computed(() => {
  const map = new Map<number, CompetitorMini>();
  allCategoryParticipants.value.forEach((p) => map.set(p.id, p.competitor));
  return map;
});

const usedParticipantIds = computed(() => {
  const used = new Set<number>();
  builderRows.value.forEach((row) => {
    if (row.red_event_competitor_id) used.add(row.red_event_competitor_id);
    if (row.blue_event_competitor_id) used.add(row.blue_event_competitor_id);
  });
  return used;
});

const availableCards = computed(() => {
  return allCategoryParticipants.value.filter((p) => !usedParticipantIds.value.has(p.id));
});

const filteredEligible = computed(() => {
  const scope = category.value.gender_scope;
  const byGender = eligibleParticipants.value.filter((p) => competitorMatchesScope(p.competitor, scope));
  const q = assignSearch.value.trim().toLowerCase();
  if (!q) return byGender;
  return byGender.filter((p) => {
    const name = `${p.competitor.first_name} ${p.competitor.last_name}`.toLowerCase();
    const school = (p.competitor.school?.abbreviation || p.competitor.school?.name || '').toLowerCase();
    const grade = (p.competitor.grade?.name || '').toLowerCase();
    return name.includes(q) || school.includes(q) || grade.includes(q);
  });
});

function hydrateBuilderRows() {
  builderRows.value = category.value.matches
    .slice()
    .sort((a, b) => a.bout_order - b.bout_order)
    .map((m) => ({
      id: m.id,
      bout_order: m.bout_order,
      stage_label: m.stage_label || 'R1',
      red_event_competitor_id: m.red_competitor?.id ?? null,
      blue_event_competitor_id: m.blue_competitor?.id ?? null,
    }));
  if (builderRows.value.length === 0) addBuilderRow();
}
hydrateBuilderRows();

function schoolLabel(participant: { competitor: CompetitorMini }) {
  const school = participant.competitor.school;
  return school ? (school.abbreviation || school.name) : '—';
}

function competitorMeta(participant: { competitor: CompetitorMini }) {
  const c = participant.competitor;
  const parts = [
    c.gender === 'male' ? 'Masculino' : 'Femenino',
    c.age !== null && c.age !== undefined ? `${c.age} años` : null,
    c.weight_kg ? `${c.weight_kg} kg` : null,
    c.height_cm ? `${c.height_cm} cm` : null,
    c.grade?.name ?? null,
  ].filter(Boolean);
  return parts.join(' · ');
}

function statusLabel(status: string) {
  return statusOptions.value.find((o) => o.value === status)?.label ?? status;
}

function updateStatus(status: string) {
  router.put(`/events/${event.value.id}/categories/${category.value.id}/status`, { status }, { preserveScroll: true });
}

function saveCategory() {
  editForm.put(`/events/${event.value.id}/categories/${category.value.id}`, { preserveScroll: true });
}

function assignParticipant(participantId: number) {
  assignForm.event_competitor_id = participantId;
  assignForm.post(`/events/${event.value.id}/categories/${category.value.id}/competitors`, { preserveScroll: true });
}

function removeAssignment(assignmentId: number, name: string) {
  if (!confirm(`¿Remover a ${name} de esta categoría?`)) return;
  router.delete(`/events/${event.value.id}/categories/${category.value.id}/competitors/${assignmentId}`, { preserveScroll: true });
}

function addBuilderRow() {
  builderRows.value.push({
    id: null,
    bout_order: builderRows.value.length + 1,
    stage_label: 'R1',
    red_event_competitor_id: null,
    blue_event_competitor_id: null,
  });
}

function removeBuilderRow(index: number) {
  builderRows.value.splice(index, 1);
  builderRows.value.forEach((row, idx) => { row.bout_order = idx + 1; });
  if (builderRows.value.length === 0) addBuilderRow();
}

function onDragCard(participantId: number) {
  dragParticipantId.value = participantId;
}

function dropToSlot(rowIndex: number, side: 'red' | 'blue') {
  const pid = dragParticipantId.value;
  if (!pid) return;
  const row = builderRows.value[rowIndex];
  const targetKey = side === 'red' ? 'red_event_competitor_id' : 'blue_event_competitor_id';
  const otherKey = side === 'red' ? 'blue_event_competitor_id' : 'red_event_competitor_id';

  // remove from any existing slot first
  builderRows.value.forEach((r) => {
    if (r.red_event_competitor_id === pid) r.red_event_competitor_id = null;
    if (r.blue_event_competitor_id === pid) r.blue_event_competitor_id = null;
  });

  if (row[otherKey] === pid) row[otherKey] = null;
  row[targetKey] = pid;
  dragParticipantId.value = null;
}

function clearSlot(rowIndex: number, side: 'red' | 'blue') {
  const row = builderRows.value[rowIndex];
  if (side === 'red') row.red_event_competitor_id = null;
  else row.blue_event_competitor_id = null;
}

function participantNameById(id: number | null) {
  if (!id) return 'Slot vacío';
  const c = participantMap.value.get(id);
  return c ? `${c.first_name} ${c.last_name}` : 'Competidor';
}

function matchSideName(side: CategoryMatch['red_competitor']) {
  const c = side?.competitor;
  return c ? `${c.first_name} ${c.last_name}` : '—';
}

function saveBracketBuilder() {
  matchesForm.rows = builderRows.value.map((row, idx) => ({
    ...row,
    bout_order: idx + 1,
  }));
  matchesForm.put(`/events/${event.value.id}/categories/${category.value.id}/matches`, { preserveScroll: true });
}
</script>

<template>
  <div class="category-show-page">
    <p class="breadcrumb">
      <Link :href="`/events/${event.id}/categories`">← Categorías</Link>
    </p>

    <section class="card card-body">
      <div class="category-header">
        <div>
          <p class="category-code">{{ category.internal_code }}</p>
          <h2 class="section-title">{{ category.name }}</h2>
          <p class="hint">{{ category.modality.name }} · Orden {{ category.competition_order }}</p>
        </div>
        <span class="status-pill status-pill--lg">{{ statusLabel(category.status) }}</span>
      </div>
    </section>

    <div class="category-grid">
      <section class="card card-body">
        <h3 class="subsection-title">Datos de categoría</h3>
        <form v-if="canManage" class="form-grid" @submit.prevent="saveCategory">
          <div class="form-group">
            <label>Nombre *</label>
            <input v-model="editForm.name" type="text" class="form-input" required />
          </div>
          <div class="form-group">
            <label>Modalidad *</label>
            <select v-model="editForm.modality_id" class="form-input" required>
              <option v-for="m in enabledModalities" :key="m.id" :value="m.modality_id">
                {{ m.modality.name }}
              </option>
            </select>
          </div>
          <div class="form-group">
            <label>Sexo competitivo *</label>
            <select v-model="editForm.gender_scope" class="form-input">
              <option v-for="opt in genderOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
            </select>
          </div>
          <div class="form-group">
            <label>Ring</label>
            <select v-model="editForm.ring_id" class="form-input">
              <option value="">Sin asignar</option>
              <option v-for="r in rings" :key="r.id" :value="r.id">{{ r.name }}</option>
            </select>
          </div>
          <div class="form-group">
            <label>Orden competencia</label>
            <input v-model.number="editForm.competition_order" type="number" min="0" class="form-input" />
          </div>
          <div class="form-group">
            <label>Ref. edad</label>
            <input v-model="editForm.reference_age" type="text" class="form-input" />
          </div>
          <div class="form-group">
            <label>Ref. grado</label>
            <input v-model="editForm.reference_grade" type="text" class="form-input" />
          </div>
          <div class="form-group">
            <label>Ref. peso</label>
            <input v-model="editForm.reference_weight" type="text" class="form-input" />
          </div>
          <div class="form-group" style="grid-column: 1 / -1">
            <label>Notas</label>
            <textarea v-model="editForm.notes" class="form-input" rows="2" />
          </div>
          <div class="form-group" style="grid-column: 1 / -1; display: flex; gap: 0.5rem; flex-wrap: wrap">
            <button type="submit" class="btn btn-primary btn-inline" :disabled="editForm.processing">Guardar cambios</button>
            <button v-if="isDraft" type="button" class="btn btn-ghost btn-inline" @click="updateStatus('bracket_pending')">
              Pasar a armado de llave
            </button>
            <button v-if="isReady" type="button" class="btn btn-ghost btn-inline" @click="updateStatus('assigned')">
              Asignar a ring
            </button>
          </div>
        </form>
      </section>

      <section v-if="isDraft" class="card card-body">
        <h3 class="subsection-title">
          Draft — armado de categoría ({{ category.category_competitors.length }})
        </h3>

        <div class="table-wrap">
          <table class="data-table">
            <thead>
              <tr>
                <th>#</th>
                <th>Competidor</th>
                <th>Escuela</th>
                <th>Contexto competitivo</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(row, idx) in rosterAssignments" :key="row.id">
                <td>{{ idx + 1 }}</td>
                <td>
                  {{ row.event_competitor.competitor.first_name }}
                  {{ row.event_competitor.competitor.last_name }}
                </td>
                <td>{{ schoolLabel(row.event_competitor) }}</td>
                <td class="hint">{{ competitorMeta(row.event_competitor) }}</td>
                <td>
                  <button
                    v-if="canManage"
                    type="button"
                    class="btn btn-ghost btn-sm btn-danger-text"
                    @click="
                      removeAssignment(
                        row.id,
                        `${row.event_competitor.competitor.first_name} ${row.event_competitor.competitor.last_name}`,
                      )
                    "
                  >
                    Quitar
                  </button>
                </td>
              </tr>
              <tr v-if="rosterAssignments.length === 0">
                <td colspan="5" class="hint table-empty">Sin competidores — agrega manualmente abajo.</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="canManage" class="assign-panel">
          <h4 class="assign-title">Agregar competidor</h4>
          <input
            v-model="assignSearch"
            type="search"
            class="form-input"
            placeholder="Buscar participante inscrito…"
          />
          <ul class="eligible-list">
            <li v-for="p in filteredEligible" :key="p.id" class="eligible-row">
              <div>
                <strong>{{ p.competitor.first_name }} {{ p.competitor.last_name }}</strong>
                <span class="eligible-meta">{{ schoolLabel(p) }} · {{ competitorMeta(p) }}</span>
              </div>
              <button
                type="button"
                class="btn btn-ghost btn-sm"
                :disabled="assignForm.processing"
                @click="assignParticipant(p.id)"
              >
                Agregar
              </button>
            </li>
            <li v-if="filteredEligible.length === 0" class="hint eligible-empty">
              {{ assignSearch.trim() ? 'Sin coincidencias.' : 'Todos los inscritos ya están en esta categoría.' }}
            </li>
          </ul>
        </div>
      </section>

      <section v-else-if="isBracketPending" class="card card-body">
        <div class="workflow-head">
          <h3 class="subsection-title">Bracket Pending — armado manual de llave</h3>
          <button type="button" class="btn btn-ghost btn-inline" @click="updateStatus('draft')">Volver a Draft</button>
        </div>
        <p class="hint">Arrastra fichas de competidores a los slots de combate para armar la llave.</p>

        <div class="builder-grid">
          <section class="builder-pool">
            <h4 class="assign-title">Competidores disponibles</h4>
            <ul class="eligible-list">
              <li
                v-for="p in availableCards"
                :key="p.id"
                class="eligible-row competitor-card"
                draggable="true"
                @dragstart="onDragCard(p.id)"
              >
                <div>
                  <strong>{{ p.competitor.first_name }} {{ p.competitor.last_name }}</strong>
                  <span class="eligible-meta">{{ schoolLabel(p) }} · {{ competitorMeta(p) }}</span>
                </div>
              </li>
              <li v-if="availableCards.length === 0" class="hint eligible-empty">Todos los competidores ya están en slots.</li>
            </ul>
          </section>

          <section class="builder-matches">
            <div class="workflow-head" style="margin-bottom: 0.6rem">
              <h4 class="assign-title">Matches / slots</h4>
              <button type="button" class="btn btn-ghost btn-sm" @click="addBuilderRow">+ Match</button>
            </div>

            <div class="match-list">
              <div v-for="(row, idx) in builderRows" :key="`row-${idx}-${row.id ?? 'new'}`" class="match-row">
                <div class="match-head">
                  <span>#{{ idx + 1 }}</span>
                  <input v-model="row.stage_label" type="text" class="form-input form-input--sm" style="max-width: 90px" />
                </div>
                <div class="match-vs">
                  <div
                    class="slot-box"
                    @dragover.prevent
                    @drop.prevent="dropToSlot(idx, 'red')"
                  >
                    <span class="slot-label">A</span>
                    <span>{{ participantNameById(row.red_event_competitor_id) }}</span>
                    <button type="button" class="btn btn-ghost btn-sm" @click="clearSlot(idx, 'red')">×</button>
                  </div>
                  <span class="vs-badge">VS</span>
                  <div
                    class="slot-box"
                    @dragover.prevent
                    @drop.prevent="dropToSlot(idx, 'blue')"
                  >
                    <span class="slot-label">B</span>
                    <span>{{ participantNameById(row.blue_event_competitor_id) }}</span>
                    <button type="button" class="btn btn-ghost btn-sm" @click="clearSlot(idx, 'blue')">×</button>
                  </div>
                </div>
                <div class="match-actions">
                  <button type="button" class="btn btn-ghost btn-sm btn-danger-text" @click="removeBuilderRow(idx)">
                    Quitar
                  </button>
                </div>
              </div>
            </div>

            <div class="status-row" style="margin-top: 1rem">
              <button type="button" class="btn btn-primary btn-inline" :disabled="matchesForm.processing" @click="saveBracketBuilder">
                Guardar llave manual
              </button>
              <button type="button" class="btn btn-ghost btn-inline" @click="updateStatus('ready')">
                Marcar llave como lista (Ready)
              </button>
            </div>
          </section>
        </div>
      </section>

      <section v-else-if="isReady" class="card card-body">
        <h3 class="subsection-title">Ready — llave validada</h3>
        <p class="hint">
          La categoría está bloqueada para cambios estructurales. Revisa llave, valida y asigna ring para competencia.
        </p>
        <div class="table-wrap">
          <table class="data-table">
            <thead>
              <tr><th>#</th><th>Etapa</th><th>Rojo</th><th>Azul</th></tr>
            </thead>
            <tbody>
              <tr v-for="m in category.matches" :key="m.id">
                <td>{{ m.bout_order }}</td>
                <td>{{ m.stage_label }}</td>
                <td>{{ matchSideName(m.red_competitor) }}</td>
                <td>{{ matchSideName(m.blue_competitor) }}</td>
              </tr>
              <tr v-if="category.matches.length === 0">
                <td colspan="4" class="hint table-empty">No hay combates registrados.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <section v-else class="card card-body">
        <h3 class="subsection-title">Estado operacional: {{ statusLabel(category.status) }}</h3>
        <p class="hint">
          Esta etapa se prepara para S2C/S3. Por ahora se mantiene navegación y foundation de lifecycle.
        </p>
      </section>
    </div>
  </div>
</template>

<style scoped>
.breadcrumb {
  margin: 0 0 0.75rem;
  font-size: 0.85rem;
}

.breadcrumb a {
  color: var(--color-navy);
  text-decoration: none;
  font-weight: 600;
}

.section-title {
  font-size: 1.05rem;
  margin: 0.15rem 0;
}

.subsection-title {
  font-size: 0.92rem;
  margin-bottom: 0.75rem;
}

.category-code {
  margin: 0;
  font-family: ui-monospace, monospace;
  font-size: 0.78rem;
  color: var(--color-text-muted);
}

.category-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 1rem;
}

.category-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
  gap: 1rem;
  margin-top: 1rem;
}

.status-row {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
  margin-top: 0.35rem;
}

.status-pill--lg {
  font-size: 0.78rem;
  padding: 0.25rem 0.65rem;
  border-radius: 999px;
  background: #f1f5f9;
  font-weight: 600;
}

.assign-panel {
  margin-top: 1.25rem;
  padding-top: 1rem;
  border-top: 1px solid var(--color-border);
}

.assign-title {
  margin: 0 0 0.5rem;
  font-size: 0.85rem;
  font-weight: 600;
}

.eligible-list {
  list-style: none;
  margin: 0.75rem 0 0;
  padding: 0;
  max-height: 280px;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.eligible-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 0.75rem;
  padding: 0.45rem 0.55rem;
  border: 1px solid var(--color-border);
  border-radius: 8px;
}

.eligible-meta {
  display: block;
  font-size: 0.78rem;
  color: var(--color-text-muted);
}

.eligible-empty {
  padding: 0.75rem 0;
  text-align: center;
}

.btn-danger-text {
  color: #dc2626;
}

.workflow-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
  flex-wrap: wrap;
}

.builder-grid {
  display: grid;
  grid-template-columns: minmax(280px, 1fr) minmax(380px, 1.2fr);
  gap: 1rem;
}

.builder-pool,
.builder-matches {
  border: 1px solid var(--color-border);
  border-radius: 8px;
  padding: 0.7rem;
  background: #f8fafc;
}

.competitor-card {
  cursor: grab;
  border-style: dashed;
}

.match-list {
  display: flex;
  flex-direction: column;
  gap: 0.6rem;
}

.match-row {
  background: #fff;
  border: 1px solid var(--color-border);
  border-radius: 8px;
  padding: 0.55rem;
}

.match-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.35rem;
  font-size: 0.78rem;
  color: #475569;
}

.match-vs {
  display: grid;
  grid-template-columns: 1fr auto 1fr;
  align-items: center;
  gap: 0.45rem;
}

.slot-box {
  display: grid;
  grid-template-columns: auto 1fr auto;
  align-items: center;
  gap: 0.3rem;
  padding: 0.4rem 0.45rem;
  border: 1px dashed #94a3b8;
  border-radius: 6px;
  min-height: 42px;
  background: #fff;
}

.slot-label {
  font-size: 0.7rem;
  font-weight: 700;
  color: #64748b;
}

.vs-badge {
  font-size: 0.72rem;
  font-weight: 700;
  color: #334155;
}

.match-actions {
  margin-top: 0.35rem;
  text-align: right;
}
</style>
