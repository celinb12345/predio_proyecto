<script setup lang="ts">
import PanelLayout from '@/components/panel/PanelLayout.vue';
import { formulario } from '@/lib/estilos';
import { Link } from '@inertiajs/vue3';

defineProps<{
    usuario: { name: string; rol: string };
    solicitudesPendientes: number;
    indicadores: {
        total: number;
        disponibles: number;
        en_mantenimiento: number;
        inactivos: number;
    };
    mantenimientos: { id: number; predio: string; inicio: string; fin: string }[];
}>();
</script>

<template>
    <PanelLayout titulo="Inicio" :usuario="usuario">
        <p class="mb-6 text-texto-suave">
            Bienvenido, <span class="font-semibold">{{ usuario.name }}</span>.
        </p>

        <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-5">
            <Link href="/panel/solicitantes" :class="formulario.tarjeta">
                <p class="text-sm text-texto-suave">Solicitudes pendientes</p>
                <p class="mt-2 text-3xl font-semibold text-amber-700">{{ solicitudesPendientes }}</p>
            </Link>
            <Link href="/panel/predios" :class="formulario.tarjeta">
                <p class="text-sm text-texto-suave">Predios registrados</p>
                <p class="mt-2 text-3xl font-semibold text-[var(--primario)]">{{ indicadores.total }}</p>
            </Link>
            <Link href="/panel/predios" :class="formulario.tarjeta">
                <p class="text-sm text-texto-suave">Disponibles</p>
                <p class="mt-2 text-3xl font-semibold text-emerald-700">{{ indicadores.disponibles }}</p>
            </Link>
            <Link href="/panel/predios" :class="formulario.tarjeta">
                <p class="text-sm text-texto-suave">En mantenimiento</p>
                <p class="mt-2 text-3xl font-semibold text-amber-700">{{ indicadores.en_mantenimiento }}</p>
            </Link>
            <Link href="/panel/predios" :class="formulario.tarjeta">
                <p class="text-sm text-texto-suave">Inhabilitados</p>
                <p class="mt-2 text-3xl font-semibold text-texto-suave">{{ indicadores.inactivos }}</p>
            </Link>
        </div>

        <section :class="[formulario.tarjeta, 'mt-8']">
            <div class="flex items-center justify-between gap-3">
                <h2 :class="formulario.titulo">Mantenimientos en los próximos 7 días</h2>
                <Link href="/panel/mantenimiento" class="text-sm font-semibold text-[var(--primario)] hover:underline">
                    Ver todos
                </Link>
            </div>

            <ul v-if="mantenimientos.length" class="mt-4 divide-y divide-borde">
                <li v-for="m in mantenimientos" :key="m.id" class="flex flex-wrap justify-between gap-2 py-3 text-sm">
                    <span class="font-medium text-texto">{{ m.predio }}</span>
                    <span class="text-texto-suave">
                        {{ m.inicio }}<template v-if="m.fin !== m.inicio"> al {{ m.fin }}</template>
                    </span>
                </li>
            </ul>
            <p v-else class="mt-4 text-sm text-texto-suave">No hay mantenimientos programados en los próximos 7 días.</p>
        </section>
    </PanelLayout>
</template>