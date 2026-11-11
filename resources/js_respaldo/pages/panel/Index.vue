<script setup lang="ts">
import PanelLayout from '@/components/panel/PanelLayout.vue';
import { modulosPara } from '@/lib/panel-menu';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    usuario: { name: string; rol: string };
}>();

const modulos = computed(() => modulosPara(props.usuario.rol));
</script>

<template>
    <PanelLayout titulo="Inicio" :usuario="usuario">
        <p class="mb-6 text-gray-600">
            Bienvenido, <span class="font-semibold">{{ usuario.name }}</span>. Estas son tus funciones como
            <span class="font-semibold">{{ usuario.rol }}</span>.
        </p>

        <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
            <Link
                v-for="m in modulos"
                :key="m.codigo"
                :href="m.href"
                class="rounded-xl bg-white p-5 ring-1 ring-gray-200 transition hover:shadow-md hover:ring-[var(--secundario)]"
            >
                <span class="rounded bg-[var(--primario)] px-2 py-0.5 text-xs font-bold text-white">{{ m.codigo }}</span>
                <h2 class="mt-3 text-lg font-semibold text-[var(--primario)]">{{ m.nombre }}</h2>
                <p class="mt-2 text-sm text-gray-500">{{ m.descripcion }}</p>
            </Link>
        </div>

        <p v-if="modulos.length === 0" class="text-gray-500">Tu rol todavía no tiene funciones asignadas.</p>
    </PanelLayout>
</template>