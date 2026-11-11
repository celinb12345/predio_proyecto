<script setup lang="ts">
import {
    bs,
    coloresTema,
    etiquetaMes,
    fechaCorta,
    opcionesBarras,
    opcionesDona,
    type Colores,
} from '@/lib/reportes';
import { onBeforeUnmount, onMounted, ref } from 'vue';
import { Bar, Doughnut } from 'vue-chartjs';

const props = defineProps<{
    emitido: string;
    emitidoPor: string;
    filtros: { desde: string; hasta: string };
    resumen: { ingresos: number; prestamos: number; horas: number; costo_mantenimiento: number };
    usoPredios: { id: number; nombre: string; usos: number; horas: number }[];
    ingresosMensuales: { mes: string; total: number }[];
    prestamosPorTipo: { tipo: string; total: number }[];
    costosMantenimiento: { predio: string; registros: number; total: number }[];
}>();

// Colores para papel: fondo blanco y texto oscuro, sin importar el tema
const colores: Colores = { ...coloresTema(), texto: '#111827', rejilla: '#d1d5db' };

const volver = `/panel/reportes?desde=${props.filtros.desde}&hasta=${props.filtros.hasta}`;

const datosIngresos = {
    labels: props.ingresosMensuales.map((i) => etiquetaMes(i.mes)),
    datasets: [
        {
            label: 'Ingresos',
            data: props.ingresosMensuales.map((i) => i.total),
            backgroundColor: colores.primario,
            borderRadius: 6,
            maxBarThickness: 48,
        },
    ],
};
const opcionesIngresos = opcionesBarras(colores, {
    formato: bs,
    formatoEje: (v) => v.toLocaleString('es-BO'),
});

const datosUso = {
    labels: props.usoPredios.map((p) => p.nombre),
    datasets: [
        {
            label: 'Usos',
            data: props.usoPredios.map((p) => p.usos),
            backgroundColor: colores.primario,
            borderRadius: 6,
            maxBarThickness: 28,
        },
    ],
};
const opcionesUso = opcionesBarras(colores, { horizontal: true, formato: (v) => `${v} usos` });

const datosTipo = {
    labels: props.prestamosPorTipo.map((p) => p.tipo),
    datasets: [
        {
            data: props.prestamosPorTipo.map((p) => p.total),
            backgroundColor: colores.paleta,
            borderWidth: 0,
        },
    ],
};
const opcionesTipo = opcionesDona(colores, (v) => `${v} préstamos`);

const datosMantenimiento = {
    labels: props.costosMantenimiento.map((c) => c.predio),
    datasets: [
        {
            label: 'Costo',
            data: props.costosMantenimiento.map((c) => c.total),
            backgroundColor: colores.secundario,
            borderRadius: 6,
            maxBarThickness: 28,
        },
    ],
};
const opcionesMantenimiento = opcionesBarras(colores, { horizontal: true, formato: bs });

function alturaBarras(cantidad: number): string {
    return `${Math.max(150, cantidad * 28)}px`;
}

// Alto útil de una hoja A4 con márgenes de 12 mm, en píxeles (96 ppp), con un margen de seguridad
const ALTO_UTIL_PX = 980;

const envoltorioRef = ref<HTMLElement | null>(null);
const hojaRef = ref<HTMLElement | null>(null);

// Antes de imprimir (botón o Ctrl+P): reduce la hoja para que quepa en una sola página
function ajustarAUnaHoja() {
    const hoja = hojaRef.value;
    const envoltorio = envoltorioRef.value;
    if (!hoja || !envoltorio) return;

    hoja.style.transform = '';
    envoltorio.style.height = '';

    const alto = hoja.scrollHeight;
    const escala = Math.min(1, ALTO_UTIL_PX / alto);

    hoja.style.transformOrigin = 'top left';
    hoja.style.transform = `scale(${escala})`;
    envoltorio.style.height = `${alto * escala}px`;
    envoltorio.style.overflow = 'hidden';
}

// Después de imprimir: vuelve al tamaño normal de pantalla
function restaurarHoja() {
    const hoja = hojaRef.value;
    const envoltorio = envoltorioRef.value;
    if (hoja) {
        hoja.style.transform = '';
        hoja.style.transformOrigin = '';
    }
    if (envoltorio) {
        envoltorio.style.height = '';
        envoltorio.style.overflow = '';
    }
}

onMounted(() => {
    window.addEventListener('beforeprint', ajustarAUnaHoja);
    window.addEventListener('afterprint', restaurarHoja);
});

onBeforeUnmount(() => {
    window.removeEventListener('beforeprint', ajustarAUnaHoja);
    window.removeEventListener('afterprint', restaurarHoja);
});

function imprimir() {
    window.print();
}
</script>

<template>
    <div ref="envoltorioRef" class="min-h-screen bg-white text-gray-900">
        <div ref="hojaRef" class="mx-auto w-[186mm] max-w-full py-6">
            <!-- Barra de acciones: no se imprime -->
            <div class="mb-6 flex items-center justify-between gap-3 print:hidden">
                <a :href="volver" class="text-sm font-medium text-gray-600 hover:underline">← Volver al reporte</a>
                <button
                    type="button"
                    class="rounded-lg px-4 py-2 text-sm font-semibold text-white"
                    :style="{ backgroundColor: colores.primario }"
                    @click="imprimir"
                >
                    Imprimir / Guardar PDF
                </button>
            </div>

            <!-- Encabezado institucional -->
            <header class="flex items-center gap-4 border-b-2 pb-4" :style="{ borderColor: colores.primario }">
                <img src="/img/fich.png" alt="Logo de la FICH UAGRM" class="h-16 w-auto" />
                <div class="min-w-0 flex-1">
                    <p class="text-[10px] font-semibold uppercase tracking-wider text-gray-500">
                        Universidad Autónoma Gabriel René Moreno
                    </p>
                    <h1 class="text-lg font-bold" :style="{ color: colores.primario }">
                        FICH · Reporte de uso y estadísticas
                    </h1>
                    <p class="text-xs text-gray-600">Sistema de gestión de predios</p>
                </div>
                <div class="text-right text-xs text-gray-600">
                    <p><span class="font-semibold text-gray-900">Periodo:</span> {{ fechaCorta(filtros.desde) }} – {{ fechaCorta(filtros.hasta) }}</p>
                    <p><span class="font-semibold text-gray-900">Emitido:</span> {{ emitido }}</p>
                </div>
            </header>

            <!-- Indicadores -->
            <section class="mt-4 grid grid-cols-4 gap-2">
                <div class="rounded-lg border border-gray-300 p-3">
                    <p class="text-[11px] text-gray-500">Ingresos cobrados</p>
                    <p class="mt-1 text-sm font-bold" :style="{ color: colores.primario }">{{ bs(resumen.ingresos) }}</p>
                </div>
                <div class="rounded-lg border border-gray-300 p-3">
                    <p class="text-[11px] text-gray-500">Préstamos aprobados</p>
                    <p class="mt-1 text-sm font-bold" :style="{ color: colores.primario }">{{ resumen.prestamos }}</p>
                </div>
                <div class="rounded-lg border border-gray-300 p-3">
                    <p class="text-[11px] text-gray-500">Horas de uso</p>
                    <p class="mt-1 text-sm font-bold" :style="{ color: colores.primario }">{{ resumen.horas.toFixed(1) }} h</p>
                </div>
                <div class="rounded-lg border border-gray-300 p-3">
                    <p class="text-[11px] text-gray-500">Costo de mantenimiento</p>
                    <p class="mt-1 text-sm font-bold" :style="{ color: colores.primario }">{{ bs(resumen.costo_mantenimiento) }}</p>
                </div>
            </section>

            <!-- Gráficas -->
            <section class="mt-5 grid grid-cols-2 gap-4">
                <div class="rounded-lg border border-gray-300 p-3">
                    <h2 class="text-sm font-semibold" :style="{ color: colores.primario }">Ingresos por mes</h2>
                    <div v-if="ingresosMensuales.length" class="mt-2 h-48">
                        <Bar :data="datosIngresos" :options="opcionesIngresos" />
                    </div>
                    <p v-else class="mt-2 text-xs text-gray-500">Sin cuotas pagadas en el periodo.</p>
                </div>

                <div class="rounded-lg border border-gray-300 p-3">
                    <h2 class="text-sm font-semibold" :style="{ color: colores.primario }">Predio más usado</h2>
                    <div v-if="usoPredios.length" class="mt-2" :style="{ height: alturaBarras(usoPredios.length) }">
                        <Bar :data="datosUso" :options="opcionesUso" />
                    </div>
                    <p v-else class="mt-2 text-xs text-gray-500">Sin préstamos aprobados en el periodo.</p>
                </div>

                <div class="rounded-lg border border-gray-300 p-3">
                    <h2 class="text-sm font-semibold" :style="{ color: colores.primario }">Préstamos por tipo de evento</h2>
                    <div v-if="prestamosPorTipo.length" class="mt-2 h-48">
                        <Doughnut :data="datosTipo" :options="opcionesTipo" />
                    </div>
                    <p v-else class="mt-2 text-xs text-gray-500">Sin préstamos aprobados en el periodo.</p>
                </div>

                <div class="rounded-lg border border-gray-300 p-3">
                    <h2 class="text-sm font-semibold" :style="{ color: colores.primario }">Costos de mantenimiento</h2>
                    <div v-if="costosMantenimiento.length" class="mt-2" :style="{ height: alturaBarras(costosMantenimiento.length) }">
                        <Bar :data="datosMantenimiento" :options="opcionesMantenimiento" />
                    </div>
                    <p v-else class="mt-2 text-xs text-gray-500">Sin mantenimientos en el periodo.</p>
                </div>
            </section>

            <!-- Detalle en tablas -->
            <section class="mt-5 grid grid-cols-2 gap-4 text-xs">
                <div>
                    <h2 class="mb-1 text-sm font-semibold" :style="{ color: colores.primario }">Detalle por predio</h2>
                    <table class="w-full border-collapse">
                        <thead>
                            <tr class="border-b border-gray-400 text-left">
                                <th class="py-1">Predio</th>
                                <th class="py-1 text-right">Usos</th>
                                <th class="py-1 text-right">Horas</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="p in usoPredios" :key="p.id" class="border-b border-gray-200">
                                <td class="py-1">{{ p.nombre }}</td>
                                <td class="py-1 text-right">{{ p.usos }}</td>
                                <td class="py-1 text-right">{{ p.horas.toFixed(1) }}</td>
                            </tr>
                            <tr v-if="!usoPredios.length">
                                <td colspan="3" class="py-1 text-gray-500">Sin registros.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div>
                    <h2 class="mb-1 text-sm font-semibold" :style="{ color: colores.primario }">Ingresos por mes</h2>
                    <table class="w-full border-collapse">
                        <thead>
                            <tr class="border-b border-gray-400 text-left">
                                <th class="py-1">Mes</th>
                                <th class="py-1 text-right">Total cobrado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="i in ingresosMensuales" :key="i.mes" class="border-b border-gray-200">
                                <td class="py-1">{{ etiquetaMes(i.mes) }}</td>
                                <td class="py-1 text-right">{{ bs(i.total) }}</td>
                            </tr>
                            <tr v-if="!ingresosMensuales.length">
                                <td colspan="2" class="py-1 text-gray-500">Sin registros.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div>
                    <h2 class="mb-1 text-sm font-semibold" :style="{ color: colores.primario }">Préstamos por tipo de evento</h2>
                    <table class="w-full border-collapse">
                        <thead>
                            <tr class="border-b border-gray-400 text-left">
                                <th class="py-1">Tipo de evento</th>
                                <th class="py-1 text-right">Préstamos</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="p in prestamosPorTipo" :key="p.tipo" class="border-b border-gray-200">
                                <td class="py-1">{{ p.tipo }}</td>
                                <td class="py-1 text-right">{{ p.total }}</td>
                            </tr>
                            <tr v-if="!prestamosPorTipo.length">
                                <td colspan="2" class="py-1 text-gray-500">Sin registros.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div>
                    <h2 class="mb-1 text-sm font-semibold" :style="{ color: colores.primario }">Costos de mantenimiento</h2>
                    <table class="w-full border-collapse">
                        <thead>
                            <tr class="border-b border-gray-400 text-left">
                                <th class="py-1">Predio</th>
                                <th class="py-1 text-right">Registros</th>
                                <th class="py-1 text-right">Costo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="c in costosMantenimiento" :key="c.predio" class="border-b border-gray-200">
                                <td class="py-1">{{ c.predio }}</td>
                                <td class="py-1 text-right">{{ c.registros }}</td>
                                <td class="py-1 text-right">{{ bs(c.total) }}</td>
                            </tr>
                            <tr v-if="!costosMantenimiento.length">
                                <td colspan="3" class="py-1 text-gray-500">Sin registros.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <footer class="mt-6 flex justify-between gap-2 border-t border-gray-300 pt-2 text-[10px] text-gray-500">
                <span>Sistema de gestión de predios · FICH-UAGRM</span>
                <span>Emitido por {{ emitidoPor }} el {{ emitido }}</span>
            </footer>
        </div>
    </div>
</template>

<style>
@page {
    size: A4;
    margin: 12mm;
}
</style>