<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { Clock, ShieldX } from '@lucide/vue';
import { computed } from 'vue';
import { logout } from '@/routes';

const props = defineProps<{
    status: 'pending' | 'rejected';
}>();

defineOptions({
    layout: {
        title: '',
        description: '',
    },
});

const page = usePage();
const email = computed(() => page.props.auth.user?.email);
const rejected = computed(() => props.status === 'rejected');
</script>

<template>
    <Head
        :title="
            rejected ? 'Cuenta no habilitada' : 'Cuenta pendiente de aprobación'
        "
    />

    <div class="flex flex-col items-center gap-4 text-center">
        <div
            class="flex size-14 items-center justify-center rounded-2xl"
            :class="rejected ? 'bg-destructive/10' : 'bg-primary/10'"
        >
            <ShieldX v-if="rejected" class="size-7 text-destructive" />
            <Clock v-else class="size-7 text-primary" />
        </div>

        <template v-if="rejected">
            <h1 class="text-xl font-medium">Tu cuenta no fue habilitada</h1>
            <p class="text-sm text-pretty text-muted-foreground">
                Por ahora no podés acortar links con esta cuenta. Si creés que
                es un error, respondé el email que te enviamos.
            </p>
        </template>
        <template v-else>
            <h1 class="text-xl font-medium">
                Tu cuenta está esperando aprobación
            </h1>
            <p class="text-sm text-pretty text-muted-foreground">
                Ya recibimos tu registro. En cuanto la aprobemos te vamos a
                avisar a
                <span class="font-medium text-foreground">{{ email }}</span>
                y vas a poder empezar a acortar links.
            </p>
        </template>

        <Link
            :href="logout()"
            as="button"
            class="mt-2 text-sm text-muted-foreground underline underline-offset-4 hover:text-foreground"
            >Cerrar sesión</Link
        >
    </div>
</template>
