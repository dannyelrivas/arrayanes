<?php
require_once __DIR__ . '/includes/auth.php';
startSession();
if (isLoggedIn()) {
    header('Location: /arrayanes/public/dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Arryanaes – Acceso</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  
  :root {
    --bg:       #0b0e14;
    --surface:  #131720;
    --border:   #1e2535;
    --accent:   #4f8eff;
    --accent2:  #00d4aa;
    --text:     #e8eaf0;
    --muted:    #6b7592;
    --danger:   #ff4f6a;
    --radius:   12px;
  }

  body {
    font-family: 'DM Sans', sans-serif;
    background: var(--bg);
    color: var(--text);
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
  }

  /* Animated background */
  body::before {
    content: '';
    position: fixed; inset: 0;
    background: 
      radial-gradient(ellipse 80% 60% at 20% 80%, rgba(79,142,255,.12) 0%, transparent 60%),
      radial-gradient(ellipse 60% 50% at 80% 20%, rgba(0,212,170,.08) 0%, transparent 60%);
    pointer-events: none;
  }

  .login-wrap {
    position: relative;
    width: 100%;
    max-width: 420px;
    padding: 24px;
  }

  .brand {
    text-align: center;
    margin-bottom: 36px;
  }

  .brand-icon {
    width: 64px; height: 64px;
    background: linear-gradient(135deg, var(--accent), var(--accent2));
    border-radius: 18px;
    margin: 0 auto 16px;
    display: flex; align-items: center; justify-content: center;
    font-size: 28px;
    box-shadow: 0 8px 32px rgba(79,142,255,.3);
  }

  .brand h1 {
    font-family: 'Syne', sans-serif;
    font-size: 2rem;
    font-weight: 800;
    letter-spacing: -1px;
    background: linear-gradient(135deg, #fff 40%, var(--accent2));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
  }

  .brand p {
    color: var(--muted);
    font-size: .85rem;
    margin-top: 4px;
  }

  .card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 20px;
    padding: 36px;
    box-shadow: 0 24px 64px rgba(0,0,0,.4);
  }

  .field { margin-bottom: 20px; }

  .field label {
    display: block;
    font-size: .78rem;
    font-weight: 500;
    color: var(--muted);
    text-transform: uppercase;
    letter-spacing: .08em;
    margin-bottom: 8px;
  }

  .field input {
    width: 100%;
    background: var(--bg);
    border: 1.5px solid var(--border);
    border-radius: var(--radius);
    padding: 13px 16px;
    color: var(--text);
    font-family: inherit;
    font-size: .95rem;
    outline: none;
    transition: border-color .2s;
  }

  .field input:focus {
    border-color: var(--accent);
    box-shadow: 0 0 0 3px rgba(79,142,255,.15);
  }

  .btn-login {
    width: 100%;
    background: linear-gradient(135deg, var(--accent) 0%, #6ba3ff 100%);
    color: #fff;
    font-family: 'Syne', sans-serif;
    font-weight: 700;
    font-size: 1rem;
    border: none;
    border-radius: var(--radius);
    padding: 14px;
    cursor: pointer;
    transition: transform .15s, box-shadow .15s, opacity .15s;
    box-shadow: 0 8px 24px rgba(79,142,255,.3);
    margin-top: 8px;
  }

  .btn-login:hover { transform: translateY(-1px); box-shadow: 0 12px 32px rgba(79,142,255,.4); }
  .btn-login:active { transform: translateY(0); }
  .btn-login:disabled { opacity: .6; cursor: not-allowed; transform: none; }

  .error-msg {
    background: rgba(255,79,106,.12);
    border: 1px solid rgba(255,79,106,.3);
    color: #ff8a9a;
    border-radius: 8px;
    padding: 10px 14px;
    font-size: .85rem;
    margin-top: 16px;
    display: none;
  }
</style>
</head>
<body>
<div class="login-wrap">
  <div class="brand">
    <div class="brand-icon">🏘️</div>
    <h1>Arryanaes</h1>
    <p>Control de pagos del fraccionamiento</p>
  </div>
  <div class="card">
    <div class="field">
      <label>Usuario</label>
      <input type="text" id="username" placeholder="Tu usuario" autocomplete="username">
    </div>
    <div class="field">
      <label>Contraseña</label>
      <input type="password" id="password" placeholder="••••••••" autocomplete="current-password">
    </div>
    <button class="btn-login" id="btnLogin" onclick="doLogin()">Entrar</button>
    <div class="error-msg" id="errMsg"></div>
  </div>
</div>

<script>
document.getElementById('password').addEventListener('keydown', e => {
  if (e.key === 'Enter') doLogin();
});

async function doLogin() {
  const btn = document.getElementById('btnLogin');
  const err = document.getElementById('errMsg');
  btn.disabled = true;
  btn.textContent = 'Verificando...';
  err.style.display = 'none';
  try {
    const res = await fetch('/arrayanes/public/api.php?action=login', {
      method: 'POST',
      headers: {'Content-Type':'application/json'},
      body: JSON.stringify({
        username: document.getElementById('username').value,
        password: document.getElementById('password').value
      })
    });
    const data = await res.json();
    if (data.success) {
      window.location.href = '/arrayanes/public/dashboard.php';
    } else {
      err.textContent = data.message || 'Error al iniciar sesión';
      err.style.display = 'block';
    }
  } catch(e) {
    console.log(e);
    err.textContent = 'Error de conexión';
    err.style.display = 'block';
  }
  btn.disabled = false;
  btn.textContent = 'Entrar';
}
</script>
</body>
</html>
