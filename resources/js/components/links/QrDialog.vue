<script setup lang="ts">
import { Download, QrCode } from '@lucide/vue';
import QRCode from 'qrcode';
import { ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';

const props = withDefaults(
    defineProps<{
        url: string;
        code: string;
        size?: 'default' | 'sm' | 'icon' | 'icon-sm';
        variant?: 'default' | 'outline' | 'secondary' | 'ghost';
    }>(),
    { size: 'default', variant: 'outline' },
);

const open = ref(false);
const svg = ref('');

const options = { margin: 2, errorCorrectionLevel: 'M' as const };

watch(open, async (isOpen) => {
    if (isOpen && !svg.value) {
        svg.value = await QRCode.toString(props.url, {
            ...options,
            type: 'svg',
        });
    }
});

function save(href: string, ext: string) {
    const a = document.createElement('a');
    a.href = href;
    a.download = `zep-${props.code}.${ext}`;
    a.click();
}

async function downloadPng() {
    save(await QRCode.toDataURL(props.url, { ...options, width: 1024 }), 'png');
}

function downloadSvg() {
    const blob = new Blob([svg.value], { type: 'image/svg+xml' });
    const href = URL.createObjectURL(blob);
    save(href, 'svg');
    setTimeout(() => URL.revokeObjectURL(href), 1000);
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogTrigger as-child>
            <Button
                type="button"
                :size="size"
                :variant="variant"
                aria-label="Código QR"
                title="Código QR"
            >
                <QrCode class="size-4" />
                <span v-if="!size.startsWith('icon')">QR</span>
            </Button>
        </DialogTrigger>
        <DialogContent class="sm:max-w-sm">
            <DialogHeader>
                <DialogTitle>Código QR</DialogTitle>
                <DialogDescription class="break-all">
                    {{ url }}
                </DialogDescription>
            </DialogHeader>
            <div
                class="mx-auto aspect-square w-full max-w-64 rounded-lg bg-white p-2 [&_svg]:h-full [&_svg]:w-full"
                v-html="svg"
            />
            <div class="grid grid-cols-2 gap-2">
                <Button type="button" variant="outline" @click="downloadPng">
                    <Download class="size-4" /> PNG
                </Button>
                <Button type="button" variant="outline" @click="downloadSvg">
                    <Download class="size-4" /> SVG
                </Button>
            </div>
        </DialogContent>
    </Dialog>
</template>
