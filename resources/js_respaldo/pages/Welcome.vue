<script setup lang="ts">
import { institucion } from '@/lib/institucion';
import { Head, Link } from '@inertiajs/vue3';
import {
    BadgeCheck,
    BarChart3,
    CalendarCheck,
    CalendarDays,
    Clock,
    FileText,
    GraduationCap,
    Mail,
    MapPin,
    Phone,
    Search,
    ShieldCheck,
} from 'lucide-vue-next';

defineProps<{
    estadisticas: {
        predios_total: number;
        predios_disponibles: number;
        reservas_anio: number;
        anio: number;
    };
    autenticado: boolean;
}>();

const pasos = [
    {
        icono: Search,
        titulo: 'Consulta',
        texto: 'Revisa los ambientes, sus fotos, su equipamiento y la tarifa que te corresponde.',
    },
    {
        icono: FileText,
        titulo: 'Solicitud',
        texto: 'Registra la fecha, la hora y el respaldo, como la carta dirigida al Decano.',
    },
    {
        icono: BadgeCheck,
        titulo: 'Aprobación',
        texto: 'El Decano da el proveído y Secretaría General aprueba la solicitud.',
    },
    {
        icono: CalendarCheck,
        titulo: 'Pago y entrega',
        texto: 'Pagas en caja, recibes el ambiente con acta y lo devuelves en buen estado.',
    },
];

const ventajas = [
    {
        icono: CalendarDays,
        titulo: 'Sin cruces de fechas',
        texto: 'El sistema impide reservar dos veces el mismo ambiente en el mismo horario.',
    },
    {
        icono: ShieldCheck,
        titulo: 'Reglas claras',
        texto: 'Tarifas según el tipo de persona y estados visibles en cada etapa del trámite.',
    },
    {
        icono: BarChart3,
        titulo: 'Información para decidir',
        texto: 'Reportes de uso, ingresos y mantenimiento para el Decano y Secretaría General.',
    },
];

const anio = new Date().getFullYear();
</script>

<template>
    <Head title="Inicio" />

    <div class="min-h-screen bg-gray-50 text-gray-900">
        <!-- Hero -->
        <header class="relative overflow-hidden bg-[linear-gradient(to_bottom_right,#1e3a8a,#172554)] text-white">
            <div class="absolute -right-32 -top-32 h-[28rem] w-[28rem] rounded-full bg-[#f59e0b]/15 blur-3xl" />
            <div class="absolute -bottom-40 -left-20 h-96 w-96 rounded-full bg-white/5 blur-3xl" />

            <nav class="relative mx-auto flex max-w-6xl items-center justify-between px-6 py-5">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/10 ring-1 ring-white/20">
                        <GraduationCap class="h-5 w-5" />
                    </div>
                    <div>
                        <p class="text-sm font-bold leading-tight">{{ institucion.sigla }}</p>
                        <p class="text-xs leading-tight text-white/70">Gestión de predios</p>
                    </div>
                </div>

                <Link
                    :href="autenticado ? '/panel' : '/login'"
                    class="rounded-lg bg-white/10 px-4 py-2 text-sm font-semibold ring-1 ring-white/20 hover:bg-white/20"
                >
                    {{ autenticado ? 'Ir al panel' : 'Iniciar sesión' }}
                </Link>
            </nav>

            <div class="relative mx-auto grid max-w-6xl gap-12 px-6 pb-24 pt-12 lg:grid-cols-2 lg:items-center lg:pt-20">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-widest text-[#f59e0b]">
                        {{ institucion.universidad }}
                    </p>
                    <h1 class="mt-4 text-4xl font-bold leading-tight sm:text-5xl">{{ institucion.facultad }}</h1>
                    <p class="mt-5 max-w-xl text-lg text-white/80">{{ institucion.descripcion }}</p>

                    <div class="mt-8 flex flex-wrap gap-3">
                        <Link
                            :href="autenticado ? '/panel' : '/login'"
                            class="rounded-lg bg-[#f59e0b] px-6 py-3 text-sm font-semibold text-[#172554] shadow-lg shadow-black/20 hover:bg-[#fbbf24]"
                        >
                            {{ autenticado ? 'Ir al panel' : 'Solicitar un ambiente' }}
                        </Link>
                        <a
                            href="#como-funciona"
                            class="rounded-lg px-6 py-3 text-sm font-semibold text-white ring-1 ring-white/30 hover:bg-white/10"
                        >
                            ¿Cómo funciona?
                        </a>
                    </div>
                </div>

                <!-- Datos en vivo -->
                <div class="rounded-2xl bg-white/10 p-6 ring-1 ring-white/20 backdrop-blur">
                    <p class="text-sm font-semibold text-white/80">Estado de los ambientes</p>

                    <div class="mt-5 grid grid-cols-2 gap-4">
                        <div class="rounded-xl bg-white p-5 text-[#1e3a8a]">
                            <p class="text-3xl font-bold">{{ estadisticas.predios_disponibles }}</p>
                            <p class="mt-1 text-sm text-gray-600">Disponibles</p>
                        </div>
                        <div class="rounded-xl bg-white/5 p-5 ring-1 ring-white/20">
                            <p class="text-3xl font-bold">{{ estadisticas.predios_total }}</p>
                            <p class="mt-1 text-sm text-white/70">Ambientes registrados</p>
                        </div>
                        <div class="col-span-2 rounded-xl bg-white/5 p-5 ring-1 ring-white/20">
                            <p class="text-3xl font-bold">{{ estadisticas.reservas_anio }}</p>
                            <p class="mt-1 text-sm text-white/70">Reservas confirmadas en {{ estadisticas.anio }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Cómo funciona -->
        <section id="como-funciona" class="mx-auto max-w-6xl scroll-mt-6 px-6 py-20">
            <div class="max-w-2xl">
                <p class="text-sm font-semibold uppercase tracking-widest text-[#1e3a8a]">Proceso</p>
                <h2 class="mt-2 text-3xl font-bold text-gray-900">Cómo solicitar un ambiente</h2>
                <p class="mt-3 text-gray-600">Todo el trámite se sigue en línea, desde la consulta hasta la entrega.</p>
            </div>

            <ol class="mt-12 grid gap-6 md:grid-cols-2 xl:grid-cols-4">
                <li v-for="(paso, i) in pasos" :key="paso.titulo" class="rounded-2xl bg-white p-6 ring-1 ring-gray-200">
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-[#1e3a8a] text-white">
                        <component :is="paso.icono" class="h-5 w-5" />
                    </span>
                    <p class="mt-5 text-xs font-semibold text-[#b45309]">Paso {{ i + 1 }}</p>
                    <h3 class="mt-1 font-semibold text-gray-900">{{ paso.titulo }}</h3>
                    <p class="mt-2 text-sm text-gray-600">{{ paso.texto }}</p>
                </li>
            </ol>
        </section>

        <!-- Ventajas -->
        <section class="bg-white">
            <div class="mx-auto grid max-w-6xl gap-8 px-6 py-20 md:grid-cols-3">
                <div v-for="v in ventajas" :key="v.titulo">
                    <component :is="v.icono" class="h-8 w-8 text-[#f59e0b]" />
                    <h3 class="mt-4 text-lg font-semibold text-gray-900">{{ v.titulo }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-gray-600">{{ v.texto }}</p>
                </div>
            </div>
        </section>

        <!-- Llamado final -->
        <section class="mx-auto max-w-6xl px-6 py-20">
            <div class="flex flex-col items-start justify-between gap-6 rounded-2xl bg-[#1e3a8a] p-8 text-white sm:flex-row sm:items-center sm:p-12">
                <div>
                    <h2 class="text-2xl font-bold">¿Necesitas un ambiente para tu evento?</h2>
                    <p class="mt-2 text-white/80">Revisa la disponibilidad y envía tu solicitud en pocos minutos.</p>
                </div>
                <Link
                    :href="autenticado ? '/panel' : '/login'"
                    class="shrink-0 rounded-lg bg-[#f59e0b] px-6 py-3 text-sm font-semibold text-[#172554] hover:bg-[#fbbf24]"
                >
                    {{ autenticado ? 'Ir al panel' : 'Iniciar sesión' }}
                </Link>
            </div>
        </section>

        <!-- Pie de página -->
        <footer class="bg-gray-900 text-gray-300">
            <div class="mx-auto grid max-w-6xl gap-10 px-6 py-14 md:grid-cols-2">
                <div>
                    <p class="font-semibold text-white">{{ institucion.facultad }}</p>
                    <p class="mt-1 text-sm">{{ institucion.universidad }}</p>
                </div>

                <div class="space-y-3 text-sm">
                    <p v-if="institucion.direccion" class="flex gap-2">
                        <MapPin class="mt-0.5 h-4 w-4 shrink-0 text-[#f59e0b]" />
                        {{ institucion.direccion }}
                    </p>
                    <p v-if="institucion.telefono" class="flex gap-2">
                        <Phone class="mt-0.5 h-4 w-4 shrink-0 text-[#f59e0b]" />
                        {{ institucion.telefono }}
                    </p>
                    <p v-if="institucion.correo" class="flex gap-2">
                        <Mail class="mt-0.5 h-4 w-4 shrink-0 text-[#f59e0b]" />
                        {{ institucion.correo }}
                    </p>
                    <p v-if="institucion.horario" class="flex gap-2">
                        <Clock class="mt-0.5 h-4 w-4 shrink-0 text-[#f59e0b]" />
                        {{ institucion.horario }}
                    </p>
                </div>
            </div>

            <div class="border-t border-white/10 py-5 text-center text-xs text-gray-500">
                © {{ anio }} {{ institucion.sigla }} · UAGRM. Todos los derechos reservados.
            </div>
        </footer>
    </div>
</template>