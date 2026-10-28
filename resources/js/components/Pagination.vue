<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import type { Paginated } from '@/types';

const props = defineProps<{ links: Paginated<unknown>['links'] }>();

// Laravel manda '&laquo; Anterior' / 'Siguiente &raquo;': se limpian las entidades
// para no tener que usar v-html.
function cleanLabel(label: string): string {
    return label
        .replace(/&laquo;|&raquo;|«|»/g, '')
        .replace(/&amp;/g, '&')
        .trim();
}

const prev = computed(() => props.links[0]);
const next = computed(() => props.links[props.links.length - 1]);
const pages = computed(() => props.links.slice(1, -1));
const current = computed(() => pages.value.find((l) => l.active));
</script>

<template>
    <nav
        v-if="links.length > 3"
        class="flex items-center justify-between gap-2"
        aria-label="Paginación"
    >
        <Link
            v-if="prev.url"
            :href="prev.url"
            preserve-scroll
            class="inline-flex h-9 shrink-0 items-center rounded-md border px-3 text-sm hover:bg-accent"
        >
            ‹ {{ cleanLabel(prev.label) }}
        </Link>
        <span
            v-else
            class="inline-flex h-9 shrink-0 items-center rounded-md border px-3 text-sm text-muted-foreground opacity-50"
            aria-disabled="true"
        >
            ‹ {{ cleanLabel(prev.label) }}
        </span>

        <span class="text-sm text-muted-foreground md:hidden">
            Página {{ current ? cleanLabel(current.label) : '' }}
        </span>

        <div class="hidden flex-wrap items-center justify-center gap-1 md:flex">
            <template v-for="(link, i) in pages" :key="i">
                <Link
                    v-if="link.url"
                    :href="link.url"
                    preserve-scroll
                    class="inline-flex h-9 min-w-9 items-center justify-center rounded-md px-3 text-sm"
                    :class="
                        link.active
                            ? 'bg-primary text-primary-foreground'
                            : 'hover:bg-accent'
                    "
                    :aria-current="link.active ? 'page' : undefined"
                    >{{ cleanLabel(link.label) }}</Link
                >
                <span
                    v-else
                    class="inline-flex h-9 min-w-9 items-center justify-center px-2 text-sm text-muted-foreground"
                    >{{ cleanLabel(link.label) }}</span
                >
            </template>
        </div>

        <Link
            v-if="next.url"
            :href="next.url"
            preserve-scroll
            class="inline-flex h-9 shrink-0 items-center rounded-md border px-3 text-sm hover:bg-accent"
        >
            {{ cleanLabel(next.label) }} ›
        </Link>
        <span
            v-else
            class="inline-flex h-9 shrink-0 items-center rounded-md border px-3 text-sm text-muted-foreground opacity-50"
            aria-disabled="true"
        >
            {{ cleanLabel(next.label) }} ›
        </span>
    </nav>
</template>
