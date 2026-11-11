<script setup lang="ts">
import PanelLayout from '@/components/panel/PanelLayout.vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    usuario: { name: string; rol: string };
    solicitud: {
        id: number;
        referencia: string;
        estado: string;
        proveido: string | null;
        evento: string;
        solicitante: string;
        fecha: string;
    };
    predios: { nombre: string; fecha: string; hora_inicio: string; hora_fin: string; activo: boolean }[];
    respaldos: { id: number; tipo: string; url: string }[];
    puedeSubirRespaldo: boolean;
    reserva: { estado: string; tipo_prestamo: string; monto_total: string } | null;
}>();

const campo =
    'mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-[var(--primario)] focus:outline-none';

const etiquetasEstado: Record<string, string> = {
    pendiente: 'Pendiente',
    aprobada: 'Aprobada',
    rechazada: 'Rechazada',
    cancelada: 'Cancelada',
};

const etiquetasTipo: Record<string, string> = {
    carta: 'Carta al Decano',
    proveido: 'Proveído',
    titulo: 'Título',
    acta: 'Acta',
    otro: 'Otro',
};

const etiquetasTipoPrestamo: Record<string, string> = {
    gratuito: 'Gratuito',
    cobrado: 'Cobrado',
};

// Proveído: solo el Decano, mientras la solicitud esté pendiente
const puedeDarProveido = computed(
    () => props.usuario.rol === 'Decano' && props.solicitud.estado === 'pendiente',
);

const formProveido = useForm({
    proveido: props.solicitud.proveido ?? '',
});

function guardarProveido() {
    formProveido.post(`/panel/solicitantes/${props.solicitud.id}/proveido`, {
        preserveScroll: true,
    });
}

// Decisión: solo Secretaría General, mientras la solicitud esté pendiente
const puedeDecidir = computed(
    () => props.usuario.rol === 'Secretaría General' && props.solicitud.estado === 'pendiente',
);

const puedeAprobar = computed(() => puedeDecidir.value && !!props.solicitud.proveido);

const formAprobacion = useForm({
    tipo_prestamo: 'gratuito',
});

function aprobar() {
    formAprobacion.post(`/panel/solicitantes/${props.solicitud.id}/aprobar`, {
        preserveScroll: true,
    });
}

function rechazar() {
    if (!window.confirm('¿Seguro que deseas rechazar esta solicitud? Sus fechas quedarán libres.')) {
        return;
    }

    router.post(`/panel/solicitantes/${props.solicitud.id}/rechazar`, {}, {
        preserveScroll: true,
    });
}

// Respaldos
const formRespaldo = useForm({
    tipo: 'carta',
    archivo: null as File | null,
});

function alElegirArchivo(evento: Event) {
    formRespaldo.archivo = (evento.target as HTMLInputElement).files?.[0] ?? null;
}

function subirRespaldo() {
    formRespaldo.post(`/panel/solicitantes/${props.solicitud.id}/respaldos`, {
        forceFormData: true,
        onSuccess: () => formRespaldo.reset(),
    });
}
</script>

<template>
    <PanelLayout :titulo="`Solicitud ${solicitud.referencia}`" :usuario="usuario">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <Link href="/panel/solicitantes" class="text-sm font-semibold text-[var(--primario)] hover:underline">
                ← Volver a solicitudes
            </Link>
            <span class="rounded-full bg-[var(--secundario)] px-3 py-1 text-sm font-semibold text-[var(--primario)]">
                {{ etiquetasEstado[solicitud.estado] ?? solicitud.estado }}
            </span>
        </div>

        <section class="mb-10 grid gap-3 rounded-xl bg-gray-50 p-5 text-sm sm:grid-cols-2 xl:grid-cols-3">
            <p><span class="font-semibold">Solicitante:</span> {{ solicitud.solicitante }}</p>
            <p><span class="font-semibold">Evento:</span> {{ solicitud.evento }}</p>
            <p><span class="font-semibold">Registrada:</span> {{ solicitud.fecha }}</p>
        </section>

        <section class="mb-10">
            <h2 class="mb-4 text-lg font-semibold text-[var(--primario)]">Proveído del Decano</h2>

            <form
                v-if="puedeDarProveido"
                class="grid max-w-3xl gap-4 rounded-xl p-4 ring-1 ring-gray-200"
                @submit.prevent="guardarProveido"
            >
                <label class="block text-sm font-medium">
                    Texto del proveído (mínimo 10 caracteres)
                    <textarea v-model="formProveido.proveido" rows="4" :class="campo"></textarea>
                    <span v-if="formProveido.errors.proveido" class="text-sm text-red-600">
                        {{ formProveido.errors.proveido }}
                    </span>
                </label>

                <div>
                    <button
                        type="submit"
                        :disabled="formProveido.processing"
                        class="rounded-lg bg-[var(--primario)] px-5 py-2.5 text-sm font-semibold text-white disabled:bg-gray-400"
                    >
                        Guardar proveído
                    </button>
                </div>
            </form>

            <p v-else-if="solicitud.proveido" class="max-w-3xl whitespace-pre-line rounded-xl bg-gray-50 p-4 text-sm">
                {{ solicitud.proveido }}
            </p>
            <p v-else class="text-sm text-gray-500">Esta solicitud todavía no tiene proveído.</p>
        </section>

        <section class="mb-10">
            <h2 class="mb-4 text-lg font-semibold text-[var(--primario)]">Decisión de Secretaría General</h2>

            <div v-if="puedeDecidir" class="grid max-w-3xl gap-4 rounded-xl p-4 ring-1 ring-gray-200">
                <p v-if="!puedeAprobar" class="text-sm text-gray-500">
                    Para aprobar hace falta que el Decano escriba primero el proveído.
                </p>

                <label class="block text-sm font-medium">
                    Tipo de préstamo
                    <select v-model="formAprobacion.tipo_prestamo" :class="campo">
                        <option value="gratuito">Gratuito (sin costo)</option>
                        <option value="cobrado">Cobrado (según la tarifa vigente de cada predio)</option>
                    </select>
                    <span v-if="formAprobacion.errors.tipo_prestamo" class="text-sm text-red-600">
                        {{ formAprobacion.errors.tipo_prestamo }}
                    </span>
                </label>

                <div class="flex flex-wrap gap-3">
                    <button
                        type="button"
                        :disabled="!puedeAprobar || formAprobacion.processing"
                        class="rounded-lg bg-[var(--primario)] px-5 py-2.5 text-sm font-semibold text-white disabled:bg-gray-400"
                        @click="aprobar"
                    >
                        Aprobar y crear reserva
                    </button>
                    <button
                        type="button"
                        class="rounded-lg px-5 py-2.5 text-sm font-semibold text-red-600 ring-1 ring-red-600 hover:bg-red-50"
                        @click="rechazar"
                    >
                        Rechazar solicitud
                    </button>
                </div>
            </div>

            <div v-else-if="reserva" class="grid max-w-3xl gap-2 rounded-xl bg-gray-50 p-5 text-sm sm:grid-cols-3">
                <p><span class="font-semibold">Reserva:</span> {{ reserva.estado }}</p>
                <p>
                    <span class="font-semibold">Tipo:</span>
                    {{ etiquetasTipoPrestamo[reserva.tipo_prestamo] ?? reserva.tipo_prestamo }}
                </p>
                <p><span class="font-semibold">Monto:</span> Bs. {{ reserva.monto_total }}</p>
            </div>

            <p v-else class="text-sm text-gray-500">
                {{ solicitud.estado === 'rechazada' ? 'Esta solicitud fue rechazada y sus fechas quedaron libres.' : 'La decisión la toma Secretaría General.' }}
            </p>
        </section>

        <section class="mb-10">
            <h2 class="mb-4 text-lg font-semibold text-[var(--primario)]">Predios solicitados</h2>
            <div class="overflow-x-auto rounded-xl ring-1 ring-gray-200">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-[var(--primario)] text-left text-white">
                        <tr>
                            <th class="px-4 py-3">Predio</th>
                            <th class="px-4 py-3">Fecha</th>
                            <th class="px-4 py-3">Horario</th>
                            <th class="px-4 py-3">Situación</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        <tr v-for="(p, i) in predios" :key="i">
                            <td class="px-4 py-3 font-medium">{{ p.nombre }}</td>
                            <td class="px-4 py-3">{{ p.fecha }}</td>
                            <td class="px-4 py-3">{{ p.hora_inicio }} – {{ p.hora_fin }}</td>
                            <td class="px-4 py-3">
                                <span v-if="p.activo" class="font-semibold text-[var(--primario)]">Activa</span>
                                <span v-else class="text-gray-500">Liberada</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="mb-10">
            <h2 class="mb-4 text-lg font-semibold text-[var(--primario)]">Respaldos</h2>

            <ul v-if="respaldos.length" class="mb-6 space-y-2">
                <li
                    v-for="r in respaldos"
                    :key="r.id"
                    class="flex items-center justify-between rounded-lg px-4 py-3 text-sm ring-1 ring-gray-200"
                >
                    <span>{{ etiquetasTipo[r.tipo] ?? r.tipo }}</span>
                    <a :href="r.url" target="_blank" class="font-semibold text-[var(--primario)] hover:underline">Ver archivo</a>
                </li>
            </ul>
            <p v-else class="mb-6 text-sm text-gray-500">Esta solicitud todavía no tiene respaldos.</p>

            <form
                v-if="puedeSubirRespaldo"
                class="grid max-w-3xl gap-4 rounded-xl p-4 ring-1 ring-gray-200 sm:grid-cols-3"
                @submit.prevent="subirRespaldo"
            >
                <label class="block text-sm font-medium">
                    Tipo
                    <select v-model="formRespaldo.tipo" :class="campo">
                        <option value="carta">Carta al Decano</option>
                        <option value="proveido">Proveído</option>
                        <option value="titulo">Título</option>
                        <option value="acta">Acta</option>
                        <option value="otro">Otro</option>
                    </select>
                    <span v-if="formRespaldo.errors.tipo" class="text-sm text-red-600">{{ formRespaldo.errors.tipo }}</span>
                </label>

                <label class="block text-sm font-medium sm:col-span-2">
                    Archivo (PDF, JPG o PNG, máximo 5 MB)
                    <input type="file" accept=".pdf,.jpg,.jpeg,.png" class="mt-1 block w-full text-sm" @change="alElegirArchivo" />
                    <span v-if="formRespaldo.errors.archivo" class="text-sm text-red-600">{{ formRespaldo.errors.archivo }}</span>
                </label>

                <div class="sm:col-span-3">
                    <button
                        type="submit"
                        :disabled="formRespaldo.processing || !formRespaldo.archivo"
                        class="rounded-lg bg-[var(--primario)] px-5 py-2.5 text-sm font-semibold text-white disabled:bg-gray-400"
                    >
                        Subir respaldo
                    </button>
                </div>
            </form>
        </section>
    </PanelLayout>
</template>