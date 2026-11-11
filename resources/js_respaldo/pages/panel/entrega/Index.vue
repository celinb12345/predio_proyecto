<script setup lang="ts">
import PanelLayout from '@/components/panel/PanelLayout.vue';
import { Link, router, useForm } from '@inertiajs/vue3';

interface Fila {
    id: number;
    referencia: string;
    solicitante: string;
    estado_reserva: string;
    situacion: string;
}

interface Enlace {
    url: string | null;
    label: string;
    active: boolean;
}

interface Personal {
    id: number;
    nombre: string;
    apellido: string;
    celular: string | null;
    activo: boolean;
}

const props = defineProps<{
    usuario: { name: string; rol: string };
    filas: {
        data: Fila[];
        links: Enlace[];
        from: number | null;
        to: number | null;
        total: number;
    };
    personal: Personal[];
}>();

const campo =
    'mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-[var(--primario)] focus:outline-none';

const formPersonal = useForm({
    nombre: '',
    apellido: '',
    celular: '',
});

function registrarPersonal() {
    formPersonal.post('/panel/entrega/personal', {
        preserveScroll: true,
        onSuccess: () => formPersonal.reset(),
    });
}

function cambiarActivo(id: number) {
    router.post(`/panel/entrega/personal/${id}/activo`, {}, { preserveScroll: true });
}
</script>

<template>
    <PanelLayout titulo="Entrega y recepción" :usuario="usuario">
        <section class="mb-10">
            <h2 class="mb-4 text-lg font-semibold text-[var(--primario)]">Personal de limpieza</h2>

            <form
                class="mb-6 grid max-w-3xl gap-4 rounded-xl p-5 ring-1 ring-gray-200 sm:grid-cols-3"
                @submit.prevent="registrarPersonal"
            >
                <label class="block text-sm font-medium">
                    Nombre
                    <input v-model="formPersonal.nombre" type="text" :class="campo" />
                    <span v-if="formPersonal.errors.nombre" class="text-sm text-red-600">{{ formPersonal.errors.nombre }}</span>
                </label>
                <label class="block text-sm font-medium">
                    Apellido
                    <input v-model="formPersonal.apellido" type="text" :class="campo" />
                    <span v-if="formPersonal.errors.apellido" class="text-sm text-red-600">{{ formPersonal.errors.apellido }}</span>
                </label>
                <label class="block text-sm font-medium">
                    Celular (opcional)
                    <input v-model="formPersonal.celular" type="text" :class="campo" />
                    <span v-if="formPersonal.errors.celular" class="text-sm text-red-600">{{ formPersonal.errors.celular }}</span>
                </label>
                <div class="sm:col-span-3">
                    <button
                        type="submit"
                        :disabled="formPersonal.processing"
                        class="rounded-lg bg-[var(--primario)] px-5 py-2.5 text-sm font-semibold text-white disabled:bg-gray-400"
                    >
                        Agregar personal
                    </button>
                </div>
            </form>

            <div v-if="personal.length" class="overflow-x-auto rounded-xl ring-1 ring-gray-200">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-[var(--primario)] text-left text-white">
                        <tr>
                            <th class="px-4 py-3">Nombre</th>
                            <th class="px-4 py-3">Celular</th>
                            <th class="px-4 py-3">Situación</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        <tr v-for="p in personal" :key="p.id">
                            <td class="px-4 py-3 font-medium">{{ p.apellido }}, {{ p.nombre }}</td>
                            <td class="px-4 py-3">{{ p.celular ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <span v-if="p.activo" class="font-semibold text-[var(--primario)]">Activo</span>
                                <span v-else class="text-gray-500">Inactivo</span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <button
                                    type="button"
                                    class="font-semibold text-[var(--primario)] hover:underline"
                                    @click="cambiarActivo(p.id)"
                                >
                                    {{ p.activo ? 'Desactivar' : 'Activar' }}
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p v-else class="text-sm text-gray-500">Todavía no hay personal de limpieza registrado.</p>
        </section>

        <section>
            <h2 class="mb-4 text-lg font-semibold text-[var(--primario)]">Reservas</h2>

            <div class="overflow-x-auto rounded-xl ring-1 ring-gray-200">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-[var(--primario)] text-left text-white">
                        <tr>
                            <th class="px-4 py-3">Solicitud</th>
                            <th class="px-4 py-3">Solicitante</th>
                            <th class="px-4 py-3">Reserva</th>
                            <th class="px-4 py-3">Situación</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        <tr v-for="fila in filas.data" :key="fila.id">
                            <td class="px-4 py-3 font-medium">{{ fila.referencia }}</td>
                            <td class="px-4 py-3">{{ fila.solicitante }}</td>
                            <td class="px-4 py-3">
                                <span class="rounded-full bg-gray-200 px-2.5 py-1 text-xs font-semibold text-gray-700">
                                    {{ fila.estado_reserva }}
                                </span>
                            </td>
                            <td class="px-4 py-3">{{ fila.situacion }}</td>
                            <td class="px-4 py-3 text-right">
                                <Link :href="`/panel/entrega/${fila.id}`" class="font-semibold text-[var(--primario)] hover:underline">
                                    Gestionar
                                </Link>
                            </td>
                        </tr>
                        <tr v-if="filas.data.length === 0">
                            <td colspan="5" class="px-4 py-8 text-center text-gray-500">No hay reservas para gestionar.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <nav class="mt-6 flex flex-wrap items-center justify-between gap-2 text-sm">
                <p class="text-gray-500">
                    Mostrando {{ filas.from ?? 0 }} a {{ filas.to ?? 0 }} de {{ filas.total }}
                </p>
                <div class="flex flex-wrap gap-1">
                    <template v-for="enlace in filas.links" :key="enlace.label">
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
        </section>
    </PanelLayout>
</template>