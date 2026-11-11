import { reactive } from 'vue';

export type Tema = 'adultos' | 'jovenes' | 'ninos';
export type Modo = 'automatico' | 'dia' | 'noche';
export type Letra = 'normal' | 'grande' | 'muy-grande';
export type Contraste = 'normal' | 'alto';

export interface Preferencias {
    tema: Tema;
    modo: Modo;
    letra: Letra;
    contraste: Contraste;
}

const CLAVE = 'predios-fich-preferencias';

const POR_DEFECTO: Preferencias = {
    tema: 'adultos',
    modo: 'automatico',
    letra: 'normal',
    contraste: 'normal',
};

function leerGuardadas(): Preferencias {
    try {
        const guardado = window.localStorage.getItem(CLAVE);

        return guardado ? { ...POR_DEFECTO, ...JSON.parse(guardado) } : { ...POR_DEFECTO };
    } catch {
        return { ...POR_DEFECTO };
    }
}

export const preferencias = reactive<Preferencias>(leerGuardadas());

// En automático, de 18:00 a 06:00 se usa el modo noche según la hora del equipo
export function modoEfectivo(): 'dia' | 'noche' {
    if (preferencias.modo !== 'automatico') {
        return preferencias.modo;
    }

    const hora = new Date().getHours();

    return hora >= 18 || hora < 6 ? 'noche' : 'dia';
}

export function aplicar(): void {
    const html = document.documentElement;

    html.dataset.tema = preferencias.tema;
    html.dataset.modo = modoEfectivo();
    html.dataset.letra = preferencias.letra;
    html.dataset.contraste = preferencias.contraste;
}

export function cambiar<K extends keyof Preferencias>(clave: K, valor: Preferencias[K]): void {
    Object.assign(preferencias, { [clave]: valor });

    try {
        window.localStorage.setItem(CLAVE, JSON.stringify(preferencias));
    } catch {
        // Si el navegador no permite guardar, la preferencia dura solo mientras la página esté abierta
    }

    aplicar();
}

aplicar();

// Revisa cada minuto si cambió la hora, para el modo automático
window.setInterval(() => {
    if (preferencias.modo === 'automatico') {
        aplicar();
    }
}, 60_000);