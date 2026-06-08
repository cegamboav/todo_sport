export type UserRole = 'admin' | 'staff' | 'professor' | 'mesa' | 'corner';

export type UserStatus =
  | 'active'
  | 'inactive'
  | 'blocked'
  | 'pending_password_change';

export interface AdminAccessState {
  can_access_masters: boolean;
  can_access_events: boolean;
  can_access_rings: boolean;
  can_access_dashboard: boolean;
  active_event_staff: {
    event_id: number;
    event_name: string;
    event_status: string;
  } | null;
}

export interface User {
  id: number;
  username: string;
  role: UserRole;
  status: UserStatus;
}

export type LicenseBanner = 'warning' | 'error';

export interface LicenseState {
  status: string;
  organization?: string | null;
  license_id?: string | null;
  expires_at?: string | null;
  days_remaining?: number | null;
  features: string[];
  max_rings?: number | null;
  banner?: LicenseBanner | null;
  is_writable: boolean;
}

export interface SharedPageProps {
  auth: {
    user: User | null;
    access: AdminAccessState | null;
  };
  license: LicenseState;
  flash: {
    success?: string | null;
    error?: string | null;
  };
  [key: string]: unknown;
}
