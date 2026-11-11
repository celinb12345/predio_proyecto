<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { AlertTriangle, CheckCircle2, Info, X, XCircle } from 'lucide-vue-next';
import { ref, watch } from 'vue';

const estilos = {
    exito: { barra: 'bg-emerald-600', color: 'text-emerald-600', titulo: 'Listo', icono: CheckCircle2 },
    advertencia: { barra: 'bg-[var(--secundario)]', color: 'text-amber-600', titulo: 'Atención', icono: AlertTriangle },
    error: { barra: 'bg-red-600', color: 'text-red-600', titulo: 'No se pudo completar', icono: XCircle },
    info: { barra: 'bg-[var(--primario)]', color: 'text-[var(--primario)]', titulo: 'Aviso', icono: Info },
} as const;

type Tipo = keyof typeof estilos;

const tipos = Object.keys(estilos) as Tipo[];
const page = usePage();
const avisos = ref<{ id: number; tipo: Tipo; mensaje: string }[]>([]);
let siguienteId = 1;

function cerrar(id: number) {
    avisos.value = avisos.value.filter((a) => a.id !== id);
}

function mostrar(tipo: Tipo, mensaje: string) {
    const id = siguienteId++;

    avisos.value.push({ id, tipo, mensaje });

    window.setTimeout(() => cerrar(id), 4500);
}

// Se revisa cada vez que cambia la página, también al entrar con un aviso ya guardado
watch(
    () => page.props.flash as Partial<Record<Tipo, string | null>> | undefined,
    (flash) => {
        if (!flash) return;

        for (const tipo of tipos) {
            const mensaje = flash[tipo];

            if (mensaje) {
                mostrar(tipo, mensaje);
            }
        }
    },
    { immediate: true },
);
</script>

<template>
    <Teleport to="body">
        <div
            aria-live="polite"
            class="pointer-events-none fixed right-4 top-4 z-[60] flex w-[calc(100%-2rem)] max-w-sm flex-col gap-3 sm:right-6 sm:top-6"
        >
            <TransitionGroup name="aviso" tag="div" class="flex flex-col gap-3">
                <div
                    v-for="a in avisos"
                    :key="a.id"
                    role="status"
                    class="pointer-events-auto flex overflow-hidden rounded-xl bg-superficie shadow-lg shadow-slate-900/10 ring-1 ring-borde"
                >
                    <span :class="['w-1.5 shrink-0', estilos[a.tipo].barra]" />

                    <div class="flex flex-1 items-start gap-3 p-4">
                        <component :is="estilos[a.tipo].icono" :class="['mt-0.5 h-5 w-5 shrink-0', estilos[a.tipo].color]" />

                        <div class="flex-1">
                            <p class="text-sm font-semibold text-texto">{{ estilos[a.tipo].titulo }}</p>
                            <p class="mt-0.5 text-sm text-texto-suave">{{ a.mensaje }}</p>
                        </div>

                        <button
                            type="button"
                            class="text-texto-suave hover:text-texto-suave"
                            aria-label="Cerrar aviso"
                            @click="cerrar(a.id)"
                        >
                            <X class="h-4 w-4" />
                        </button>
                    </div>
                </div>
            </TransitionGroup>
        </div>
    </Teleport>
</template>

<style scoped>
.aviso-enter-from,
.aviso-leave-to {
    opacity: 0;
    transform: translateY(-8px);
}

.aviso-enter-active,
.aviso-leave-active {
    transition: all 0.25s ease;
}
</style>