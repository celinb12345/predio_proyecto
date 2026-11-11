<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Eye, EyeOff, GraduationCap } from 'lucide-vue-next';
import CargaPagina from '@/components/CargaPagina.vue';
import { ref } from 'vue';

defineProps<{
    canResetPassword: boolean;
    status?: string | null;
}>();

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const mostrarClave = ref(false);

function ingresar() {
    form.post('/login', {
        onFinish: () => form.reset('password'),
    });
}

const campo =
    'mt-1 block w-full rounded-lg border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 placeholder:text-gray-400 focus:border-[#1e3a8a] focus:outline-none focus:ring-2 focus:ring-[#1e3a8a]/20';
</script>

<template>
    <Head title="Iniciar sesión" />

    <div class="min-h-screen bg-gray-50 text-gray-900 lg:grid lg:grid-cols-2">
        <!-- Panel institucional (solo en PC) -->
                 <CargaPagina />
        <aside
            class="relative hidden overflow-hidden p-12 text-white lg:flex lg:flex-col lg:justify-between bg-[linear-gradient(to_bottom_right,#1e3a8a,#172554)]"
        >
            <div class="absolute -right-24 -top-24 h-96 w-96 rounded-full bg-[#f59e0b]/20 blur-3xl" />

            <div class="relative flex items-center gap-3">
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-white/10 ring-1 ring-white/20">
                    <GraduationCap class="h-6 w-6" />
                </div>
                <div>
                    <p class="text-xs uppercase tracking-widest text-white/70">
                        Universidad Autónoma Gabriel René Moreno
                    </p>
                    <p class="font-semibold">Facultad Integral del Chaco</p>
                </div>
            </div>

            <div class="relative">
                <h1 class="text-4xl font-bold leading-tight">Gestión de predios</h1>
                <p class="mt-4 max-w-md text-white/80">
                    Reservas, entregas y control de los ambientes de la facultad, en un solo lugar.
                </p>
                <div class="mt-8 h-1 w-16 rounded-full bg-[#f59e0b]" />
            </div>

            <p class="relative text-xs text-white/60">© Facultad Integral del Chaco · UAGRM</p>
        </aside>

        <!-- Formulario -->
        <main class="flex min-h-screen flex-col items-center justify-center px-6 py-10 sm:px-12">
            <!-- Encabezado solo en celular y tablet -->
            <div class="mb-8 flex flex-col items-center text-center lg:hidden">
                <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-[#1e3a8a] text-white">
                    <GraduationCap class="h-7 w-7" />
                </div>
                <p class="mt-4 text-xs font-semibold uppercase tracking-widest text-[#1e3a8a]">
                    Facultad Integral del Chaco
                </p>
                <p class="text-xs text-gray-500">UAGRM · Gestión de predios</p>
            </div>

            <div class="w-full max-w-md rounded-2xl bg-white p-8 shadow-xl shadow-blue-900/5 ring-1 ring-gray-200 sm:p-10">
                <div class="mb-8">
                    <h2 class="text-2xl font-bold text-[#1e3a8a]">Iniciar sesión</h2>
                    <p class="mt-1 text-sm text-gray-500">Ingresa con tu correo y tu contraseña.</p>
                </div>

                <div
                    v-if="status"
                    class="mb-6 rounded-lg bg-green-50 px-4 py-3 text-sm text-green-700 ring-1 ring-green-200"
                >
                    {{ status }}
                </div>

                <form class="space-y-5" @submit.prevent="ingresar">
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">Correo electrónico</label>
                        <input
                            id="email"
                            v-model="form.email"
                            type="email"
                            autocomplete="username"
                            required
                            autofocus
                            placeholder="correo@ejemplo.com"
                            :class="campo"
                        />
                        <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">{{ form.errors.email }}</p>
                    </div>

                    <div>
                        <div class="flex items-center justify-between gap-2">
                            <label for="password" class="block text-sm font-medium text-gray-700">Contraseña</label>
                            <Link
                                v-if="canResetPassword"
                                href="/forgot-password"
                                class="text-sm font-medium text-[#1e3a8a] hover:underline"
                            >
                                ¿Olvidaste tu contraseña?
                            </Link>
                        </div>

                        <div class="relative">
                            <input
                                id="password"
                                v-model="form.password"
                                :type="mostrarClave ? 'text' : 'password'"
                                autocomplete="current-password"
                                required
                                placeholder="••••••••"
                                :class="[campo, 'pr-12']"
                            />
                            <button
                                type="button"
                                class="absolute inset-y-0 right-0 flex items-center px-4 text-gray-400 hover:text-gray-600"
                                :aria-label="mostrarClave ? 'Ocultar contraseña' : 'Mostrar contraseña'"
                                @click="mostrarClave = !mostrarClave"
                            >
                                <EyeOff v-if="mostrarClave" class="h-5 w-5" />
                                <Eye v-else class="h-5 w-5" />
                            </button>
                        </div>
                        <p v-if="form.errors.password" class="mt-1 text-sm text-red-600">{{ form.errors.password }}</p>
                    </div>

                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        <input
                            v-model="form.remember"
                            type="checkbox"
                            class="h-4 w-4 rounded border-gray-300 text-[#1e3a8a] focus:ring-[#1e3a8a]"
                        />
                        Recordarme en este equipo
                    </label>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full rounded-lg bg-[#1e3a8a] px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-[#1e40af] disabled:opacity-60"
                    >
                        {{ form.processing ? 'Ingresando…' : 'Ingresar' }}
                    </button>
                </form>

                <p class="mt-8 border-t border-gray-100 pt-6 text-center text-sm text-gray-500">
                    ¿No tienes cuenta? Acércate a Secretaría General de la facultad.
                </p>
            </div>

            <p class="mt-8 text-xs text-gray-400">Sistema de gestión de predios · FICH</p>
        </main>
    </div>
</template>