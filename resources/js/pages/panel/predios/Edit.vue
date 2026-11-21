<script setup lang="ts">
import PanelLayout from '@/components/panel/PanelLayout.vue';
import { formulario } from '@/lib/estilos';
import { Link, useForm } from '@inertiajs/vue3';
import { AlertTriangle } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    usuario: { name: string; rol: string };
    editando: {
        id: number;
        nombre: string;
        capacidad: number | null;
        ubicacion: string | null;
        link_google_maps: string | null;
        estado: string;
        tipo_predio_id: number;
        responsable_id: number;
    };
    tipos: { id: number; descripcion: string }[];
    responsables: { id: number; nombre: string; apellido: string }[];
}>();

const estados = [
    { valor: 'disponible', texto: 'Disponible' },
    { valor: 'en_mantenimiento', texto: 'En mantenimiento' },
    { valor: 'inactivo', texto: 'Inhabilitado' },
];

const form = useForm({
    nombre: props.editando.nombre,
    capacidad: props.editando.capacidad ?? ('' as number | string),
    ubicacion: props.editando.ubicacion ?? '',
    link_google_maps: props.editando.link_google_maps ?? '',
    estado: props.editando.estado,
    tipo_predio_id: props.editando.tipo_predio_id as number | string,
    responsable_id: props.editando.responsable_id as number | string,
});

// Aviso antes de guardar: el cambio de estado no cancela las reservas existentes
const avisoEstado = computed(
    () => props.editando.estado === 'disponible' && form.estado !== 'disponible',
);

function guardar() {
    form.put(`/panel/predios/${props.editando.id}`);
}
</script>

<template>
    <PanelLayout titulo="Editar predio" :usuario="usuario">
        <div class="mb-6">
            <Link :href="`/panel/predios/${editando.id}`" class="text-sm font-medium text-texto-suave hover:text-[var(--primario)]">
                ← Volver a la ficha del predio
            </Link>
        </div>

        <form :class="[formulario.tarjeta, 'grid max-w-3xl gap-5 sm:grid-cols-2']" @submit.prevent="guardar">
            <div class="sm:col-span-2">
                <h2 :class="formulario.titulo">Datos del predio</h2>
                <p :class="formulario.subtitulo">Los cambios se muestran a los solicitantes en cuanto se guardan.</p>
            </div>

            <div class="sm:col-span-2">
                <label for="nombre" :class="formulario.etiqueta">Nombre del predio</label>
                <input id="nombre" v-model="form.nombre" type="text" :class="formulario.campo" />
                <p v-if="form.errors.nombre" :class="formulario.error">{{ form.errors.nombre }}</p>
            </div>

            <div>
                <label for="tipo" :class="formulario.etiqueta">Tipo de predio</label>
                <select id="tipo" v-model="form.tipo_predio_id" :class="formulario.campo">
                    <option v-for="tipo in tipos" :key="tipo.id" :value="tipo.id">{{ tipo.descripcion }}</option>
                </select>
                <p v-if="form.errors.tipo_predio_id" :class="formulario.error">{{ form.errors.tipo_predio_id }}</p>
            </div>

            <div>
                <label for="responsable" :class="formulario.etiqueta">Responsable</label>
                <select id="responsable" v-model="form.responsable_id" :class="formulario.campo">
                    <option v-for="r in responsables" :key="r.id" :value="r.id">{{ r.apellido }}, {{ r.nombre }}</option>
                </select>
                <p v-if="form.errors.responsable_id" :class="formulario.error">{{ form.errors.responsable_id }}</p>
            </div>

            <div>
                <label for="capacidad" :class="formulario.etiqueta">Capacidad</label>
                <input id="capacidad" v-model="form.capacidad" type="number" min="1" :class="formulario.campo" />
                <p v-if="form.errors.capacidad" :class="formulario.error">{{ form.errors.capacidad }}</p>
                <p v-else :class="formulario.ayuda">Número de personas. Opcional.</p>
            </div>

            <div>
                <label for="estado" :class="formulario.etiqueta">Estado</label>
                <select id="estado" v-model="form.estado" :class="formulario.campo">
                    <option v-for="e in estados" :key="e.valor" :value="e.valor">{{ e.texto }}</option>
                </select>
                <p v-if="form.errors.estado" :class="formulario.error">{{ form.errors.estado }}</p>
            </div>

            <div v-if="avisoEstado" class="flex gap-3 rounded-lg bg-amber-50 px-4 py-3 text-sm text-amber-900 ring-1 ring-amber-200 sm:col-span-2">
                <AlertTriangle class="mt-0.5 h-5 w-5 shrink-0 text-amber-600" />
                <p>
                    Si el predio ya tiene fechas reservadas, esas reservas no se cancelan automáticamente. Revísalas en Liberación de fechas.
                </p>
            </div>

            <div class="sm:col-span-2">
                <label for="ubicacion" :class="formulario.etiqueta">Ubicación</label>
                <input id="ubicacion" v-model="form.ubicacion" type="text" :class="formulario.campo" />
                <p v-if="form.errors.ubicacion" :class="formulario.error">{{ form.errors.ubicacion }}</p>
            </div>

            <div class="sm:col-span-2">
                <label for="mapa" :class="formulario.etiqueta">Enlace de Google Maps</label>
                <input id="mapa" v-model="form.link_google_maps" type="url" :class="formulario.campo" />
                <p v-if="form.errors.link_google_maps" :class="formulario.error">{{ form.errors.link_google_maps }}</p>
                <p v-else :class="formulario.ayuda">Opcional.</p>
            </div>

            <div class="flex flex-col-reverse gap-3 border-t border-borde pt-6 sm:col-span-2 sm:flex-row sm:justify-end">
                <Link :href="`/panel/predios/${editando.id}`" :class="formulario.botonSecundario">Cancelar</Link>
                <button type="submit" :disabled="form.processing" :class="formulario.botonPrimario">
                    {{ form.processing ? 'Guardando…' : 'Guardar cambios' }}
                </button>
            </div>
        </form>
    </PanelLayout>
</template>