export type ModuloClave =
    | 'CU1'
    | 'CU2'
    | 'CU3'
    | 'CU4'
    | 'CU5'
    | 'CU6'
    | 'CU7'
    | 'CU8'
    | 'SOL_PREDIOS'
    | 'SOL_SOLICITUDES'
    | 'SOL_PAGOS';

export type ModuloPanel = {
    clave: ModuloClave;
    codigo: string;
    nombre: string;
    descripcion: string;
    href: string;
};

export const modulosPanel: Record<ModuloClave, ModuloPanel> = {
    CU1: {
        clave: 'CU1',
        codigo: 'CU1',
        nombre: 'Usuarios',
        descripcion: 'Personas, roles y cuentas de acceso',
        href: '/panel/usuarios',
    },
    CU2: {
        clave: 'CU2',
        codigo: 'CU2',
        nombre: 'Predios',
        descripcion: 'Predios, equipos, inventario y tarifas',
        href: '/panel/predios',
    },
    CU3: {
        clave: 'CU3',
        codigo: 'CU3',
        nombre: 'Solicitudes',
        descripcion: 'Solicitudes, respaldos, proveídos y aprobaciones',
        href: '/panel/solicitantes',
    },
    CU4: {
        clave: 'CU4',
        codigo: 'CU4',
        nombre: 'Liberación de fechas',
        descripcion: 'Liberar fechas y reprogramar reservas',
        href: '/panel/financiera',
    },
    CU5: {
        clave: 'CU5',
        codigo: 'CU5',
        nombre: 'Entrega y recepción',
        descripcion: 'Actas, devoluciones, limpieza, sanciones y beneficios',
        href: '/panel/entrega',
    },
    CU6: {
        clave: 'CU6',
        codigo: 'CU6',
        nombre: 'Mantenimiento',
        descripcion: 'Mantenimientos de predios y sus costos',
        href: '/panel/mantenimiento',
    },
    CU7: {
        clave: 'CU7',
        codigo: 'CU7',
        nombre: 'Pagos',
        descripcion: 'Pagos en caja, cuotas y vencimientos',
        href: '/panel/pagos',
    },
    CU8: {
        clave: 'CU8',
        codigo: 'CU8',
        nombre: 'Reportes',
        descripcion: 'Estadísticas de uso, ingresos y costos',
        href: '/panel/reportes',
    },
    SOL_PREDIOS: {
        clave: 'SOL_PREDIOS',
        codigo: 'CU2',
        nombre: 'Predios disponibles',
        descripcion: 'Consulta predios, ubicación y equipamiento',
        href: '/panel/consulta/predios',
    },
    SOL_SOLICITUDES: {
        clave: 'SOL_SOLICITUDES',
        codigo: 'CU3',
        nombre: 'Mis solicitudes',
        descripcion: 'Pide un préstamo y revisa su estado',
        href: '/panel/solicitantes',
    },
    SOL_PAGOS: {
        clave: 'SOL_PAGOS',
        codigo: 'CU7',
        nombre: 'Mis pagos',
        descripcion: 'Cuotas, vencimientos y comprobantes',
        href: '/panel/mis-pagos',
    },
};

const secretaria: ModuloClave[] = ['CU1', 'CU2', 'CU3', 'CU4', 'CU5', 'CU6', 'CU7', 'CU8'];
const decano: ModuloClave[] = ['CU3', 'CU8'];
const solicitante: ModuloClave[] = ['SOL_PREDIOS', 'SOL_SOLICITUDES', 'SOL_PAGOS'];

export const modulosPorRol: Record<string, ModuloClave[]> = {
    'Secretaría General': secretaria,
    Decano: decano,
    Director: solicitante,
    Estudiante: solicitante,
    Cliente: solicitante,
};

export function modulosPara(rol: string | null | undefined): ModuloPanel[] {
    return (modulosPorRol[rol ?? ''] ?? []).map((clave) => modulosPanel[clave]);
}