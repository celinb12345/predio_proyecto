<script setup lang="ts">
import PanelLayout from '@/components/panel/PanelLayout.vue';
import { formulario } from '@/lib/estilos';
import { Link, useForm } from '@inertiajs/vue3';
import { AlertTriangle } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps<{
    usuario: { name: string; rol: string };
    editando: {
        id: number;
        name: string;
        email: string;
        role_id: number;
        activo: boolean;
        foto: string | null;
    };
    roles: { id: number; descripcion: string }[];
}>();

const form = useForm({
    role_id: props.editando.role_id as number | string,
    activo: props.editando.activo,
    password: '',
    foto: null as File | null,
});

const vistaPrevia = ref<string | null>(props.editando.foto);

// Avisos que aparecen mientras se edita, antes de guardar
const avisoDesactivar = computed(() => props.editando.activo && !form.activo);
const avisoRol = computed(() => Number(form.role_id) !== props.editando.role_id);

function alElegirFoto(evento: Event) {
    const archivo = (evento.target as HTMLInputElement).files?.[0] ?? null;

    form.foto = archivo;
    vistaPrevia.value = archivo ? URL.createObjectURL(archivo) : props.editando.foto;
}

function guardar() {
    form
        .transform((datos) => ({ ...datos, _method: 'put' }))
        .post(`/panel/usuarios/${props.editando.id}`, { forceFormData: true });
}
</script>

<template>
    <PanelLayout titulo="Editar usuario" :usuario="usuario">
        <div class="mb-6">
            <Link href="/panel/usuarios" class="text-sm font-medium text-texto-suave hover:text-[var(--primario)]">
                ← Volver a usuarios
            </Link>
        </div>

        <form class="grid max-w-5xl gap-6 xl:grid-cols-3" @submit.prevent="guardar">
            <!-- Resumen y foto -->
            <section :class="[formulario.tarjeta, 'self-start xl:row-span-2']">
                <div class="flex flex-col items-center text-center">
                    <img
                        v-if="vistaPrevia"
                        :src="vistaPrevia"
                        alt=""
                        class="h-24 w-24 rounded-full object-cover ring-4 ring-borde"
                    />
                    <div
                        v-else
                        class="flex h-24 w-24 items-center justify-center rounded-full bg-[var(--primario)] text-3xl font-semibold text-white ring-4 ring-borde"
                    >
                        {{ editando.name.charAt(0) }}
                    </div>

                    <p class="mt-4 font-semibold text-texto">{{ editando.name }}</p>
                    <p class="text-sm text-texto-suave">{{ editando.email }}</p>

                    <span
                        :class="[
                            'mt-3 rounded-full px-3 py-1 text-xs font-semibold ring-1',
                            editando.activo
                                ? 'bg-emerald-50 text-emerald-700 ring-emerald-200'
                                : 'bg-fondo text-texto-suave ring-borde',
                        ]"
                    >
                        {{ editando.activo ? 'Cuenta activa' : 'Cuenta inactiva' }}
                    </span>
                </div>

                <div class="mt-6 border-t border-borde pt-6">
                    <label for="foto" :class="formulario.etiqueta">Cambiar foto</label>
                    <input
                        id="foto"
                        type="file"
                        accept="image/*"
                        class="mt-2 block w-full text-sm text-texto-suave file:mr-3 file:rounded-lg file:border-0 file:bg-fondo file:px-3 file:py-2 file:text-sm file:font-medium file:text-texto-suave hover:file:bg-slate-200"
                        @change="alElegirFoto"
                    />
                    <p v-if="form.errors.foto" :class="formulario.error">{{ form.errors.foto }}</p>
                </div>
            </section>

            <!-- Acceso -->
            <section :class="[formulario.tarjeta, 'xl:col-span-2']">
                <h2 :class="formulario.titulo">Acceso al sistema</h2>
                <p :class="formulario.subtitulo">
                    Define qué menú ve el usuario y si puede ingresar. El nombre y el correo no se cambian desde aquí.
                </p>

                <div class="mt-6 grid gap-5">
                    <div>
                        <label for="rol" :class="formulario.etiqueta">Rol</label>
                        <select id="rol" v-model="form.role_id" :class="formulario.campo">
                            <option v-for="rol in roles" :key="rol.id" :value="rol.id">
                                {{ rol.descripcion }}
                            </option>
                        </select>
                        <p v-if="form.errors.role_id" :class="formulario.error">{{ form.errors.role_id }}</p>
                    </div>

                    <label
                        class="flex cursor-pointer items-center justify-between gap-4 rounded-lg border border-borde px-4 py-3 hover:bg-fondo"
                    >
                        <span>
                            <span class="block text-sm font-medium text-texto-suave">Cuenta activa</span>
                            <span class="block text-xs text-texto-suave">
                                Un usuario inactivo no puede ingresar al sistema.
                            </span>
                        </span>
                        <input
                            v-model="form.activo"
                            type="checkbox"
                            class="h-5 w-5 rounded border-borde text-[var(--primario)] focus:ring-[var(--primario)]"
                        />
                    </label>
                    <p v-if="form.errors.activo" :class="formulario.error">{{ form.errors.activo }}</p>

                    <div
                        v-if="avisoDesactivar"
                        class="flex gap-3 rounded-lg bg-amber-50 px-4 py-3 text-sm text-amber-900 ring-1 ring-amber-200"
                    >
                        <AlertTriangle class="mt-0.5 h-5 w-5 shrink-0 text-amber-600" />
                        <p>
                            Al desactivar la cuenta, <strong>{{ editando.name }}</strong> no podrá ingresar al sistema
                            hasta que la vuelvas a activar.
                        </p>
                    </div>

                    <div
                        v-if="avisoRol"
                        class="flex gap-3 rounded-lg bg-amber-50 px-4 py-3 text-sm text-amber-900 ring-1 ring-amber-200"
                    >
                        <AlertTriangle class="mt-0.5 h-5 w-5 shrink-0 text-amber-600" />
                        <p>Al cambiar el rol, el menú que verá el usuario cambia la próxima vez que ingrese.</p>
                    </div>
                </div>
            </section>

            <!-- Seguridad -->
            <section :class="[formulario.tarjeta, 'xl:col-span-2']">
                <h2 :class="formulario.titulo">Seguridad</h2>
                <p :class="formulario.subtitulo">Déjalo vacío para mantener la contraseña actual.</p>

                <div class="mt-6">
                    <label for="password" :class="formulario.etiqueta">Nueva contraseña</label>
                    <input
                        id="password"
                        v-model="form.password"
                        type="password"
                        autocomplete="new-password"
                        :class="formulario.campo"
                    />
                    <p v-if="form.errors.password" :class="formulario.error">{{ form.errors.password }}</p>
                </div>
            </section>

            <!-- Acciones -->
            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end xl:col-span-3">
                <Link href="/panel/usuarios" :class="formulario.botonSecundario">Cancelar</Link>
                <button type="submit" :disabled="form.processing" :class="formulario.botonPrimario">
                    {{ form.processing ? 'Guardando…' : 'Guardar cambios' }}
                </button>
            </div>
        </form>
    </PanelLayout>
</template>