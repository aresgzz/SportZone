<?php
// Historial de compras del comprador
require_once '../includes/conexion.php';
exigirComprador();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $accion = dato('accion');

    // El comprador solo puede cancelar sus pedidos mientras están pendientes
    if ($accion == 'cancelar_pedido') {
        if (cancelarPedido($db, oid(dato('pedido_id')), ['pendiente'], $usuario['_id'])) {
            avisar('success', 'Pedido cancelado. Las unidades han vuelto al stock.');
        } else {
            avisar('danger', 'Ese pedido ya no se puede cancelar.');
        }
    }

    // Quitar una línea de un pedido pendiente
    if ($accion == 'quitar_linea') {
        if (quitarLinea($db, oid(dato('pedido_id')), oid(dato('producto_id')), ['pendiente'], $usuario['_id'])) {
            avisar('success', 'Línea eliminada. Las unidades han vuelto al stock.');
        } else {
            avisar('danger', 'No puedes modificar esa línea.');
        }
    }
    irA('/comprador/compras.php');
}

include '../includes/cabecera.php';

// Solo los pedidos de este usuario (el id sale de la sesión, mo de la dirección web)
$pedidos = $db->pedidos->find(
    ['usuario_id' => $usuario['_id'], 'estado' => ['$ne' => 'carrito']],
    ['sort' => ['fecha' => -1]]
)->toArray();
?>

<section class="cabecera-pagina text-white py-4">
  <div class="container">
    <h1 class="h2 mb-0"><i class="bi bi-receipt"></i> Mis compras</h1>
  </div>
</section>

<div class="container py-4">
  <?php if (count($pedidos) == 0) { ?>
    <div class="text-center text-muted py-5">
      <i class="bi bi-bag display-3"></i>
      <p class="mt-2">Todavía no has hecho ninguna compra.</p>
      <a href="/tienda.php" class="btn btn-primary">Ir a la tienda</a>
    </div>
  <?php } ?>

  <?php foreach ($pedidos as $pedido) { ?>
    <?php mostrarPedido($pedido, false, null); ?>
  <?php } ?>
</div>

<?php include '../includes/pie.php'; ?>
