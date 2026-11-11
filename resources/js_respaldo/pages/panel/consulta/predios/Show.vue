<script setup lang="ts">
import PanelLayout from '@/components/panel/PanelLayout.vue';
import { Link } from '@inertiajs/vue3';

defineProps<{
    usuario: { name: string; rol: string };
    predio: {
        id: number;
        nombre: string;
        tipo: string | null;
        capacidad: number | null;
        ubicacion: string | null;
        link_google_maps: string | null;
        estado: string;
    };
    galeria: {
        url: string;
        tipo: string;
        descripcion: string | null;
        es_imagen: boolean;
        es_video: boolean;
    }[];
    tarifa: { monto: number; resolucion: string } | null;
    tienePersona: boolean;
    inventario: { equipo: string; cantidad: number; estado: string }[];
}>();

const etiquetasEstado: Record<string, string> = {
    disponible: 'Disponible',
    mantenimiento: 'En mantenimiento',
    inhabilitado: 'No disponible',
};

const etiquetasTipoFoto: Record<string, string> = {
    ambiente: 'Ambiente',
    entrada: 'Entrada',
    otro: 'Otro',
};
</script>

<template>
    <PanelLayout :titulo="predio.nombre" :usuario="usuario">
        <Link href="/panel/consulta/predios" class="mb-6 inline-block text-sm font-semibold text-[var(--primario)] hover:underline">
            ← Volver a predios
        </Link>

        <!-- Datos del predio -->
        <section class="mb-8 rounded-xl bg-white p-5 text-gray-900 ring-1 ring-gray-200">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-lg font-semibold">{{ predio.nombre }}</h2>
                    <p v-if="predio.tipo" class="text-sm text-gray-500">{{ predio.tipo }}</p>
                </div>
                <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-700">
                    {{ etiquetasEstado[predio.estado] ?? predio.estado }}
                </span>
            </div>

            <dl class="mt-4 grid gap-4 text-sm sm:grid-cols-2">
                <div>
                    <dt class="text-gray-500">Capacidad</dt>
                    <dd class="font-medium">{{ predio.capacidad ?? 'No indicada' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Ubicación</dt>
                    <dd class="font-medium">{{ predio.ubicacion ?? 'No indicada' }}</dd>
                </div>
            </dl>

            <a
                v-if="predio.link_google_maps"
                :href="predio.link_google_maps"
                target="_blank"
                rel="noopener noreferrer"
                class="mt-4 inline-block text-sm font-semibold text-[var(--primario)] hover:underline"
            >
                Ver en Google Maps
            </a>
        </section>

        <!-- Tarifa del solicitante -->
        <section class="mb-8 rounded-xl bg-amber-50 p-5 text-gray-900 ring-1 ring-amber-200">
            <h2 class="text-sm font-semibold text-gray-700">Tu tarifa para este predio</h2>

            <template v-if="tarifa">
                <p v-if="tarifa.monto === 0" class="mt-2 font-semibold">Sin costo para tu tipo de persona.</p>
                <p v-else class="mt-2 text-2xl font-bold text-[var(--primario)]">Bs {{ tarifa.monto.toFixed(2) }}</p>
                <p class="mt-1 text-xs text-gray-500">Resolución: {{ tarifa.resolucion }}</p>
            </template>
            <p v-else-if="!tienePersona" class="mt-2 text-sm">
                Tu cuenta no está vinculada a una persona, por eso no podemos mostrar tu tarifa.
            </p>
            <p v-else class="mt-2 text-sm">
                No hay tarifa vigente para tu tipo de persona en este predio. Consulta en Secretaría General.
            </p>
        </section>

        <!-- Galería -->
        <section class="mb-8">
            <h2 class="mb-4 text-lg font-semibold text-[var(--primario)]">Fotos y videos</h2>

            <div v-if="galeria.length" class="grid gap-4 sm:grid-cols-2">
                <figure
                    v-for="(g, i) in galeria"
                    :key="i"
                    class="overflow-hidden rounded-xl bg-white text-gray-900 ring-1 ring-gray-200"
                >
                    <img
                        v-if="g.es_imagen"
                        :src="g.url"
                        :alt="g.descripcion ?? predio.nombre"
                        loading="lazy"
                        class="h-56 w-full object-cover"
                    />
                    <video
                        v-else-if="g.es_video"
                        :src="g.url"
                        controls
                        preload="metadata"
                        class="h-56 w-full bg-black object-contain"
                    />
                    <a
                        v-else
                        :href="g.url"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="flex h-56 items-center justify-center text-sm font-semibold text-[var(--primario)]"
                    >
                        Abrir archivo
                    </a>
                    <figcaption class="p-3 text-sm text-gray-600">
                        {{ etiquetasTipoFoto[g.tipo] ?? g.tipo }}<span v-if="g.descripcion"> · {{ g.descripcion }}</span>
                    </figcaption>
                </figure>
            </div>
            <p v-else class="text-sm text-gray-500">Este predio todavía no tiene fotos.</p>
        </section>

        <!-- Equipamiento -->
        <section class="mb-8">
            <h2 class="mb-4 text-lg font-semibold text-[var(--primario)]">Equipamiento</h2>

            <ul v-if="inventario.length" class="space-y-2">
                <li
                    v-for="(e, i) in inventario"
                    :key="i"
                    class="flex flex-wrap items-center justify-between gap-2 rounded-lg bg-white px-4 py-3 text-sm text-gray-900 ring-1 ring-gray-200"
                >
                    <span class="font-medium">{{ e.equipo }}</span>
                    <span class="text-gray-500">{{ e.cantidad }} · {{ e.estado }}</span>
                </li>
            </ul>
            <p v-else class="text-sm text-gray-500">Este predio no tiene equipamiento registrado.</p>
        </section>

        <div v-if="predio.estado === 'disponible'">
            <Link
                href="/panel/solicitantes/crear"
                class="inline-block w-full rounded-lg bg-[var(--primario)] px-5 py-2.5 text-center text-sm font-semibold text-white sm:w-auto"
            >
                Solicitar este predio
            </Link>
        </div>
    </PanelLayout>
</template>