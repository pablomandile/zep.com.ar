<script setup lang="ts">
import { computed } from 'vue';
import type { StatRow } from '@/types';

const props = defineProps<{
    title: string;
    rows: StatRow[];
    labels?: Record<string, string>;
}>();

const total = computed(() => props.rows.reduce((s, r) => s + r.clicks, 0));
const max = computed(() => Math.max(1, ...props.rows.map((r) => r.clicks)));

function percent(clicks: number) {
    return total.value ? Math.round((clicks / total.value) * 100) : 0;
}
</script>

<template>
    <section class="rounded-2xl border bg-card p-4">
        <h3 class="mb-3 text-sm font-medium">{{ title }}</h3>
        <p v-if="rows.length === 0" class="text-sm text-muted-foreground">
            Sin datos todavía.
        </p>
        <ul v-else class="grid gap-2">
            <li
                v-for="row in rows"
                :key="row.label"
                class="grid gap-1"
                :title="`${row.clicks} clics (${percent(row.clicks)}%)`"
            >
                <div class="flex items-baseline justify-between gap-2 text-sm">
                    <span class="truncate">{{
                        labels?.[row.label] ?? row.label
                    }}</span>
                    <span class="shrink-0 text-muted-foreground tabular-nums"
                        >{{ row.clicks }}
                        <span class="text-xs"
                            >· {{ percent(row.clicks) }}%</span
                        ></span
                    >
                </div>
                <div class="h-1.5 rounded-full bg-muted">
                    <div
                        class="h-full rounded-full bg-primary/80"
                        :style="{ width: `${(row.clicks / max) * 100}%` }"
                    />
                </div>
            </li>
        </ul>
    </section>
</template>
