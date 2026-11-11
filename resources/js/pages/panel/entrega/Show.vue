<script setup lang="ts">
import PanelLayout from '@/components/panel/PanelLayout.vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import { watch } from 'vue';

const props = defineProps<{
    usuario: { name: string; rol: string };
    reserva: {
        id: number;
        referencia: string;
        solicitante: string;
        persona_id: number;
        estado: string;
    };
    equipamiento: { id: number; predio: string; equipo: string; cantidad: number; estado: string }[];
    acta: { fecha: string; fecha_devolucion: string | null; devuelta: boolean } | null;
    detalles: {
        id: number;
        predio: string;
        equipo: string;
        cantidad: number;
        estado_entrega: string;
        estado_devolucion: string | null;
    }[];
    incidentes: { descripcion: string; fecha: string; persona: string }[];
    limpieza: { estado: string; fecha: string; personal: string } | null;
    sanciones: { id: number; motivo: string; fecha: string; estado: string; persona: string }[];
    beneficios: { descripcion: string; fecha: string }[];
    personalActivo: { id: number; nombre: string; apellido: string }[];
    puedeCrearActa: boolean;
    puedeDevolver: boolean;
    puedeReportarIncidente: boolean;
    puedeAsignarLimpieza: boolean;
    puedeMarcarRealizada: boolean;
    puedeSancionar: boolean;
}>();

const campo =
    'mt-1 w-full rounded-lg border border-borde px-3 py-2 text-sm focus:border-[var(--primario)] focus:outline-none';

const etiquetasEstado: Record<string, string> = {
    bueno: 'Bueno',
    regular: 'Regular',
    malo: 'Malo',
    faltante: 'Faltante',
};

const etiquetasLimpieza: Record<string, string> = {
    asignada: 'Asignada',
    realizada: 'Realizada',
    cancelada: 'Cancelada',
};

const etiquetasSancion: Record<string, string> = {
    pendiente: 'Pendiente',
    cumplida: 'Cumplida',
    anulada: 'Anulada',
};

// Acta de entrega
const formActa = useForm({
    compromiso_buen_uso: false,
    firmada: false,
    detalles: props.equipamiento.map((e) => ({
        equipamiento_predio_id: e.id,
        cantidad: e.cantidad,
        estado_entrega: 'bueno',
    })),
});

function registrarActa() {
    formActa.post(`/panel/entrega/${props.reserva.id}/acta`, {
        preserveScroll: true,
    });
}

// Devolución
const formDevolucion = useForm({
    detalles: [] as { detalle_id: number; estado_devolucion: string }[],
});

// Se recalcula cada vez que cambian los equipos, por ejemplo al registrar el acta sin recargar la página
function prepararDevolucion(): void {
    formDevolucion.detalles = props.detalles.map((d) => ({
        detalle_id: d.id,
        estado_devolucion: 'bueno',
    }));
}

watch(() => props.detalles, prepararDevolucion, { immediate: true });
function registrarDevolucion() {
    if (!window.confirm('¿Registrar la devolución? La reserva quedará finalizada.')) {
        return;
    }

    formDevolucion.post(`/panel/entrega/${props.reserva.id}/devolucion`, {
        preserveScroll: true,
    });
}

// Incidentes
const formIncidente = useForm({
    descripcion: '',
    persona_id: props.reserva.persona_id,
});

function agregarIncidente() {
    formIncidente.post(`/panel/entrega/${props.reserva.id}/incidentes`, {
        preserveScroll: true,
        onSuccess: () => formIncidente.reset('descripcion'),
    });
}

// Limpieza
const formLimpieza = useForm({
    personal_limpieza_id: '' as number | string,
    fecha: new Date().toISOString().slice(0, 10),
});

function asignarLimpieza() {
    formLimpieza.post(`/panel/entrega/${props.reserva.id}/limpieza`, {
        preserveScroll: true,
    });
}

function limpiezaRealizada() {
    router.post(`/panel/entrega/${props.reserva.id}/limpieza/realizada`, {}, {
        preserveScroll: true,
    });
}

// Sanciones
const formSancion = useForm({
    motivo: '',
});

function registrarSancion() {
    formSancion.post(`/panel/entrega/${props.reserva.id}/sanciones`, {
        preserveScroll: true,
        onSuccess: () => formSancion.reset(),
    });
}

function cambiarSancion(id: number, estado: 'cumplida' | 'anulada') {
    const texto = estado === 'cumplida' ? 'marcar como cumplida' : 'anular';

    if (!window.confirm(`¿Seguro que deseas ${texto} esta sanción?`)) {
        return;
    }

    router.post(`/panel/entrega/sanciones/${id}/estado`, { estado }, {
        preserveScroll: true,
    });
}
</script>

<template>
    <PanelLayout :titulo="`Entrega de ${reserva.referencia}`" :usuario="usuario">
        <Link href="/panel/entrega" class="mb-6 inline-block text-sm font-semibold text-[var(--primario)] hover:underline">
            ← Volver a entrega y recepción
        </Link>

        <section class="mb-10 grid gap-3 rounded-xl bg-fondo p-5 text-sm sm:grid-cols-2 xl:grid-cols-3">
            <p><span class="font-semibold">Solicitante:</span> {{ reserva.solicitante }}</p>
            <p><span class="font-semibold">Reserva:</span> {{ reserva.estado }}</p>
            <p v-if="acta"><span class="font-semibold">Entrega:</span> {{ acta.fecha }}</p>
            <p v-if="acta">
                <span class="font-semibold">Devolución:</span> {{ acta.fecha_devolucion ?? 'Pendiente' }}
            </p>
        </section>

        <!-- Acta de entrega -->
        <section class="mb-10">
            <h2 class="mb-4 text-lg font-semibold text-[var(--primario)]">Acta de entrega</h2>

            <form
                v-if="puedeCrearActa"
                class="grid gap-5 rounded-xl p-5 ring-1 ring-borde"
                @submit.prevent="registrarActa"
            >
                <p class="text-sm text-texto-suave">
                    Revisa cada equipo del inventario de los predios y anota su estado al entregarlos.
                </p>

                <div v-if="formActa.detalles.length" class="overflow-x-auto rounded-lg ring-1 ring-borde">
                    <table class="min-w-full divide-y divide-borde text-sm">
                        <thead class="bg-[var(--primario)] text-left text-white">
                            <tr>
                                <th class="px-4 py-3">Predio</th>
                                <th class="px-4 py-3">Equipo</th>
                                <th class="px-4 py-3">Cantidad</th>
                                <th class="px-4 py-3">Estado al entregar</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-borde bg-superficie">
                            <tr v-for="(fila, i) in formActa.detalles" :key="i">
                                <td class="px-4 py-3">{{ equipamiento[i]?.predio }}</td>
                                <td class="px-4 py-3">{{ equipamiento[i]?.equipo }}</td>
                                <td class="px-4 py-3">
                                    <input v-model="fila.cantidad" type="number" min="1" :class="campo" />
                                </td>
                                <td class="px-4 py-3">
                                    <select v-model="fila.estado_entrega" :class="campo">
                                        <option value="bueno">Bueno</option>
                                        <option value="regular">Regular</option>
                                        <option value="malo">Malo</option>
                                    </select>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p v-else class="text-sm text-texto-suave">Estos predios no tienen equipos registrados en su inventario.</p>

                <span v-if="formActa.errors.detalles" class="text-sm text-red-600">{{ formActa.errors.detalles }}</span>

                <label class="flex items-start gap-2 text-sm">
                    <input v-model="formActa.compromiso_buen_uso" type="checkbox" class="mt-1" />
                    <span>El solicitante acepta el compromiso de buen uso del predio.</span>
                </label>
                <span v-if="formActa.errors.compromiso_buen_uso" class="text-sm text-red-600">
                    {{ formActa.errors.compromiso_buen_uso }}
                </span>

                <label class="flex items-start gap-2 text-sm">
                    <input v-model="formActa.firmada" type="checkbox" class="mt-1" />
                    <span>El acta fue firmada por el solicitante.</span>
                </label>
                <span v-if="formActa.errors.firmada" class="text-sm text-red-600">{{ formActa.errors.firmada }}</span>

                <div>
                    <button
                        type="submit"
                        :disabled="formActa.processing"
                        class="rounded-lg bg-[var(--primario)] px-5 py-2.5 text-sm font-semibold text-white disabled:bg-gray-400"
                    >
                        Registrar entrega
                    </button>
                </div>
            </form>

            <div v-else-if="detalles.length" class="overflow-x-auto rounded-xl ring-1 ring-borde">
                <table class="min-w-full divide-y divide-borde text-sm">
                    <thead class="bg-[var(--primario)] text-left text-white">
                        <tr>
                            <th class="px-4 py-3">Predio</th>
                            <th class="px-4 py-3">Equipo</th>
                            <th class="px-4 py-3">Cantidad</th>
                            <th class="px-4 py-3">Al entregar</th>
                            <th class="px-4 py-3">Al devolver</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-borde bg-superficie">
                        <tr v-for="d in detalles" :key="d.id">
                            <td class="px-4 py-3">{{ d.predio }}</td>
                            <td class="px-4 py-3">{{ d.equipo }}</td>
                            <td class="px-4 py-3">{{ d.cantidad }}</td>
                            <td class="px-4 py-3">{{ etiquetasEstado[d.estado_entrega] ?? d.estado_entrega }}</td>
                            <td class="px-4 py-3">
                                {{ d.estado_devolucion ? (etiquetasEstado[d.estado_devolucion] ?? d.estado_devolucion) : '—' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <p v-else-if="!acta" class="text-sm text-texto-suave">
                Esta reserva no está activa, por eso no se puede registrar la entrega.
            </p>
        </section>

        <!-- Devolución -->
        <section v-if="puedeDevolver" class="mb-10">
            <h2 class="mb-4 text-lg font-semibold text-[var(--primario)]">Devolución del predio</h2>

            <form class="grid gap-5 rounded-xl p-5 ring-1 ring-borde" @submit.prevent="registrarDevolucion">
                <p class="text-sm text-texto-suave">
                    Indica el estado de cada equipo al devolverlo. Si todo vuelve bueno y no hay incidentes, el solicitante recibe un beneficio.
                </p>

                <div class="overflow-x-auto rounded-lg ring-1 ring-borde">
                    <table class="min-w-full divide-y divide-borde text-sm">
                        <thead class="bg-[var(--primario)] text-left text-white">
                            <tr>
                                <th class="px-4 py-3">Predio</th>
                                <th class="px-4 py-3">Equipo</th>
                                <th class="px-4 py-3">Cantidad</th>
                                <th class="px-4 py-3">Estado al devolver</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-borde bg-superficie">
                            <tr v-for="(d, i) in detalles" :key="d.id">
                                <td class="px-4 py-3">{{ d.predio }}</td>
                                <td class="px-4 py-3">{{ d.equipo }}</td>
                                <td class="px-4 py-3">{{ d.cantidad }}</td>
                                <td class="px-4 py-3">
                                    <select v-model="formDevolucion.detalles[i].estado_devolucion" :class="campo">
                                        <option value="bueno">Bueno</option>
                                        <option value="regular">Regular</option>
                                        <option value="malo">Malo</option>
                                        <option value="faltante">Faltante</option>
                                    </select>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <span v-if="formDevolucion.errors.detalles" class="text-sm text-red-600">{{ formDevolucion.errors.detalles }}</span>

                <div>
                    <button
                        type="submit"
                        :disabled="formDevolucion.processing"
                        class="rounded-lg bg-[var(--primario)] px-5 py-2.5 text-sm font-semibold text-white disabled:bg-gray-400"
                    >
                        Registrar devolución
                    </button>
                </div>
            </form>
        </section>

        <!-- Incidentes -->
        <section v-if="acta" class="mb-10">
            <h2 class="mb-4 text-lg font-semibold text-[var(--primario)]">Incidentes</h2>

            <form
                v-if="puedeReportarIncidente"
                class="mb-6 grid max-w-3xl gap-4 rounded-xl p-5 ring-1 ring-borde"
                @submit.prevent="agregarIncidente"
            >
                <label class="block text-sm font-medium">
                    Descripción del incidente
                    <textarea v-model="formIncidente.descripcion" rows="3" :class="campo"></textarea>
                    <span v-if="formIncidente.errors.descripcion" class="text-sm text-red-600">
                        {{ formIncidente.errors.descripcion }}
                    </span>
                </label>

                <div>
                    <button
                        type="submit"
                        :disabled="formIncidente.processing"
                        class="rounded-lg bg-[var(--primario)] px-5 py-2.5 text-sm font-semibold text-white disabled:bg-gray-400"
                    >
                        Registrar incidente
                    </button>
                </div>
            </form>

            <ul v-if="incidentes.length" class="space-y-2">
                <li v-for="(inc, i) in incidentes" :key="i" class="rounded-lg px-4 py-3 text-sm ring-1 ring-borde">
                    <p class="font-medium">{{ inc.descripcion }}</p>
                    <p class="text-texto-suave">Responsable: {{ inc.persona }} · {{ inc.fecha }}</p>
                </li>
            </ul>
            <p v-else class="text-sm text-texto-suave">No hay incidentes registrados.</p>
        </section>

        <!-- Limpieza -->
        <section class="mb-10">
            <h2 class="mb-4 text-lg font-semibold text-[var(--primario)]">Limpieza del predio</h2>

            <div
                v-if="limpieza"
                class="flex flex-wrap items-center justify-between gap-3 rounded-xl p-5 text-sm ring-1 ring-borde"
            >
                <p>
                    <span class="font-semibold">Personal:</span> {{ limpieza.personal }}
                    · <span class="font-semibold">Fecha:</span> {{ limpieza.fecha }}
                    · <span class="font-semibold">Estado:</span> {{ etiquetasLimpieza[limpieza.estado] ?? limpieza.estado }}
                </p>
                <button
                    v-if="puedeMarcarRealizada"
                    type="button"
                    class="rounded-lg bg-[var(--primario)] px-4 py-2 text-sm font-semibold text-white"
                    @click="limpiezaRealizada"
                >
                    Marcar como realizada
                </button>
            </div>

            <form
                v-else-if="puedeAsignarLimpieza"
                class="grid max-w-3xl gap-4 rounded-xl p-5 ring-1 ring-borde sm:grid-cols-3"
                @submit.prevent="asignarLimpieza"
            >
                <label class="block text-sm font-medium sm:col-span-2">
                    Personal de limpieza
                    <select v-model="formLimpieza.personal_limpieza_id" :class="campo">
                        <option value="" disabled>Selecciona a una persona</option>
                        <option v-for="p in personalActivo" :key="p.id" :value="p.id">{{ p.apellido }}, {{ p.nombre }}</option>
                    </select>
                    <span v-if="formLimpieza.errors.personal_limpieza_id" class="text-sm text-red-600">
                        {{ formLimpieza.errors.personal_limpieza_id }}
                    </span>
                </label>

                <label class="block text-sm font-medium">
                    Fecha
                    <input v-model="formLimpieza.fecha" type="date" :class="campo" />
                    <span v-if="formLimpieza.errors.fecha" class="text-sm text-red-600">{{ formLimpieza.errors.fecha }}</span>
                </label>

                <div class="sm:col-span-3">
                    <button
                        type="submit"
                        :disabled="formLimpieza.processing"
                        class="rounded-lg bg-[var(--primario)] px-5 py-2.5 text-sm font-semibold text-white disabled:bg-gray-400"
                    >
                        Asignar limpieza
                    </button>
                </div>
            </form>

            <p v-else class="text-sm text-texto-suave">No hay limpieza asignada.</p>
        </section>

        <!-- Sanciones -->
        <section class="mb-10">
            <h2 class="mb-4 text-lg font-semibold text-[var(--primario)]">Sanciones</h2>

            <form
                v-if="puedeSancionar"
                class="mb-6 grid max-w-3xl gap-4 rounded-xl p-5 ring-1 ring-borde"
                @submit.prevent="registrarSancion"
            >
                <label class="block text-sm font-medium">
                    Motivo de la sanción
                    <textarea v-model="formSancion.motivo" rows="2" :class="campo"></textarea>
                    <span v-if="formSancion.errors.motivo" class="text-sm text-red-600">{{ formSancion.errors.motivo }}</span>
                </label>

                <div>
                    <button
                        type="submit"
                        :disabled="formSancion.processing"
                        class="rounded-lg bg-[var(--primario)] px-5 py-2.5 text-sm font-semibold text-white disabled:bg-gray-400"
                    >
                        Registrar sanción
                    </button>
                </div>
            </form>

            <div v-if="sanciones.length" class="overflow-x-auto rounded-xl ring-1 ring-borde">
                <table class="min-w-full divide-y divide-borde text-sm">
                    <thead class="bg-[var(--primario)] text-left text-white">
                        <tr>
                            <th class="px-4 py-3">Motivo</th>
                            <th class="px-4 py-3">Responsable</th>
                            <th class="px-4 py-3">Fecha</th>
                            <th class="px-4 py-3">Estado</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-borde bg-superficie">
                        <tr v-for="s in sanciones" :key="s.id">
                            <td class="px-4 py-3">{{ s.motivo }}</td>
                            <td class="px-4 py-3">{{ s.persona }}</td>
                            <td class="px-4 py-3">{{ s.fecha }}</td>
                            <td class="px-4 py-3">{{ etiquetasSancion[s.estado] ?? s.estado }}</td>
                            <td class="px-4 py-3 text-right">
                                <template v-if="s.estado === 'pendiente'">
                                    <button
                                        type="button"
                                        class="mr-3 font-semibold text-[var(--primario)] hover:underline"
                                        @click="cambiarSancion(s.id, 'cumplida')"
                                    >
                                        Marcar cumplida
                                    </button>
                                    <button
                                        type="button"
                                        class="font-semibold text-red-600 hover:underline"
                                        @click="cambiarSancion(s.id, 'anulada')"
                                    >
                                        Anular
                                    </button>
                                </template>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p v-else class="text-sm text-texto-suave">No hay sanciones registradas para esta reserva.</p>
        </section>

        <!-- Beneficios del solicitante -->
        <section class="mb-10">
            <h2 class="mb-4 text-lg font-semibold text-[var(--primario)]">Beneficios del solicitante</h2>

            <ul v-if="beneficios.length" class="space-y-2">
                <li v-for="(b, i) in beneficios" :key="i" class="rounded-lg px-4 py-3 text-sm ring-1 ring-borde">
                    <span class="font-medium">{{ b.descripcion }}</span>
                    <span class="text-texto-suave"> · {{ b.fecha }}</span>
                </li>
            </ul>
            <p v-else class="text-sm text-texto-suave">Este solicitante todavía no tiene beneficios.</p>
        </section>
    </PanelLayout>
</template>