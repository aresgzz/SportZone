<?php
// PÁGINA 2 : LA TIENDAA
include 'includes/cabecera.php';

$productos = $db->productos->find([], ['sort' => ['nombre' => 1], 'collation' => ['locale' => 'es']])->toArray();

// con $marcas guardo lo que ha hecho el comprador con cada producto:
// 'carrito' (lo tiene en el carrito)
// 'pendiente' (compra sin aceptar)
// 'aceptado' (compra aceptada)
$marcas = [];
if (esComprador()) {
    $misPedidos = $db->pedidos->find([
        'usuario_id' => $usuario['_id'],
        'estado' => ['$in' => ['carrito', 'pendiente', 'aceptado']]
    ])->toArray();

    for ($i = 0; $i < count($misPedidos); $i++) {
        $estado = $misPedidos[$i]['estado'];
        for ($j = 0; $j < count($misPedidos[$i]['lineas']); $j++) {
            $id = (string)$misPedidos[$i]['lineas'][$j]['producto_id'];
            // Siempre me tengo que quedar con el estado más avanzado: aceptado > pendiente > carrito
            if ($estado == 'aceptado') {
                $marcas[$id] = 'aceptado';
            } elseif ($estado == 'pendiente' && (!isset($marcas[$id]) || $marcas[$id] == 'carrito')) {
                $marcas[$id] = 'pendiente';
            } elseif ($estado == 'carrito' && !isset($marcas[$id])) {
                $marcas[$id] = 'carrito';
            }
        }
    }
}
?>

<section class="cabecera-pagina text-white py-4">
  <div class="container">
    <h1 class="h2 mb-1">Tienda</h1>
    <p class="mb-0 opacity-75">Al añadir un producto al carrito, la unidad queda reservada para ti.</p>
  </div>
</section>

<div class="container py-4">

  <div class="row g-3 mb-4 align-items-center">
    <div class="col-md-5">
      <div class="input-group">
        <span class="input-group-text"><i class="bi bi-search"></i></span>
        <input type="search" id="buscador" class="form-control" placeholder="Buscar productos...">
      </div>
    </div>
    <?php if (esComprador()) { ?>
      <div class="col-md-7 d-flex flex-wrap gap-2 justify-content-md-end small">
        <span class="badge text-bg-success"><i class="bi bi-check-circle-fill"></i> Comprado</span>
        <span class="badge text-bg-warning"><i class="bi bi-hourglass-split"></i> Compra pendiente</span>
        <span class="badge text-bg-primary"><i class="bi bi-cart-fill"></i> En tu carrito</span>
      </div>
    <?php } ?>
  </div>

  <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 g-4">
    <?php foreach ($productos as $p) {
      $id = (string)$p['_id'];

      // Aquí hago la marca con la que se indica si el comprador ya tiene su producto
      $clase = '';
      $etiqueta = '';
      if (isset($marcas[$id])) {
        if ($marcas[$id] == 'aceptado') {
          $clase = 'comprado';
          $etiqueta = '<span class="marca bg-success"><i class="bi bi-check-circle-fill"></i> Comprado</span>';
        } elseif ($marcas[$id] == 'pendiente') {
          $clase = 'pendiente';
          $etiqueta = '<span class="marca bg-warning text-dark"><i class="bi bi-hourglass-split"></i> Pendiente</span>';
        } else {
          $clase = 'en-carrito';
          $etiqueta = '<span class="marca bg-primary"><i class="bi bi-cart-fill"></i> En tu carrito</span>';
        }
      }
    ?>
      <div class="col producto" data-nombre="<?php echo h(mb_strtolower($p['nombre'] . ' ' . $p['descripcion'])); ?>">
        <div class="card h-100 tarjeta <?php echo $clase; ?>">
          <div class="position-relative">
            <img src="<?php echo h(imagenProducto($p['imagen'])); ?>" class="card-img-top" alt="<?php echo h($p['nombre']); ?>">
            <?php echo $etiqueta; ?>
          </div>
          <div class="card-body">
            <h5 class="card-title"><?php echo h($p['nombre']); ?></h5>
            <p class="card-text text-muted small"><?php echo h($p['descripcion']); ?></p>
            <div class="d-flex justify-content-between align-items-center">
              <span class="fs-4 fw-bold"><?php echo number_format($p['precio'], 2, ',', '.'); ?> €</span>
              <?php if ($p['stock'] == 0) { ?>
                <span class="badge text-bg-secondary">Agotado</span>
              <?php } elseif ($p['stock'] <= 3) { ?>
                <span class="badge text-bg-danger">¡Solo quedan <?php echo (int)$p['stock']; ?>!</span>
              <?php } else { ?>
                <span class="small text-muted"><?php echo (int)$p['stock']; ?> en stock</span>
              <?php } ?>
            </div>
          </div>
          <div class="card-footer bg-white border-0 pb-3">
            <?php if ($usuario == null) { ?>
              <a href="/acceso.php" class="btn btn-outline-primary w-100"><i class="bi bi-person"></i> Inicia sesión para comprar</a>
            <?php } elseif (esAdmin()) { ?>
              <a href="/admin/index.php?p=productos" class="btn btn-outline-dark w-100"><i class="bi bi-gear"></i> Gestionar producto</a>
            <?php } elseif ($p['stock'] == 0) { ?>
              <button class="btn btn-secondary w-100" disabled>Sin stock</button>
            <?php } else { ?>
              <form action="/comprador/carrito.php" method="post">
                <input type="hidden" name="accion" value="anadir">
                <input type="hidden" name="producto_id" value="<?php echo $id; ?>">
                <button class="btn btn-primary w-100"><i class="bi bi-cart-plus"></i> Añadir al carrito</button>
              </form>
            <?php } ?>
          </div>
        </div>
      </div>
    <?php } ?>
  </div>

  <p class="text-center text-muted py-5 d-none" id="sinResultados">No hay productos con ese nombre.</p>
</div>

<?php include 'includes/pie.php'; ?>
