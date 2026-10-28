<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { Check, Link2, Search, ShieldCheck, UserX, X } from '@lucide/vue';
import { computed, ref } from 'vue';
import Pagination from '@/components/Pagination.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { getInitials } from '@/composables/useInitials';
import { approve, index, reject } from '@/routes/admin/users';
import type { Paginated, User } from '@/types';

type AdminUser = User & { links_count: number; approved_at: string | null };

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Usuarios', href: index() }],
    },
});

const props = defineProps<{
    users: Paginated<AdminUser>;
    filters: { status: string; search: string };
    pendingCount: number;
}>();

const page = usePage();
const currentUserId = computed(() => page.props.auth.user.id);

const search = ref(props.filters.search);
const busyId = ref<number | null>(null);
const toRevoke = ref<AdminUser | null>(null);
const revokeOpen = ref(false);

const statusFilters = [
    { value: '', label: 'Todos' },
    { value: 'pending', label: 'Pendientes' },
    { value: 'approved', label: 'Aprobados' },
    { value: 'rejected', label: 'Rechazados' },
];

const statusLabels: Record<AdminUser['status'], string> = {
    pending: 'Pendiente',
    approved: 'Aprobado',
    rejected: 'Rechazado',
};

const statusClasses: Record<AdminUser['status'], string> = {
    pending:
        'border-transparent bg-amber-100 text-amber-800 dark:bg-amber-500/15 dark:text-amber-300',
    approved:
        'border-transparent bg-emerald-100 text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-300',
    rejected:
        'border-transparent bg-red-100 text-red-800 dark:bg-red-500/15 dark:text-red-300',
};

function visit(params: { status?: string }) {
    const query: Record<string, string> = {};
    const status = params.status ?? props.filters.status;
    const term = search.value.trim();

    if (status) {
        query.status = status;
    }

    if (term) {
        query.search = term;
    }

    router.get(
        index.url({ query }),
        {},
        { preserveState: true, replace: true },
    );
}

function formatDate(value: string) {
    return new Date(value).toLocaleDateString('es-AR', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
}

function post(url: string, user: AdminUser) {
    busyId.value = user.id;
    router.post(
        url,
        {},
        {
            preserveScroll: true,
            onFinish: () => (busyId.value = null),
        },
    );
}

function approveUser(user: AdminUser) {
    post(approve.url(user.id), user);
}

function rejectUser(user: AdminUser) {
    post(reject.url(user.id), user);
}

function confirmRevoke() {
    const user = toRevoke.value;

    if (!user) {
        return;
    }

    revokeOpen.value = false;
    rejectUser(user);
}

function canRevoke(user: AdminUser) {
    return user.status === 'approved' && user.id !== currentUserId.value;
}

function askRevoke(user: AdminUser) {
    toRevoke.value = user;
    revokeOpen.value = true;
}
</script>

<template>
    <Head title="Usuarios" />

    <div class="mx-auto flex w-full max-w-6xl flex-col gap-6 px-4 py-6">
        <div class="flex flex-wrap items-center gap-3">
            <h1 class="text-2xl font-semibold tracking-tight">Usuarios</h1>
            <Badge
                v-if="pendingCount > 0"
                :class="statusClasses.pending"
                data-test="pending-count"
            >
                {{ pendingCount }}
                {{ pendingCount === 1 ? 'pendiente' : 'pendientes' }}
            </Badge>
            <Badge v-else variant="secondary">Sin pendientes</Badge>
        </div>

        <div class="flex flex-col gap-3 md:flex-row md:items-center">
            <form
                class="flex min-w-0 flex-1 gap-2"
                role="search"
                @submit.prevent="visit({})"
            >
                <div class="relative min-w-0 flex-1">
                    <Search
                        class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                    />
                    <Input
                        v-model="search"
                        type="search"
                        placeholder="Buscar por nombre o email"
                        aria-label="Buscar usuarios"
                        class="pl-9"
                    />
                </div>
                <Button type="submit" variant="outline">Buscar</Button>
            </form>

            <div
                class="flex flex-wrap gap-1 rounded-lg bg-muted p-1"
                role="group"
                aria-label="Filtrar por estado"
            >
                <button
                    v-for="f in statusFilters"
                    :key="f.value"
                    type="button"
                    class="rounded-md px-3 py-1.5 text-sm transition-colors"
                    :class="
                        filters.status === f.value
                            ? 'bg-background font-medium text-foreground shadow-sm'
                            : 'text-muted-foreground hover:text-foreground'
                    "
                    :aria-pressed="filters.status === f.value"
                    @click="visit({ status: f.value })"
                >
                    {{ f.label }}
                </button>
            </div>
        </div>

        <p
            v-if="users.data.length === 0"
            class="rounded-2xl border bg-card p-8 text-center text-muted-foreground"
        >
            No hay usuarios que coincidan con la búsqueda.
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
                            <th class="w-[34%] px-4 py-3 font-medium">
                                Usuario
                            </th>
                            <th class="w-[12%] px-4 py-3 font-medium">
                                Estado
                            </th>
                            <th class="w-[8%] px-4 py-3 text-right font-medium">
                                Links
                            </th>
                            <th class="w-[14%] px-4 py-3 font-medium">Alta</th>
                            <th class="w-[32%] px-4 py-3">
                                <span class="sr-only">Acciones</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-for="user in users.data" :key="user.id">
                            <td class="px-4 py-3">
                                <div class="flex min-w-0 items-center gap-3">
                                    <span
                                        class="flex size-9 shrink-0 items-center justify-center rounded-full bg-muted text-xs font-medium"
                                        aria-hidden="true"
                                        >{{ getInitials(user.name) }}</span
                                    >
                                    <div class="min-w-0">
                                        <p
                                            class="flex min-w-0 items-center gap-2 font-medium"
                                        >
                                            <span class="truncate">{{
                                                user.name
                                            }}</span>
                                            <Badge
                                                v-if="user.is_admin"
                                                variant="outline"
                                            >
                                                <ShieldCheck /> Admin
                                            </Badge>
                                        </p>
                                        <p
                                            class="truncate text-muted-foreground"
                                            :title="user.email"
                                        >
                                            {{ user.email }}
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <Badge :class="statusClasses[user.status]">{{
                                    statusLabels[user.status]
                                }}</Badge>
                            </td>
                            <td class="px-4 py-3 text-right tabular-nums">
                                {{ user.links_count }}
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">
                                {{ formatDate(user.created_at) }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap justify-end gap-2">
                                    <Button
                                        v-if="user.status !== 'approved'"
                                        size="sm"
                                        :disabled="busyId === user.id"
                                        @click="approveUser(user)"
                                    >
                                        <Check class="size-4" /> Aprobar
                                    </Button>
                                    <Button
                                        v-if="user.status === 'pending'"
                                        size="sm"
                                        variant="outline"
                                        :disabled="busyId === user.id"
                                        @click="rejectUser(user)"
                                    >
                                        <X class="size-4" /> Rechazar
                                    </Button>
                                    <Button
                                        v-if="canRevoke(user)"
                                        size="sm"
                                        variant="ghost"
                                        class="text-destructive hover:text-destructive"
                                        :disabled="busyId === user.id"
                                        @click="askRevoke(user)"
                                    >
                                        <UserX class="size-4" /> Revocar acceso
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
                    v-for="user in users.data"
                    :key="user.id"
                    class="rounded-2xl border bg-card p-4"
                >
                    <div class="flex min-w-0 items-start gap-3">
                        <span
                            class="flex size-10 shrink-0 items-center justify-center rounded-full bg-muted text-sm font-medium"
                            aria-hidden="true"
                            >{{ getInitials(user.name) }}</span
                        >
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-medium">{{ user.name }}</p>
                            <p class="truncate text-sm text-muted-foreground">
                                {{ user.email }}
                            </p>
                            <div class="mt-2 flex flex-wrap items-center gap-2">
                                <Badge :class="statusClasses[user.status]">{{
                                    statusLabels[user.status]
                                }}</Badge>
                                <Badge v-if="user.is_admin" variant="outline">
                                    <ShieldCheck /> Admin
                                </Badge>
                            </div>
                        </div>
                    </div>
                    <div
                        class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-muted-foreground"
                    >
                        <span class="inline-flex items-center gap-1">
                            <Link2 class="size-4" />
                            {{ user.links_count }}
                            {{ user.links_count === 1 ? 'link' : 'links' }}
                        </span>
                        <span>Alta: {{ formatDate(user.created_at) }}</span>
                    </div>
                    <div
                        v-if="user.status !== 'approved' || canRevoke(user)"
                        class="mt-3 flex flex-wrap gap-2"
                    >
                        <Button
                            v-if="user.status !== 'approved'"
                            size="sm"
                            class="flex-1"
                            :disabled="busyId === user.id"
                            @click="approveUser(user)"
                        >
                            <Check class="size-4" /> Aprobar
                        </Button>
                        <Button
                            v-if="user.status === 'pending'"
                            size="sm"
                            variant="outline"
                            class="flex-1"
                            :disabled="busyId === user.id"
                            @click="rejectUser(user)"
                        >
                            <X class="size-4" /> Rechazar
                        </Button>
                        <Button
                            v-if="canRevoke(user)"
                            size="sm"
                            variant="outline"
                            class="flex-1 text-destructive hover:text-destructive"
                            :disabled="busyId === user.id"
                            @click="askRevoke(user)"
                        >
                            <UserX class="size-4" /> Revocar acceso
                        </Button>
                    </div>
                </li>
            </ul>
        </template>

        <Pagination :links="users.links" />
    </div>

    <Dialog v-model:open="revokeOpen">
        <DialogContent>
            <DialogHeader>
                <DialogTitle
                    >¿Revocar el acceso de {{ toRevoke?.name }}?</DialogTitle
                >
                <DialogDescription>
                    No va a poder entrar más a Zep y se le van a invalidar sus
                    tokens de API. Sus links no se borran. Podés volver a
                    aprobarlo cuando quieras.
                </DialogDescription>
            </DialogHeader>
            <DialogFooter class="gap-2">
                <DialogClose as-child>
                    <Button variant="secondary">Cancelar</Button>
                </DialogClose>
                <Button variant="destructive" @click="confirmRevoke">
                    Revocar acceso
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
