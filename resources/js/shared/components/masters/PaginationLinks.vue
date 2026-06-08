<script setup lang="ts">

import { Link } from '@inertiajs/vue3';

import { computed } from 'vue';

import type { Paginated } from '@shared/types/masters';

import { normalizePaginator } from '@shared/utils/normalizePaginator';



const props = defineProps<{

  paginator?: Paginated<unknown> | Record<string, unknown> | null;

}>();



const normalized = computed(() => normalizePaginator(props.paginator));

</script>



<template>

  <nav v-if="normalized.meta.last_page > 1" class="pagination">

    <span class="hint">Total: {{ normalized.meta.total }}</span>

    <div class="pagination-links">

      <template v-for="(link, i) in normalized.links" :key="i">

        <Link

          v-if="link.url"

          :href="link.url"

          class="pagination-link"

          :class="{ 'is-active': link.active }"

          v-html="link.label"

        />

        <span v-else class="pagination-link is-disabled" v-html="link.label" />

      </template>

    </div>

  </nav>

</template>

