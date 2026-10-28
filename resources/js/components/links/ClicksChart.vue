<script setup lang="ts">
import { computed, ref } from 'vue';

const props = defineProps<{
    days: { date: string; clicks: number }[];
}>();

const hovered = ref<number | null>(null);

const max = computed(() => Math.max(1, ...props.days.map((d) => d.clicks)));

// Tope del eje redondeado "lindo" (1, 2, 5, 10, 20, 50...).
const axisMax = computed(() => {
    const m = max.value;
    const pow = 10 ** Math.floor(Math.log10(m));
    const step = [1, 2, 5, 10].find((s) => s * pow >= m) ?? 10;

    return step * pow;
});

const dayFormat = new Intl.DateTimeFormat('es-AR', {
    day: 'numeric',
    month: 'short',
    timeZone: 'UTC',
});
const longFormat = new Intl.DateTimeFormat('es-AR', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    timeZone: 'UTC',
});

function label(date: string, long = false) {
    const d = new Date(`${date}T00:00:00Z`);

    return (long ? longFormat : dayFormat).format(d);
}

// Pocas etiquetas en el eje X: primera, del medio y última.
const tickIndexes = computed(() => {
    const n = props.days.length;

    return new Set([0, Math.floor((n - 1) / 2), n - 1]);
});

const active = computed(() =>
    hovered.value === null ? null : props.days[hovered.value],
);

// Sobre la barra activa; cerca de los bordes se ancla al costado para no salirse.
const tooltipStyle = computed(() => {
    if (hovered.value === null) {
        return {};
    }

    const ratio = (hovered.value + 0.5) / props.days.length;

    if (ratio < 0.2) {
        return { left: '0' };
    }

    if (ratio > 0.8) {
        return { right: '0' };
    }

    return { left: `${ratio * 100}%`, transform: 'translateX(-50%)' };
});
</script>

<template>
    <figure class="w-full">
        <div class="relative flex h-48 gap-2">
            <!-- Eje Y: solo el tope y el cero, recesivos. -->
            <div
                class="flex w-6 shrink-0 flex-col justify-between text-right text-[11px] text-muted-foreground tabular-nums"
                aria-hidden="true"
            >
                <span>{{ axisMax }}</span>
                <span>0</span>
            </div>

            <div class="relative min-w-0 flex-1">
                <div
                    class="pointer-events-none absolute inset-x-0 top-0 border-t border-dashed border-border"
                />
                <div
                    class="pointer-events-none absolute inset-x-0 bottom-0 border-t border-border"
                />

                <div
                    class="absolute inset-0 flex items-end gap-0.5"
                    @mouseleave="hovered = null"
                >
                    <div
                        v-for="(day, i) in days"
                        :key="day.date"
                        class="group relative flex h-full min-w-0 flex-1 cursor-default items-end"
                        tabindex="0"
                        :aria-label="`${label(day.date, true)}: ${day.clicks} clics`"
                        @mouseenter="hovered = i"
                        @focus="hovered = i"
                        @blur="hovered = null"
                    >
                        <div
                            class="w-full rounded-t-[4px] bg-primary transition-opacity"
                            :class="
                                hovered !== null && hovered !== i
                                    ? 'opacity-40'
                                    : 'opacity-90'
                            "
                            :style="{
                                height: day.clicks
                                    ? `max(3px, ${(day.clicks / axisMax) * 100}%)`
                                    : '0',
                            }"
                        />
                    </div>
                </div>

                <div
                    v-if="active"
                    class="pointer-events-none absolute -top-2 z-10 -translate-y-full rounded-md border bg-popover px-3 py-1.5 text-xs whitespace-nowrap text-popover-foreground shadow-md"
                    :style="tooltipStyle"
                    role="status"
                >
                    <span class="font-medium capitalize">{{
                        label(active.date, true)
                    }}</span>
                    ·
                    <span class="tabular-nums">{{ active.clicks }}</span>
                    {{ active.clicks === 1 ? 'clic' : 'clics' }}
                </div>
            </div>
        </div>

        <div
            class="mt-1 ml-8 flex justify-between text-[11px] text-muted-foreground"
            aria-hidden="true"
        >
            <template v-for="(day, i) in days" :key="day.date">
                <span v-if="tickIndexes.has(i)">{{ label(day.date) }}</span>
            </template>
        </div>

        <details class="mt-3 text-sm">
            <summary
                class="cursor-pointer text-muted-foreground hover:text-foreground"
            >
                Ver como tabla
            </summary>
            <table class="mt-2 w-full text-left">
                <thead class="text-muted-foreground">
                    <tr>
                        <th class="py-1 font-normal">Día</th>
                        <th class="py-1 text-right font-normal">Clics</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="day in days" :key="day.date" class="border-t">
                        <td class="py-1 capitalize">
                            {{ label(day.date, true) }}
                        </td>
                        <td class="py-1 text-right tabular-nums">
                            {{ day.clicks }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </details>
    </figure>
</template>
