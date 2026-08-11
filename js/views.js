/**
 * views.js - Lógica de vistas, navegación y renderizado
 * Contiene: tema, ripple, navegación por tabs, accordion,
 * filtros, y los 4 renders principales (Inicio, Calendario, Materias, Ajustes).
 * 
 * @author Luis Cabezas - Inteligencia.com.co
 * @version 1.0.0
 */

// ============================================================
// TEMA (Claro / Oscuro)
// ============================================================

/** Obtiene el tema actual del localStorage */
function getTheme(){return localStorage.getItem('theme')||'auto';}

/**
 * Aplica el tema y actualiza el botón de toggle.
 * @param {string} t - Tema a aplicar ('light', 'dark' o 'auto')
 */
function applyTheme(t){
localStorage.setItem('theme',t);
if(t==='auto')document.documentElement.removeAttribute('data-theme');
else document.documentElement.setAttribute('data-theme',t);
const isDark=t==='dark'||(t==='auto'&&matchMedia('(prefers-color-scheme:dark)').matches);
document.getElementById('themeBtn').innerHTML=isDark?ic('sun',20):ic('moon',20);
}
document.getElementById('themeBtn').addEventListener('click',()=>{applyTheme(getTheme()==='dark'?'light':'dark');});
applyTheme(getTheme());

// ============================================================
// EFECTO RIPPLE (feedback táctil visual en tab bar)
// ============================================================
document.querySelectorAll('.tab-item').forEach(btn=>{
btn.addEventListener('click',function(e){
const r=document.createElement('span');r.classList.add('ripple');
const rect=this.getBoundingClientRect();
const sz=Math.max(rect.width,rect.height);
r.style.width=r.style.height=sz+'px';
r.style.left=(e.clientX-rect.left-sz/2)+'px';
r.style.top=(e.clientY-rect.top-sz/2)+'px';
this.appendChild(r);setTimeout(()=>r.remove(),500);
});
});

// ============================================================
// NAVEGACIÓN POR TABS
// ============================================================
const titles={inicio:'Inicio',calendario:'Calendario',materias:'Materias',ajustes:'Ajustes'};
document.querySelectorAll('.tab-item').forEach(tab=>{tab.addEventListener('click',()=>switchView(tab.dataset.view));});

/**
 * Cambia la vista activa, actualiza el tab bar y renderiza.
 * @param {string} v - Nombre de la vista ('inicio', 'calendario', 'materias', 'ajustes')
 */
function switchView(v){
curView=v;sv('view',v);
document.querySelectorAll('.tab-item').forEach(t=>{t.classList.toggle('active',t.dataset.view===v);t.setAttribute('aria-selected',t.dataset.view===v);});
document.querySelectorAll('.view').forEach(el=>el.classList.remove('active'));
document.getElementById('view-'+v).classList.add('active');
document.getElementById('navTitle').textContent=titles[v];
render(v);
}
/** Despacha el render según la vista activa */
function render(v){({inicio:rInicio,calendario:rCal,materias:rMat,ajustes:rAjustes})[v]();}

// ============================================================
// ACCORDION (solo un panel abierto a la vez por grupo)
// ============================================================

/**
 * Toggle de accordion exclusivo: cierra hermanos y abre/cierra el seleccionado.
 * @param {HTMLElement} btn - Botón header del accordion clickeado
 */
function toggleAcc(btn){
const a=btn.closest('[data-acc]'),p=a.parentElement,open=a.classList.contains('open');
p.querySelectorAll(':scope>[data-acc]').forEach(x=>x.classList.remove('open'));
if(!open)a.classList.add('open');
}

// ============================================================
// FILTROS
// ============================================================

/**
 * Obtiene sesiones filtradas por materia y modalidad.
 * @param {string} matFilter - ID de materia o 'todas'
 * @returns {Array} Sesiones filtradas
 */
function getSessions(matFilter){
return DATA.sesiones.filter(s=>{
if(matFilter&&matFilter!=='todas'&&s.m!==matFilter)return false;
if(fMod!=='todas'&&s.t!==fMod)return false;return true;});
}
/**
 * Genera HTML de filtros completos (select materia + botones modalidad).
 * @param {string} selectId - ID para el select
 * @param {string} matVal - Valor actual del filtro de materia
 * @returns {string} HTML
 */
function filtersHTML(selectId,matVal){
let h='<div class="filters"><select class="filter-select" id="'+selectId+'" aria-label="Filtrar materia"><option value="todas">Todas las materias</option>';
DATA.materias.forEach(m=>{h+='<option value="'+m.id+'"'+(matVal===m.id?' selected':'')+'>'+m.nombre+'</option>';});
h+='</select><div class="filter-buttons">';
h+='<button class="filter-btn'+(fMod==='todas'?' active':'')+'" data-md="todas">Todas</button>';
h+='<button class="filter-btn'+(fMod==='Pre'?' active pre-active':'')+'" data-md="Pre">'+ic('building',13)+' Pre</button>';
h+='<button class="filter-btn'+(fMod==='AMT'?' active amt-active':'')+'" data-md="AMT">'+ic('monitor',13)+' AMT</button>';
h+='</div></div>';return h;
}
/**
 * Enlaza los eventos de los filtros al DOM renderizado.
 * @param {string} selectId - ID del select de materia
 * @param {string} key - Variable global a actualizar
 * @param {Function} renderFn - Función de render a ejecutar tras el cambio
 */
function bindF(selectId,key,renderFn){
const sel=document.getElementById(selectId);
if(sel)sel.addEventListener('change',e=>{window[key]=e.target.value;sv(key,e.target.value);renderFn();});
document.querySelectorAll('#view-'+curView+' .filter-btn[data-md]').forEach(btn=>{
btn.addEventListener('click',()=>{fMod=btn.dataset.md;sv('fMod',fMod);renderFn();});
});
}

// ============================================================
// VISTA: INICIO (Agenda con hero, stats y accordion mensual)
// ============================================================
function rInicio(){
const el=document.getElementById('view-inicio');
const ts=todayStr();
const sessions=getSessions(fMat);
const upcoming=sessions.filter(s=>s.f>=ts).sort((a,b)=>a.f.localeCompare(b.f));
const totalPre=sessions.filter(s=>s.t==='Pre').length;
const totalAMT=sessions.filter(s=>s.t==='AMT').length;
const remainPre=upcoming.filter(s=>s.t==='Pre').length;
const remainAMT=upcoming.filter(s=>s.t==='AMT').length;
const pctPre=sessions.length?Math.round(totalPre/sessions.length*100):0;
const pctAMT=sessions.length?Math.round(totalAMT/sessions.length*100):0;
let nextH='';
if(upcoming.length){const nx=upcoming[0],mat=gM(nx.m),doc=gD(mat.docente_id);
nextH=`<div class="next-class"><div class="lbl">Próxima clase</div><div class="cn">${ic(mat.icon,20)} ${mat.nombre}</div><div class="cm">${ic('calendar',14)} ${fDL(nx.f)} · ${mat.hora}</div><div class="cm">${ic('user',14)} ${doc.nombre}</div><span class="cb">${nx.t==='Pre'?ic('building',12)+' Presencial':ic('monitor',12)+' Virtual'}</span></div>`;}
const stats=`<div class="stats-grid"><div class="stat-box"><div class="value">${upcoming.length}</div><div class="label">Sesiones restantes</div></div><div class="stat-box blue"><div class="value">${sessions.length}</div><div class="label">Total sesiones</div></div><div class="stat-box"><div class="value">${remainPre}</div><div class="label">${ic('building',14)} Presenciales por ir</div></div><div class="stat-box blue"><div class="value">${remainAMT}</div><div class="label">${ic('monitor',14)} Virtuales por ir</div></div></div><div style="background:var(--bg-secondary);border-radius:12px;padding:14px 16px;margin-bottom:20px;box-shadow:var(--card-shadow)"><div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px"><span style="font-size:13px;font-weight:600;color:var(--text-secondary)">Distribución</span><span style="font-size:12px;color:var(--text-tertiary)">${totalPre+totalAMT} sesiones</span></div><div style="display:flex;height:8px;border-radius:4px;overflow:hidden;gap:2px"><div style="flex:${totalPre};background:var(--accent);border-radius:4px"></div><div style="flex:${totalAMT};background:var(--blue);border-radius:4px"></div></div><div style="display:flex;justify-content:space-between;margin-top:8px"><span style="font-size:12px;color:var(--accent);font-weight:600">${ic('building',12)} ${totalPre} Pre (${pctPre}%)</span><span style="font-size:12px;color:var(--blue);font-weight:600">${ic('monitor',12)} ${totalAMT} AMT (${pctAMT}%)</span></div></div>`;
const sorted=sessions.slice().sort((a,b)=>a.f.localeCompare(b.f));
const byM={};sorted.forEach(s=>{const d=toD(s.f);const k=d.getFullYear()+'-'+String(d.getMonth()).padStart(2,'0');if(!byM[k])byM[k]=[];byM[k].push(s);});
const curM=new Date().getFullYear()+'-'+String(new Date().getMonth()).padStart(2,'0');
let listH='';
if(!Object.keys(byM).length){listH='<div class="empty-state"><p>Sin sesiones para este filtro</p></div>';}
else Object.entries(byM).forEach(([k,items])=>{const[y,m]=k.split('-');const mn=MO[parseInt(m)];const isCur=k===curM;const pc=items.filter(s=>s.t==='Pre').length,ac=items.filter(s=>s.t==='AMT').length;
listH+=`<div class="accordion${isCur?' open':''}" data-acc><button class="accordion-header" onclick="toggleAcc(this)"><div class="ah-left"><span class="ml">${mn} ${y}</span><span class="mc">${items.length} · ${pc} pre · ${ac} virt</span></div><span class="accordion-chevron">${ic('chevronRight',16)}</span></button><div class="accordion-body"><div class="accordion-body-inner">`;
items.forEach(s=>{const mat=gM(s.m),d=toD(s.f),doc=gD(mat.docente_id);listH+=`<div class="si"><div class="si-date"><div class="n">${d.getDate()}</div><div class="d">${DS[d.getDay()]}</div></div><div class="si-info"><div class="t">${mat.nombre}</div><div class="s">${mat.hora} · ${doc.nombre.split(' ').slice(0,2).join(' ')}</div></div><span class="badge ${s.t==='Pre'?'pre':'amt'}">${s.t==='Pre'?'Pre':'AMT'}</span></div>`;});
listH+='</div></div></div>';});
el.innerHTML=filtersHTML('fMatInicio',fMat)+nextH+stats+listH;bindF('fMatInicio','fMat',rInicio);
}

// ============================================================
// VISTA: CALENDARIO (Grilla mensual con modal de detalle)
// ============================================================
function rCal(){
const el=document.getElementById('view-calendario');
const fd=new Date(calYear,calMonth,1).getDay(),td=new Date(calYear,calMonth+1,0).getDate();
const tds=todayStr(),tdd=new Date(tds+'T12:00:00');
const sessions=getSessions(fMatCal);
const ms=sessions.filter(s=>{const d=toD(s.f);return d.getMonth()===calMonth&&d.getFullYear()===calYear;});
const sbd={};ms.forEach(s=>{const day=toD(s.f).getDate();if(!sbd[day])sbd[day]=[];sbd[day].push(s);});
let h=filtersHTML('fMatCalSel',fMatCal);
h+=`<div class="cal-nav"><button onclick="pM()" aria-label="Mes anterior">${ic('chevronLeft',18)}</button><span class="month">${MO[calMonth]} ${calYear}</span><button onclick="nM()" aria-label="Mes siguiente">${ic('chevronRight',18)}</button></div>`;
h+=`<div class="cal-header">${DS.map(d=>'<span>'+d+'</span>').join('')}</div><div class="cal-body">`;
for(let i=0;i<fd;i++)h+='<div class="cc empty"></div>';
for(let day=1;day<=td;day++){const isT=day===tdd.getDate()&&calMonth===tdd.getMonth()&&calYear===tdd.getFullYear();const isS=selDay===day;const ss=sbd[day];const cl=ss?'onclick="sD('+day+')"':'';let bc='';if(ss&&!isS&&!isT){const hp=ss.some(s=>s.t==='Pre'),ha=ss.some(s=>s.t==='AMT');bc=hp&&ha?'bm':hp?'bp':'ba';}const cls=[isT?'today':'',isS?'sel':'',ss?'hc':'',bc].filter(Boolean).join(' ');h+=`<div class="cc ${cls}" ${cl}>${day}<div class="dots">${ss?ss.map(s=>'<span class="dot '+(s.t==='Pre'?'pre':'amt')+'"></span>').join(''):''}</div></div>`;}
h+='</div>';el.innerHTML=h;bindF('fMatCalSel','fMatCal',rCal);
}
/** Navega al mes anterior */
function pM(){selDay=null;calMonth--;if(calMonth<0){calMonth=11;calYear--;}sv('calMonth',calMonth);sv('calYear',calYear);rCal();}

/** Navega al mes siguiente */
function nM(){selDay=null;calMonth++;if(calMonth>11){calMonth=0;calYear++;}sv('calMonth',calMonth);sv('calYear',calYear);rCal();}

/**
 * Selecciona un día y abre el modal con el detalle de sesiones.
 * @param {number} day - Número del día seleccionado
 */
function sD(day){selDay=day;rCal();
const sessions=getSessions(fMatCal).filter(s=>{const d=toD(s.f);return d.getDate()===day&&d.getMonth()===calMonth&&d.getFullYear()===calYear;});
if(!sessions.length)return;
const title=`${ic('calendarCheck',18)} ${DS[new Date(calYear,calMonth,day).getDay()]} ${day} de ${MO[calMonth]}`;
let body='';sessions.forEach(s=>{const mat=gM(s.m),doc=gD(mat.docente_id);body+=`<div class="si"><div class="si-info"><div class="t">${ic(mat.icon,16)} ${mat.nombre}</div><div class="s">${ic('clock',12)} ${mat.hora}</div><div class="s">${ic('user',12)} ${doc.nombre}</div></div><span class="badge ${s.t==='Pre'?'pre':'amt'}">${s.t==='Pre'?ic('building',12)+' Presencial':ic('monitor',12)+' Virtual'}</span></div>`;});
document.getElementById('modalTitle').innerHTML=title;document.getElementById('modalBody').innerHTML=body;document.getElementById('modalOverlay').classList.add('open');
}
/** Cierra el modal de detalle del día */
function closeModal(e){if(e&&e.target!==document.getElementById('modalOverlay'))return;document.getElementById('modalOverlay').classList.remove('open');selDay=null;rCal();}

// ============================================================
// VISTA: MATERIAS (Fichas por materia con accordion de sesiones)
// ============================================================
function rMat(){
const el=document.getElementById('view-materias');
const materias=fMatMat==='todas'?DATA.materias:DATA.materias.filter(m=>m.id===fMatMat);
let h=filtersHTML('fMatMatSel',fMatMat);
if(!materias.length){h+='<div class="empty-state"><p>Sin resultados</p></div>';el.innerHTML=h;bindF('fMatMatSel','fMatMat',rMat);return;}
materias.forEach(mat=>{const doc=gD(mat.docente_id);const sessions=DATA.sesiones.filter(s=>s.m===mat.id).filter(s=>fMod==='todas'||s.t===fMod).sort((a,b)=>a.f.localeCompare(b.f));const pc=sessions.filter(s=>s.t==='Pre').length,ac=sessions.filter(s=>s.t==='AMT').length;
const byM={};sessions.forEach(s=>{const d=toD(s.f);const k=d.getFullYear()+'-'+String(d.getMonth()).padStart(2,'0');if(!byM[k])byM[k]=[];byM[k].push(s);});
h+=`<div class="materia-card"><div class="mh"><div class="mi" style="background:${mat.color}20;color:${mat.color}">${ic(mat.icon,20)}</div><div><h3 style="font-size:15px;font-weight:600;line-height:1.3">${mat.nombre}</h3><p style="font-size:13px;color:var(--text-tertiary);margin-top:2px">${doc.nombre}</p></div></div>`;
h+=`<div class="mm"><span class="mc2">${ic('calendar',12)} ${mat.dia}</span><span class="mc2">${ic('clock',12)} ${mat.hora}</span><span class="mc2">${ic('building',12)} ${pc} pre</span><span class="mc2">${ic('monitor',12)} ${ac} virt</span></div>`;
Object.entries(byM).forEach(([k,items])=>{const[y,m2]=k.split('-');const mn=MO[parseInt(m2)];
h+=`<div class="accordion" data-acc style="margin-bottom:8px;box-shadow:none;background:var(--bg-tertiary)"><button class="accordion-header" onclick="toggleAcc(this)" style="padding:10px 12px"><div class="ah-left"><span class="ml" style="font-size:13px">${mn}</span><span class="mc" style="font-size:11px">${items.length} sesiones</span></div><span class="accordion-chevron">${ic('chevronRight',14)}</span></button><div class="accordion-body"><div class="accordion-body-inner" style="padding:10px 12px"><div style="display:flex;flex-wrap:wrap;gap:6px">`;
items.forEach(s=>{h+=`<span class="tc ${s.t==='Pre'?'pre':'amt'}">${s.t==='Pre'?ic('building',11):ic('monitor',11)} ${fD(s.f)}</span>`;});
h+='</div></div></div></div>';});h+='</div>';});
el.innerHTML=h;bindF('fMatMatSel','fMatMat',rMat);
}

// ============================================================
// VISTA: AJUSTES (Tema, info del programa, créditos)
// ============================================================
function rAjustes(){
const el=document.getElementById('view-ajustes');const theme=getTheme();const isDark=theme==='dark'||(theme==='auto'&&matchMedia('(prefers-color-scheme:dark)').matches);
const p=PROGRAMA||{nombre:'',universidad:'',campus:'',semestre:''};
el.innerHTML=`<div class="card-group-title">Apariencia</div><div class="card-group"><div class="sr"><span style="font-size:15px">Modo oscuro</span><button class="toggle${isDark?' on':''}" id="dkTgl" aria-label="Modo oscuro"></button></div></div><div class="card-group-title">Compartir</div><div class="card-group"><div class="sr"><span style="font-size:15px">Compartir app</span><button id="shareBtn" style="padding:8px 16px;border:none;background:var(--accent);color:#fff;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;font-family:inherit">Compartir</button></div></div><div class="card-group-title">Información</div><div class="card-group"><div class="sr"><span>Programa</span><span style="color:var(--text-tertiary);font-size:14px">${p.nombre}</span></div><div class="sr"><span>Semestre</span><span style="color:var(--text-tertiary);font-size:14px">${p.semestre}</span></div><div class="sr"><span>Universidad</span><span style="color:var(--text-tertiary);font-size:14px">${p.universidad}</span></div><div class="sr"><span>Campus</span><span style="color:var(--text-tertiary);font-size:14px">${p.campus}</span></div><div class="sr"><span>Materias</span><span style="color:var(--text-tertiary);font-size:14px">${DATA.materias.length}</span></div><div class="sr"><span>Total sesiones</span><span style="color:var(--text-tertiary);font-size:14px">${DATA.sesiones.length}</span></div></div><div class="card-group-title">Leyenda</div><div class="card-group"><div class="sr"><span>${ic('building',16)} Presencial (Pre)</span><span class="badge pre">Pre</span></div><div class="sr"><span>${ic('monitor',16)} Virtual (AMT)</span><span class="badge amt">AMT</span></div></div><div class="card-group-title">App</div><div class="card-group"><div class="sr"><span>Versión</span><span style="color:var(--text-tertiary);font-size:14px">1.0.0</span></div><div class="sr"><span style="font-size:15px">Buscar actualización</span><button id="clearCacheBtn" style="padding:8px 16px;border:none;background:var(--blue);color:#fff;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;font-family:inherit">Actualizar</button></div></div><div style="text-align:center;padding:32px 16px 16px;color:var(--text-tertiary);font-size:12px;line-height:1.8"><div style="font-weight:600;color:var(--text-secondary)">Desarrollado por Luis Cabezas</div><div>Estudiante</div><div><a href="https://inteligencia.com.co" target="_blank" rel="noopener" style="color:var(--accent);text-decoration:none;font-weight:500">Inteligencia.com.co</a></div></div>`;
document.getElementById('dkTgl').addEventListener('click',function(){applyTheme(this.classList.contains('on')?'light':'dark');rAjustes();});
document.getElementById('shareBtn').addEventListener('click', async function(){
const shareData={title:'Horario UdeA - Pedagogía en Ruralidad y Paz',text:'Consulta el horario de clases de Pedagogía en Ruralidad y Paz - Universidad de Antioquia',url:'https://ryp.inteligencia.com.co'};
if(navigator.share){
try{
// Intentar compartir con imagen adjunta
const res=await fetch('./icons/logo-app.png');
const blob=await res.blob();
const file=new File([blob],'horario-udea.png',{type:'image/png'});
if(navigator.canShare&&navigator.canShare({files:[file]})){
await navigator.share({...shareData,files:[file]});
}else{await navigator.share(shareData);}
}catch(e){/* usuario canceló */}
}else{navigator.clipboard.writeText(shareData.url).then(()=>{this.textContent='¡Copiado!';setTimeout(()=>{this.textContent='Compartir';},2000);});}
});
document.getElementById('clearCacheBtn').addEventListener('click', async function(){
this.textContent='Limpiando...';
try{
if('caches' in window){const keys=await caches.keys();await Promise.all(keys.map(k=>caches.delete(k)));}
if(navigator.serviceWorker){const reg=await navigator.serviceWorker.getRegistration();if(reg)await reg.unregister();}
localStorage.clear();
this.textContent='¡Listo!';
setTimeout(()=>location.reload(true),1000);
}catch(e){this.textContent='Error';setTimeout(()=>{this.textContent='Actualizar';},2000);}
});
}

// ============================================================
// INICIALIZACIÓN
// ============================================================

/**
 * Punto de entrada de la aplicación.
 * Carga los datos desde data.json y renderiza la vista guardada.
 */
async function init() {
  try {
    await loadData();
    switchView(curView);
  } catch (e) {
    console.error('Error cargando datos:', e);
    document.getElementById('view-inicio').innerHTML = '<div class="empty-state"><p>Error cargando datos. Recarga la página.</p></div>';
  }
  if ('serviceWorker' in navigator) { navigator.serviceWorker.register('./sw.js').catch(() => {}); }
}
init();
