<script setup lang="ts">
import PanelLayout from '@/components/panel/PanelLayout.vue';
import { Link } from '@inertiajs/vue3';

interface Enlace {
    url: string | null;
    label: string;
    active: boolean;
}

defineProps<{
    usuario: { name: string; rol: string };
    predios: {
        data: {
            id: number;
            nombre: string;
            tipo: string | null;
            capacidad: number | null;
            ubicacion: string | null;
            estado: string;
            portada: string | null;
        }[];
        links: Enlace[];
        from: number | null;
        to: number | null;
        total: number;
    };
}>();

const etiquetasEstado: Record<string, string> = {
    disponible: 'Disponible',
    mantenimiento: 'En mantenimiento',
    inhabilitado: 'No disponible',
};

const clasesEstado: Record<string, string> = {
    disponible: 'bg-green-100 text-green-800',
    mantenimiento: 'bg-amber-100 text-amber-800',
    inhabilitado: 'bg-gray-200 text-gray-700',
};
</script>

<template>
    <PanelLayout titulo="Predios disponibles" :usuario="usuario">
        <p class="mb-6 text-sm text-gray-600">
            Consulta los predios, su ubicación, sus fotos y su equipamiento. Para pedir un préstamo, usa Mis solicitudes.
        </p>

        <ul v-if="predios.data.length" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <li v-for="p in predios.data" :key="p.id">
                <Link
                    :href="`/panel/consulta/predios/${p.id}`"
                    class="flex h-full flex-col overflow-hidden rounded-xl bg-white text-gray-900 ring-1 ring-gray-200 hover:ring-[var(--primario)]"
                >
                    <img
                        v-if="p.portada"
                        :src="p.portada"
                        :alt="`Foto de ${p.nombre}`"
                        loading="lazy"
                        class="h-40 w-full object-cover"
                    />
                    <div v-else class="flex h-40 w-full items-center justify-center bg-gray-100 text-sm text-gray-400">
                        Sin fotos
                    </div>

                    <div class="flex flex-1 flex-col p-5">
                        <div class="flex items-start justify-between gap-3">
                            <h2 class="font-semibold text-[var(--primario)]">{{ p.nombre }}</h2>
                            <span
                                :class="['shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold', clasesEstado[p.estado] ?? 'bg-gray-100 text-gray-700']"
                            >
                                {{ etiquetasEstado[p.estado] ?? p.estado }}
                            </span>
                        </div>
                        <p v-if="p.tipo" class="mt-1 text-sm text-gray-500">{{ p.tipo }}</p>
                        <p class="mt-2 text-sm text-gray-600">Capacidad: {{ p.capacidad ?? 'No indicada' }}</p>
                        <p class="mt-1 text-sm text-gray-600">{{ p.ubicacion ?? 'Ubicación no indicada' }}</p>
                    </div>
                </Link>
            </li>
        </ul>
        <p v-else class="text-sm text-gray-500">Todavía no hay predios registrados.</p>

        <nav class="mt-6 flex flex-wrap items-center justify-between gap-2 text-sm">
            <p class="text-gray-500">
                Mostrando {{ predios.from ?? 0 }} a {{ predios.to ?? 0 }} de {{ predios.total }}
            </p>
            <div class="flex flex-wrap gap-1">
                <template v-for="enlace in predios.links" :key="enlace.label">
                    <Link
                        v-if="enlace.url"
                        :href="enlace.url"
                        preserve-state
                        class="rounded-md px-3 py-1.5"
                        :class="enlace.active ? 'bg-[var(--primario)] text-white' : 'bg-white text-gray-700 ring-1 ring-gray-200 hover:bg-gray-50'"
                    >
                        <span v-html="enlace.label" />
                    </Link>
                    <span v-else class="rounded-md px-3 py-1.5 text-gray-400">
                        <span v-html="enlace.label" />
                    </span>
                </template>
            </div>
        </nav>
    </PanelLayout>
</template>