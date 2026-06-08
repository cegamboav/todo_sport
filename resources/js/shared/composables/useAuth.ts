import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import type { LicenseState, SharedPageProps, User } from '@shared/types';

export function useAuth() {
  const page = usePage<SharedPageProps>();

  const user = computed<User | null>(() => page.props.auth.user);
  const license = computed<LicenseState>(() => page.props.license);

  return {
    user,
    license,
    isAuthenticated: computed(() => user.value !== null),
  };
}
