
<?php
// ══════════════════════════════════════════════════════════
//  CONFIGURACIÓN FIREBASE — oculta en el servidor PHP
// ══════════════════════════════════════════════════════════
define('FB_PROJECT_ID', 'mir4ndadelebroo22');
define('FB_API_KEY',    'AIzaSyCev9O13tkZQbNtq53LDnOnVYtYpr5wLqI');
define('FB_URL',        'https://firestore.googleapis.com/v1/projects/' . FB_PROJECT_ID . '/databases/(default)/documents/noticias');

// ══════════════════════════════════════════════════════════
//  ENDPOINT AJAX — si llega ?ajax=1 respondemos JSON y salimos
// ══════════════════════════════════════════════════════════
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json; charset=utf-8');

    $id = trim($_GET['id'] ?? '');
    $url = $id !== ''
        ? FB_URL . '/' . urlencode($id) . '?key=' . FB_API_KEY
        : FB_URL . '?pageSize=50&key=' . FB_API_KEY;

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_HTTPHEADER     => ['Accept: application/json'],
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);

    if ($err)       { http_response_code(502); echo json_encode(['error' => $err]); exit; }
    if ($code !== 200) { http_response_code($code); echo $body; exit; }

    $data = json_decode($body, true);

    function fs_to_php(array $doc): array {
        $out = ['id' => basename($doc['name'] ?? '')];
        foreach ($doc['fields'] ?? [] as $k => $v) {
            if      (isset($v['stringValue']))    $out[$k] = $v['stringValue'];
            elseif  (isset($v['timestampValue'])) $out[$k] = $v['timestampValue'];
            elseif  (isset($v['integerValue']))   $out[$k] = (int)$v['integerValue'];
            elseif  (isset($v['booleanValue']))   $out[$k] = (bool)$v['booleanValue'];
            else                                   $out[$k] = null;
        }
        return $out;
    }

    if ($id !== '') {
        echo json_encode(fs_to_php($data));
    } else {
        $noticias = array_map('fs_to_php', $data['documents'] ?? []);
        usort($noticias, fn($a,$b) => strcmp($b['fecha'] ?? '', $a['fecha'] ?? ''));
        echo json_encode(['noticias' => $noticias]);
    }
    exit;
}
// ══════════════════════════════════════════════════════════
//  HTML — el resto del archivo es la página pública
// ══════════════════════════════════════════════════════════
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Noticias — Ayuntamiento de Miranda de Ebro</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Source+Sans+3:wght@300;400;600&display=swap" rel="stylesheet">
<style>
  :root {
    --azul:       #1a3a6b;
    --azul-m:     #2c5282;
    --azul-c:     #3b82c4;
    --oro:        #c8a84b;
    --oro-c:      #e8c96a;
    --blanco:     #f9f8f5;
    --gris-c:     #eef0f4;
    --gris-m:     #b0b8c8;
    --texto:      #1e2536;
    --texto-s:    #4a5568;
  }
  * { margin:0; padding:0; box-sizing:border-box; }
  body { font-family:'Source Sans 3',sans-serif; background:var(--blanco); color:var(--texto); min-height:100vh; display:flex; flex-direction:column; }

  /* CABECERA */
  header { background:var(--azul); border-bottom:3px solid var(--oro); }
  .h-inner { max-width:1200px; margin:0 auto; padding:0 24px; height:70px; display:flex; align-items:center; gap:14px; }
  .h-inner img { height:50px; width:auto; }
  .h-nombre { font-family:'Playfair Display',serif; color:white; font-size:1.1rem; font-weight:600; line-height:1.2; }
  .h-nombre small { display:block; font-family:'Source Sans 3',sans-serif; font-size:0.68rem; font-weight:300; letter-spacing:.1em; text-transform:uppercase; color:#9ab3d4; margin-top:2px; }
  .btn-inicio { margin-left:auto; color:#9ab3d4; font-size:.80rem; text-decoration:none; display:flex; align-items:center; gap:6px; border:1px solid rgba(255,255,255,.2); padding:6px 14px; transition:all .2s; }
  .btn-inicio:hover { color:white; border-color:var(--oro); }

  /* MIGAS */
  .miga { background:var(--gris-c); border-bottom:1px solid #dde2ec; padding:10px 24px; font-size:.78rem; color:var(--texto-s); }
  .miga a { color:var(--azul-c); text-decoration:none; }
  .miga a:hover { text-decoration:underline; }
  .miga .sep { margin:0 6px; }

  main { flex:1; }

  /* ════ VISTA LISTA ════ */
  .lista-hero { background:linear-gradient(135deg,var(--azul) 0%,var(--azul-m) 100%); padding:48px 24px 40px; position:relative; overflow:hidden; }
  .lista-hero::before { content:''; position:absolute; right:-60px; top:-60px; width:320px; height:320px; border:1px solid rgba(200,168,75,.15); border-radius:50%; }
  .lista-hero-inner { max-width:1200px; margin:0 auto; position:relative; }
  .etiqueta { display:inline-block; background:var(--oro); color:var(--azul); font-size:.68rem; font-weight:700; letter-spacing:.14em; text-transform:uppercase; padding:4px 12px; margin-bottom:14px; }
  .lista-hero h1 { font-family:'Playfair Display',serif; font-size:2.4rem; color:white; margin-bottom:8px; }
  .lista-hero p { color:#8ab0d4; font-size:.95rem; }

  .buscar-wrap { max-width:1200px; margin:0 auto; padding:28px 24px 0; display:flex; gap:10px; }
  #buscador { flex:1; border:2px solid #dde2ec; padding:10px 16px; font-family:'Source Sans 3',sans-serif; font-size:.90rem; outline:none; transition:border-color .2s; background:white; }
  #buscador:focus { border-color:var(--azul-c); }
  .btn-buscar { background:var(--azul); color:white; border:none; padding:10px 20px; font-family:'Source Sans 3',sans-serif; font-size:.85rem; font-weight:600; cursor:pointer; transition:background .2s; white-space:nowrap; }
  .btn-buscar:hover { background:var(--azul-c); }

  .noticias-wrap { max-width:1200px; margin:0 auto; padding:28px 24px 52px; }
  .contador { font-size:.80rem; color:var(--texto-s); margin-bottom:18px; padding-bottom:12px; border-bottom:1px solid var(--gris-c); }
  .grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(300px,1fr)); gap:20px; }

  .card { background:white; border:1px solid #dde2ec; overflow:hidden; cursor:pointer; transition:box-shadow .2s,transform .2s; display:flex; flex-direction:column; }
  .card:hover { box-shadow:0 8px 28px rgba(26,58,107,.13); transform:translateY(-3px); }
  .card-img { height:190px; overflow:hidden; background:var(--gris-c); flex-shrink:0; }
  .card-img img { width:100%; height:100%; object-fit:cover; transition:transform .3s; display:block; }
  .card:hover .card-img img { transform:scale(1.04); }
  .card-img .no-img { width:100%; height:100%; display:flex; align-items:center; justify-content:center; font-size:3rem; color:var(--gris-m); background:linear-gradient(135deg,#e8edf5,#d8e0ee); }
  .card-body { padding:18px; flex:1; display:flex; flex-direction:column; }
  .card-meta { display:flex; justify-content:space-between; margin-bottom:10px; }
  .card-autor { font-size:.70rem; font-weight:700; letter-spacing:.08em; text-transform:uppercase; color:var(--azul-c); }
  .card-fecha { font-size:.72rem; color:var(--gris-m); }
  .card-titulo { font-family:'Playfair Display',serif; font-size:1.05rem; font-weight:600; line-height:1.35; margin-bottom:10px; flex:1; }
  .card-preview { font-size:.83rem; color:var(--texto-s); line-height:1.6; display:-webkit-box; -webkit-line-clamp:3; -webkit-box-orient:vertical; overflow:hidden; }
  .card-leer { margin-top:14px; font-size:.78rem; font-weight:700; letter-spacing:.06em; text-transform:uppercase; color:var(--azul-c); }
  .card:hover .card-leer { color:var(--oro); }

  /* ════ VISTA DETALLE ════ */
  #v-detalle { display:none; }
  .det-wrap { max-width:860px; margin:0 auto; padding:40px 24px 64px; }
  .btn-volver { display:inline-flex; align-items:center; gap:8px; color:var(--azul-c); font-size:.82rem; font-weight:600; cursor:pointer; background:none; border:none; margin-bottom:28px; letter-spacing:.04em; text-transform:uppercase; font-family:'Source Sans 3',sans-serif; }
  .btn-volver:hover { color:var(--azul); }
  .det-meta { display:flex; align-items:center; gap:14px; margin-bottom:14px; flex-wrap:wrap; }
  .det-autor { font-size:.72rem; font-weight:700; letter-spacing:.10em; text-transform:uppercase; color:white; background:var(--azul-c); padding:3px 10px; }
  .det-fecha { font-size:.80rem; color:var(--texto-s); }
  .det-titulo { font-family:'Playfair Display',serif; font-size:2.2rem; font-weight:700; line-height:1.2; color:var(--azul); margin-bottom:20px; }
  .det-img { margin-bottom:28px; border:1px solid #dde2ec; overflow:hidden; max-height:460px; }
  .det-img img { width:100%; object-fit:cover; display:block; }
  .det-cuerpo { font-size:1.02rem; line-height:1.85; }
  .det-cuerpo p { margin-bottom:1.2em; }
  hr.sep { border:none; border-top:2px solid var(--gris-c); margin:36px 0; }
  .compartir { display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
  .compartir span { font-size:.78rem; font-weight:700; letter-spacing:.08em; text-transform:uppercase; color:var(--texto-s); }
  .btn-sh { background:var(--gris-c); border:1px solid #dde2ec; padding:6px 14px; font-size:.78rem; font-weight:600; cursor:pointer; color:var(--texto); transition:all .2s; font-family:'Source Sans 3',sans-serif; }
  .btn-sh:hover { background:var(--azul); color:white; border-color:var(--azul); }

  /* ESTADOS */
  .estado { text-align:center; padding:64px 24px; color:var(--texto-s); grid-column:1/-1; }
  .spinner { width:36px; height:36px; border:3px solid var(--gris-c); border-top-color:var(--azul-c); border-radius:50%; animation:spin .7s linear infinite; margin:0 auto 14px; }
  @keyframes spin { to { transform:rotate(360deg); } }

  /* FOOTER */
  footer { background:var(--azul); border-top:3px solid var(--oro); padding:20px 24px; text-align:center; }
  .f-links { display:flex; justify-content:center; gap:0; flex-wrap:wrap; margin-bottom:10px; }
  .f-links a { color:#7a9ec0; font-size:.76rem; text-decoration:none; padding:0 12px; border-right:1px solid rgba(255,255,255,.12); line-height:1; }
  .f-links a:last-child { border-right:none; }
  .f-links a:hover { color:var(--oro-c); }
  .copy { font-size:.75rem; color:#4a6a90; }

  @media(max-width:680px) {
    .lista-hero h1 { font-size:1.7rem; }
    .det-titulo { font-size:1.5rem; }
    .grid { grid-template-columns:1fr; }
  }
</style>
</head>
<body>

<header>
  <div class="h-inner">
    <img src="http://imagehostinger.vercel.app/imagenes/publicas/transicional/63d583919322627e.png" alt="Escudo">
    <div class="h-nombre">
      Ayuntamiento de Miranda de Ebro
      <small>Burgos · Castilla y León</small>
    </div>
    <a href="index.php" class="btn-inicio">← Inicio</a>
  </div>
</header>

<nav class="miga">
  <a href="index.php">Inicio</a>
  <span class="sep">›</span>
  <span id="miga">Noticias</span>
</nav>

<main>

  <!-- LISTA -->
  <div id="v-lista">
    <div class="lista-hero">
      <div class="lista-hero-inner">
        <span class="etiqueta">Sala de prensa</span>
        <h1>Noticias municipales</h1>
        <p>Mantente informado de todo lo que ocurre en Miranda de Ebro</p>
      </div>
    </div>

    <div class="buscar-wrap">
      <input type="search" id="buscador" placeholder="Buscar en noticias…" autocomplete="off">
      <button class="btn-buscar" onclick="filtrar()">🔍 Buscar</button>
    </div>

    <div class="noticias-wrap">
      <div class="contador" id="contador"></div>
      <div class="grid" id="grid">
        <div class="estado"><div class="spinner"></div><p>Cargando noticias…</p></div>
      </div>
    </div>
  </div>

  <!-- DETALLE -->
  <div id="v-detalle">
    <div class="det-wrap">
      <button class="btn-volver" onclick="volver()">← Volver a noticias</button>
      <div class="det-meta">
        <span class="det-autor" id="d-autor"></span>
        <span class="det-fecha" id="d-fecha"></span>
      </div>
      <h1 class="det-titulo" id="d-titulo"></h1>
      <div class="det-img" id="d-img-wrap" style="display:none"><img id="d-img" src="" alt=""></div>
      <div class="det-cuerpo" id="d-cuerpo"></div>
      <hr class="sep">
      <div class="compartir">
        <span>Compartir:</span>
        <button class="btn-sh" onclick="share('twitter')">𝕏 Twitter</button>
        <button class="btn-sh" onclick="share('facebook')">Facebook</button>
        <button class="btn-sh" onclick="share('whatsapp')">WhatsApp</button>
        <button class="btn-sh" onclick="copiar()">🔗 Copiar enlace</button>
      </div>
    </div>
  </div>

</main>

<footer>
  <div class="f-links">
    <a href="#">Sede Electrónica</a>
    <a href="#">Transparencia</a>
    <a href="#">Contacto</a>
    <a href="#">Aviso legal</a>
    <a href="#">Privacidad</a>
    <a href="#">Cookies</a>
  </div>
  <div class="copy">© 2026 Ayuntamiento de Miranda de Ebro</div>
</footer>

<script>
// La API apunta al mismo archivo PHP con ?ajax=1
const API = location.pathname;

let todas = [];
let actual = null;

function esc(s) {
  if (!s) return '';
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function fecha(iso) {
  if (!iso) return '';
  const d = new Date(iso);
  return isNaN(d) ? iso : d.toLocaleDateString('es-ES',{day:'numeric',month:'long',year:'numeric'});
}
function parrafos(txt) {
  return (txt||'').split(/\n+/).map(p=>p.trim()).filter(Boolean).map(p=>`<p>${esc(p)}</p>`).join('');
}

async function cargar() {
  try {
    const r = await fetch(API + '?ajax=1');
    const j = await r.json();
    if (!r.ok) throw new Error(j.error || 'Error');
    todas = j.noticias || [];
    renderLista(todas);
    const id = new URLSearchParams(location.search).get('id');
    if (id) abrirDetalle(id);
  } catch(e) {
    document.getElementById('grid').innerHTML =
      `<div class="estado"><p>⚠️ No se pudieron cargar las noticias.<br><small>${e.message}</small></p></div>`;
  }
}

function renderLista(noticias) {
  document.getElementById('contador').textContent =
    noticias.length ? `${noticias.length} noticia${noticias.length!==1?'s':''} encontrada${noticias.length!==1?'s':''}` : 'Sin resultados';

  document.getElementById('grid').innerHTML = noticias.length === 0
    ? `<div class="estado"><p>📰 No hay noticias disponibles.</p></div>`
    : noticias.map(n => `
      <article class="card" onclick="abrirDetalle('${esc(n.id)}')">
        <div class="card-img">
          ${n.imagen
            ? `<img src="${esc(n.imagen)}" alt="${esc(n.titulo)}" loading="lazy" onerror="this.parentElement.innerHTML='<div class=\\'no-img\\'>📰</div>'">`
            : '<div class="no-img">📰</div>'}
        </div>
        <div class="card-body">
          <div class="card-meta">
            <span class="card-autor">${esc(n.quienLoHizo||'Ayuntamiento')}</span>
            <span class="card-fecha">${fecha(n.fecha)}</span>
          </div>
          <div class="card-titulo">${esc(n.titulo||'Sin título')}</div>
          <div class="card-preview">${esc(n.cuerpo||'')}</div>
          <span class="card-leer">Leer noticia →</span>
        </div>
      </article>`).join('');
}

async function abrirDetalle(id) {
  let n = todas.find(x => x.id === id);
  if (!n) {
    try {
      const r = await fetch(`${API}?ajax=1&id=${encodeURIComponent(id)}`);
      n = await r.json();
      if (!r.ok) throw new Error(n.error);
    } catch(e) { alert('Error al cargar la noticia: ' + e.message); return; }
  }
  actual = n;
  history.pushState({id}, '', `?id=${id}`);
  document.getElementById('d-titulo').textContent = n.titulo || 'Sin título';
  document.getElementById('d-autor').textContent  = n.quienLoHizo || 'Ayuntamiento';
  document.getElementById('d-fecha').textContent  = '📅 ' + fecha(n.fecha);
  document.getElementById('d-cuerpo').innerHTML   = parrafos(n.cuerpo);
  const iw = document.getElementById('d-img-wrap');
  const im = document.getElementById('d-img');
  if (n.imagen) { im.src = n.imagen; im.alt = n.titulo||''; iw.style.display='block'; }
  else { iw.style.display='none'; }
  document.getElementById('miga').textContent = n.titulo || 'Noticia';
  document.title = (n.titulo||'Noticia') + ' — Ayuntamiento de Miranda de Ebro';
  document.getElementById('v-lista').style.display   = 'none';
  document.getElementById('v-detalle').style.display = 'block';
  window.scrollTo({top:0,behavior:'smooth'});
}

function volver() {
  document.getElementById('v-lista').style.display   = 'block';
  document.getElementById('v-detalle').style.display = 'none';
  document.getElementById('miga').textContent = 'Noticias';
  document.title = 'Noticias — Ayuntamiento de Miranda de Ebro';
  history.pushState({}, '', location.pathname);
  window.scrollTo({top:0,behavior:'smooth'});
}

function filtrar() {
  const q = document.getElementById('buscador').value.trim().toLowerCase();
  renderLista(!q ? todas : todas.filter(n =>
    (n.titulo||'').toLowerCase().includes(q) ||
    (n.cuerpo||'').toLowerCase().includes(q) ||
    (n.quienLoHizo||'').toLowerCase().includes(q)
  ));
}
document.getElementById('buscador').addEventListener('keydown', e => e.key==='Enter' && filtrar());

window.addEventListener('popstate', e => e.state?.id ? abrirDetalle(e.state.id) : volver());

function share(red) {
  const u = encodeURIComponent(location.href);
  const t = encodeURIComponent(actual?.titulo||'');
  const links = {
    twitter:  `https://twitter.com/intent/tweet?url=${u}&text=${t}`,
    facebook: `https://www.facebook.com/sharer/sharer.php?u=${u}`,
    whatsapp: `https://wa.me/?text=${t}%20${u}`,
  };
  window.open(links[red],'_blank','width=600,height=400');
}
function copiar() {
  navigator.clipboard.writeText(location.href)
    .then(()=>alert('Enlace copiado'))
    .catch(()=>alert('No se pudo copiar'));
}

cargar();
</script>
</body>
</html>
