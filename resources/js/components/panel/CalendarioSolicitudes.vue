<script setup lang="ts">
import { CalendarDays, ChevronLeft, ChevronRight, X } from 'lucide-vue-next';
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';

interface Evento {
    id: number;
    fecha: string;
    inicio: string;
    fin: string;
    referencia: string;
    evento: string;
    solicitante: string;
    predio: string;
}

interface Mantenimiento {
    inicio: string;
    fin: string;
    predio: string;
    descripcion: string;
}

interface RespuestaCalendario {
    dias: Record<string, number>;
    eventos: Evento[];
    mantenimientos: Mantenimiento[];
}

const ALTURA_HORA = 56;
const HORA_INICIO_BASE = 6;
const HORA_FIN_BASE = 22;
const COLORES_SOLICITUD = ['#2563eb', '#16a34a', '#dc2626', '#9333ea', '#ea580c', '#0891b2', '#db2777', '#65a30d'];
const OPACIDAD_NIVEL = [0, 0.15, 0.3, 0.6, 0.85];
const DIAS_SEMANA = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];

function pad(n: number): string {
    return String(n).padStart(2, '0');
}

function claveFecha(anio: number, mes: number, dia: number): string {
    return `${anio}-${pad(mes)}-${pad(dia)}`;
}

function aMinutos(hora: string): number {
    const [h, m] = hora.split(':').map(Number);

    return h * 60 + m;
}

function soloFecha(fecha: string): string {
    return fecha.slice(0, 10);
}

const hoy = new Date();
const fechaHoy = claveFecha(hoy.getFullYear(), hoy.getMonth() + 1, hoy.getDate());

const abierto = ref(false);
const cargando = ref(false);
const error = ref('');
const dias = ref<Record<string, number>>({});
const eventos = ref<Evento[]>([]);
const mantenimientos = ref<Mantenimiento[]>([]);
const mes = ref({ anio: hoy.getFullYear(), mes: hoy.getMonth() + 1 });
const fechaSeleccionada = ref(fechaHoy);
const botonCerrar = ref<HTMLButtonElement | null>(null);
let peticion = 0;

const claveMes = computed(() => `${mes.value.anio}-${pad(mes.value.mes)}`);

const tituloMes = computed(() => {
    const texto = new Date(mes.value.anio, mes.value.mes - 1, 1).toLocaleDateString('es', {
        month: 'long',
        year: 'numeric',
    });

    return texto.charAt(0).toUpperCase() + texto.slice(1);
});

const tituloDia = computed(() => {
    const [anio, m, d] = fechaSeleccionada.value.split('-').map(Number);
    const texto = new Date(anio, m - 1, d).toLocaleDateString('es', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
    });

    return texto.charAt(0).toUpperCase() + texto.slice(1);
});

// Celdas del mes: primero los espacios vacíos hasta el día de la semana, con lunes como primer día
const celdas = computed<(string | null)[]>(() => {
    const { anio, mes: m } = mes.value;
    const vacios = (new Date(anio, m - 1, 1).getDay() + 6) % 7;
    const total = new Date(anio, m, 0).getDate();
    const lista: (string | null)[] = Array.from({ length: vacios }, () => null);

    for (let d = 1; d <= total; d++) {
        lista.push(claveFecha(anio, m, d));
    }

    return lista;
});

function cantidadDia(clave: string): number {
    return dias.value[clave] ?? 0;
}

// Un día cuenta como mantenimiento si cae entre la fecha de inicio y la fecha fin de algún mantenimiento
function hayMantenimiento(clave: string): boolean {
    return mantenimientos.value.some((m) => m.inicio <= clave && clave <= m.fin);
}

function estiloDia(clave: string): Record<string, string> {
    if (hayMantenimiento(clave)) {
        return { backgroundColor: '#dc2626' };
    }

    const nivel = Math.min(cantidadDia(clave), 4);

    if (nivel === 0) {
        return {};
    }

    return {
        backgroundColor: `color-mix(in srgb, var(--primario) ${OPACIDAD_NIVEL[nivel] * 100}%, transparent)`,
    };
}

function textoClaroDia(clave: string): boolean {
    return hayMantenimiento(clave) || cantidadDia(clave) >= 3;
}

// Cada solicitud tiene siempre el mismo color, según su referencia
function colorDe(referencia: string): string {
    let suma = 0;

    for (const caracter of referencia) {
        suma += caracter.charCodeAt(0);
    }

    return COLORES_SOLICITUD[suma % COLORES_SOLICITUD.length];
}

const mantenimientosDelDia = computed(() =>
    mantenimientos.value.filter((m) => m.inicio <= fechaSeleccionada.value && fechaSeleccionada.value <= m.fin),
);

const eventosDia = computed(() =>
    eventos.value
        .filter((e) => soloFecha(e.fecha) === fechaSeleccionada.value)
        .sort((a, b) => aMinutos(a.inicio) - aMinutos(b.inicio)),
);

// Rango de horas visible: 06:00 a 22:00, o más si hay solicitudes fuera de ese rango
const rango = computed(() => {
    const inicios = eventosDia.value.map((e) => Math.floor(aMinutos(e.inicio) / 60));
    const fines = eventosDia.value.map((e) => Math.ceil(aMinutos(e.fin) / 60));

    return {
        desde: Math.min(HORA_INICIO_BASE, ...inicios),
        hasta: Math.min(24, Math.max(HORA_FIN_BASE, ...fines)),
    };
});

const horas = computed(() =>
    Array.from({ length: rango.value.hasta - rango.value.desde }, (_, i) => rango.value.desde + i),
);

// Solicitudes que se cruzan en horario se muestran en carriles, una al lado de otra
const bloques = computed(() => {
    const finesPorCarril: number[] = [];

    const asignados = eventosDia.value.map((evento) => {
        const inicio = aMinutos(evento.inicio);
        const fin = aMinutos(evento.fin);
        let carril = finesPorCarril.findIndex((f) => f <= inicio);

        if (carril === -1) {
            carril = finesPorCarril.length;
            finesPorCarril.push(fin);
        } else {
            finesPorCarril[carril] = fin;
        }

        return { evento, carril, inicio, fin };
    });

    const total = Math.max(1, finesPorCarril.length);

    return asignados.map((b) => ({
        evento: b.evento,
        estilo: {
            top: `${((b.inicio - rango.value.desde * 60) / 60) * ALTURA_HORA}px`,
            height: `${Math.max(((b.fin - b.inicio) / 60) * ALTURA_HORA - 4, 30)}px`,
            left: `calc(${(b.carril / total) * 100}% + 4px)`,
            width: `calc(${100 / total}% - 8px)`,
            backgroundColor: colorDe(b.evento.referencia),
        },
    }));
});

async function cargarMes(): Promise<void> {
    const id = ++peticion;
    cargando.value = true;
    error.value = '';

    try {
        const respuesta = await fetch(`/panel/solicitantes/calendario?mes=${claveMes.value}`, {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
        });

        if (!respuesta.ok) {
            throw new Error('Respuesta no válida');
        }

        const datos: RespuestaCalendario = await respuesta.json();

        if (id === peticion) {
            dias.value = datos.dias;
            eventos.value = datos.eventos;
            mantenimientos.value = datos.mantenimientos;
        }
    } catch {
        if (id === peticion) {
            error.value = 'No se pudo cargar el calendario. Revisa tu conexión e intenta de nuevo.';
        }
    } finally {
        if (id === peticion) {
            cargando.value = false;
        }
    }
}

watch(claveMes, () => {
    cargarMes();
});

function cambiarMes(delta: number): void {
    const nuevo = new Date(mes.value.anio, mes.value.mes - 1 + delta, 1);
    mes.value = { anio: nuevo.getFullYear(), mes: nuevo.getMonth() + 1 };
    fechaSeleccionada.value = claveFecha(mes.value.anio, mes.value.mes, 1);
}

function irAHoy(): void {
    mes.value = { anio: hoy.getFullYear(), mes: hoy.getMonth() + 1 };
    fechaSeleccionada.value = fechaHoy;
}

function abrir(): void {
    abierto.value = true;
    document.body.style.overflow = 'hidden';
    cargarMes();
    nextTick(() => botonCerrar.value?.focus());
}

function cerrar(): void {
    abierto.value = false;
    document.body.style.overflow = '';
}

function alPresionarTecla(evento: KeyboardEvent): void {
    if (evento.key === 'Escape' && abierto.value) {
        cerrar();
    }
}

onMounted(() => window.addEventListener('keydown', alPresionarTecla));

onBeforeUnmount(() => {
    window.removeEventListener('keydown', alPresionarTecla);
    document.body.style.overflow = '';
});
</script>

<template>
    <button
        type="button"
        aria-haspopup="dialog"
        class="inline-flex items-center gap-2 rounded-lg bg-superficie px-3.5 py-2.5 text-sm font-semibold text-texto ring-1 ring-borde hover:bg-fondo"
        @click="abrir"
    >
        <CalendarDays class="h-4 w-4" aria-hidden="true" />
        Calendario
    </button>

    <Teleport to="body">
        <div
            v-if="abierto"
            class="fixed inset-0 z-[70] flex items-stretch justify-center bg-black/40 backdrop-blur-sm sm:items-center sm:p-6"
            @click.self="cerrar"
        >
            <section
                role="dialog"
                aria-modal="true"
                aria-labelledby="titulo-calendario"
                class="flex h-full w-full flex-col overflow-hidden bg-superficie text-texto shadow-2xl ring-1 ring-borde sm:h-auto sm:max-h-[92vh] sm:max-w-6xl sm:rounded-2xl"
            >
                <header class="flex items-center justify-between gap-3 border-b border-borde px-5 py-4">
                    <div>
                        <h2 id="titulo-calendario" class="text-lg font-bold text-[var(--primario)]">
                            Calendario de solicitudes
                        </h2>
                        <p class="text-xs text-texto-suave">Solo se muestran las solicitudes aprobadas.</p>
                    </div>

                    <button
                        ref="botonCerrar"
                        type="button"
                        class="rounded-lg p-2 text-texto-suave hover:bg-fondo"
                        aria-label="Cerrar calendario"
                        @click="cerrar"
                    >
                        <X class="h-5 w-5" aria-hidden="true" />
                    </button>
                </header>

                <div class="flex-1 overflow-y-auto p-5">
                    <!-- Navegación de meses -->
                    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                class="rounded-lg p-2 text-texto ring-1 ring-borde hover:bg-fondo"
                                aria-label="Mes anterior"
                                @click="cambiarMes(-1)"
                            >
                                <ChevronLeft class="h-4 w-4" aria-hidden="true" />
                            </button>
                            <p class="min-w-[10rem] text-center font-semibold" aria-live="polite">{{ tituloMes }}</p>
                            <button
                                type="button"
                                class="rounded-lg p-2 text-texto ring-1 ring-borde hover:bg-fondo"
                                aria-label="Mes siguiente"
                                @click="cambiarMes(1)"
                            >
                                <ChevronRight class="h-4 w-4" aria-hidden="true" />
                            </button>
                        </div>

                        <button
                            type="button"
                            class="rounded-lg px-3 py-2 text-sm font-semibold text-[var(--primario)] ring-1 ring-borde hover:bg-fondo"
                            @click="irAHoy"
                        >
                            Hoy
                        </button>
                    </div>

                    <p v-if="error" class="mb-4 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700 ring-1 ring-red-200">
                        {{ error }}
                    </p>

                    <div class="grid gap-8 lg:grid-cols-2">
                        <!-- Calendario -->
                        <div>
                            <div class="grid grid-cols-7 gap-1 text-center text-xs font-semibold uppercase text-texto-suave">
                                <span v-for="dia in DIAS_SEMANA" :key="dia" class="py-2">{{ dia }}</span>
                            </div>

                            <div class="grid grid-cols-7 gap-1">
                                <template v-for="(clave, indice) in celdas" :key="indice">
                                    <div v-if="!clave" class="aspect-square" />

                                    <button
                                        v-else
                                        type="button"
                                        :style="estiloDia(clave)"
                                        :aria-label="`Día ${Number(clave.slice(8))}: ${cantidadDia(clave)} solicitudes aprobadas${hayMantenimiento(clave) ? ', con mantenimiento' : ''}`"
                                        :aria-pressed="clave === fechaSeleccionada"
                                        :class="[
                                            'flex aspect-square flex-col items-center justify-center rounded-lg text-sm ring-1 ring-borde transition hover:ring-[var(--primario)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--primario)]',
                                            textoClaroDia(clave) ? 'text-white' : 'text-texto',
                                            clave === fechaSeleccionada ? 'ring-2 ring-[var(--primario)]' : '',
                                            clave === fechaHoy ? 'font-bold' : '',
                                        ]"
                                        @click="fechaSeleccionada = clave"
                                    >
                                        <span>{{ Number(clave.slice(8)) }}</span>
                                        <span v-if="cantidadDia(clave) > 0" class="text-[10px] font-semibold leading-none">
                                            {{ cantidadDia(clave) }}
                                        </span>
                                    </button>
                                </template>
                            </div>

                            <!-- Leyenda -->
                            <div class="mt-4 flex flex-wrap items-center gap-3 text-xs text-texto-suave">
                                <span>Solicitudes aprobadas por día:</span>
                                <span v-for="n in 4" :key="n" class="flex items-center gap-1.5">
                                    <span
                                        class="h-3 w-3 rounded ring-1 ring-borde"
                                        :style="{ backgroundColor: `color-mix(in srgb, var(--primario) ${OPACIDAD_NIVEL[n] * 100}%, transparent)` }"
                                    />
                                    {{ n === 4 ? '4 o más' : n }}
                                </span>
                                <span class="flex items-center gap-1.5">
                                    <span class="h-3 w-3 rounded bg-red-600" />
                                    Mantenimiento
                                </span>
                            </div>
                        </div>

                        <!-- Horario del día seleccionado -->
                        <div>
                            <div class="mb-4 flex items-center justify-between gap-3">
                                <h3 class="font-semibold">{{ tituloDia }}</h3>
                                <span v-if="cargando" class="text-sm text-texto-suave">Cargando…</span>
                            </div>

                            <!-- Mantenimientos del día -->
                            <ul v-if="mantenimientosDelDia.length > 0" class="mb-4 space-y-2">
                                <li
                                    v-for="(m, i) in mantenimientosDelDia"
                                    :key="`mantenimiento-${i}`"
                                    class="rounded-xl bg-red-600 p-3 text-sm text-white ring-1 ring-black/10"
                                >
                                    <p class="font-semibold">Mantenimiento · {{ m.predio }}</p>
                                    <p class="text-xs opacity-90">{{ m.descripcion }}</p>
                                </li>
                            </ul>

                            <p v-if="!cargando && eventosDia.length === 0" class="rounded-xl bg-fondo p-6 text-center text-sm text-texto-suave">
                                No hay solicitudes aprobadas este día.
                            </p>

                            <div v-else-if="eventosDia.length > 0" class="relative" :style="{ height: `${horas.length * ALTURA_HORA}px` }">
                                <!-- Líneas y horas -->
                                <div
                                    v-for="hora in horas"
                                    :key="hora"
                                    class="absolute inset-x-0 border-t border-borde"
                                    :style="{ top: `${(hora - rango.desde) * ALTURA_HORA}px` }"
                                >
                                    <span class="absolute left-0 -translate-y-1/2 bg-superficie pr-2 text-xs text-texto-suave">
                                        {{ pad(hora) }}:00
                                    </span>
                                </div>

                                <!-- Bloques de solicitudes -->
                                <div class="absolute inset-y-0 left-14 right-0">
                                    <article
                                        v-for="bloque in bloques"
                                        :key="bloque.evento.id"
                                        :style="bloque.estilo"
                                        class="absolute overflow-hidden rounded-lg px-2 py-1 text-white shadow ring-1 ring-black/10"
                                        :title="`${bloque.evento.solicitante} · ${bloque.evento.predio}`"
                                    >
                                        <p class="truncate text-xs font-semibold">{{ bloque.evento.solicitante }}</p>
                                        <p class="truncate text-[11px] opacity-90">
                                            {{ bloque.evento.inicio.slice(0, 5) }}–{{ bloque.evento.fin.slice(0, 5) }} · {{ bloque.evento.predio }}
                                        </p>
                                    </article>
                                </div>
                            </div>

                            <!-- Detalle del día -->
                            <ul v-if="eventosDia.length > 0" class="mt-6 space-y-2">
                                <li
                                    v-for="e in eventosDia"
                                    :key="e.id"
                                    class="flex items-start gap-3 rounded-xl bg-fondo p-3 ring-1 ring-borde"
                                >
                                    <span class="mt-1 h-3 w-3 shrink-0 rounded-full" :style="{ backgroundColor: colorDe(e.referencia) }" />
                                    <div class="text-sm">
                                        <p class="font-semibold">
                                            {{ e.solicitante }}
                                            <span class="font-normal text-texto-suave">· {{ e.evento }}</span>
                                        </p>
                                        <p class="text-texto-suave">
                                            {{ e.inicio.slice(0, 5) }} – {{ e.fin.slice(0, 5) }} · {{ e.predio }}
                                        </p>
                                        <p class="text-xs text-texto-suave">{{ e.referencia }}</p>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </Teleport>
</template>