import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import type { SelectOption } from '@shared/types/masters';

export interface EventWorkspaceRegistrationItem {
  id: number;
  label: string;
  amount: string | number;
  status: string;
  is_billable: boolean;
  admin_override: boolean;
  event_modality_id: number | null;
  event_combo_id: number | null;
}

export interface EventWorkspaceParticipant {
  id: number;
  competitor: {
    first_name: string;
    last_name: string;
    school?: { name: string; abbreviation?: string | null } | null;
  };
  registration_items: EventWorkspaceRegistrationItem[];
}

export interface ParticipantMetrics {
  catalog_competitors: number;
  enrolled: number;
  pending_to_add: number;
  pending_payment: number;
}

export interface EventWorkspaceData {
  id: number;
  name: string;
  status: string;
  event_date: string | null;
  venue: string | null;
  settings?: {
    third_place_mode: string;
    allow_team_forms: boolean;
    bronze_mode: string | null;
  };
  event_modalities: {
    id: number;
    modality_id: number;
    enabled: boolean;
    price: string | number;
    modality: { id: number; code: string; name: string };
  }[];
  combos: {
    id: number;
    name: string;
    price: string | number;
    enabled: boolean;
    modalities: { id: number; code: string; name: string }[];
  }[];
  event_competitors: EventWorkspaceParticipant[];
  event_staff: { id: number; user: { id: number; username: string; role: string } }[];
}

export interface EventWorkspacePageProps {
  event: EventWorkspaceData;
  participantMetrics: ParticipantMetrics;
  workspace: {
    title: string;
    status: string;
    status_label: string;
    event_date: string | null;
    venue: string | null;
    summary: { participants: number; pending_charges: number };
  };
  canManage: boolean;
  canManageStaff: boolean;
  canEnrollParticipants: boolean;
  isAdmin: boolean;
  schoolOptions: SelectOption[];
  gradeOptions: SelectOption[];
  statusOptions: SelectOption[];
  thirdPlaceOptions: (SelectOption & { description?: string })[];
  registrationStatusOptions: SelectOption[];
  searchUrls: { competitors: string; pendingCompetitors: string; staffUsers: string };
  canAccessEventAdmin?: boolean;
  canAccessEventOperations?: boolean;
}

export function useEventWorkspacePage() {
  const page = usePage<EventWorkspacePageProps>();

  const event = computed(() => page.props.event);
  const workspace = computed(() => page.props.workspace);
  const canManage = computed(() => page.props.canManage);
  const canManageStaff = computed(() => page.props.canManageStaff);
  const canEnrollParticipants = computed(() => page.props.canEnrollParticipants);
  const isAdmin = computed(() => page.props.isAdmin);

  return {
    page,
    event,
    workspace,
    canManage,
    canManageStaff,
    canEnrollParticipants,
    isAdmin,
    schoolOptions: computed(() => page.props.schoolOptions),
    gradeOptions: computed(() => page.props.gradeOptions),
    statusOptions: computed(() => page.props.statusOptions),
    thirdPlaceOptions: computed(() => page.props.thirdPlaceOptions),
    registrationStatusOptions: computed(() => page.props.registrationStatusOptions),
    searchUrls: computed(() => page.props.searchUrls),
    participantMetrics: computed(() => page.props.participantMetrics),
    canAccessEventAdmin: computed(() => page.props.canAccessEventAdmin ?? page.props.canManage),
    canAccessEventOperations: computed(() => page.props.canAccessEventOperations ?? false),
  };
}
