<script setup lang="ts">
import Notificacion from '@/components/Notificacion.vue';
import PanelSidebar from '@/components/panel/PanelSidebar.vue';
import PreferenciasVisuales from '@/components/PreferenciasVisuales.vue';
import { modulosPara } from '@/lib/panel-menu';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    titulo: string;
    usuario: { name: string; rol: string };
}>();

const modulos = computed(() => modulosPara(props.usuario.rol));
</script>

<template>
    <Head :title="titulo" />

    <div class="flex min-h-screen bg-fondo text-texto">
        <Notificacion />

        <div class="hidden md:flex">
            <PanelSidebar :usuario="usuario" :modulos="modulos" />
        </div>

        <main class="flex-1 p-4 md:p-8">
            <div class="rounded-2xl bg-superficie p-6 shadow-sm ring-1 ring-borde">
                <header class="mb-6 flex flex-wrap items-start justify-between gap-4 border-b-4 border-[var(--secundario)] pb-3">
                    <h1 class="text-2xl font-bold text-[var(--primario)]">{{ titulo }}</h1>
                    <PreferenciasVisuales />
                </header>

                <slot />
            </div>
        </main>
    </div>
</template>