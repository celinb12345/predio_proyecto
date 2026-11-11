<script setup lang="ts">
import PanelLayout from '@/components/panel/PanelLayout.vue';
import TarifarioPredio from '@/components/panel/TarifarioPredio.vue';
import { formulario } from '@/lib/estilos';
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

interface Foto {
    id: number;
    tipo: string;
    descripcion: string | null;
    url: string;
}

interface Equipamiento {
    id: number;
    equipo_id: number;
    equipo: string;
    cantidad: number;
    estado: string;
}

const props = defineProps<{
    usuario: { name: string; rol: string };
    predio: {
        id: number;
        nombre: string;
        tipo: string;
        responsable: string;
        capacidad: number | null;
        ubicacion: string | null;
        link_google_maps: string | null;
        estado: string;
    };
    galeria: Foto[];
    inventario: Equipamiento[];
    equipos: { id: number; descripcion: string }[];
    tarifario: {
        id: number;
        tipo_persona: string;
        monto: string;
        resolucion: string;
        vigente: boolean;
        fecha: string | null;
    }[];
    tiposPersona: { id: number; descripcion: string }[];
}>();

const pagina = usePage();

const etiquetasEstadoPredio: Record<string, string> = {
    disponible: 'Disponible',
    mantenimiento: 'En mantenimiento',
    inhabilitado: 'Inhabilitado',
};

const clasesEstadoPredio: Record<string, string> = {
    disponible: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
    mantenimiento: 'bg-amber-50 text-amber-800 ring-amber-200',
    inhabilitado: 'bg-fondo text-texto-suave ring-borde',
};

const etiquetasTipoFoto: Record<string, string> = { ambiente: 'Ambiente', entrada: 'Entrada', otro: 'Otro' };

const etiquetasEstadoEquipo: Record<string, string> = {
    bueno: 'Bueno',
    regular: 'Regular',
    malo: 'Malo',
    baja: 'Dado de baja',
};

const estadosEquipo = Object.entries(etiquetasEstadoEquipo).map(([valor, texto]) => ({ valor, texto }));

const errorEquipo = computed(() => (pagina.props.errors as Record<string, string> | undefined)?.equipo);

// Galería
const formFoto = useForm({
    archivo: null as File | null,
    tipo: 'ambiente',
    descripcion: '',
});

function alElegirFoto(evento: Event) {
    formFoto.archivo = (evento.target as HTMLInputElement).files?.[0] ?? null;
}

function subirFoto() {
    formFoto.post(`/panel/predios/${props.predio.id}/galeria`, {
        forceFormData: true,
        onSuccess: () => formFoto.reset(),
    });
}

function borrarFoto(foto: Foto) {
    if (confirm('¿Eliminar esta foto de la galería?')) {
        router.delete(`/panel/predios/${props.predio.id}/galeria/${foto.id}`, { preserveScroll: true });
    }
}

// Inventario
const formEquipo = useForm({
    equipo_id: '' as number | string,
    cantidad: 1,
    estado: 'bueno',
});

function agregarEquipo() {
    formEquipo.post(`/panel/predios/${props.predio.id}/inventario`, {
        preserveScroll: true,
        onSuccess: () => formEquipo.reset(),
    });
}

const editandoId = ref<number | null>(null);
const edicion = ref({ equipo_id: 0, cantidad: 0, estado: 'bueno' });

function iniciarEdicion(fila: Equipamiento) {
    editandoId.value = fila.id;
    edicion.value = { equipo_id: fila.equipo_id, cantidad: fila.cantidad, estado: fila.estado };
}

function guardarEdicion(fila: Equipamiento) {
    router.put(`/panel/predios/${props.predio.id}/inventario/${fila.id}`, edicion.value, {
        preserveScroll: true,
        onSuccess: () => (editandoId.value = null),
    });
}

function quitarEquipo(fila: Equipamiento) {
    if (confirm(`¿Quitar ${fila.equipo} de este predio?`)) {
        router.delete(`/panel/predios/${props.predio.id}/inventario/${fila.id}`, { preserveScroll: true });
    }
}

const campoTabla =
    'block w-full rounded-lg border border-borde bg-superficie px-3 py-1.5 text-sm text-texto focus:border-[var(--primario)] focus:outline-none focus:ring-2 focus:ring-[var(--primario)]/20';
</script>

<template>
    <PanelLayout :titulo="predio.nombre" :usuario="usuario">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <Link href="/panel/predios" class="text-sm font-medium text-texto-suave hover:text-[var(--primario)]">
                ← Volver a predios
            </Link>
            <Link :href="`/panel/predios/${predio.id}/editar`" :class="formulario.botonSecundario">
                Editar datos del predio
            </Link>
        </div>

        <!-- Datos del predio -->
        <section :class="[formulario.tarjeta, 'mb-8']">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="text-xl font-semibold text-texto">{{ predio.nombre }}</h2>
                    <p class="text-sm text-texto-suave">{{ predio.tipo }}</p>
                </div>
                <span
                    :class="[
                        'rounded-full px-3 py-1 text-xs font-semibold ring-1',
                        clasesEstadoPredio[predio.estado] ?? 'bg-fondo text-texto-suave ring-borde',
                    ]"
                >
                    {{ etiquetasEstadoPredio[predio.estado] ?? predio.estado }}
                </span>
            </div>

            <dl class="mt-6 grid gap-5 text-sm sm:grid-cols-2 xl:grid-cols-3">
                <div>
                    <dt class="text-texto-suave">Responsable</dt>
                    <dd class="mt-0.5 font-medium text-texto">{{ predio.responsable }}</dd>
                </div>
                <div>
                    <dt class="text-texto-suave">Capacidad</dt>
                    <dd class="mt-0.5 font-medium text-texto">{{ predio.capacidad ?? 'No indicada' }}</dd>
                </div>
                <div>
                    <dt class="text-texto-suave">Ubicación</dt>
                    <dd class="mt-0.5 font-medium text-texto">{{ predio.ubicacion ?? 'No indicada' }}</dd>
                </div>
                <div class="sm:col-span-2 xl:col-span-3">
                    <dt class="text-texto-suave">Mapa</dt>
                    <dd class="mt-0.5">
                        <a
                            v-if="predio.link_google_maps"
                            :href="predio.link_google_maps"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="font-semibold text-[var(--primario)] hover:underline"
                        >
                            Abrir en Google Maps
                        </a>
                        <span v-else class="text-texto-suave">No registrado</span>
                    </dd>
                </div>
            </dl>
        </section>

        <!-- Galería -->
        <section :class="[formulario.tarjeta, 'mb-8']">
            <h2 :class="formulario.titulo">Galería</h2>
            <p :class="formulario.subtitulo">Fotos y videos que ven los solicitantes en la consulta de predios.</p>

            <form class="mt-6 grid gap-4 rounded-xl bg-fondo p-5 sm:grid-cols-4" @submit.prevent="subirFoto">
                <div class="sm:col-span-2">
                    <label for="archivo" :class="formulario.etiqueta">Archivo</label>
                    <input
                        id="archivo"
                        type="file"
                        accept="image/*"
                        class="mt-1.5 block w-full text-sm text-texto-suave file:mr-3 file:rounded-lg file:border-0 file:bg-superficie file:px-3 file:py-2 file:text-sm file:font-medium file:text-texto-suave file:ring-1 file:ring-borde hover:file:bg-fondo"
                        @change="alElegirFoto"
                    />
                    <p v-if="formFoto.errors.archivo" :class="formulario.error">{{ formFoto.errors.archivo }}</p>
                </div>

                <div>
                    <label for="tipo-foto" :class="formulario.etiqueta">Tipo</label>
                    <select id="tipo-foto" v-model="formFoto.tipo" :class="formulario.campo">
                        <option value="ambiente">Ambiente</option>
                        <option value="entrada">Entrada</option>
                        <option value="otro">Otro</option>
                    </select>
                    <p v-if="formFoto.errors.tipo" :class="formulario.error">{{ formFoto.errors.tipo }}</p>
                </div>

                <div>
                    <label for="descripcion-foto" :class="formulario.etiqueta">Descripción</label>
                    <input id="descripcion-foto" v-model="formFoto.descripcion" type="text" :class="formulario.campo" />
                </div>

                <div class="sm:col-span-4">
                    <button
                        type="submit"
                        :disabled="formFoto.processing || !formFoto.archivo"
                        :class="formulario.botonPrimario"
                    >
                        {{ formFoto.processing ? 'Subiendo…' : 'Subir foto' }}
                    </button>
                </div>
            </form>

            <div v-if="galeria.length" class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <figure v-for="foto in galeria" :key="foto.id" class="overflow-hidden rounded-xl ring-1 ring-borde">
                    <img :src="foto.url" alt="" class="h-40 w-full object-cover" />
                    <figcaption class="flex items-center justify-between gap-2 p-3 text-sm">
                        <span class="text-texto-suave">
                            {{ etiquetasTipoFoto[foto.tipo] ?? foto.tipo }}
                            <span v-if="foto.descripcion" class="block text-xs text-texto-suave">{{ foto.descripcion }}</span>
                        </span>
                        <button type="button" class="font-semibold text-red-600 hover:underline" @click="borrarFoto(foto)">
                            Eliminar
                        </button>
                    </figcaption>
                </figure>
            </div>
            <p v-else class="mt-6 text-sm text-texto-suave">Este predio todavía no tiene fotos.</p>
        </section>

        <!-- Inventario -->
        <section :class="[formulario.tarjeta, 'mb-8']">
            <h2 :class="formulario.titulo">Inventario de equipos</h2>
            <p :class="formulario.subtitulo">Equipos disponibles en el predio y su estado.</p>

            <form class="mt-6 grid gap-4 rounded-xl bg-fondo p-5 sm:grid-cols-4" @submit.prevent="agregarEquipo">
                <div class="sm:col-span-2">
                    <label for="equipo" :class="formulario.etiqueta">Equipo</label>
                    <select id="equipo" v-model="formEquipo.equipo_id" :class="formulario.campo">
                        <option value="" disabled>Selecciona un equipo</option>
                        <option v-for="equipo in equipos" :key="equipo.id" :value="equipo.id">
                            {{ equipo.descripcion }}
                        </option>
                    </select>
                    <p v-if="formEquipo.errors.equipo_id" :class="formulario.error">{{ formEquipo.errors.equipo_id }}</p>
                </div>

                <div>
                    <label for="cantidad" :class="formulario.etiqueta">Cantidad</label>
                    <input id="cantidad" v-model="formEquipo.cantidad" type="number" min="0" :class="formulario.campo" />
                    <p v-if="formEquipo.errors.cantidad" :class="formulario.error">{{ formEquipo.errors.cantidad }}</p>
                </div>

                <div>
                    <label for="estado-equipo" :class="formulario.etiqueta">Estado</label>
                    <select id="estado-equipo" v-model="formEquipo.estado" :class="formulario.campo">
                        <option v-for="e in estadosEquipo" :key="e.valor" :value="e.valor">{{ e.texto }}</option>
                    </select>
                    <p v-if="formEquipo.errors.estado" :class="formulario.error">{{ formEquipo.errors.estado }}</p>
                </div>

                <div class="sm:col-span-4">
                    <button type="submit" :disabled="formEquipo.processing" :class="formulario.botonPrimario">
                        Agregar equipo
                    </button>
                </div>
            </form>

            <p v-if="errorEquipo" class="mt-4 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700 ring-1 ring-red-200">
                {{ errorEquipo }}
            </p>

            <div class="mt-6 overflow-hidden rounded-xl ring-1 ring-borde">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-borde text-sm">
                        <thead class="bg-[var(--primario)] text-left text-xs font-semibold uppercase tracking-wide text-white">
                            <tr>
                                <th class="px-5 py-3">Equipo</th>
                                <th class="px-5 py-3">Cantidad</th>
                                <th class="px-5 py-3">Estado</th>
                                <th class="px-5 py-3"><span class="sr-only">Acciones</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-borde bg-superficie text-texto">
                            <tr v-for="fila in inventario" :key="fila.id">
                                <td class="px-5 py-4 font-medium">{{ fila.equipo }}</td>

                                <template v-if="editandoId === fila.id">
                                    <td class="px-5 py-3">
                                        <input v-model="edicion.cantidad" type="number" min="0" :class="campoTabla" class="w-24" />
                                    </td>
                                    <td class="px-5 py-3">
                                        <select v-model="edicion.estado" :class="campoTabla">
                                            <option v-for="e in estadosEquipo" :key="e.valor" :value="e.valor">{{ e.texto }}</option>
                                        </select>
                                    </td>
                                    <td class="px-5 py-3">
                                        <div class="flex justify-end gap-4">
                                            <button type="button" class="font-semibold text-[var(--primario)] hover:underline" @click="guardarEdicion(fila)">
                                                Guardar
                                            </button>
                                            <button type="button" class="font-semibold text-texto-suave hover:underline" @click="editandoId = null">
                                                Cancelar
                                            </button>
                                        </div>
                                    </td>
                                </template>

                                <template v-else>
                                    <td class="px-5 py-4 text-texto-suave">{{ fila.cantidad }}</td>
                                    <td class="px-5 py-4 text-texto-suave">{{ etiquetasEstadoEquipo[fila.estado] ?? fila.estado }}</td>
                                    <td class="px-5 py-4">
                                        <div class="flex justify-end gap-4">
                                            <button type="button" class="font-semibold text-[var(--primario)] hover:underline" @click="iniciarEdicion(fila)">
                                                Editar
                                            </button>
                                            <button type="button" class="font-semibold text-red-600 hover:underline" @click="quitarEquipo(fila)">
                                                Quitar
                                            </button>
                                        </div>
                                    </td>
                                </template>
                            </tr>
                            <tr v-if="inventario.length === 0">
                                <td colspan="4" class="px-5 py-10 text-center text-texto-suave">
                                    Este predio todavía no tiene equipos.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <!-- Tarifario: componente existente, sin cambios -->
        <TarifarioPredio :predio-id="predio.id" :tarifario="tarifario" :tipos-persona="tiposPersona" />
    </PanelLayout>
</template>