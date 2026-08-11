/**
 * data.js - Cargador de datos y estado de la aplicación
 * Carga la base de datos desde data.json y gestiona el estado persistido en localStorage.
 * 
 * @author Luis Cabezas - Inteligencia.com.co
 * @version 1.0.0
 */

// ============================================================
// CARGA DE DATOS
// ============================================================

/** Datos de la aplicación (docentes, materias, sesiones) */
let DATA = null;

/** Información del programa académico */
let PROGRAMA = null;

/**
 * Carga los datos desde data.json y los transforma al formato interno.
 * Las sesiones se mapean a un formato compacto: { m: materia_id, f: fecha, t: modalidad }
 * @async
 * @throws {Error} Si el fetch falla (requiere servidor HTTP, no funciona con file://)
 */
async function loadData() {
  const res = await fetch('./data/data.json');
  const json = await res.json();

  PROGRAMA = json.programa;

  DATA = {
    docentes: json.docentes,
    materias: json.materias,
    sesiones: json.sesiones.map(s => ({
      m: s.materia_id,
      f: s.fecha,
      t: s.modalidad
    }))
  };
}

// ============================================================
// HELPERS
// ============================================================

/**
 * Busca una materia por ID.
 * @param {string} id - ID de la materia
 * @returns {Object} Objeto materia
 */
const gM = id => DATA.materias.find(m => m.id === id);

/**
 * Busca un docente por ID.
 * @param {string} id - ID del docente
 * @returns {Object} Objeto docente
 */
const gD = id => DATA.docentes.find(d => d.id === id);

/**
 * Convierte un string de fecha ISO a objeto Date (mediodía para evitar problemas de timezone).
 * @param {string} s - Fecha en formato YYYY-MM-DD
 * @returns {Date}
 */
const toD = s => new Date(s + 'T12:00:00');

/**
 * Retorna la fecha actual como string ISO (YYYY-MM-DD).
 * @returns {string}
 */
const todayStr = () => new Date().toISOString().split('T')[0];

/** Nombres de meses en español */
const MO = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];

/** Días de la semana abreviados en español */
const DS = ['Dom','Lun','Mar','Mié','Jue','Vie','Sáb'];

/**
 * Formatea fecha corta: "Vie 21 ago"
 * @param {string} s - Fecha ISO
 * @returns {string}
 */
function fD(s) {
  const d = toD(s);
  return DS[d.getDay()] + ' ' + d.getDate() + ' ' + MO[d.getMonth()].substring(0, 3).toLowerCase();
}

/**
 * Formatea fecha larga: "Vie 21 de Agosto"
 * @param {string} s - Fecha ISO
 * @returns {string}
 */
function fDL(s) {
  const d = toD(s);
  return DS[d.getDay()] + ' ' + d.getDate() + ' de ' + MO[d.getMonth()];
}

// ============================================================
// ESTADO PERSISTIDO (localStorage)
// ============================================================

/**
 * Carga un valor del localStorage con fallback.
 * @param {string} k - Clave (se prefija con 'udea_')
 * @param {*} fb - Valor por defecto si no existe
 * @returns {*}
 */
function ld(k, fb) {
  const v = localStorage.getItem('udea_' + k);
  return v !== null ? JSON.parse(v) : fb;
}

/**
 * Guarda un valor en localStorage.
 * @param {string} k - Clave (se prefija con 'udea_')
 * @param {*} v - Valor a guardar
 */
function sv(k, v) {
  localStorage.setItem('udea_' + k, JSON.stringify(v));
}

// --- Variables de estado ---

/** Vista activa actual */
let curView = ld('view', 'inicio');

/** Mes y año mostrados en el calendario */
let calMonth = ld('calMonth', 7);
let calYear = ld('calYear', 2026);

/** Día seleccionado en el calendario */
let selDay = null;

/** Filtro de materia - vista Inicio */
let fMat = ld('fMat', 'todas');

/** Filtro de modalidad - global (Pre/AMT/todas) */
let fMod = ld('fMod', 'todas');

/** Filtro de materia - vista Calendario (independiente) */
let fMatCal = ld('fMatCal', 'todas');

/** Filtro de materia - vista Materias (independiente) */
let fMatMat = ld('fMatMat', 'todas');
