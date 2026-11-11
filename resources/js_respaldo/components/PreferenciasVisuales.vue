<script setup lang="ts">
import { cambiar, preferencias, type Contraste, type Letra, type Modo, type Tema } from '@/lib/preferencias';
import { SlidersHorizontal, X } from 'lucide-vue-next';
import { ref } from 'vue';

const abierto = ref(false);

// Los colores de muestra son fijos, para que cada opción se vea aunque no esté activa
const temas: { valor: Tema; nombre: string; descripcion: string; principal: string; acento: string }[] = [
    { valor: 'adultos', nombre: 'Adultos', descripcion: 'Estilo institucional', principal: '#1e3a8a', acento: '#f59e0b' },
    { valor: 'jovenes', nombre: 'Jóvenes', descripcion: 'Colores vivos', principal: '#6d28d9', acento: '#f97316' },
    { valor: 'ninos', nombre: 'Niños', descripcion: 'Colores claros y letra grande', principal: '#0369a1', acento: '#facc15' },
];

const modos: { valor: Modo; texto: string }[] = [
    { valor: 'automatico', texto: 'Automático' },
    { valor: 'dia', texto: 'Día' },
    { valor: 'noche', texto: 'Noche' },
];

const letras: { valor: Letra; texto: string; etiqueta: string }[] = [
    { valor: 'normal', texto: 'A', etiqueta: 'Tamaño de letra normal' },
    { valor: 'grande', texto: 'A+', etiqueta: 'Tamaño de letra grande' },
    { valor: 'muy-grande', texto: 'A++', etiqueta: 'Tamaño de letra muy grande' },
];

const contrastes: { valor: Contraste; texto: string }[] = [
    { valor: 'normal', texto: 'Normal' },
    { valor: 'alto', texto: 'Alto contraste' },
];

const claseBotonBase = 'rounded-lg px-3 py-2 text-sm font-semibold ring-1 transition';

function claseSegmento(activo: boolean): string {
    return activo
        ? 'bg-[var(--primario)] text-white ring-[var(--primario)]'
        : 'bg-superficie text-texto ring-borde hover:bg-fondo';
}

function claseTema(activo: boolean): string {
    return activo
        ? 'bg-fondo text-texto ring-2 ring-[var(--primario)]'
        : 'bg-superficie text-texto ring-borde hover:bg-fondo';
}
</script>

<template>
    <div class="relative">
        <button
            type="button"
            class="inline-flex items-center gap-2 rounded-lg bg-superficie px-3.5 py-2 text-sm font-semibold text-texto ring-1 ring-borde hover:bg-fondo"
            :aria-expanded="abierto"
            aria-haspopup="dialog"
            @click="abierto = !abierto"
        >
            <SlidersHorizontal class="h-4 w-4" aria-hidden="true" />
            Apariencia
        </button>

        <div v-if="abierto" class="fixed inset-0 z-40" @click="abierto = false" />

        <div
            v-if="abierto"
            role="dialog"
            aria-label="Apariencia"
            class="absolute right-0 z-50 mt-2 w-[min(22rem,calc(100vw-2rem))] rounded-2xl bg-superficie p-5 text-texto shadow-xl ring-1 ring-borde"
        >
            <div class="mb-4 flex items-center justify-between">
                <p class="font-semibold">Apariencia</p>
                <button
                    type="button"
                    class="rounded-md p-1 text-texto-suave hover:bg-fondo"
                    aria-label="Cerrar"
                    @click="abierto = false"
                >
                    <X class="h-4 w-4" aria-hidden="true" />
                </button>
            </div>

            <!-- Tema -->
            <fieldset class="mb-5">
                <legend class="mb-2 text-sm font-semibold">Tema</legend>
                <div class="grid gap-2">
                    <button
                        v-for="t in temas"
                        :key="t.valor"
                        type="button"
                        :aria-pressed="preferencias.tema === t.valor"
                        :class="['flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-left ring-1 transition', claseTema(preferencias.tema === t.valor)]"
                        @click="cambiar('tema', t.valor)"
                    >
                        <span class="flex shrink-0 gap-1">
                            <span class="h-5 w-5 rounded-full" :style="{ background: t.principal }" />
                            <span class="h-5 w-5 rounded-full" :style="{ background: t.acento }" />
                        </span>
                        <span class="flex-1">
                            <span class="block text-sm font-semibold">{{ t.nombre }}</span>
                            <span class="block text-xs text-texto-suave">{{ t.descripcion }}</span>
                        </span>
                    </button>
                </div>
            </fieldset>

            <!-- Modo día / noche -->
            <fieldset class="mb-5">
                <legend class="mb-2 text-sm font-semibold">Modo</legend>
                <div class="grid grid-cols-3 gap-2">
                    <button
                        v-for="m in modos"
                        :key="m.valor"
                        type="button"
                        :aria-pressed="preferencias.modo === m.valor"
                        :class="[claseBotonBase, claseSegmento(preferencias.modo === m.valor)]"
                        @click="cambiar('modo', m.valor)"
                    >
                        {{ m.texto }}
                    </button>
                </div>
                <p class="mt-2 text-xs text-texto-suave">
                    Automático usa el modo noche de 18:00 a 06:00, según la hora de tu equipo.
                </p>
            </fieldset>

            <!-- Tamaño de letra -->
            <fieldset class="mb-5">
                <legend class="mb-2 text-sm font-semibold">Tamaño de letra</legend>
                <div class="grid grid-cols-3 gap-2">
                    <button
                        v-for="l in letras"
                        :key="l.valor"
                        type="button"
                        :aria-label="l.etiqueta"
                        :aria-pressed="preferencias.letra === l.valor"
                        :class="[claseBotonBase, claseSegmento(preferencias.letra === l.valor)]"
                        @click="cambiar('letra', l.valor)"
                    >
                        {{ l.texto }}
                    </button>
                </div>
            </fieldset>

            <!-- Contraste -->
            <fieldset>
                <legend class="mb-2 text-sm font-semibold">Contraste</legend>
                <div class="grid grid-cols-2 gap-2">
                    <button
                        v-for="c in contrastes"
                        :key="c.valor"
                        type="button"
                        :aria-pressed="preferencias.contraste === c.valor"
                        :class="[claseBotonBase, claseSegmento(preferencias.contraste === c.valor)]"
                        @click="cambiar('contraste', c.valor)"
                    >
                        {{ c.texto }}
                    </button>
                </div>
            </fieldset>
        </div>
    </div>
</template>