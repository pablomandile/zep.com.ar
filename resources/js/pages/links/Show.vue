<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, ExternalLink } from '@lucide/vue';
import BreakdownList from '@/components/links/BreakdownList.vue';
import ClicksChart from '@/components/links/ClicksChart.vue';
import CopyButton from '@/components/links/CopyButton.vue';
import EditLinkDialog from '@/components/links/EditLinkDialog.vue';
import LinkStatusBadge from '@/components/links/LinkStatusBadge.vue';
import QrDialog from '@/components/links/QrDialog.vue';
import { index, show } from '@/routes/links';
import type { LinkStats, ShortLink } from '@/types';

const props = defineProps<{
    link: ShortLink;
    days: number;
    stats: LinkStats;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Mis enlaces', href: index() },
            { title: 'Estadísticas', href: index() },
        ],
    },
});

const ranges = [
    { value: 7, label: '7 días' },
    { value: 30, label: '30 días' },
    { value: 90, label: '90 días' },
    { value: 365, label: '1 año' },
];

function setRange(days: number) {
    router.get(
        show.url(props.link.code),
        { days },
        { preserveScroll: true, preserveState: true, replace: true },
    );
}

const deviceLabels: Record<string, string> = {
    desktop: 'Computadora',
    mobile: 'Celular',
    tablet: 'Tablet',
};

const regionNames = new Intl.DisplayNames(['es'], { type: 'region' });

function countryLabels(rows: LinkStats['countries']) {
    return Object.fromEntries(
        rows
            .filter((r) => /^[A-Z]{2}$/.test(r.label))
            .map((r) => [r.label, regionNames.of(r.label) ?? r.label]),
    );
}

const dateTime = new Intl.DateTimeFormat('es-AR', {
    dateStyle: 'medium',
    timeStyle: 'short',
});

function displayUrl(url: string) {
    return url.replace(/^https?:\/\//, '');
}
</script>

<template>
    <Head :title="`Estadísticas de /${link.code}`" />

    <div class="mx-auto flex w-full max-w-5xl flex-col gap-6 px-4 py-6">
        <Link
            :href="index()"
            class="inline-flex w-fit items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
        >
            <ArrowLeft class="size-4" /> Mis enlaces
        </Link>

        <header class="rounded-2xl border bg-card p-4 md:p-6">
            <div class="flex flex-wrap items-center gap-2">
                <h1
                    class="min-w-0 truncate text-2xl font-semibold text-primary"
                >
                    {{ displayUrl(link.short_url) }}
                </h1>
                <LinkStatusBadge :link="link" />
            </div>
            <a
                :href="link.original_url"
                target="_blank"
                rel="noopener noreferrer"
                class="mt-1 flex min-w-0 items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
                :title="link.original_url"
            >
                <span class="truncate">{{ link.original_url }}</span>
                <ExternalLink class="size-3.5 shrink-0" />
            </a>
            <div class="mt-4 flex flex-wrap gap-2">
                <CopyButton :text="link.short_url" />
                <QrDialog :url="link.short_url" :code="link.code" />
                <EditLinkDialog :link="link" />
            </div>
        </header>

        <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
            <div class="rounded-2xl border bg-card p-4">
                <p class="text-sm text-muted-foreground">Clics totales</p>
                <p class="mt-1 text-3xl font-semibold tabular-nums">
                    {{ stats.total_clicks }}
                </p>
            </div>
            <div class="rounded-2xl border bg-card p-4">
                <p class="text-sm text-muted-foreground">En el período</p>
                <p class="mt-1 text-3xl font-semibold tabular-nums">
                    {{ stats.period_clicks }}
                </p>
            </div>
            <div class="rounded-2xl border bg-card p-4">
                <p class="text-sm text-muted-foreground">Visitantes únicos</p>
                <p class="mt-1 text-3xl font-semibold tabular-nums">
                    {{ stats.unique_visitors }}
                </p>
            </div>
            <div class="rounded-2xl border bg-card p-4">
                <p class="text-sm text-muted-foreground">Último clic</p>
                <p class="mt-2 text-sm font-medium">
                    {{
                        stats.last_clicked_at
                            ? dateTime.format(new Date(stats.last_clicked_at))
                            : 'Todavía ninguno'
                    }}
                </p>
            </div>
        </div>

        <section class="rounded-2xl border bg-card p-4 md:p-6">
            <div
                class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
            >
                <h2 class="font-medium">Clics por día</h2>
                <div class="flex gap-1 rounded-lg bg-muted p-1" role="group">
                    <button
                        v-for="range in ranges"
                        :key="range.value"
                        type="button"
                        class="flex-1 rounded-md px-3 py-1 text-sm transition-colors sm:flex-none"
                        :class="
                            days === range.value
                                ? 'bg-background font-medium shadow-sm'
                                : 'text-muted-foreground hover:text-foreground'
                        "
                        :aria-pressed="days === range.value"
                        @click="setRange(range.value)"
                    >
                        {{ range.label }}
                    </button>
                </div>
            </div>
            <ClicksChart :days="stats.per_day" />
        </section>

        <div class="grid gap-3 md:grid-cols-2 lg:grid-cols-3">
            <BreakdownList title="De dónde vienen" :rows="stats.referers" />
            <BreakdownList
                title="Dispositivos"
                :rows="stats.devices"
                :labels="deviceLabels"
            />
            <BreakdownList title="Navegadores" :rows="stats.browsers" />
            <BreakdownList title="Sistemas operativos" :rows="stats.os" />
            <BreakdownList
                title="Países"
                :rows="stats.countries"
                :labels="countryLabels(stats.countries)"
            />
        </div>
    </div>
</template>
