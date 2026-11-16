<?php
// Carrito del comprador y sus acciones (añadir, quitar, vaciar, confirmar)
require_once '../includes/conexion.php';
exigirComprador();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $accion = dato('accion');

    //  AÑADIR 1 UNIDAD (resta 1 de stock) 
    if ($accion == 'anadir') {
        $error = anadirAlCarrito($db, $usuario['_id'], oid(dato('producto_id')));
        if ($error == '') {
            avisar('success', 'Producto añadido al carrito. La unidad queda reservada para ti.');
        } else {
            avisar('danger', $error);
        }
        irA('/tienda.php');
    }

    //  QUITAR UNA LÍNEA (devuelve el stock) 
    if ($accion == 'quitar_linea') {
        $hecho = quitarLinea($db, oid(dato('pedido_id')), oid(dato('producto_id')), ['carrito', 'pendiente'], $usuario['_id']);
        if ($hecho) {
            avisar('success', 'Línea eliminada. Las unidades han vuelto al stock.');
        } else {
            avisar('danger', 'No puedes modificar esa línea.');
        }
        irA('/comprador/carrito.php');
    }

    //  VACIAR EL CARRITO (devuelve el stock) 
    if ($accion == 'vaciar') {
        vaciarCarrito($db, $usuario['_id']);
        avisar('info', 'Has vaciado el carrito. Las unidades han vuelto al stock.');
        irA('/comprador/carrito.php');
    }

    //  CONFIRMAR LA COMPRA  (el carrito pasa a ser un pedido pendiente)
    if ($accion == 'confirmar') {
        // 'lineas.0 existe' significa que tiene al menos una línea
        $resultado = $db->pedidos->updateOne(
            ['usuario_id' => $usuario['_id'], 'estado' => 'carrito', 'lineas.0' => ['$exists' => true]],
            ['$set' => ['estado' => 'pendiente', 'fecha' => date('Y-m-d H:i:s')]]
        );
        if ($resultado->getModifiedCount() == 0) {
            avisar('warning', 'Tu carrito está vacío.');
            irA('/comprador/carrito.php');
        }
        avisar('success', '¡Compra realizada! Está pendiente de que el administrador la acepte.');
        irA('/comprador/compras.php');
    }

    irA('/comprador/carrito.php');
}

include '../includes/cabecera.php';
$carrito = $db->pedidos->findOne(['usuario_id' => $usuario['_id'], 'estado' => 'carrito']);
$lineas = [];
if ($carrito != null) {
    $lineas = $carrito['lineas'];
}
?>

<section class="cabecera-pagina text-white py-4">
  <div class="container">
    <h1 class="h2 mb-0"><i class="bi bi-cart3"></i> Mi carrito</h1>
  </div>
</section>

<div class="container py-4">
  <?php if (count($lineas) == 0) { ?>
    <div class="text-center text-muted py-5">
      <i class="bi bi-cart-x display-3"></i>
      <p class="mt-2">Tu carrito está vacío.</p>
      <a href="/tienda.php" class="btn btn-primary">Ir a la tienda</a>
    </div>
  <?php } else { ?>
    <div class="card shadow-sm">
      <div class="table-responsive">
        <table class="table align-middle mb-0">
          <thead class="table-light">
            <tr><th>Producto</th><th class="text-end">Precio</th><th class="text-center">Cantidad</th><th class="text-end">Subtotal</th><th></th></tr>
          </thead>
          <tbody>
            <?php for ($i = 0; $i < count($lineas); $i++) { $l = $lineas[$i]; ?>
              <tr>
                <td>
                  <img src="<?php echo h(imagenProducto($l['imagen'])); ?>" class="miniatura" alt="">
                  <?php echo h($l['nombre']); ?>
                </td>
                <td class="text-end"><?php echo number_format($l['precio'], 2, ',', '.'); ?> €</td>
                <td class="text-center"><?php echo (int)$l['cantidad']; ?></td>
                <td class="text-end"><?php echo number_format($l['precio'] * $l['cantidad'], 2, ',', '.'); ?> €</td>
                <td class="text-end">
                  <form method="post" data-confirmar="¿Quitar este producto del carrito?">
                    <input type="hidden" name="accion" value="quitar_linea">
                    <input type="hidden" name="pedido_id" value="<?php echo (string)$carrito['_id']; ?>">
                    <input type="hidden" name="producto_id" value="<?php echo (string)$l['producto_id']; ?>">
                    <button class="btn btn-sm btn-outline-danger" title="Quitar"><i class="bi bi-trash"></i></button>
                  </form>
                </td>
              </tr>
            <?php } ?>
          </tbody>
        </table>
      </div>
    </div>

    <h4 class="text-end mt-3">Total: <?php echo number_format(totalPedido($lineas), 2, ',', '.'); ?> €</h4>
    <p class="text-end small text-muted">Tus unidades están reservadas: ya se han descontado del stock.</p>
    <div class="d-flex justify-content-end gap-2">
      <form method="post" data-confirmar="¿Vaciar el carrito? Las unidades volverán al stock.">
        <input type="hidden" name="accion" value="vaciar">
        <button class="btn btn-outline-secondary">Vaciar carrito</button>
      </form>
      <form method="post">
        <input type="hidden" name="accion" value="confirmar">
        <button class="btn btn-success"><i class="bi bi-bag-check"></i> Confirmar compra</button>
      </form>
    </div>
  <?php } ?>
</div>

<?php include '../includes/pie.php'; ?>
