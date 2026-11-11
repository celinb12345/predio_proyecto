import {
    ArcElement,
    BarElement,
    CategoryScale,
    Chart as ChartJS,
    Legend,
    LinearScale,
    Tooltip,
    type ChartOptions,
} from 'chart.js';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

ChartJS.register(ArcElement, BarElement, CategoryScale, LinearScale, Tooltip, Legend);

export interface Colores {
    primario: string;
    secundario: string;
    texto: string;
    rejilla: string;
    paleta: string[];
}

const formatoNumero = new Intl.NumberFormat('es-BO', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
});

export function bs(valor: number): string {
    return `Bs ${formatoNumero.format(valor)}`;
}

export function etiquetaMes(mes: string): string {
    const [anio, numeroMes] = mes.split('-').map(Number);
    const texto = new Date(anio, numeroMes - 1, 1).toLocaleDateString('es-BO', {
        month: 'short',
        year: 'numeric',
    });
    return texto.charAt(0).toUpperCase() + texto.slice(1);
}

export function fechaCorta(iso: string): string {
    return iso.split('-').reverse().join('/');
}

// Convierte cualquier expresión CSS (incluida var(--...)) a un color que Chart.js entiende
function resolverColor(expresion: string, respaldo: string): string {
    const elemento = document.createElement('span');
    elemento.style.color = expresion;
    document.body.appendChild(elemento);
    const resuelto = getComputedStyle(elemento).color;
    elemento.remove();
    return resuelto || respaldo;
}

export function coloresTema(_version = 0): Colores {
    const primario = resolverColor('var(--primario)', '#1d4ed8');
    const secundario = resolverColor('var(--secundario)', '#f59e0b');

    return {
        primario,
        secundario,
        texto: getComputedStyle(document.body).color || '#1f2937',
        rejilla: 'rgba(128, 128, 128, 0.25)',
        paleta: [primario, secundario, '#10b981', '#ef4444', '#8b5cf6', '#14b8a6', '#f97316', '#64748b'],
    };
}

// Se recalculan los colores cuando cambia el modo día/noche o el tema del html
export function usarColores() {
    const version = ref(0);
    let observador: MutationObserver | null = null;

    onMounted(() => {
        observador = new MutationObserver(() => version.value++);
        observador.observe(document.documentElement, { attributes: true });
    });

    onBeforeUnmount(() => observador?.disconnect());

    return computed<Colores>(() => coloresTema(version.value));
}

export function opcionesBarras(
    c: Colores,
    opciones: {
        horizontal?: boolean;
        formato?: (valor: number) => string;
        formatoEje?: (valor: number) => string;
    } = {},
): ChartOptions<'bar'> {
    const formato = opciones.formato ?? ((valor: number) => String(valor));
    const formatoEje = opciones.formatoEje ?? formato;
    const horizontal = opciones.horizontal ?? false;

    const ejeValor = {
        beginAtZero: true,
        grid: { color: c.rejilla },
        border: { display: false },
        ticks: {
            color: c.texto,
            callback: (valor: string | number) => formatoEje(Number(valor)),
        },
    };

    const ejeCategoria = {
        grid: { display: false },
        border: { color: c.rejilla },
        ticks: { color: c.texto },
    };

    return {
        indexAxis: horizontal ? 'y' : 'x',
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: (contexto) => ` ${formato(Number(contexto.raw))}`,
                },
            },
        },
        scales: horizontal ? { x: ejeValor, y: ejeCategoria } : { x: ejeCategoria, y: ejeValor },
    };
}

export function opcionesDona(c: Colores, formato: (valor: number) => string): ChartOptions<'doughnut'> {
    return {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '60%',
        plugins: {
            legend: {
                position: 'bottom',
                labels: { color: c.texto, boxWidth: 12, padding: 16 },
            },
            tooltip: {
                callbacks: {
                    label: (contexto) => ` ${contexto.label}: ${formato(Number(contexto.raw))}`,
                },
            },
        },
    };
}