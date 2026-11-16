<?php
// EL NAV
require_once 'conexion.php';
$paginaActual = $_SERVER['SCRIPT_NAME'];

// Número de unidades que tiene el comprador en el carrito
$unidadesCarrito = 0;
if (esComprador()) {
    $carrito = $db->pedidos->findOne(['usuario_id' => $usuario['_id'], 'estado' => 'carrito']);
    if ($carrito != null) {
        for ($i = 0; $i < count($carrito['lineas']); $i++) {
            $unidadesCarrito = $unidadesCarrito + $carrito['lineas'][$i]['cantidad'];
        }
    }
}

// Aviso guardado por la página anterior
$aviso = null;
if (isset($_SESSION['aviso'])) {
    $aviso = $_SESSION['aviso'];
    unset($_SESSION['aviso']);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>SportZone</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="/css/estilos.css" rel="stylesheet">
</head>
<body class="d-flex flex-column min-vh-100">

<nav class="navbar navbar-expand-lg navbar-dark barra sticky-top">
  <div class="container">
    <a class="navbar-brand fw-bold" href="/index.php"><i class="bi bi-trophy-fill"></i> SportZone</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menu"><span class="navbar-toggler-icon"></span></button>
    <div class="collapse navbar-collapse" id="menu">
      <ul class="navbar-nav me-auto">
        <li class="nav-item"><a class="nav-link <?php if ($paginaActual == '/index.php') { echo 'active'; } ?>" href="/index.php">Inicio</a></li>
        <li class="nav-item"><a class="nav-link <?php if ($paginaActual == '/tienda.php') { echo 'active'; } ?>" href="/tienda.php">Tienda</a></li>
        <li class="nav-item"><a class="nav-link <?php if ($paginaActual == '/contacto.php') { echo 'active'; } ?>" href="/contacto.php">Contacto</a></li>
        <?php if (esAdmin()) { ?>
          <li class="nav-item"><a class="nav-link <?php if ($paginaActual == '/admin/index.php') { echo 'active'; } ?>" href="/admin/index.php">Administración</a></li>
        <?php } ?>
      </ul>

      <?php if ($usuario == null) { ?>
        <!-- Sin sesión: botón de login -->
        <a href="/acceso.php" class="btn btn-light"><i class="bi bi-person"></i> Iniciar sesión</a>
      <?php } else { ?>
        <!-- Con sesión: carrito (sÓlo compradores) y foto del usuario -->
        <div class="d-flex align-items-center gap-3">
          <?php if (esComprador()) { ?>
            <a href="/comprador/carrito.php" class="btn btn-outline-light position-relative" title="Carrito">
              <i class="bi bi-cart3"></i>
              <?php if ($unidadesCarrito > 0) { ?>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"><?php echo $unidadesCarrito; ?></span>
              <?php } ?>
            </a>
          <?php } ?>
          <div class="dropdown">
            <a href="#" data-bs-toggle="dropdown">
              <?php if ($usuario['foto'] != '') { ?>
                <img src="<?php echo '/' . h($usuario['foto']); ?>" class="avatar" alt="Mi cuenta">
              <?php } else { ?>
                <span class="avatar avatar-letra"><?php echo h(strtoupper(mb_substr($usuario['nombre'], 0, 1))); ?></span>
              <?php } ?>
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
              <li><span class="dropdown-item-text fw-semibold"><?php echo h($usuario['nombre']); ?></span></li>
              <li><hr class="dropdown-divider"></li>
              <?php if (esAdmin()) { ?>
                <li><a class="dropdown-item" href="/admin/index.php"><i class="bi bi-speedometer2"></i> Panel de administración</a></li>
              <?php } else { ?>
                <li><a class="dropdown-item" href="/comprador/carrito.php"><i class="bi bi-cart3"></i> Mi carrito</a></li>
                <li><a class="dropdown-item" href="/comprador/compras.php"><i class="bi bi-receipt"></i> Mis compras</a></li>
              <?php } ?>
              <li><a class="dropdown-item" href="/comprador/perfil.php"><i class="bi bi-person-gear"></i> Mi perfil</a></li>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item" href="/salir.php"><i class="bi bi-box-arrow-right"></i> Cerrar sesión</a></li>
            </ul>
          </div>
        </div>
      <?php } ?>
    </div>
  </div>
</nav>

<main class="flex-grow-1">
<?php if ($aviso != null) { ?>
  <div class="container mt-3">
    <div class="alert alert-<?php echo h($aviso['tipo']); ?> alert-dismissible">
      <?php echo h($aviso['texto']); ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  </div>
<?php } ?>
