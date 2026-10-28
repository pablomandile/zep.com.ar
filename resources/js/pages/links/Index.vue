<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    BarChart3,
    MousePointerClick,
    Plus,
    Search,
    Trash2,
} from '@lucide/vue';
import { ref, watch } from 'vue';
import CopyButton from '@/components/links/CopyButton.vue';
import EditLinkDialog from '@/components/links/EditLinkDialog.vue';
import LinkStatusBadge from '@/components/links/LinkStatusBadge.vue';
import QrDialog from '@/components/links/QrDialog.vue';
import Pagination from '@/components/Pagination.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { dashboard } from '@/routes';
import { destroy, index, show } from '@/routes/links';
import type { Paginated, ShortLink } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Mis enlaces', href: index() }],
    },
});

const props = defineProps<{
    links: Paginated<ShortLink>;
    filters: { search: string; status: string };
}>();

const search = ref(props.filters.search);
const status = ref(props.filters.status);

const statusOptions = [
    { value: '', label: 'Todos' },
    { value: 'active', label: 'Activos' },
    { value: 'inactive', label: 'Inactivos' },
];

let timer: ReturnType<typeof setTimeout> | undefined;

function applyFilters() {
    router.get(
        index.url(),
        {
            search: search.value || undefined,
            status: status.value || undefined,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

watch(search, () => {
    clearTimeout(timer);
    timer = setTimeout(applyFilters, 350);
});

watch(status, applyFilters);

function remove(link: ShortLink) {
    router.delete(destroy.url(link.code), { preserveScroll: true });
}

const dateFormat = new Intl.DateTimeFormat('es-AR', { dateStyle: 'medium' });

function displayUrl(url: string) {
    return url.replace(/^https?:\/\//, '');
}
</script>

<template>
    <Head title="Mis enlaces" />

    <div class="mx-auto flex w-full max-w-5xl flex-col gap-6 px-4 py-6">
        <div
            class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
        >
            <h1 class="text-2xl font-semibold tracking-tight">Mis enlaces</h1>
            <Button as-child>
                <Link :href="dashboard()">
                    <Plus class="size-4" /> Acortar otro
                </Link>
            </Button>
        </div>

        <div class="flex flex-col gap-3 sm:flex-row">
            <div class="relative min-w-0 flex-1">
                <Search
                    class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                    v-model="search"
                    type="search"
                    placeholder="Buscar por código, URL o título"
                    class="pl-9"
                    aria-label="Buscar"
                />
            </div>
            <div class="flex gap-1 rounded-lg bg-muted p-1" role="group">
                <button
                    v-for="option in statusOptions"
                    :key="option.value"
                    type="button"
                    class="flex-1 rounded-md px-3 py-1.5 text-sm transition-colors sm:flex-none"
                    :class="
                        status === option.value
                            ? 'bg-background font-medium shadow-sm'
                            : 'text-muted-foreground hover:text-foreground'
                    "
                    :aria-pressed="status === option.value"
                    @click="status = option.value"
                >
                    {{ option.label }}
                </button>
            </div>
        </div>

        <div
            v-if="links.data.length === 0"
            class="rounded-2xl border border-dashed p-10 text-center text-muted-foreground"
        >
            <template v-if="filters.search || filters.status">
                No hay links que coincidan con la búsqueda.
            </template>
            <template v-else>
                Todavía no acortaste ningún link.
                <Link :href="dashboard()" class="text-foreground underline"
                    >Acortá el primero</Link
                >.
            </template>
        </div>

        <ul v-else class="grid grid-cols-1 gap-3">
            <li
                v-for="link in links.data"
                :key="link.id"
                class="rounded-2xl border bg-card p-4"
                :class="{ 'opacity-70': !link.is_active }"
            >
                <div class="flex items-start gap-3">
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <Link
                                :href="show(link.code)"
                                class="truncate font-semibold text-primary hover:underline"
                                >{{ displayUrl(link.short_url) }}</Link
                            >
                            <LinkStatusBadge :link="link" />
                        </div>
                        <p
                            class="mt-1 truncate text-sm text-muted-foreground"
                            :title="link.original_url"
                        >
                            {{ link.original_url }}
                        </p>
                        <p
                            class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-xs text-muted-foreground"
                        >
                            <span class="inline-flex items-center gap-1">
                                <MousePointerClick class="size-3.5" />
                                {{ link.clicks_count }}
                                {{ link.clicks_count === 1 ? 'clic' : 'clics' }}
                            </span>
                            <span
                                >Creado el
                                {{
                                    dateFormat.format(new Date(link.created_at))
                                }}</span
                            >
                            <span v-if="link.expires_at"
                                >Vence el
                                {{
                                    dateFormat.format(new Date(link.expires_at))
                                }}</span
                            >
                        </p>
                    </div>
                </div>

                <div
                    class="mt-3 flex flex-wrap items-center gap-1 border-t pt-3"
                >
                    <CopyButton
                        :text="link.short_url"
                        size="sm"
                        variant="ghost"
                    />
                    <QrDialog
                        :url="link.short_url"
                        :code="link.code"
                        size="sm"
                        variant="ghost"
                    />
                    <Button size="sm" variant="ghost" as-child>
                        <Link :href="show(link.code)">
                            <BarChart3 class="size-4" /> Estadísticas
                        </Link>
                    </Button>
                    <div class="ml-auto flex gap-1">
                        <EditLinkDialog :link="link" />
                        <Dialog>
                            <DialogTrigger as-child>
                                <Button
                                    size="icon-sm"
                                    variant="ghost"
                                    aria-label="Eliminar"
                                    title="Eliminar"
                                >
                                    <Trash2 class="size-4" />
                                </Button>
                            </DialogTrigger>
                            <DialogContent>
                                <DialogHeader>
                                    <DialogTitle
                                        >¿Eliminar /{{
                                            link.code
                                        }}?</DialogTitle
                                    >
                                    <DialogDescription>
                                        El link corto deja de funcionar y se
                                        pierden sus estadísticas. El código no
                                        se vuelve a usar para otro link.
                                    </DialogDescription>
                                </DialogHeader>
                                <DialogFooter>
                                    <DialogClose as-child>
                                        <Button variant="outline"
                                            >Cancelar</Button
                                        >
                                    </DialogClose>
                                    <DialogClose as-child>
                                        <Button
                                            variant="destructive"
                                            @click="remove(link)"
                                            >Eliminar</Button
                                        >
                                    </DialogClose>
                                </DialogFooter>
                            </DialogContent>
                        </Dialog>
                    </div>
                </div>
            </li>
        </ul>

        <Pagination :links="links.links" />
    </div>
</template>
