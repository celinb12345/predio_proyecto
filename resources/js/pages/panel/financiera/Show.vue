<script setup lang="ts">
import PanelLayout from '@/components/panel/PanelLayout.vue';
import { Link, useForm } from '@inertiajs/vue3';

const props = defineProps<{
    usuario: { name: string; rol: string };
    fila: {
        id: number;
        predio: string;
        fecha_texto: string;
        horario: string;
        activo: boolean;
        referencia: string;
        solicitante: string;
        evento: string;
        estado_reserva: string | null;
        tipo_prestamo: string | null;
        monto_total: string | null;
    };
    liberaciones: { motivo: string; registro: string; horario: string; usuario: string }[];
    reprogramaciones: { motivo: string; registro: string; horario: string; usuario: string }[];
}>();

const campo =
    'mt-1 w-full rounded-lg border border-borde px-3 py-2 text-sm focus:border-[var(--primario)] focus:outline-none';

const formLiberar = useForm({
    motivo: '',
});

function liberar() {
    if (!window.confirm('¿Liberar esta fecha? El horario quedará libre para otras solicitudes.')) {
        return;
    }

    formLiberar.post(`/panel/financiera/${props.fila.id}/liberar`);
}

const formReprogramar = useForm({
    motivo: '',
    fecha: '',
    hora_inicio: '',
    hora_fin: '',
});

function reprogramar() {
    formReprogramar.post(`/panel/financiera/${props.fila.id}/reprogramar`);
}
</script>

<template>
    <PanelLayout :titulo="`Fecha de ${fila.referencia}`" :usuario="usuario">
        <Link href="/panel/financiera" class="mb-6 inline-block text-sm font-semibold text-[var(--primario)] hover:underline">
            ← Volver a liberación de fechas
        </Link>

        <section class="mb-10 grid gap-3 rounded-xl bg-fondo p-5 text-sm sm:grid-cols-2 xl:grid-cols-3">
            <p><span class="font-semibold">Predio:</span> {{ fila.predio }}</p>
            <p><span class="font-semibold">Fecha:</span> {{ fila.fecha_texto }}</p>
            <p><span class="font-semibold">Horario:</span> {{ fila.horario }}</p>
            <p><span class="font-semibold">Solicitante:</span> {{ fila.solicitante }}</p>
            <p><span class="font-semibold">Evento:</span> {{ fila.evento }}</p>
            <p><span class="font-semibold">Reserva:</span> {{ fila.estado_reserva ?? 'Sin reserva' }}</p>
            <p v-if="fila.tipo_prestamo">
                <span class="font-semibold">Préstamo:</span>
                {{ fila.tipo_prestamo === 'gratuito' ? 'Gratuito' : 'Cobrado' }}
                <span v-if="fila.monto_total"> · Bs. {{ fila.monto_total }}</span>
            </p>
            <p>
                <span class="font-semibold">Situación:</span>
                <span v-if="fila.activo" class="font-semibold text-[var(--primario)]">Activa</span>
                <span v-else class="text-texto-suave">Liberada</span>
            </p>
        </section>

        <p v-if="!fila.activo" class="mb-10 max-w-3xl rounded-lg bg-fondo p-4 text-sm text-texto-suave">
            Esta fecha ya fue liberada. Revisa el historial abajo para ver qué pasó con la reserva.
        </p>

        <div v-else class="mb-10 grid gap-8 lg:grid-cols-2">
            <form class="grid gap-4 rounded-xl p-5 ring-1 ring-borde" @submit.prevent="liberar">
                <h2 class="text-lg font-semibold text-[var(--primario)]">Liberar fecha</h2>
                <p class="text-sm text-texto-suave">La reserva se cancela si no le quedan otras fechas activas.</p>

                <label class="block text-sm font-medium">
                    Motivo
                    <textarea v-model="formLiberar.motivo" rows="3" :class="campo"></textarea>
                    <span v-if="formLiberar.errors.motivo" class="text-sm text-red-600">{{ formLiberar.errors.motivo }}</span>
                </label>

                <div>
                    <button
                        type="submit"
                        :disabled="formLiberar.processing"
                        class="rounded-lg bg-red-600 px-5 py-2.5 text-sm font-semibold text-white disabled:bg-gray-400"
                    >
                        Liberar fecha
                    </button>
                </div>
            </form>

            <form class="grid gap-4 rounded-xl p-5 ring-1 ring-borde" @submit.prevent="reprogramar">
                <h2 class="text-lg font-semibold text-[var(--primario)]">Reprogramar a otra fecha</h2>
                <p class="text-sm text-texto-suave">La fecha actual se libera y la reserva pasa a la nueva fecha.</p>

                <label class="block text-sm font-medium">
                    Motivo
                    <textarea v-model="formReprogramar.motivo" rows="2" :class="campo"></textarea>
                    <span v-if="formReprogramar.errors.motivo" class="text-sm text-red-600">{{ formReprogramar.errors.motivo }}</span>
                </label>

                <div class="grid gap-4 sm:grid-cols-3">
                    <label class="block text-sm font-medium">
                        Nueva fecha
                        <input v-model="formReprogramar.fecha" type="date" :class="campo" />
                        <span v-if="formReprogramar.errors.fecha" class="text-sm text-red-600">{{ formReprogramar.errors.fecha }}</span>
                    </label>
                    <label class="block text-sm font-medium">
                        Hora inicio
                        <input v-model="formReprogramar.hora_inicio" type="time" :class="campo" />
                        <span v-if="formReprogramar.errors.hora_inicio" class="text-sm text-red-600">{{ formReprogramar.errors.hora_inicio }}</span>
                    </label>
                    <label class="block text-sm font-medium">
                        Hora fin
                        <input v-model="formReprogramar.hora_fin" type="time" :class="campo" />
                        <span v-if="formReprogramar.errors.hora_fin" class="text-sm text-red-600">{{ formReprogramar.errors.hora_fin }}</span>
                    </label>
                </div>

                <div>
                    <button
                        type="submit"
                        :disabled="formReprogramar.processing"
                        class="rounded-lg bg-[var(--primario)] px-5 py-2.5 text-sm font-semibold text-white disabled:bg-gray-400"
                    >
                        Reprogramar
                    </button>
                </div>
            </form>
        </div>

        <section class="mb-10">
            <h2 class="mb-4 text-lg font-semibold text-[var(--primario)]">Historial de liberaciones</h2>
            <div v-if="liberaciones.length" class="overflow-x-auto rounded-xl ring-1 ring-borde">
                <table class="min-w-full divide-y divide-borde text-sm">
                    <thead class="bg-[var(--primario)] text-left text-white">
                        <tr>
                            <th class="px-4 py-3">Motivo</th>
                            <th class="px-4 py-3">Horario liberado</th>
                            <th class="px-4 py-3">Registrado</th>
                            <th class="px-4 py-3">Por</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-borde bg-superficie">
                        <tr v-for="(l, i) in liberaciones" :key="i">
                            <td class="px-4 py-3">{{ l.motivo }}</td>
                            <td class="px-4 py-3">{{ l.horario }}</td>
                            <td class="px-4 py-3">{{ l.registro }}</td>
                            <td class="px-4 py-3">{{ l.usuario }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p v-else class="text-sm text-texto-suave">No hay liberaciones registradas para esta solicitud.</p>
        </section>

        <section class="mb-10">
            <h2 class="mb-4 text-lg font-semibold text-[var(--primario)]">Historial de reprogramaciones</h2>
            <div v-if="reprogramaciones.length" class="overflow-x-auto rounded-xl ring-1 ring-borde">
                <table class="min-w-full divide-y divide-borde text-sm">
                    <thead class="bg-[var(--primario)] text-left text-white">
                        <tr>
                            <th class="px-4 py-3">Motivo</th>
                            <th class="px-4 py-3">Horario anterior</th>
                            <th class="px-4 py-3">Registrado</th>
                            <th class="px-4 py-3">Por</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-borde bg-superficie">
                        <tr v-for="(r, i) in reprogramaciones" :key="i">
                            <td class="px-4 py-3">{{ r.motivo }}</td>
                            <td class="px-4 py-3">{{ r.horario }}</td>
                            <td class="px-4 py-3">{{ r.registro }}</td>
                            <td class="px-4 py-3">{{ r.usuario }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p v-else class="text-sm text-texto-suave">No hay reprogramaciones registradas para esta solicitud.</p>
        </section>
    </PanelLayout>
</template>