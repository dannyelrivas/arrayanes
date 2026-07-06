<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
$user = currentUser();
$meses = MESES;
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Arryanaes – Dashboard</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,400&display=swap" rel="stylesheet">
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  :root {
    --bg:       #0b0e14;
    --surface:  #131720;
    --surface2: #1a2030;
    --border:   #1e2535;
    --accent:   #4f8eff;
    --accent2:  #00d4aa;
    --text:     #e8eaf0;
    --muted:    #6b7592;
    --danger:   #ff4f6a;
    --warn:     #ffb547;
    --success:  #00d4aa;
    --r:        10px;
  }

  html, body { height: 100%; }
  body { font-family: 'DM Sans', sans-serif; background: var(--bg); color: var(--text); display: flex; flex-direction: column; }

  /* ─── HEADER ───────────────────────────────── */
  .header {
    background: var(--surface);
    border-bottom: 1px solid var(--border);
    padding: 0 24px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    position: sticky; top: 0; z-index: 100;
    flex-shrink: 0;
  }

  .logo {
    font-family: 'Syne', sans-serif;
    font-weight: 800;
    font-size: 1.2rem;
    letter-spacing: -0.5px;
    display: flex; align-items: center; gap: 10px;
  }

  .logo-dot {
    width: 28px; height: 28px;
    background: linear-gradient(135deg, var(--accent), var(--accent2));
    border-radius: 8px;
    display: inline-flex; align-items: center; justify-content: center;
    font-size: 14px;
  }

  .header-right { display: flex; align-items: center; gap: 12px; }

  .user-badge {
    display: flex; align-items: center; gap: 8px;
    background: var(--surface2); border: 1px solid var(--border);
    border-radius: 20px; padding: 5px 14px 5px 8px;
    font-size: .82rem;
  }

  .user-avatar {
    width: 26px; height: 26px;
    background: linear-gradient(135deg, var(--accent), var(--accent2));
    border-radius: 50%; display: flex; align-items: center; justify-content: center;
    font-size: 11px; font-weight: 700;
  }

  .role-badge {
    font-size: .68rem; padding: 2px 7px;
    border-radius: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: .06em;
  }
  .role-admin  { background: rgba(79,142,255,.15); color: var(--accent); }
  .role-consulta { background: rgba(0,212,170,.12); color: var(--accent2); }

  .btn-sm {
    background: var(--surface2); border: 1px solid var(--border);
    color: var(--muted); padding: 6px 14px; border-radius: 8px;
    font-family: inherit; font-size: .82rem; cursor: pointer;
    transition: all .15s;
  }
  .btn-sm:hover { border-color: var(--accent); color: var(--accent); }

  /* ─── LAYOUT ────────────────────────────────── */
  .main { flex: 1; padding: 28px 28px 48px; max-width: 1400px; width: 100%; margin: 0 auto; }

  /* ─── STATS CARDS ───────────────────────────── */
  .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; margin-bottom: 28px; }

  .stat-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 14px;
    padding: 20px;
    position: relative; overflow: hidden;
    transition: border-color .2s;
  }
  .stat-card:hover { border-color: var(--accent); }
  .stat-card::after {
    content: '';
    position: absolute; top: 0; right: 0;
    width: 80px; height: 80px;
    border-radius: 50%;
    transform: translate(30%, -30%);
    opacity: .08;
  }
  .stat-card.blue::after { background: var(--accent); }
  .stat-card.green::after { background: var(--accent2); }
  .stat-card.red::after { background: var(--danger); }
  .stat-card.yellow::after { background: var(--warn); }

  .stat-label { font-size: .72rem; text-transform: uppercase; letter-spacing: .1em; color: var(--muted); margin-bottom: 10px; }
  .stat-value { font-family: 'Syne', sans-serif; font-size: 2.2rem; font-weight: 800; line-height: 1; }
  .stat-sub { font-size: .75rem; color: var(--muted); margin-top: 6px; }
  .stat-card.blue .stat-value { color: var(--accent); }
  .stat-card.green .stat-value { color: var(--accent2); }
  .stat-card.red .stat-value { color: var(--danger); }
  .stat-card.yellow .stat-value { color: var(--warn); }

  /* ─── TOOLBAR ───────────────────────────────── */
  .toolbar {
    display: flex; flex-wrap: wrap; gap: 10px;
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 14px;
    padding: 16px;
    margin-bottom: 20px;
    align-items: center;
  }

  .toolbar input, .toolbar select {
    background: var(--bg); border: 1.5px solid var(--border);
    color: var(--text); border-radius: 8px;
    padding: 8px 14px; font-family: inherit; font-size: .87rem;
    outline: none; transition: border-color .2s;
    min-width: 140px;
  }
  .toolbar input:focus, .toolbar select:focus { border-color: var(--accent); }
  .toolbar input::placeholder { color: var(--muted); }

  .btn-primary {
    background: var(--accent); color: #fff;
    border: none; border-radius: 8px; padding: 8px 18px;
    font-family: 'Syne', sans-serif; font-weight: 700; font-size: .87rem;
    cursor: pointer; transition: all .15s;
    white-space: nowrap;
  }
  .btn-primary:hover { background: #6ba3ff; }

  .btn-danger {
    background: rgba(255,79,106,.12); color: var(--danger);
    border: 1px solid rgba(255,79,106,.25); border-radius: 8px; padding: 8px 14px;
    font-family: inherit; font-size: .87rem; cursor: pointer; transition: all .15s;
    white-space: nowrap;
  }
  .btn-danger:hover { background: rgba(255,79,106,.22); }

  .btn-success {
    background: rgba(0,212,170,.12); color: var(--success);
    border: 1px solid rgba(0,212,170,.25); border-radius: 8px; padding: 8px 14px;
    font-family: inherit; font-size: .87rem; cursor: pointer; transition: all .15s;
    white-space: nowrap;
  }
  .btn-success:hover { background: rgba(0,212,170,.22); }

  .spacer { flex: 1; }

  /* ─── TABLE ──────────────────────────────────── */
  .table-wrap {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 14px;
    overflow: hidden;
  }

  table { width: 100%; border-collapse: collapse; }
  thead th {
    background: var(--surface2);
    padding: 12px 16px;
    font-size: .72rem; text-transform: uppercase; letter-spacing: .1em; color: var(--muted);
    font-weight: 600; text-align: left;
    border-bottom: 1px solid var(--border);
    white-space: nowrap;
  }
  tbody tr {
    border-bottom: 1px solid var(--border);
    transition: background .12s;
    cursor: pointer;
  }
  tbody tr:last-child { border-bottom: none; }
  tbody tr:hover { background: var(--surface2); }
  tbody td { padding: 12px 16px; font-size: .88rem; }

  .badge {
    display: inline-flex; align-items: center;
    padding: 3px 10px; border-radius: 20px; font-size: .72rem; font-weight: 600;
    text-transform: uppercase; letter-spacing: .06em; white-space: nowrap;
    gap: 5px;
  }
  .badge::before { content: ''; width: 6px; height: 6px; border-radius: 50%; background: currentColor; }

  .badge-activo  { background: rgba(0,212,170,.12); color: var(--success); }
  .badge-moroso  { background: rgba(255,79,106,.12); color: var(--danger); }
  .badge-pagado  { background: rgba(79,142,255,.12); color: var(--accent); }
  .badge-nopagado{ background: rgba(255,181,71,.12); color: var(--warn); }
  .badge-inactivo { background: rgba(107,117,146,.15); color: var(--muted); }

  .name-cell strong { display: block; font-size: .9rem; }
  .name-cell span   { display: block; font-size: .76rem; color: var(--muted); }

  .tag-count { display: inline-flex; align-items: center; gap: 4px; font-size: .8rem; color: var(--muted); }

  /* ─── PAGINATION ─────────────────────────────── */
  .pagination { display: flex; justify-content: center; gap: 6px; padding: 16px; }
  .page-btn {
    background: var(--surface2); border: 1px solid var(--border);
    color: var(--muted); width: 34px; height: 34px; border-radius: 8px;
    font-family: inherit; cursor: pointer; transition: all .15s;
    display: flex; align-items: center; justify-content: center; font-size: .85rem;
  }
  .page-btn:hover, .page-btn.active { background: var(--accent); border-color: var(--accent); color: #fff; }
  .page-info { font-size: .8rem; color: var(--muted); display: flex; align-items: center; padding: 0 8px; }

  /* ─── MODAL ──────────────────────────────────── */
  .modal-backdrop {
    position: fixed; inset: 0; background: rgba(0,0,0,.7);
    display: none; align-items: center; justify-content: center; z-index: 200;
    padding: 20px;
  }
  .modal-backdrop.open { display: flex; }

  .modal {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 18px;
    width: 100%; max-width: 680px;
    max-height: 90vh; overflow-y: auto;
    box-shadow: 0 40px 80px rgba(0,0,0,.5);
    animation: slideUp .2s ease;
  }

  @keyframes slideUp {
    from { transform: translateY(20px); opacity: 0; }
    to   { transform: translateY(0);    opacity: 1; }
  }

  .modal-header {
    padding: 24px 28px 0;
    display: flex; justify-content: space-between; align-items: flex-start;
  }
  .modal-title { font-family: 'Syne', sans-serif; font-size: 1.25rem; font-weight: 700; }
  .modal-sub { font-size: .83rem; color: var(--muted); margin-top: 2px; }
  .btn-close {
    background: var(--surface2); border: 1px solid var(--border);
    color: var(--muted); width: 30px; height: 30px; border-radius: 8px;
    cursor: pointer; font-size: 1.1rem; display: flex; align-items: center; justify-content: center;
  }
  .btn-close:hover { color: var(--text); }

  .modal-body { padding: 20px 28px 28px; }

  .modal-section { margin-bottom: 24px; }
  .modal-section h3 {
    font-family: 'Syne', sans-serif; font-size: .75rem; text-transform: uppercase;
    letter-spacing: .1em; color: var(--muted); margin-bottom: 12px;
    padding-bottom: 8px; border-bottom: 1px solid var(--border);
  }

  .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
  .info-item label { display: block; font-size: .72rem; color: var(--muted); margin-bottom: 3px; }
  .info-item span { font-size: .9rem; }

  .tags-list { display: flex; flex-direction: column; gap: 8px; }
  .tag-row {
    display: flex; align-items: center; justify-content: space-between;
    background: var(--surface2); border: 1px solid var(--border);
    border-radius: 8px; padding: 10px 14px;
  }
  .tag-num { font-family: 'Syne', sans-serif; font-weight: 700; font-size: .9rem; }
  .tag-fc  { font-size: .75rem; color: var(--muted); }

  .pago-form { display: flex; flex-direction: column; gap: 14px; }
  .pago-row { display: flex; gap: 10px; flex-wrap: wrap; }

  .form-field { flex: 1; min-width: 120px; }
  .form-field label { display: block; font-size: .72rem; color: var(--muted); margin-bottom: 6px; text-transform: uppercase; letter-spacing: .07em; }
  .form-field input, .form-field select {
    width: 100%; background: var(--bg); border: 1.5px solid var(--border);
    color: var(--text); border-radius: 8px; padding: 9px 12px;
    font-family: inherit; font-size: .88rem; outline: none; transition: border-color .2s;
  }
  .form-field input:focus, .form-field select:focus { border-color: var(--accent); }

  .pagos-hist { display: flex; flex-direction: column; gap: 6px; max-height: 200px; overflow-y: auto; }
  .pago-hist-row {
    display: flex; align-items: center; justify-content: space-between;
    font-size: .83rem; padding: 6px 10px; border-radius: 6px; background: var(--surface2);
  }
  .pago-hist-row.pagado { border-left: 2px solid var(--success); }
  .pago-hist-row.nopagado { border-left: 2px solid var(--danger); }

  /* ─── TOAST ─────────────────────────────────── */
  .toast-wrap { position: fixed; bottom: 24px; right: 24px; z-index: 500; display: flex; flex-direction: column; gap: 8px; }
  .toast {
    background: var(--surface); border: 1px solid var(--border);
    border-radius: 10px; padding: 12px 18px;
    font-size: .85rem; box-shadow: 0 8px 24px rgba(0,0,0,.3);
    animation: fadeIn .2s ease;
    display: flex; align-items: center; gap: 10px; min-width: 240px;
  }
  .toast.success { border-color: rgba(0,212,170,.3); }
  .toast.error   { border-color: rgba(255,79,106,.3); }
  @keyframes fadeIn { from { opacity: 0; transform: translateY(8px); } }

  .loading { text-align: center; padding: 60px 20px; color: var(--muted); }
  .spinner {
    width: 32px; height: 32px;
    border: 3px solid var(--border); border-top-color: var(--accent);
    border-radius: 50%; animation: spin .6s linear infinite; margin: 0 auto 16px;
  }
  @keyframes spin { to { transform: rotate(360deg); } }

  .empty { text-align: center; padding: 60px 20px; color: var(--muted); font-size: .9rem; }

  @media (max-width: 600px) {
    .main { padding: 16px; }
    .stats-grid { grid-template-columns: 1fr 1fr; }
    .info-grid { grid-template-columns: 1fr; }
    .toolbar { flex-direction: column; }
    .toolbar input, .toolbar select { min-width: 100%; }
  }
</style>
</head>
<body>

<!-- HEADER -->
<header class="header">
  <div class="logo">
    <span class="logo-dot">🏘️</span>
    Arryanaes
  </div>
  <div class="header-right">
    <div class="user-badge">
      <div class="user-avatar"><?= strtoupper(substr($user['nombre'], 0, 1)) ?></div>
      <?= htmlspecialchars($user['nombre']) ?>
      <span class="role-badge role-<?= $user['rol'] ?>"><?= $user['rol'] ?></span>
    </div>
    <button class="btn-sm" onclick="doLogout()">Salir</button>
  </div>
</header>

<!-- MAIN -->
<main class="main">

  <!-- STATS -->
  <div class="stats-grid" id="statsGrid">
    <div class="stat-card blue"><div class="stat-label">Total Residentes</div><div class="stat-value" id="statTotal">–</div><div class="stat-sub">Registrados</div></div>
    <div class="stat-card green"><div class="stat-label">Al Corriente</div><div class="stat-value" id="statActivos">–</div><div class="stat-sub">Sin adeudo</div></div>
    <div class="stat-card red"><div class="stat-label">Morosos</div><div class="stat-value" id="statMorosos">–</div><div class="stat-sub">Con adeudo</div></div>
    <div class="stat-card"><div class="stat-label">Inactivos</div><div class="stat-value" id="statInactivos">–</div><div class="stat-sub">Sin registro activo</div></div>
    <div class="stat-card yellow"><div class="stat-label">Pagaron Este Mes</div><div class="stat-value" id="statPagaronMes">–</div><div class="stat-sub" id="statMesLabel">–</div></div>
    <div class="stat-card blue"><div class="stat-label">Total Tags</div><div class="stat-value" id="statTags">–</div><div class="stat-sub" id="statTagsMorosos">–</div></div>
    <!-- Después de la tarjeta de Tags -->
    <div class="stat-card green">
      <div class="stat-label">Pagos Adelantados</div>
      <div class="stat-value" id="statAdelantados">–</div>
      <div class="stat-sub">Residentes con meses futuros</div>
    </div>
  </div>

  <!-- TOOLBAR -->
  <div class="toolbar">
    <input type="text" id="filtroNombre" placeholder="🔍 Buscar por nombre..." oninput="buscarDebounce()">
    <select id="filtroCalle" onchange="buscar()">
      <option value="">Todas las calles</option>
    </select>
    <select id="filtroEstatus" onchange="buscar()">
      <option value="">Todos los estatus</option>
      <option value="ACTIVO">✅ Activos</option>
      <option value="MOROSO">🔴 Morosos</option>
      <option value="INACTIVO">⚫ Inactivos</option>
    </select>
    <input type="text" id="filtroTag" placeholder="# Número de tag" oninput="buscarDebounce()">
    <div class="spacer"></div>
    <?php if ($user['rol'] === 'admin'): ?>
    <button class="btn-danger" onclick="procesarMorosos()">⚠️ Proceso Morosos</button>
    <button class="btn-primary" onclick="abrirNuevoResidente()">+ Nuevo Residente</button>
    <?php endif; ?>
  </div>

  <!-- TABLE -->
  <div class="table-wrap">
    <div id="tableContent">
      <div class="loading"><div class="spinner"></div>Cargando residentes...</div>
    </div>
    <div class="pagination" id="pagination"></div>
  </div>
</main>

<!-- MODAL DETALLE RESIDENTE -->
<div class="modal-backdrop" id="modalDetalle" onclick="cerrarModal(event)">
  <div class="modal">
    <div class="modal-header">
      <div>
        <div class="modal-title" id="modalNombre">—</div>
        <div class="modal-sub" id="modalDireccion">—</div>
      </div>
      <button class="btn-close" onclick="document.getElementById('modalDetalle').classList.remove('open')">✕</button>
    </div>
    <div class="modal-body">

      <div class="modal-section">
        <h3>Información del residente</h3>
        <div class="info-grid">
          <div class="info-item"><label>Calle</label><span id="dCalle">—</span></div>
          <div class="info-item"><label>Núm Ext</label><span id="dExt">—</span></div>
          <div class="info-item"><label>Núm Int</label><span id="dInt">—</span></div>
          <div class="info-item"><label>Identificación</label><span id="dIdent">—</span></div>
        </div>
        <div style="margin-top:10px" id="dComentario"></div>
      </div>

      <div class="modal-section">
        <h3>Tags / Tarjetas de acceso</h3>
        <div class="tags-list" id="modalTags"></div>
      </div>

      <?php if ($user['rol'] === 'admin'): ?>
      <div class="modal-section" id="seccionPago">
        <h3>Registrar pago</h3>
        <div class="pago-form">
          <div class="pago-row">
            <div class="form-field">
              <label>Mes</label>
              <select id="pagoMes"></select>
            </div>
            <div class="form-field">
              <label>Año</label>
              <select id="pagoAnio"></select>
            </div>
            <div class="form-field">
              <label>Monto ($)</label>
              <input type="number" id="pagoMonto" placeholder="0.00" step="0.01" min="0">
            </div>
            <div class="form-field">
              <label>Método</label>
              <select id="pagoMetodo">
                <option value="EFECTIVO">Efectivo</option>
                <option value="TRANSFERENCIA">Transferencia</option>
                <option value="CHEQUE">Cheque</option>
                <option value="OTRO">Otro</option>
              </select>
            </div>
          </div>
          <div class="pago-row">
            <div class="form-field" style="flex:2">
              <label>Referencia / Nota</label>
              <input type="text" id="pagoRef" placeholder="Número de referencia o nota...">
            </div>
            <div class="form-field" style="flex:1; display:flex; align-items:flex-end;">
              <button class="btn-primary" style="width:100%" onclick="registrarPago()">✓ Registrar pago</button>
            </div>
          </div>
          <div style="display:flex; gap:10px; flex-wrap:wrap;">
            <button class="btn-danger" style="font-size:.82rem; padding:7px 14px" onclick="marcarMoroso()">🔴 Marcar como Moroso</button>
            <button class="btn-sm" style="font-size:.82rem; padding:7px 14px" onclick="marcarInactivo()">⚫ Marcar como Inactivo</button>
          </div>
        </div>
      </div>
      <?php endif; ?>

      <div class="modal-section">
        <h3>Historial de pagos</h3>
        <div class="pagos-hist" id="modalPagosHist"></div>
      </div>
    </div>
  </div>
</div>

<!-- TOASTS -->
<div class="toast-wrap" id="toastWrap"></div>

<script>
const IS_ADMIN = <?= $user['rol'] === 'admin' ? 'true' : 'false' ?>;
const MESES = <?= json_encode($meses) ?>;
let currentPage = 1;
let currentResidenteId = null;
let buscarTimer = null;

// ─── INIT ─────────────────────────────────────────────
window.addEventListener('DOMContentLoaded', () => {
  cargarCalles();
  cargarStats();
  buscar();
  if (IS_ADMIN) initPagoForm();
});

function initPagoForm() {
  const now = new Date();
  const selMes = document.getElementById('pagoMes');
  const selAnio = document.getElementById('pagoAnio');
  for (let m = 1; m <= 12; m++) {
    const o = document.createElement('option');
    o.value = m; o.textContent = MESES[m];
    if (m === now.getMonth()+1) o.selected = true;
    selMes.appendChild(o);
  }
  for (let y = now.getFullYear(); y >= now.getFullYear()-2; y--) {
    const o = document.createElement('option');
    o.value = y; o.textContent = y;
    selAnio.appendChild(o);
  }
}

// ─── STATS ────────────────────────────────────────────
async function cargarStats() {
  const data = await api('stats_dashboard');
  if (!data.success) return;
  document.getElementById('statTotal').textContent       = data.total;
  document.getElementById('statActivos').textContent     = data.activos;
  document.getElementById('statMorosos').textContent     = data.morosos;
  document.getElementById('statPagaronMes').textContent  = data.pagaron_mes;
  document.getElementById('statMesLabel').textContent    = MESES[data.mes_actual] + ' ' + data.anio_actual;
  document.getElementById('statTags').textContent        = data.total_tags;
  document.getElementById('statTagsMorosos').textContent = data.tags_morosos + ' morosos';
  document.getElementById('statAdelantados').textContent = data.adelantados;
  document.getElementById('statInactivos').textContent = data.inactivos;
}

// ─── CALLES ───────────────────────────────────────────
async function cargarCalles() {
  const data = await api('calles');
  if (!data.success) return;
  const sel = document.getElementById('filtroCalle');
  data.calles.forEach(c => {
    const o = document.createElement('option');
    o.value = c; o.textContent = c;
    sel.appendChild(o);
  });
}

// ─── Inactivar ───────────────────────────────────────────
async function marcarInactivo() {
  if (!currentResidenteId || !confirm('¿Marcar este residente como inactivo?')) return;
  const data = await api('marcar_inactivo', {}, 'POST', {residente_id: currentResidenteId});
  if (data.success) {
    toast('⚫ Residente marcado como inactivo', 'success');
    verResidente(currentResidenteId);
    cargarStats();
    buscar(currentPage);
  }
}

// ─── BUSCAR ───────────────────────────────────────────
function buscarDebounce() {
  clearTimeout(buscarTimer);
  buscarTimer = setTimeout(buscar, 350);
}

async function buscar(page = 1) {
  currentPage = page;
  const params = new URLSearchParams({
    action:  'listar_residentes',
    nombre:  document.getElementById('filtroNombre').value,
    calle:   document.getElementById('filtroCalle').value,
    estatus: document.getElementById('filtroEstatus').value,
    tag:     document.getElementById('filtroTag').value,
    page:    page
  });
  document.getElementById('tableContent').innerHTML = '<div class="loading"><div class="spinner"></div>Cargando...</div>';
  const data = await fetch('/arrayanes/public/api.php?' + params).then(r => r.json());
  renderTabla(data);
}

function renderTabla(data) {
  if (!data.success || !data.data.length) {
    document.getElementById('tableContent').innerHTML = '<div class="empty">Sin resultados</div>';
    document.getElementById('pagination').innerHTML = '';
    return;
  }

  const rows = data.data.map(r => {
    const estatus = r.tags_morosos > 0 ? 'moroso' 
              : r.tags_activos > 0  ? 'activo' 
              : 'inactivo';
    const eLabel  = estatus === 'moroso'   ? 'MOROSO' 
              : estatus === 'inactivo' ? 'INACTIVO' 
              : 'ACTIVO';
    const pmActual = parseInt(r.pago_mes_actual);
    const pmAnterior = parseInt(r.pago_mes_anterior);
    const pagoMes = pmActual === 1 ? '<span class="badge badge-pagado">Pagado</span>' : '<span class="badge badge-nopagado">Pendiente</span>';
    const pagoAnt = pmAnterior === 1 ? '✅' : pmAnterior === 0 ? '🔴' : '–';
    const adelanto = r.meses_adelantados > 0 
      ? `<span class="badge badge-activo">+${r.meses_adelantados} mes${r.meses_adelantados>1?'es':''}</span>` 
      : '';
    return `
      <tr onclick="verResidente(${r.id})">
        <td style="font-family:'Syne',sans-serif;font-weight:700;color:var(--accent)">${r.id}</td>
        <td><div class="name-cell"><strong>${esc(r.nombre)} ${esc(r.apellidos||'')}</strong><span>${esc(r.identificacion||'')}</span></div></td>
        <td>${esc(r.calle)} ${esc(r.numero_ext||'')}${r.numero_int ? '-'+esc(r.numero_int) : ''}</td>
        <td><span class="badge badge-${estatus}">${eLabel}</span></td>
        <td>${pagoMes} ${adelanto}</td>
        <td style="text-align:center">${pagoAnt}</td>
        <td><span class="tag-count">🏷️ ${r.total_tags}</span></td>
      </tr>`;
  }).join('');

  document.getElementById('tableContent').innerHTML = `
    <table>
      <thead>
        <tr>
          <th>#Tag</th>
          <th>Residente</th>
          <th>Dirección</th>
          <th>Estatus</th>
          <th>Mes actual</th>
          <th>Mes anterior</th>
          <th>Tags</th>
        </tr>
      </thead>
      <tbody>${rows}</tbody>
    </table>`;

  renderPaginacion(data);
}

function renderPaginacion(data) {
  const p = document.getElementById('pagination');
  if (data.pages <= 1) { p.innerHTML = ''; return; }
  let html = '';
  if (data.page > 1) html += `<button class="page-btn" onclick="buscar(${data.page-1})">‹</button>`;
  for (let i = Math.max(1, data.page-2); i <= Math.min(data.pages, data.page+2); i++) {
    html += `<button class="page-btn${i===data.page?' active':''}" onclick="buscar(${i})">${i}</button>`;
  }
  if (data.page < data.pages) html += `<button class="page-btn" onclick="buscar(${data.page+1})">›</button>`;
  html += `<span class="page-info">${data.total} residentes</span>`;
  p.innerHTML = html;
}

// ─── MODAL RESIDENTE ──────────────────────────────────
async function verResidente(id) {
  currentResidenteId = id;
  document.getElementById('modalDetalle').classList.add('open');
  document.getElementById('modalNombre').textContent    = 'Cargando...';
  document.getElementById('modalDireccion').textContent = '';
  document.getElementById('modalTags').innerHTML        = '<div style="color:var(--muted);font-size:.85rem">Cargando...</div>';
  document.getElementById('modalPagosHist').innerHTML   = '';

  const data = await api('detalle_residente', {id});
  if (!data.success) return;
  const r = data.residente;
  const nombreCompleto = `${r.nombre} ${r.apellidos||''}`.trim();
  document.getElementById('modalNombre').textContent    = nombreCompleto;
  document.getElementById('modalDireccion').textContent = `${r.calle} ${r.numero_ext||''}${r.numero_int?'-'+r.numero_int:''}`;
  document.getElementById('dCalle').textContent = r.calle;
  document.getElementById('dExt').textContent   = r.numero_ext || '—';
  document.getElementById('dInt').textContent   = r.numero_int || '—';
  document.getElementById('dIdent').textContent = r.identificacion || '—';
  document.getElementById('dComentario').textContent = r.comentario ? '💬 ' + r.comentario : '';

  // Mostrar meses adelantados si tiene
  const subEl = document.getElementById('modalDireccion');
  if (data.meses_adelantados > 0) {
    subEl.innerHTML = `${esc(r.calle)} ${esc(r.numero_ext||'')}${r.numero_int?'-'+esc(r.numero_int):''} 
      <span class="badge badge-activo" style="margin-left:8px">
        ⏩ ${data.meses_adelantados} mes${data.meses_adelantados > 1 ? 'es' : ''} adelantado${data.meses_adelantados > 1 ? 's' : ''}
      </span>`;
  }

  // Tags
  const tagsEl = document.getElementById('modalTags');
  if (!data.tags.length) {
    tagsEl.innerHTML = '<div style="color:var(--muted);font-size:.85rem">Sin tags registrados</div>';
  } else {
    tagsEl.innerHTML = data.tags.map(t => `
      <div class="tag-row">
        <div>
          <div class="tag-num"># ${esc(t.numero_tag)}</div>
          <div class="tag-fc">FC: ${esc(t.facility_code||'—')} · Grupo: ${esc(t.access_group||'—')}</div>
        </div>
        <span class="badge badge-${t.estatus.toLowerCase()}">${t.estatus}</span>
      </div>`).join('');
  }

  // Historial pagos
  const histEl = document.getElementById('modalPagosHist');
  if (!data.pagos.length) {
    histEl.innerHTML = '<div style="color:var(--muted);font-size:.83rem">Sin historial de pagos</div>';
  } else {
    const ahora = new Date();
    histEl.innerHTML = data.pagos.map(p => {
      const esFuturo = parseInt(p.anio) > ahora.getFullYear() || 
                       (parseInt(p.anio) === ahora.getFullYear() && parseInt(p.mes) > ahora.getMonth() + 1);
      const labelMes = MESES[parseInt(p.mes)] + ' ' + p.anio + (esFuturo ? ' 📅' : '');
      const labelPago = p.pagado == 1 
        ? (esFuturo ? '⏩ Adelantado' : '✅ Pagado') 
        : '🔴 No pagado';
      const clase = p.pagado == 1 ? 'pagado' : 'nopagado';
      return `
        <div class="pago-hist-row ${clase}">
          <span>${labelMes}</span>
          <span>${labelPago}</span>
          <span style="color:var(--muted);font-size:.75rem">${p.metodo_pago||''} ${p.monto > 0 ? '$' + parseFloat(p.monto).toFixed(2) : ''}</span>
        </div>`;
    }).join('');
  }
}
function cerrarModal(e) {
  if (e.target === document.getElementById('modalDetalle'))
    document.getElementById('modalDetalle').classList.remove('open');
}

// ─── PAGO ─────────────────────────────────────────────
async function registrarPago() {
  if (!currentResidenteId) return;
  const body = {
    residente_id: currentResidenteId,
    mes:    document.getElementById('pagoMes').value,
    anio:   document.getElementById('pagoAnio').value,
    monto:  document.getElementById('pagoMonto').value || 0,
    metodo: document.getElementById('pagoMetodo').value,
    referencia: document.getElementById('pagoRef').value,
    pagado: 1
  };
  const data = await api('registrar_pago', {}, 'POST', body);
  if (data.success) {
    toast('✅ Pago registrado correctamente', 'success');
    verResidente(currentResidenteId);
    cargarStats();
    buscar(currentPage);
  } else {
    toast('❌ Error: ' + (data.error || 'No se pudo registrar'), 'error');
  }
}

async function marcarMoroso() {
  if (!currentResidenteId || !confirm('¿Marcar este residente como moroso?')) return;
  const data = await api('marcar_moroso', {}, 'POST', {residente_id: currentResidenteId});
  if (data.success) {
    toast('🔴 Residente marcado como moroso', 'success');
    verResidente(currentResidenteId);
    cargarStats();
    buscar(currentPage);
  }
}

async function procesarMorosos() {
  if (!confirm('¿Ejecutar proceso de morosos?\n\nEsto marcará como MOROSO a todos los residentes que no pagaron el mes anterior.')) return;
  const data = await api('proceso_morosos', {}, 'POST', {});
  if (data.success) {
    toast(`⚠️ Se marcaron ${data.marcados} residentes como morosos`, 'success');
    cargarStats();
    buscar(currentPage);
  }
}

// ─── UTILS ────────────────────────────────────────────
async function api(action, params = {}, method = 'GET', body = null) {
  const qs = new URLSearchParams({action, ...params});
  const opts = {method, headers: {'Content-Type': 'application/json'}};
  if (body) opts.body = JSON.stringify(body);
  const url = method === 'GET' ? `/arrayanes/public/api.php?${qs}` : `/arrayanes/public/api.php?action=${action}`;
  try {
    return await fetch(url, opts).then(r => r.json());
  } catch(e) { return {error: e.message}; }
}

function esc(s) {
  if (!s) return '';
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

function toast(msg, type = 'success') {
  const wrap = document.getElementById('toastWrap');
  const el = document.createElement('div');
  el.className = `toast ${type}`;
  el.textContent = msg;
  wrap.appendChild(el);
  setTimeout(() => el.remove(), 3500);
}

async function doLogout() {
  await fetch('/arrayanes/public/api.php?action=logout');
  window.location.href = '/arrayanes/index.php';
}

function abrirNuevoResidente() {
  toast('💡 Función en desarrollo. Usa el importador SQL para agregar residentes masivamente.', 'success');
}
</script>
</body>
</html>
