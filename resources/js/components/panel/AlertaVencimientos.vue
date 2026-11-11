<script setup lang="ts">
import { TriangleAlert, X } from 'lucide-vue-next';
import { onBeforeUnmount, onMounted, ref } from 'vue';

interface Alerta {
    predio: string;
    tipo: string;
    vence: string;
    dias: number;
}

const props = defineProps<{
    alertas: Alerta[];
}>();

const visible = ref(true);
const parpadeando = ref(true);
let temporizador: number | undefined;

onMounted(() => {
    temporizador = window.setTimeout(() => {
        parpadeando.value = false;
    }, 10_000);
});

onBeforeUnmount(() => {
    window.clearTimeout(temporizador);
});

function formatearFecha(fecha: string): string {
    const [anio, mes, dia] = fecha.split('-');

    return `${dia}/${mes}/${anio}`;
}

function textoDias(dias: number): string {
    if (dias === 0) return 'vence hoy';
    if (dias === 1) return 'vence mañana';

    return `vence en ${dias} días`;
}
</script>

<template>
    <Teleport to="body">
        <aside
            v-if="visible && alertas.length > 0"
            role="alert"
            aria-live="assertive"
            :class="[
                'fixed right-4 top-4 z-[60] w-[min(24rem,calc(100vw-2rem))] rounded-2xl bg-superficie p-5 text-texto shadow-xl ring-2 ring-[var(--secundario)]',
                parpadeando ? 'parpadea' : '',
            ]"
        >
            <div class="flex items-start gap-3">
                <TriangleAlert class="mt-0.5 h-6 w-6 shrink-0 text-[var(--secundario)]" aria-hidden="true" />

                <div class="flex-1">
                    <p class="font-semibold">Tarifas por vencer</p>
                    <ul class="mt-2 space-y-1.5 text-sm">
                        <li v-for="(a, indice) in alertas" :key="indice">
                            <span class="font-medium">{{ a.predio }}</span> · {{ a.tipo }}:
                            {{ textoDias(a.dias) }} ({{ formatearFecha(a.vence) }})
                        </li>
                    </ul>
                </div>

                <button
                    type="button"
                    class="rounded-md p-1 text-texto-suave hover:bg-fondo"
                    aria-label="Cerrar aviso"
                    @click="visible = false"
                >
                    <X class="h-4 w-4" aria-hidden="true" />
                </button>
            </div>
        </aside>
    </Teleport>
</template>

<style scoped>
.parpadea {
    animation: parpadeo 1s ease-in-out infinite;
}

@keyframes parpadeo {
    0%,
    100% {
        box-shadow: 0 0 0 0 transparent;
    }

    50% {
        box-shadow: 0 0 0 6px var(--secundario);
    }
}
</style>