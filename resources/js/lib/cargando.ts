import { router } from '@inertiajs/vue3';
import { ref } from 'vue';

// Tiempo mínimo que se ve la animación, para que no parpadee en cada cambio de pantalla
const DURACION_MINIMA = 1000;

export const cargando = ref(true);

let inicioNavegacion = 0;

router.on('start', () => {
    inicioNavegacion = Date.now();
    cargando.value = true;
});

router.on('finish', () => {
    const restante = DURACION_MINIMA - (Date.now() - inicioNavegacion);

    window.setTimeout(() => {
        cargando.value = false;
    }, Math.max(0, restante));
});

// Carga inicial de la aplicación
window.setTimeout(() => {
    cargando.value = false;
}, 1400);