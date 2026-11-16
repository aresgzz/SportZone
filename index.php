<?php
// PÁGINA 1 : LA HOMEPAGE
include 'includes/cabecera.php';

// Aquí meto como novedades los últimos productos que he añadido como admin
$novedades = $db->productos->find([], ['sort' => ['_id' => -1], 'limit' => 3])->toArray();
?>

<!-- Portadita -->
<section class="portada text-white">
  <div class="container py-5">
    <div class="row align-items-center py-lg-4">
      <div class="col-lg-7">
        <span class="badge rounded-pill text-bg-light mb-3">Nueva temporada</span>
        <h1 class="display-4 fw-bold">Todo el material para superar tus límites</h1>
        <p class="lead mt-3 mb-4">Fútbol, running, pádel, fitness y mucho más. Elige tu equipación y reserva tus unidades al instante.</p>
        <a href="/tienda.php" class="btn btn-light btn-lg me-2 mb-2"><i class="bi bi-bag"></i> Ver la tienda</a>
        <a href="/contacto.php" class="btn btn-outline-light btn-lg mb-2">Contacto</a>
      </div>
      <div class="col-lg-5 d-none d-lg-block">
        <div class="iconos">
          <span><i class="bi bi-trophy"></i></span>
          <span><i class="bi bi-bicycle"></i></span>
          <span><i class="bi bi-lightning-charge"></i></span>
          <span><i class="bi bi-stopwatch"></i></span>
          <span><i class="bi bi-heart-pulse"></i></span>
          <span><i class="bi bi-fire"></i></span>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Ventajas -->
<section class="container py-5">
  <div class="row g-4">
    <div class="col-md-4">
      <h5><i class="bi bi-box-seam text-primary"></i> Stock real</h5>
      <p class="text-muted">Al añadir un producto al carrito, ya lo tienes reservado.</p>
    </div>
    <div class="col-md-4">
      <h5><i class="bi bi-receipt text-primary"></i> Tus compras a la vista</h5>
      <p class="text-muted">Consulta tu historial y el estado de cada pedido.</p>
    </div>
    <div class="col-md-4">
      <h5><i class="bi bi-arrow-counterclockwise text-primary"></i> Cancela cuando quieras</h5>
      <p class="text-muted">Mientras el pedido no esté aceptado, puedes cancelar cuando quieras.</p>
    </div>
  </div>
</section>

<!-- Novedades -->
<section class="container">
  <div class="d-flex justify-content-between align-items-end mb-3">
    <h2 class="h3 mb-0">Últimas novedades</h2>
    <a href="/tienda.php">Ver todo <i class="bi bi-arrow-right"></i></a>
  </div>
  <div class="row row-cols-1 row-cols-md-3 g-4">
    <?php foreach ($novedades as $p) { ?>
      <div class="col">
        <div class="card h-100 tarjeta">
          <img src="<?php echo h(imagenProducto($p['imagen'])); ?>" class="card-img-top" alt="<?php echo h($p['nombre']); ?>">
          <div class="card-body">
            <h5 class="card-title"><?php echo h($p['nombre']); ?></h5>
            <p class="card-text text-muted small"><?php echo h($p['descripcion']); ?></p>
            <div class="fs-5 fw-bold"><?php echo number_format($p['precio'], 2, ',', '.'); ?> €</div>
          </div>
        </div>
      </div>
    <?php } ?>
  </div>
</section>

<?php include 'includes/pie.php'; ?>
