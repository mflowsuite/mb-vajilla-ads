<?php
session_name('mbv_panel');
session_start();
require_once __DIR__ . '/config.php';

// --- Session timeout check ---
if (isset($_SESSION['last_active']) && (time() - $_SESSION['last_active'] > SESSION_TIMEOUT)) {
    session_destroy();
    session_start();
    session_name('mbv_panel');
}
if (isset($_SESSION['logged_in'])) {
    $_SESSION['last_active'] = time();
}

// --- Logout ---
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ' . PANEL_BASE . '/panel.php');
    exit;
}

// --- Login POST ---
$login_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username'])) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($username === PANEL_USER && password_verify($password, PANEL_PASS_HASH)) {
        $_SESSION['logged_in'] = true;
        $_SESSION['last_active'] = time();
        $_SESSION['csrf_token'] = bin2hex(random_bytes(CSRF_TOKEN_LENGTH));
        header('Location: ' . PANEL_BASE . '/panel.php');
        exit;
    } else {
        $login_error = 'Usuario o contraseña incorrectos.';
    }
}

// --- Not logged in → show login form ---
if (!isset($_SESSION['logged_in'])) {
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>MB Vajilla — Panel</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Karla:wght@400;500;600;700&family=Playfair+Display+SC:wght@700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= PANEL_BASE ?>/assets/panel.css">
</head>
<body class="login-page">
<div class="login-wrap">
  <div class="login-box">
    <div class="login-logo">MB Vajilla</div>
    <p class="login-subtitle">Panel de administración</p>
    <?php if ($login_error): ?>
    <div class="login-error"><?= htmlspecialchars($login_error) ?></div>
    <?php endif; ?>
    <form method="POST" action="<?= PANEL_BASE ?>/panel.php">
      <div class="field-group">
        <label for="username">Usuario</label>
        <input type="text" id="username" name="username" autocomplete="username" required>
      </div>
      <div class="field-group">
        <label for="password">Contraseña</label>
        <input type="password" id="password" name="password" autocomplete="current-password" required>
      </div>
      <button type="submit" class="btn-primary btn-full">Ingresar</button>
    </form>
  </div>
</div>
</body>
</html>
<?php
    exit;
}

// --- Logged in → show dashboard shell ---
$csrf = $_SESSION['csrf_token'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>MB Vajilla — Panel</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Karla:wght@400;500;600;700&family=Playfair+Display+SC:wght@700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= PANEL_BASE ?>/assets/panel.css">
</head>
<body class="panel-page">

<!-- Top bar -->
<header class="topbar">
  <button class="hamburger" id="hamburger" aria-label="Menú">&#9776;</button>
  <span class="topbar-logo">MB Vajilla</span>
  <a href="<?= PANEL_BASE ?>/panel.php?logout=1" class="topbar-logout">Salir</a>
</header>

<!-- Sidebar -->
<nav class="sidebar" id="sidebar">
  <ul class="sidebar-nav">
    <li><a href="#dashboard" class="nav-link active" data-section="dashboard">
      <span class="nav-icon">&#9632;</span> Dashboard
    </a></li>
    <li><a href="#productos" class="nav-link" data-section="productos">
      <span class="nav-icon">&#9633;</span> Productos
    </a></li>
    <li><a href="#contenido" class="nav-link nav-disabled" data-section="contenido">
      <span class="nav-icon">&#9632;</span> Contenido <span class="badge-soon">Pronto</span>
    </a></li>
    <li><a href="#multimedia" class="nav-link nav-disabled" data-section="multimedia">
      <span class="nav-icon">&#9632;</span> Multimedia <span class="badge-soon">Pronto</span>
    </a></li>
    <li><a href="#estadisticas" class="nav-link nav-disabled" data-section="estadisticas">
      <span class="nav-icon">&#9632;</span> Estadísticas <span class="badge-soon">Pronto</span>
    </a></li>
  </ul>
</nav>

<!-- Main content -->
<main class="main-content" id="main-content">
  <div id="section-dashboard"></div>
  <div id="section-productos" class="hidden"></div>
</main>

<script>
  window.MBV_CSRF = <?= json_encode($csrf) ?>;
</script>
<script src="<?= PANEL_BASE ?>/assets/panel.js"></script>
</body>
</html>
<?php
