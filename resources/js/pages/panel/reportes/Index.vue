<script setup lang="ts">
import PanelLayout from '@/components/panel/PanelLayout.vue';
import { bs, etiquetaMes, opcionesBarras, opcionesDona, usarColores } from '@/lib/reportes';
import { router } from '@inertiajs/vue3';
import { Banknote, CalendarCheck, Clock, Printer, Wrench } from 'lucide-vue-next';
import { Bar, Doughnut } from 'vue-chartjs';
import { computed, ref } from 'vue';

const props = defineProps<{
    usuario: { name: string; rol: string };
    filtros: { desde: string; hasta: string };
    resumen: { ingresos: number; prestamos: number; horas: number; costo_mantenimiento: number };
    usoPredios: { id: number; nombre: string; usos: number; horas: number }[];
    ingresosMensuales: { mes: string; total: number }[];
    prestamosPorTipo: { tipo: string; total: number }[];
    costosMantenimiento: { predio: string; registros: number; total: number }[];
}>();

const colores = usarColores();

const campo =
    'mt-1 w-full rounded-lg border border-borde px-3 py-2 text-sm focus:border-[var(--primario)] focus:outline-none';

const desde = ref(props.filtros.desde);
const hasta = ref(props.filtros.hasta);

function aplicarFiltro() {
    router.get(
        '/panel/reportes',
        { desde: desde.value, hasta: hasta.value },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

const urlImprimir = computed(
    () => `/panel/reportes/imprimir?desde=${props.filtros.desde}&hasta=${props.filtros.hasta}`,
);

const tarjetas = computed(() => [
    { etiqueta: 'Ingresos cobrados', valor: bs(props.resumen.ingresos), icono: Banknote },
    { etiqueta: 'Préstamos aprobados', valor: String(props.resumen.prestamos), icono: CalendarCheck },
    { etiqueta: 'Horas de uso', valor: `${props.resumen.horas.toFixed(1)} h`, icono: Clock },
    { etiqueta: 'Costo de mantenimiento', valor: bs(props.resumen.costo_mantenimiento), icono: Wrench },
]);

// Ingresos por mes
const datosIngresos = computed(() => ({
    labels: props.ingresosMensuales.map((i) => etiquetaMes(i.mes)),
    datasets: [
        {
            label: 'Ingresos',
            data: props.ingresosMensuales.map((i) => i.total),
            backgroundColor: colores.value.primario,
            borderRadius: 6,
            maxBarThickness: 56,
        },
    ],
}));
const opcionesIngresos = computed(() =>
    opcionesBarras(colores.value, { formato: bs, formatoEje: (v) => v.toLocaleString('es-BO') }),
);

// Predio más usado
const datosUso = computed(() => ({
    labels: props.usoPredios.map((p) => p.nombre),
    datasets: [
        {
            label: 'Usos',
            data: props.usoPredios.map((p) => p.usos),
            backgroundColor: colores.value.primario,
            borderRadius: 6,
            maxBarThickness: 32,
        },
    ],
}));
const opcionesUso = computed(() =>
    opcionesBarras(colores.value, { horizontal: true, formato: (v) => `${v} usos` }),
);

// Préstamos por tipo de evento
const datosTipo = computed(() => ({
    labels: props.prestamosPorTipo.map((p) => p.tipo),
    datasets: [
        {
            data: props.prestamosPorTipo.map((p) => p.total),
            backgroundColor: colores.value.paleta,
            borderWidth: 0,
        },
    ],
}));
const opcionesTipo = computed(() => opcionesDona(colores.value, (v) => `${v} préstamos`));

// Costos de mantenimiento
const datosMantenimiento = computed(() => ({
    labels: props.costosMantenimiento.map((c) => c.predio),
    datasets: [
        {
            label: 'Costo',
            data: props.costosMantenimiento.map((c) => c.total),
            backgroundColor: colores.value.secundario,
            borderRadius: 6,
            maxBarThickness: 32,
        },
    ],
}));
const opcionesMantenimiento = computed(() =>
    opcionesBarras(colores.value, { horizontal: true, formato: bs }),
);

function alturaBarras(cantidad: number): string {
    return `${Math.max(200, cantidad * 40)}px`;
}
</script>

<template>
    <PanelLayout titulo="Reportes y estadísticas" :usuario="usuario">
        <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <form
                class="grid flex-1 gap-4 rounded-xl p-5 ring-1 ring-borde sm:grid-cols-[1fr_1fr_auto] sm:items-end"
                @submit.prevent="aplicarFiltro"
            >
                <label class="block text-sm font-medium">
                    Desde
                    <input v-model="desde" type="date" :class="campo" />
                </label>
                <label class="block text-sm font-medium">
                    Hasta
                    <input v-model="hasta" type="date" :class="campo" />
                </label>
                <button
                    type="submit"
                    class="rounded-lg bg-[var(--primario)] px-5 py-2.5 text-sm font-semibold text-white"
                >
                    Aplicar
                </button>
            </form>

            <a
                :href="urlImprimir"
                target="_blank"
                rel="noopener"
                class="inline-flex items-center justify-center gap-2 rounded-lg px-5 py-2.5 text-sm font-semibold text-[var(--primario)] ring-1 ring-[var(--primario)] hover:bg-[var(--primario)] hover:text-white"
            >
                <Printer class="h-4 w-4" />
                Vista de impresión
            </a>
        </div>

        <!-- Indicadores -->
        <section class="mb-8 grid grid-cols-2 gap-4 xl:grid-cols-4">
            <div
                v-for="t in tarjetas"
                :key="t.etiqueta"
                class="rounded-xl bg-superficie p-5 text-texto ring-1 ring-borde"
            >
                <div class="flex items-center justify-between gap-2">
                    <p class="text-sm text-texto-suave">{{ t.etiqueta }}</p>
                    <component :is="t.icono" class="h-5 w-5 text-[var(--primario)]" />
                </div>
                <p class="mt-2 text-xl font-bold text-[var(--primario)]">{{ t.valor }}</p>
            </div>
        </section>

        <div class="grid gap-6 xl:grid-cols-2">
            <!-- Ingresos por mes -->
            <section class="rounded-xl bg-superficie p-5 text-texto ring-1 ring-borde">
                <h2 class="font-semibold text-[var(--primario)]">Ingresos por mes</h2>
                <p class="mb-4 text-sm text-texto-suave">Cuotas pagadas en el periodo</p>
                <div v-if="ingresosMensuales.length" class="h-72">
                    <Bar :data="datosIngresos" :options="opcionesIngresos" />
                </div>
                <p v-else class="text-sm text-texto-suave">No hay cuotas pagadas en este período.</p>
            </section>

            <!-- Predio más usado -->
            <section class="rounded-xl bg-superficie p-5 text-texto ring-1 ring-borde">
                <h2 class="font-semibold text-[var(--primario)]">Predio más usado</h2>
                <p class="mb-4 text-sm text-texto-suave">Cantidad de préstamos aprobados por predio</p>
                <div
                    v-if="usoPredios.length"
                    :style="{ height: alturaBarras(usoPredios.length) }"
                >
                    <Bar :data="datosUso" :options="opcionesUso" />
                </div>
                <p v-else class="text-sm text-texto-suave">No hay préstamos aprobados en este período.</p>
            </section>

            <!-- Préstamos por tipo -->
            <section class="rounded-xl bg-superficie p-5 text-texto ring-1 ring-borde">
                <h2 class="font-semibold text-[var(--primario)]">Préstamos por tipo de evento</h2>
                <p class="mb-4 text-sm text-texto-suave">Distribución de solicitudes aprobadas</p>
                <div v-if="prestamosPorTipo.length" class="h-72">
                    <Doughnut :data="datosTipo" :options="opcionesTipo" />
                </div>
                <p v-else class="text-sm text-texto-suave">No hay préstamos aprobados en este período.</p>
            </section>

            <!-- Costos de mantenimiento -->
            <section class="rounded-xl bg-superficie p-5 text-texto ring-1 ring-borde">
                <h2 class="font-semibold text-[var(--primario)]">Costos de mantenimiento</h2>
                <p class="mb-4 text-sm text-texto-suave">Gasto por predio en el periodo</p>
                <div
                    v-if="costosMantenimiento.length"
                    :style="{ height: alturaBarras(costosMantenimiento.length) }"
                >
                    <Bar :data="datosMantenimiento" :options="opcionesMantenimiento" />
                </div>
                <p v-else class="text-sm text-texto-suave">No hay mantenimientos registrados en este período.</p>
            </section>
        </div>
    </PanelLayout>
</template>