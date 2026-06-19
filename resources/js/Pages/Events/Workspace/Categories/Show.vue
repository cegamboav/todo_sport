<script setup lang="ts">
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import EventWorkspaceLayout from '@layouts/EventWorkspaceLayout.vue';
import { useEventWorkspacePage } from '@shared/composables/useEventWorkspacePage';
import { formatWeightKg } from '@shared/utils/formatWeight';

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
  match_code: string | null;
  bout_order: number;
  round_number: number;
  match_type: string;
  status: string;
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
  existing_category_name?: string | null;
}

type BuilderRow = {
  id: number;
  bout_order: number;
  round_number: number;
  match_type: string;
  match_code: string | null;
  stage_label: string;
  red_event_competitor_id: number | null;
  blue_event_competitor_id: number | null;
};

const { event, canManage, isAdmin } = useEventWorkspacePage();
const page = usePage<{
  category: CategoryDetail;
  eligibleParticipants: EligibleParticipant[];
  assignedElsewhereParticipants?: EligibleParticipant[];
  categoryCompetitorsForBuilder?: CategoryAssignment[];
  rings: { id: number; name: string }[];
  categoryStatusOptions: { value: string; label: string }[];
  categoryGenderOptions: { value: string; label: string }[];
  matchTypeOptions: { value: string; label: string }[];
}>();

const category = computed(() => page.props.category);
const eligibleParticipants = computed(() => page.props.eligibleParticipants);
const statusOptions = computed(() => page.props.categoryStatusOptions);
const genderOptions = computed(() => page.props.categoryGenderOptions);
const matchTypeOptions = computed(() => page.props.matchTypeOptions ?? []);

const assignSearch = ref('');
const showAssignedElsewhere = ref(false);
const overrideDialog = ref<{ participantId: number; categoryName: string } | null>(null);
const dragParticipantId = ref<number | null>(null);
const builderRows = ref<BuilderRow[]>([]);

const isDraft = computed(() => category.value.status === 'draft');
const isBracketPending = computed(() => category.value.status === 'bracket_pending');
const isReady = computed(() => category.value.status === 'ready');
const isAssigned = computed(() => category.value.status === 'assigned');
const isAdvancedLocked = computed(() => ['in_progress', 'finished', 'awarded'].includes(category.value.status));
const hasMatches = computed(() => category.value.matches.length > 0);
const canGenerateBracket = computed(() => rosterAssignments.value.length >= 2);

const canMoveToList = computed(() => {
  if (builderRows.value.length === 0) return false;

  if (builderRows.value.length === 1 && builderRows.value[0].match_type === 'final') {
    const row = builderRows.value[0];
    return row.red_event_competitor_id !== null && row.blue_event_competitor_id !== null;
  }

  const initialRows = builderRows.value.filter(
    (row) => row.round_number === 1 && row.match_type !== 'final' && row.match_type !== 'third_place',
  );

  if (initialRows.length === 0) return false;

  return initialRows.every((row) => {
    const red = row.red_event_competitor_id;
    const blue = row.blue_event_competitor_id;
    if (red !== null && blue !== null) return true;
    if ((red !== null) !== (blue !== null)) return true;
    if (row.match_type === 'bye' && red === null && blue === null) return true;

    return false;
  });
});

const editForm = useForm({
  name: category.value.name,
  modality_id: category.value.modality_id,
  gender_scope: category.value.gender_scope,
  competition_order: category.value.competition_order,
  notes: category.value.notes ?? '',
  reference_age: category.value.reference_age ?? '',
  reference_grade: category.value.reference_grade ?? '',
  reference_weight: category.value.reference_weight ?? '',
});

const assignForm = useForm({
  event_competitor_id: null as number | null,
  admin_override: false,
});
const matchesForm = useForm({
  rows: [] as Array<{
    id: number;
    red_event_competitor_id: number | null;
    blue_event_competitor_id: number | null;
  }>,
});
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
    if (!isEditableMatchRow(row)) return;
    if (row.red_event_competitor_id) used.add(row.red_event_competitor_id);
    if (row.blue_event_competitor_id) used.add(row.blue_event_competitor_id);
  });
  return used;
});

const availableCards = computed(() => {
  return allCategoryParticipants.value.filter((p) => !usedParticipantIds.value.has(p.id));
});

const assignedElsewhereParticipants = computed(() => page.props.assignedElsewhereParticipants ?? []);

const filteredEligible = computed(() => {
  const scope = category.value.gender_scope;
  const base = showAssignedElsewhere.value
    ? [...eligibleParticipants.value, ...assignedElsewhereParticipants.value]
    : eligibleParticipants.value;
  const byGender = base.filter((p) => competitorMatchesScope(p.competitor, scope));
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
    .sort((a, b) => a.round_number - b.round_number || a.bout_order - b.bout_order)
    .map((m) => ({
      id: m.id,
      bout_order: m.bout_order,
      round_number: m.round_number,
      match_type: m.match_type,
      match_code: m.match_code,
      stage_label: m.stage_label || 'R1',
      red_event_competitor_id: m.red_competitor?.id ?? null,
      blue_event_competitor_id: m.blue_competitor?.id ?? null,
    }));
}
hydrateBuilderRows();

watch(
  () => category.value.matches,
  () => {
    hydrateBuilderRows();
  },
  { deep: true },
);

function isEditableMatchRow(row: BuilderRow): boolean {
  if (category.value.matches.length === 1 && row.match_type === 'final') return true;
  if (row.round_number !== 1) return false;
  return row.match_type !== 'final' && row.match_type !== 'third_place';
}

function matchTypeLabel(type: string) {
  return matchTypeOptions.value.find((o) => o.value === type)?.label ?? type;
}

function schoolLabel(participant: { competitor: CompetitorMini }) {
  const school = participant.competitor.school;
  return school ? (school.abbreviation || school.name) : '—';
}

function competitorMeta(participant: { competitor: CompetitorMini }) {
  const c = participant.competitor;
  const parts = [
    c.gender === 'male' ? 'Masculino' : 'Femenino',
    c.age !== null && c.age !== undefined ? `${c.age} años` : null,
    formatWeightKg(c.weight_kg),
    c.height_cm ? `${c.height_cm} cm` : null,
    c.grade?.name ?? null,
  ].filter(Boolean);
  return parts.join(' · ');
}

interface CompetitorDetailChip {
  label: string;
  value: string;
}

function competitorDetailChips(participant: { competitor: CompetitorMini }): CompetitorDetailChip[] {
  const c = participant.competitor;
  const chips: Array<CompetitorDetailChip | null> = [
    { label: 'Escuela', value: schoolLabel(participant) },
    c.age !== null && c.age !== undefined ? { label: 'Edad', value: `${c.age} años` } : null,
    formatWeightKg(c.weight_kg) ? { label: 'Peso', value: formatWeightKg(c.weight_kg)! } : null,
    c.height_cm ? { label: 'Est.', value: `${c.height_cm} cm` } : null,
    c.grade?.name ? { label: 'Grado', value: c.grade.name } : null,
  ];

  return chips.filter((chip): chip is CompetitorDetailChip => chip !== null);
}

function statusLabel(status: string) {
  return statusOptions.value.find((o) => o.value === status)?.label ?? status;
}

function updateStatus(status: string, options?: { confirmed?: boolean }) {
  router.put(
    `/events/${event.value.id}/categories/${category.value.id}/status`,
    { status, confirmed: options?.confirmed ?? false },
    { preserveScroll: true },
  );
}

function editBrackets() {
  updateStatus('bracket_pending');
}

function revertToAssignment() {
  const message =
    'Esta acción eliminará todos los encuentros y la estructura de la llave.\n\n¿Desea continuar?';
  if (!confirm(message)) return;
  updateStatus('draft', { confirmed: true });
}

function requestAssignParticipant(participant: EligibleParticipant) {
  if (participant.existing_category_name && isAdmin.value) {
    overrideDialog.value = {
      participantId: participant.id,
      categoryName: participant.existing_category_name,
    };
    return;
  }

  assignParticipant(participant.id);
}

function assignParticipant(participantId: number, adminOverride = false) {
  assignForm.event_competitor_id = participantId;
  assignForm.admin_override = adminOverride;
  assignForm.post(`/events/${event.value.id}/categories/${category.value.id}/competitors`, {
    preserveScroll: true,
    onSuccess: () => {
      overrideDialog.value = null;
    },
    onError: (errors) => {
      if (errors.modality_conflict && isAdmin.value) {
        overrideDialog.value = {
          participantId,
          categoryName: String(errors.existing_category_name ?? 'otra categoría'),
        };
      }
    },
  });
}

function confirmAdminOverride() {
  if (!overrideDialog.value) return;
  assignParticipant(overrideDialog.value.participantId, true);
}

function saveCategory() {
  editForm.put(`/events/${event.value.id}/categories/${category.value.id}`, { preserveScroll: true });
}

function removeAssignment(assignmentId: number, name: string) {
  if (!confirm(`¿Remover a ${name} de esta categoría?`)) return;
  router.delete(`/events/${event.value.id}/categories/${category.value.id}/competitors/${assignmentId}`, { preserveScroll: true });
}

function addBuilderRow() {
  // Estructura fija: los encuentros se crean con generar auto/manual.
}

function removeBuilderRow(_index: number) {
  // No se eliminan encuentros individuales en S2B.1.
}

function generateBracket(mode: 'auto' | 'manual') {
  const url =
    mode === 'auto'
      ? `/events/${event.value.id}/categories/${category.value.id}/bracket/generate-auto`
      : `/events/${event.value.id}/categories/${category.value.id}/bracket/generate-manual`;

  router.post(url, {}, { preserveScroll: true });
}

function onDragCard(participantId: number) {
  dragParticipantId.value = participantId;
}

function dropToSlot(rowIndex: number, side: 'red' | 'blue') {
  const row = builderRows.value[rowIndex];
  if (!isEditableMatchRow(row)) return;
  const pid = dragParticipantId.value;
  if (!pid) return;
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

function slotParticipantId(row: BuilderRow, side: 'red' | 'blue'): number | null {
  return side === 'red' ? row.red_event_competitor_id : row.blue_event_competitor_id;
}

function slotCompactMeta(participantId: number | null): string | null {
  if (!participantId) return null;
  const c = participantMap.value.get(participantId);
  if (!c) return null;

  const parts = [
    c.school?.abbreviation || c.school?.name || null,
    c.age !== null && c.age !== undefined ? `${c.age}a` : null,
    formatWeightKg(c.weight_kg)?.replace(' kg', 'kg') ?? null,
    c.height_cm ? `${c.height_cm}cm` : null,
  ].filter(Boolean);

  return parts.length > 0 ? parts.join(' · ') : null;
}

function participantNameById(id: number | null) {
  if (!id) return 'Slot vacío';
  const c = participantMap.value.get(id);
  return c ? `${c.first_name} ${c.last_name}` : 'Competidor';
}

function slotDisplay(row: BuilderRow, side: 'red' | 'blue') {
  const id = side === 'red' ? row.red_event_competitor_id : row.blue_event_competitor_id;
  if (id) return participantNameById(id);
  if (row.match_type === 'bye') return 'BYE';
  return 'Por definir';
}

function matchSideName(side: CategoryMatch['red_competitor'], matchType?: string) {
  const c = side?.competitor;
  if (c) return `${c.first_name} ${c.last_name}`;
  if (matchType === 'bye') return 'BYE';
  return 'Por definir';
}

function saveBracketBuilder() {
  matchesForm.rows = builderRows.value.map((row) => ({
    id: row.id,
    red_event_competitor_id: row.red_event_competitor_id,
    blue_event_competitor_id: row.blue_event_competitor_id,
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

    <div class="category-grid" :class="{ 'category-grid--parallel': isBracketPending }">
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
          </div>
        </form>
      </section>

      <section v-if="isDraft" class="card card-body">
        <h3 class="subsection-title">
          Asignación de participantes ({{ category.category_competitors.length }})
        </h3>

        <div v-if="canManage" class="generate-actions">
          <button
            type="button"
            class="btn btn-primary btn-inline"
            :disabled="!canGenerateBracket"
            @click="generateBracket('auto')"
          >
            Generar automáticamente
          </button>
          <button
            type="button"
            class="btn btn-ghost btn-inline"
            :disabled="!canGenerateBracket"
            @click="generateBracket('manual')"
          >
            Generar manualmente
          </button>
        </div>
        <p v-if="canManage" class="hint">
          Con al menos 2 competidores, genera la llave completa y pasa a elaboración de llaves.
        </p>
        <p v-if="canManage && !canGenerateBracket" class="hint hint-warn">
          Agrega al menos 2 competidores para poder generar la llave.
        </p>

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
            placeholder="Buscar participante inscrito en la modalidad…"
          />
          <label v-if="isAdmin" class="admin-filter">
            <input v-model="showAssignedElsewhere" type="checkbox" />
            Mostrar participantes ya asignados
          </label>
          <p v-if="assignForm.errors.event_competitor_id" class="hint hint-warn">
            {{ assignForm.errors.event_competitor_id }}
          </p>
          <ul class="eligible-list">
            <li
              v-for="p in filteredEligible"
              :key="p.id"
              class="eligible-row"
              :class="{ 'eligible-row--conflict': p.existing_category_name }"
            >
              <div>
                <strong>{{ p.competitor.first_name }} {{ p.competitor.last_name }}</strong>
                <span class="eligible-meta">{{ schoolLabel(p) }} · {{ competitorMeta(p) }}</span>
                <span v-if="p.existing_category_name" class="conflict-badge">
                  Ya asignado en: {{ p.existing_category_name }}
                </span>
              </div>
              <button
                v-if="!p.existing_category_name || isAdmin"
                type="button"
                class="btn btn-ghost btn-sm"
                :disabled="assignForm.processing"
                @click="requestAssignParticipant(p)"
              >
                {{ p.existing_category_name ? 'Override administrativo' : 'Agregar' }}
              </button>
            </li>
            <li v-if="filteredEligible.length === 0" class="hint eligible-empty">
              {{
                assignSearch.trim()
                  ? 'Sin coincidencias.'
                  : 'No hay participantes elegibles inscritos en esta modalidad.'
              }}
            </li>
          </ul>
        </div>

        <div v-if="overrideDialog" class="override-dialog card card-body">
          <p class="hint hint-warn">
            Este participante ya pertenece a otra categoría de la modalidad {{ category.modality.name }}.
          </p>
          <p class="hint">Categoría actual: <strong>{{ overrideDialog.categoryName }}</strong></p>
          <div class="status-row">
            <button type="button" class="btn btn-ghost btn-inline" @click="overrideDialog = null">Cancelar</button>
            <button
              type="button"
              class="btn btn-primary btn-inline"
              :disabled="assignForm.processing"
              @click="confirmAdminOverride"
            >
              Asignar igualmente
            </button>
          </div>
        </div>
      </section>

      <section v-else-if="isBracketPending" class="card card-body bracket-workspace">
        <div class="workflow-head">
          <h3 class="subsection-title">Elaborando llaves</h3>
          <button type="button" class="btn btn-ghost btn-inline" @click="revertToAssignment">Volver a asignación</button>
        </div>

        <p class="hint">
          Asigna participantes en los slots de la primera ronda (arrastrar o editar). Luego mueve a lista cuando esté completa.
        </p>

        <div v-if="builderRows.length === 0" class="hint bracket-empty">
          No hay encuentros en esta categoría. Vuelve a asignación para generar una nueva llave.
        </div>

        <div v-else class="builder-grid">
          <section class="builder-pool">
            <h4 class="assign-title">Competidores disponibles ({{ availableCards.length }})</h4>
            <p class="hint builder-pool-hint">Arrastra un competidor a los slots marcados como asignación requerida.</p>
            <ul class="eligible-list eligible-list--builder">
              <li
                v-for="p in availableCards"
                :key="p.id"
                class="eligible-row competitor-card"
                draggable="true"
                @dragstart="onDragCard(p.id)"
              >
                <div class="competitor-card-body">
                  <strong class="competitor-card-name">
                    {{ p.competitor.first_name }} {{ p.competitor.last_name }}
                  </strong>
                  <div class="competitor-card-details">
                    <span
                      v-for="chip in competitorDetailChips(p)"
                      :key="`${p.id}-${chip.label}`"
                      class="detail-chip"
                    >
                      <span class="detail-chip-label">{{ chip.label }}</span>
                      {{ chip.value }}
                    </span>
                  </div>
                </div>
              </li>
              <li v-if="availableCards.length === 0" class="hint eligible-empty">Todos los competidores ya están en slots editables.</li>
            </ul>
          </section>

          <section class="builder-matches">
            <h4 class="assign-title">Estructura de encuentros ({{ builderRows.length }})</h4>

            <div class="match-list">
              <div
                v-for="(row, idx) in builderRows"
                :key="`row-${row.id}`"
                class="match-row"
                :class="isEditableMatchRow(row) ? 'match-row--editable' : 'match-row--locked'"
              >
                <div class="match-head">
                  <div class="match-head-left">
                    <span class="match-code">{{ row.match_code || `#${idx + 1}` }}</span>
                    <span class="match-meta">R{{ row.round_number }} · {{ row.stage_label }}</span>
                  </div>
                  <div class="match-head-badges">
                    <span v-if="isEditableMatchRow(row)" class="assignment-badge">Asignación requerida</span>
                    <span v-else class="readonly-badge">Solo lectura</span>
                    <span class="type-pill">{{ matchTypeLabel(row.match_type) }}</span>
                  </div>
                </div>
                <div class="match-vs">
                  <div
                    class="slot-box"
                    :class="isEditableMatchRow(row) ? 'slot-box--editable' : 'slot-box--readonly'"
                    @dragover.prevent="isEditableMatchRow(row)"
                    @drop.prevent="dropToSlot(idx, 'red')"
                  >
                    <span class="slot-label">A</span>
                    <div class="slot-content">
                      <span class="slot-name">{{ slotDisplay(row, 'red') }}</span>
                      <span v-if="slotCompactMeta(slotParticipantId(row, 'red'))" class="slot-meta">
                        {{ slotCompactMeta(slotParticipantId(row, 'red')) }}
                      </span>
                    </div>
                    <button
                      v-if="isEditableMatchRow(row)"
                      type="button"
                      class="btn btn-ghost btn-sm"
                      @click="clearSlot(idx, 'red')"
                    >
                      ×
                    </button>
                  </div>
                  <span class="vs-badge">VS</span>
                  <div
                    class="slot-box"
                    :class="isEditableMatchRow(row) ? 'slot-box--editable' : 'slot-box--readonly'"
                    @dragover.prevent="isEditableMatchRow(row)"
                    @drop.prevent="dropToSlot(idx, 'blue')"
                  >
                    <span class="slot-label">B</span>
                    <div class="slot-content">
                      <span class="slot-name">{{ slotDisplay(row, 'blue') }}</span>
                      <span v-if="slotCompactMeta(slotParticipantId(row, 'blue'))" class="slot-meta">
                        {{ slotCompactMeta(slotParticipantId(row, 'blue')) }}
                      </span>
                    </div>
                    <button
                      v-if="isEditableMatchRow(row)"
                      type="button"
                      class="btn btn-ghost btn-sm"
                      @click="clearSlot(idx, 'blue')"
                    >
                      ×
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <div class="status-row" style="margin-top: 1rem">
              <button type="button" class="btn btn-primary btn-inline" :disabled="matchesForm.processing" @click="saveBracketBuilder">
                Guardar asignaciones
              </button>
              <button
                type="button"
                class="btn btn-inline"
                :class="canMoveToList ? 'btn-move-ready' : 'btn-ghost'"
                :disabled="!canMoveToList"
                @click="updateStatus('ready')"
              >
                <span v-if="canMoveToList" class="btn-move-ready-icon" aria-hidden="true">✓</span>
                Mover a lista
              </button>
            </div>
          </section>
        </div>
      </section>

      <section v-else-if="isReady" class="card card-body">
        <div class="workflow-head">
          <h3 class="subsection-title">En lista</h3>
          <button v-if="canManage" type="button" class="btn btn-ghost btn-inline" @click="editBrackets">
            Editar llaves
          </button>
        </div>
        <p class="hint">
          La categoría está en lista y bloqueada para cambios estructurales.
        </p>
        <div class="table-wrap">
          <table class="data-table">
            <thead>
              <tr>
                <th>Código</th>
                <th>Ronda</th>
                <th>Tipo</th>
                <th>Competidor A</th>
                <th>Competidor B</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="m in category.matches" :key="m.id">
                <td class="match-code-cell">{{ m.match_code || '—' }}</td>
                <td>R{{ m.round_number }}</td>
                <td>{{ matchTypeLabel(m.match_type) }}</td>
                <td>{{ matchSideName(m.red_competitor, m.match_type) }}</td>
                <td>{{ matchSideName(m.blue_competitor, m.match_type) }}</td>
              </tr>
              <tr v-if="category.matches.length === 0">
                <td colspan="5" class="hint table-empty">No hay encuentros registrados.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <section v-else-if="isAssigned" class="card card-body">
        <div class="workflow-head">
          <h3 class="subsection-title">Assigned — categoría en ring</h3>
          <button v-if="canManage" type="button" class="btn btn-ghost btn-inline" @click="updateStatus('ready')">
            Volver a Ready
          </button>
        </div>
        <p class="hint">Ring: {{ category.ring?.name ?? '—' }}. Preparado para operación (S2C+).</p>
        <div class="table-wrap">
          <table class="data-table">
            <thead>
              <tr>
                <th>Código</th>
                <th>Tipo</th>
                <th>Competidor A</th>
                <th>Competidor B</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="m in category.matches" :key="m.id">
                <td class="match-code-cell">{{ m.match_code || '—' }}</td>
                <td>{{ matchTypeLabel(m.match_type) }}</td>
                <td>{{ matchSideName(m.red_competitor, m.match_type) }}</td>
                <td>{{ matchSideName(m.blue_competitor, m.match_type) }}</td>
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

.category-grid--parallel {
  grid-template-columns: minmax(300px, 380px) minmax(0, 1fr);
  align-items: start;
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

.admin-filter {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin: 0.65rem 0;
  font-size: 0.82rem;
  color: var(--color-text-muted);
}

.eligible-row--conflict {
  opacity: 0.85;
  background: #fffbeb;
}

.conflict-badge {
  display: block;
  margin-top: 0.2rem;
  font-size: 0.75rem;
  color: #b45309;
  font-weight: 600;
}

.override-dialog {
  margin-top: 1rem;
  border: 1px solid #fcd34d;
  background: #fffbeb;
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

.bracket-workspace {
  min-height: 0;
}

.builder-grid {
  display: grid;
  grid-template-columns: minmax(300px, 360px) minmax(0, 1fr);
  gap: 1rem;
  align-items: stretch;
}

.builder-pool,
.builder-matches {
  border: 1px solid var(--color-border);
  border-radius: 8px;
  padding: 0.85rem;
  background: #f8fafc;
  min-height: 0;
}

.builder-pool {
  display: flex;
  flex-direction: column;
}

.builder-pool-hint {
  margin: 0 0 0.65rem;
  font-size: 0.78rem;
}

.builder-pool .eligible-list--builder {
  flex: 1;
  min-height: 500px;
  max-height: min(72vh, 720px);
  margin-top: 0;
}

.competitor-card {
  cursor: grab;
  border-style: dashed;
  align-items: flex-start;
}

.competitor-card:active {
  cursor: grabbing;
}

.competitor-card-body {
  flex: 1;
  min-width: 0;
}

.competitor-card-name {
  display: block;
  font-size: 0.92rem;
  line-height: 1.25;
}

.competitor-card-details {
  display: flex;
  flex-wrap: wrap;
  gap: 0.3rem;
  margin-top: 0.4rem;
}

.detail-chip {
  display: inline-flex;
  align-items: center;
  gap: 0.2rem;
  font-size: 0.72rem;
  padding: 0.15rem 0.4rem;
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 5px;
  color: #334155;
}

.detail-chip-label {
  font-weight: 700;
  color: #64748b;
  text-transform: uppercase;
  font-size: 0.62rem;
  letter-spacing: 0.03em;
}

.match-list {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.match-row {
  border-radius: 8px;
  padding: 0.65rem;
  transition: background 0.15s ease, border-color 0.15s ease;
}

.match-row--editable {
  background: linear-gradient(90deg, #fef3c7 0%, #eff6ff 12%, #eff6ff 100%);
  border: 2px solid #2563eb;
  border-left: 5px solid #f59e0b;
  box-shadow: 0 0 0 1px rgba(37, 99, 235, 0.15), 0 4px 12px rgba(37, 99, 235, 0.18);
}

.match-row--locked {
  opacity: 0.55;
  background: #e2e8f0;
  border: 1px dashed #cbd5e1;
  filter: grayscale(0.35);
}

.match-head {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 0.75rem;
  margin-bottom: 0.45rem;
  font-size: 0.78rem;
  color: #475569;
}

.match-head-left {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.45rem;
}

.match-head-badges {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: flex-end;
  gap: 0.35rem;
}

.assignment-badge {
  font-size: 0.66rem;
  font-weight: 700;
  padding: 0.18rem 0.55rem;
  border-radius: 999px;
  background: #2563eb;
  color: #fff;
  letter-spacing: 0.02em;
  white-space: nowrap;
}

.readonly-badge {
  font-size: 0.66rem;
  font-weight: 600;
  padding: 0.18rem 0.55rem;
  border-radius: 999px;
  background: #e2e8f0;
  color: #64748b;
  white-space: nowrap;
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
  gap: 0.35rem;
  padding: 0.45rem 0.5rem;
  border-radius: 6px;
  min-height: 44px;
}

.slot-content {
  display: flex;
  flex-direction: column;
  gap: 0.1rem;
  min-width: 0;
}

.slot-name {
  font-size: 0.82rem;
  font-weight: 600;
  line-height: 1.2;
  word-break: break-word;
}

.slot-meta {
  font-size: 0.68rem;
  color: #64748b;
  line-height: 1.2;
  word-break: break-word;
}

.slot-box--editable {
  border: 2px dashed #2563eb;
  background: #fff;
  box-shadow: inset 0 0 0 1px rgba(245, 158, 11, 0.25);
}

.slot-box--readonly {
  border: 1px solid #e2e8f0;
  background: #f8fafc;
  color: #64748b;
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

.generate-actions {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
  margin-bottom: 0.75rem;
}

.hint-warn {
  color: #b45309;
}

.bracket-empty {
  padding: 1rem;
  text-align: center;
  border: 1px dashed var(--color-border);
  border-radius: 8px;
  background: #f8fafc;
}

.match-code {
  font-family: ui-monospace, monospace;
  font-weight: 700;
  font-size: 0.82rem;
}

.match-code-cell {
  font-family: ui-monospace, monospace;
  font-weight: 600;
}

.match-meta {
  font-size: 0.75rem;
  color: #64748b;
}

.type-pill {
  font-size: 0.68rem;
  font-weight: 600;
  padding: 0.12rem 0.45rem;
  border-radius: 999px;
  background: #e2e8f0;
  color: #334155;
}

.btn-move-ready {
  background: #16a34a;
  color: #fff;
  border: 1px solid #16a34a;
  font-weight: 600;
}

.btn-move-ready:hover:not(:disabled) {
  background: #15803d;
  border-color: #15803d;
  color: #fff;
}

.btn-move-ready-icon {
  margin-right: 0.25rem;
  font-weight: 700;
}

@media (min-width: 1024px) {
  .builder-pool .eligible-list--builder {
    min-height: 540px;
  }
}

@media (max-width: 1100px) {
  .category-grid--parallel {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 900px) {
  .builder-grid {
    grid-template-columns: 1fr;
  }

  .builder-pool .eligible-list--builder {
    min-height: 360px;
    max-height: 50vh;
  }
}
</style>
