<script setup lang="ts">
import PanelLayout from '@/components/panel/PanelLayout.vue';
import { formulario } from '@/lib/estilos';
import { Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps<{
    usuario: { name: string; rol: string };
    roles: { id: number; descripcion: string }[];
    tiposPersona: { id: number; descripcion: string }[];
}>();

const form = useForm({
    nombre: '',
    apellido: '',
    ci: '',
    celular: '',
    tipo_persona_id: '' as number | string,
    email: '',
    password: '',
    role_id: '' as number | string,
    foto: null as File | null,
});

const vistaPrevia = ref<string | null>(null);

function alElegirFoto(evento: Event) {
    const archivo = (evento.target as HTMLInputElement).files?.[0] ?? null;

    form.foto = archivo;
    vistaPrevia.value = archivo ? URL.createObjectURL(archivo) : null;
}

function guardar() {
    form.post('/panel/usuarios', { forceFormData: true });
}
</script>

<template>
    <PanelLayout titulo="Nuevo usuario" :usuario="usuario">
        <div class="mb-6">
            <Link href="/panel/usuarios" class="text-sm font-medium text-slate-500 hover:text-[#1e3a8a]">
                ← Volver a usuarios
            </Link>
        </div>

        <form class="grid max-w-5xl gap-6 xl:grid-cols-3" @submit.prevent="guardar">
            <!-- Foto -->
            <section :class="[formulario.tarjeta, 'self-start xl:row-span-2']">
                <h2 :class="formulario.titulo">Foto</h2>
                <p :class="formulario.subtitulo">Opcional. Se muestra en la lista de usuarios.</p>

                <div class="mt-6 flex flex-col items-center gap-4">
                    <img
                        v-if="vistaPrevia"
                        :src="vistaPrevia"
                        alt="Vista previa de la foto"
                        class="h-28 w-28 rounded-full object-cover ring-4 ring-slate-100"
                    />
                    <div
                        v-else
                        class="flex h-28 w-28 items-center justify-center rounded-full bg-slate-100 text-sm text-slate-400 ring-4 ring-slate-50"
                    >
                        Sin foto
                    </div>

                    <label
                        class="w-full cursor-pointer rounded-lg border border-dashed border-slate-300 px-4 py-3 text-center text-sm font-medium text-slate-600 hover:border-[#1e3a8a] hover:bg-slate-50"
                    >
                        Elegir imagen
                        <input type="file" accept="image/*" class="sr-only" @change="alElegirFoto" />
                    </label>
                    <p v-if="form.errors.foto" :class="formulario.error">{{ form.errors.foto }}</p>
                </div>
            </section>

            <!-- Datos personales -->
            <section :class="[formulario.tarjeta, 'xl:col-span-2']">
                <h2 :class="formulario.titulo">Datos personales</h2>
                <p :class="formulario.subtitulo">Información de la persona registrada en la facultad.</p>

                <div class="mt-6 grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="nombre" :class="formulario.etiqueta">Nombre</label>
                        <input id="nombre" v-model="form.nombre" type="text" :class="formulario.campo" />
                        <p v-if="form.errors.nombre" :class="formulario.error">{{ form.errors.nombre }}</p>
                    </div>

                    <div>
                        <label for="apellido" :class="formulario.etiqueta">Apellido</label>
                        <input id="apellido" v-model="form.apellido" type="text" :class="formulario.campo" />
                        <p v-if="form.errors.apellido" :class="formulario.error">{{ form.errors.apellido }}</p>
                    </div>

                    <div>
                        <label for="ci" :class="formulario.etiqueta">CI</label>
                        <input id="ci" v-model="form.ci" type="text" :class="formulario.campo" />
                        <p v-if="form.errors.ci" :class="formulario.error">{{ form.errors.ci }}</p>
                        <p v-else :class="formulario.ayuda">Opcional.</p>
                    </div>

                    <div>
                        <label for="celular" :class="formulario.etiqueta">Celular</label>
                        <input id="celular" v-model="form.celular" type="text" :class="formulario.campo" />
                        <p v-if="form.errors.celular" :class="formulario.error">{{ form.errors.celular }}</p>
                        <p v-else :class="formulario.ayuda">Opcional.</p>
                    </div>

                    <div class="sm:col-span-2">
                        <label for="tipo_persona" :class="formulario.etiqueta">Tipo de persona</label>
                        <select id="tipo_persona" v-model="form.tipo_persona_id" :class="formulario.campo">
                            <option value="" disabled>Selecciona una opción</option>
                            <option v-for="tipo in tiposPersona" :key="tipo.id" :value="tipo.id">
                                {{ tipo.descripcion }}
                            </option>
                        </select>
                        <p v-if="form.errors.tipo_persona_id" :class="formulario.error">
                            {{ form.errors.tipo_persona_id }}
                        </p>
                    </div>
                </div>
            </section>

            <!-- Cuenta de acceso -->
            <section :class="[formulario.tarjeta, 'xl:col-span-2']">
                <h2 :class="formulario.titulo">Cuenta de acceso</h2>
                <p :class="formulario.subtitulo">Correo, contraseña inicial y el rol que define el menú del usuario.</p>

                <div class="mt-6 grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="email" :class="formulario.etiqueta">Correo electrónico</label>
                        <input id="email" v-model="form.email" type="email" autocomplete="off" :class="formulario.campo" />
                        <p v-if="form.errors.email" :class="formulario.error">{{ form.errors.email }}</p>
                    </div>

                    <div>
                        <label for="rol" :class="formulario.etiqueta">Rol</label>
                        <select id="rol" v-model="form.role_id" :class="formulario.campo">
                            <option value="" disabled>Selecciona un rol</option>
                            <option v-for="rol in roles" :key="rol.id" :value="rol.id">
                                {{ rol.descripcion }}
                            </option>
                        </select>
                        <p v-if="form.errors.role_id" :class="formulario.error">{{ form.errors.role_id }}</p>
                    </div>

                    <div class="sm:col-span-2">
                        <label for="password" :class="formulario.etiqueta">Contraseña inicial</label>
                        <input
                            id="password"
                            v-model="form.password"
                            type="password"
                            autocomplete="new-password"
                            :class="formulario.campo"
                        />
                        <p v-if="form.errors.password" :class="formulario.error">{{ form.errors.password }}</p>
                    </div>
                </div>
            </section>

            <!-- Acciones -->
            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end xl:col-span-3">
                <Link href="/panel/usuarios" :class="formulario.botonSecundario">Cancelar</Link>
                <button type="submit" :disabled="form.processing" :class="formulario.botonPrimario">
                    {{ form.processing ? 'Guardando…' : 'Guardar usuario' }}
                </button>
            </div>
        </form>
    </PanelLayout>
</template>