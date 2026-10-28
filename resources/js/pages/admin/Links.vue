<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { MousePointerClick, Power, PowerOff, Search } from '@lucide/vue';
import { ref } from 'vue';
import CopyButton from '@/components/links/CopyButton.vue';
import Pagination from '@/components/Pagination.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { index, toggle } from '@/routes/admin/links';
import type { Paginated, ShortLink } from '@/types';

type AdminLink = ShortLink & {
    user: { id: number; name: string; email: string };
};

type LinkState = 'active' | 'disabled' | 'expired';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Todos los links', href: index() }],
    },
});

const props = defineProps<{
    links: Paginated<AdminLink>;
    filters: { search: string };
}>();

const search = ref(props.filters.search);
const busyCode = ref<string | null>(null);

const stateLabels: Record<LinkState, string> = {
    active: 'Activo',
    disabled: 'Desactivado',
    expired: 'Vencido',
};

const stateClasses: Record<LinkState, string> = {
    active: 'border-transparent bg-emerald-100 text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-300',
    disabled:
        'border-transparent bg-red-100 text-red-800 dark:bg-red-500/15 dark:text-red-300',
    expired:
        'border-transparent bg-amber-100 text-amber-800 dark:bg-amber-500/15 dark:text-amber-300',
};

function stateOf(link: AdminLink): LinkState {
    if (!link.is_active) {
        return 'disabled';
    }

    if (link.expires_at && new Date(link.expires_at).getTime() < Date.now()) {
        return 'expired';
    }

    return 'active';
}

function submitSearch() {
    const term = search.value.trim();

    router.get(
        index.url({ query: term ? { search: term } : {} }),
        {},
        { preserveState: true, replace: true },
    );
}

function toggleLink(link: AdminLink) {
    busyCode.value = link.code;
    router.post(
        toggle.url(link.code),
        {},
        {
            preserveScroll: true,
            onFinish: () => (busyCode.value = null),
        },
    );
}

function displayUrl(url: string) {
    return url.replace(/^https?:\/\//, '');
}

function formatDate(value: string) {
    return new Date(value).toLocaleDateString('es-AR', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
}
</script>

<template>
    <Head title="Todos los links" />

    <div class="mx-auto flex w-full max-w-6xl flex-col gap-6 px-4 py-6">
        <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1">
            <h1 class="text-2xl font-semibold tracking-tight">
                Todos los links
            </h1>
            <span class="text-sm text-muted-foreground"
                >{{ links.total }} en total</span
            >
        </div>

        <form
            class="flex min-w-0 gap-2"
            role="search"
            @submit.prevent="submitSearch"
        >
            <div class="relative min-w-0 flex-1">
                <Search
                    class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                    v-model="search"
                    type="search"
                    placeholder="Buscar por código, URL o email del dueño"
                    aria-label="Buscar links"
                    class="pl-9"
                />
            </div>
            <Button type="submit" variant="outline">Buscar</Button>
        </form>

        <p
            v-if="links.data.length === 0"
            class="rounded-2xl border bg-card p-8 text-center text-muted-foreground"
        >
            No hay links que coincidan con la búsqueda.
        </p>

        <template v-else>
            <!-- Desktop: tabla -->
            <div
                class="hidden overflow-hidden rounded-2xl border bg-card md:block"
            >
                <table class="w-full table-fixed text-sm">
                    <thead
                        class="border-b bg-muted/50 text-left text-muted-foreground"
                    >
                        <tr>
                            <th class="w-[32%] px-4 py-3 font-medium">Link</th>
                            <th class="w-[20%] px-4 py-3 font-medium">Dueño</th>
                            <th class="w-[7%] px-4 py-3 text-right font-medium">
                                Clics
                            </th>
                            <th class="w-[11%] px-4 py-3 font-medium">
                                Estado
                            </th>
                            <th class="w-[10%] px-4 py-3 font-medium">Fecha</th>
                            <th class="w-[20%] px-4 py-3">
                                <span class="sr-only">Acciones</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-for="link in links.data" :key="link.id">
                            <td class="px-4 py-3">
                                <a
                                    :href="link.short_url"
                                    target="_blank"
                                    rel="noopener"
                                    class="block truncate font-medium hover:underline"
                                    >{{ displayUrl(link.short_url) }}</a
                                >
                                <p
                                    class="truncate text-muted-foreground"
                                    :title="link.original_url"
                                >
                                    {{ link.original_url }}
                                </p>
                            </td>
                            <td class="px-4 py-3">
                                <p class="truncate">{{ link.user.name }}</p>
                                <p
                                    class="truncate text-muted-foreground"
                                    :title="link.user.email"
                                >
                                    {{ link.user.email }}
                                </p>
                            </td>
                            <td class="px-4 py-3 text-right tabular-nums">
                                {{ link.clicks_count }}
                            </td>
                            <td class="px-4 py-3">
                                <Badge :class="stateClasses[stateOf(link)]">{{
                                    stateLabels[stateOf(link)]
                                }}</Badge>
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">
                                {{ formatDate(link.created_at) }}
                            </td>
                            <td class="px-4 py-3">
                                <div
                                    class="flex items-center justify-end gap-1"
                                >
                                    <CopyButton
                                        :text="link.short_url"
                                        size="icon-sm"
                                        variant="ghost"
                                    />
                                    <Button
                                        size="sm"
                                        variant="ghost"
                                        :class="
                                            link.is_active
                                                ? 'text-destructive hover:text-destructive'
                                                : ''
                                        "
                                        :disabled="busyCode === link.code"
                                        @click="toggleLink(link)"
                                    >
                                        <PowerOff
                                            v-if="link.is_active"
                                            class="size-4"
                                        />
                                        <Power v-else class="size-4" />
                                        {{
                                            link.is_active
                                                ? 'Desactivar'
                                                : 'Reactivar'
                                        }}
                                    </Button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Mobile: tarjetas -->
            <ul class="flex flex-col gap-3 md:hidden">
                <li
                    v-for="link in links.data"
                    :key="link.id"
                    class="rounded-2xl border bg-card p-4"
                >
                    <div class="flex min-w-0 items-start justify-between gap-2">
                        <a
                            :href="link.short_url"
                            target="_blank"
                            rel="noopener"
                            class="min-w-0 truncate font-medium hover:underline"
                            >{{ displayUrl(link.short_url) }}</a
                        >
                        <Badge :class="stateClasses[stateOf(link)]">{{
                            stateLabels[stateOf(link)]
                        }}</Badge>
                    </div>
                    <p
                        class="mt-1 truncate text-sm text-muted-foreground"
                        :title="link.original_url"
                    >
                        → {{ link.original_url }}
                    </p>
                    <p class="mt-2 truncate text-sm">
                        {{ link.user.name }}
                        <span class="text-muted-foreground"
                            >· {{ link.user.email }}</span
                        >
                    </p>
                    <div
                        class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-muted-foreground"
                    >
                        <span class="inline-flex items-center gap-1">
                            <MousePointerClick class="size-4" />
                            {{ link.clicks_count }}
                            {{ link.clicks_count === 1 ? 'clic' : 'clics' }}
                        </span>
                        <span>{{ formatDate(link.created_at) }}</span>
                    </div>
                    <div class="mt-3 flex gap-2">
                        <CopyButton
                            :text="link.short_url"
                            size="sm"
                            variant="outline"
                        />
                        <Button
                            size="sm"
                            variant="outline"
                            class="flex-1"
                            :class="
                                link.is_active
                                    ? 'text-destructive hover:text-destructive'
                                    : ''
                            "
                            :disabled="busyCode === link.code"
                            @click="toggleLink(link)"
                        >
                            <PowerOff v-if="link.is_active" class="size-4" />
                            <Power v-else class="size-4" />
                            {{ link.is_active ? 'Desactivar' : 'Reactivar' }}
                        </Button>
                    </div>
                </li>
            </ul>
        </template>

        <Pagination :links="links.links" />
    </div>
</template>
