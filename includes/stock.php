<?php


// STOCK, CARRITO Y PEDIDOS 
// Reglas :
// Un pedido es 1 documento que lleva dentro sus líneas:
//   { usuario_id, estado, fecha, lineas: [ { producto_id, nombre, imagen, precio, cantidad } ] }
// Estados: carrito -> pendiente -> aceptado / cancelado

// Aquí devuelvo unidades al stock de un producto
function devolverStock($db, $productoId, $cantidad) {
    $db->productos->updateOne(['_id' => $productoId], ['$inc' => ['stock' => $cantidad]]);
}

// Añade 1 unidad de un producto al carrito y resto 1 de stock.
function anadirAlCarrito($db, $usuarioId, $productoId) {
    // Resto 1 al stock solo si queda al menos 1 en stock. Es una única operación, es decir, aunque compren dos personas a la vez el stock nunca va a quedar en negativo
    $resultado = $db->productos->updateOne(
        ['_id' => $productoId, 'stock' => ['$gte' => 1]],
        ['$inc' => ['stock' => -1]]
    );
    if ($resultado->getModifiedCount() == 0) {
        return 'No hay stock disponible de ese producto.';
    }

    // Ya está reservada la unidad. La meto en el carrito (si algo falla, la devuelvo)
    try {
        $producto = $db->productos->findOne(['_id' => $productoId]);

        // Si el usuario no tiene carrito se crea uno vacío (upsert = actualizar o insertar)
        $db->pedidos->updateOne(
            ['usuario_id' => $usuarioId, 'estado' => 'carrito'],
            ['$setOnInsert' => ['fecha' => date('Y-m-d H:i:s'), 'lineas' => []]],
            ['upsert' => true]
        );

        // Si el producto ya estaba en el carrito, sumamos 1 a su línea ('lineas.$.cantidad' = la cantidad de la línea que ha coincidido con el filtro)
        $filtroYaEsta = ['usuario_id' => $usuarioId, 'estado' => 'carrito', 'lineas.producto_id' => $productoId];
        $cambio = $db->pedidos->updateOne($filtroYaEsta, ['$inc' => ['lineas.$.cantidad' => 1]]);

        if ($cambio->getMatchedCount() == 0) {
            // Si no estaba, añado una línea nueva con el precio de en ese momento (así el historial no cambia si luego el yo como admin subo el precio)
            $linea = [
                'producto_id' => $productoId, 'nombre' => $producto['nombre'],
                'imagen' => $producto['imagen'], 'precio' => (float)$producto['precio'], 'cantidad' => 1
            ];
            $filtroNoEsta = ['usuario_id' => $usuarioId, 'estado' => 'carrito', 'lineas.producto_id' => ['$ne' => $productoId]];
            $cambio = $db->pedidos->updateOne($filtroNoEsta, ['$push' => ['lineas' => $linea]]);

            // Si tampoco coincide, otra petición creó la línea justo a la vez: sumamos a esa
            if ($cambio->getMatchedCount() == 0) {
                $cambio = $db->pedidos->updateOne($filtroYaEsta, ['$inc' => ['lineas.$.cantidad' => 1]]);
            }
        }
        if ($cambio->getMatchedCount() == 0) {
            throw new Exception('No se pudo guardar la línea');
        }
        return '';
    } catch (Exception $error) {
        devolverStock($db, $productoId, 1);
        return 'Ha ocurrido un error al añadir el producto. Inténtalo otra vez.';
    }
}

// Quita 1 línea (1 producto) de un pedido y devuelve sus unidades al stock. $estados: estados en los que se puede quitar. $usuarioId: si no es null, el pedido tiene que ser de ese usuario
function quitarLinea($db, $pedidoId, $productoId, $estados, $usuarioId) {
    $filtro = ['_id' => $pedidoId, 'estado' => ['$in' => $estados], 'lineas.producto_id' => $productoId];
    if ($usuarioId != null) {
        $filtro['usuario_id'] = $usuarioId;
    }

    // $pull saca la línea del pedido. findOneAndUpdate devuelve el pedido tal y como estaba antes. Solo la petición que realmente quita la línea lo recibe, así el stock se devuelve 1 sola vez
    $antes = $db->pedidos->findOneAndUpdate($filtro, ['$pull' => ['lineas' => ['producto_id' => $productoId]]]);
    if ($antes == null) {
        return false;
    }

    for ($i = 0; $i < count($antes['lineas']); $i++) {
        if ((string)$antes['lineas'][$i]['producto_id'] == (string)$productoId) {
            devolverStock($db, $productoId, $antes['lineas'][$i]['cantidad']);
        }
    }

    // Si era un pedido pendiente y se ha quedado sin líneas, queda cancelado
    $db->pedidos->updateOne(
        ['_id' => $pedidoId, 'estado' => 'pendiente', 'lineas' => ['$size' => 0]],
        ['$set' => ['estado' => 'cancelado']]
    );
    return true;
}

// Vacía el carrito del usuario devolviendo todo el stock
function vaciarCarrito($db, $usuarioId) {
    $antes = $db->pedidos->findOneAndUpdate(
        ['usuario_id' => $usuarioId, 'estado' => 'carrito'],
        ['$set' => ['lineas' => []]]
    );
    if ($antes == null) {
        return;
    }
    for ($i = 0; $i < count($antes['lineas']); $i++) {
        devolverStock($db, $antes['lineas'][$i]['producto_id'], $antes['lineas'][$i]['cantidad']);
    }
}

// Cancela un pedido entero y devuelve todas sus unidades al stock. $estados: en qué estados se puede cancelar. $usuarioId: si no es null, el pedido tiene que ser de ese usuario
function cancelarPedido($db, $pedidoId, $estados, $usuarioId) {
    $filtro = ['_id' => $pedidoId, 'estado' => ['$in' => $estados]];
    if ($usuarioId != null) {
        $filtro['usuario_id'] = $usuarioId;
    }

    // Cambio estado y recibo el pedido de antes. Así las unidades se devuelven 1 sola vez aunque pulsen el botón dos veces
    $antes = $db->pedidos->findOneAndUpdate($filtro, ['$set' => ['estado' => 'cancelado']]);
    if ($antes == null) {
        return false;
    }
    for ($i = 0; $i < count($antes['lineas']); $i++) {
        devolverStock($db, $antes['lineas'][$i]['producto_id'], $antes['lineas'][$i]['cantidad']);
    }
    return true;
}

// Suma el total de las líneas de un pedido
function totalPedido($lineas) {
    $total = 0;
    for ($i = 0; $i < count($lineas); $i++) {
        $total = $total + $lineas[$i]['precio'] * $lineas[$i]['cantidad'];
    }
    return $total;
}

// Hace un pedido con sus líneas y los botones que correspondan. $cliente solo se rellena cuando lo ve el administrador (nombre y email del comprador)
function mostrarPedido($pedido, $esAdmin, $cliente) {
    $destino = '/comprador/compras.php';
    if ($esAdmin) {
        $destino = '/admin/index.php';
    }
    $colores = ['pendiente' => 'warning', 'aceptado' => 'success', 'cancelado' => 'secondary'];
    $estado = $pedido['estado'];
    ?>
    <div class="card mb-3 pedido pedido-<?php echo h($estado); ?>">
      <div class="card-header d-flex justify-content-between flex-wrap gap-2">
        <div>
          <strong>Pedido #<?php echo h(strtoupper(substr((string)$pedido['_id'], -6))); ?></strong>
          <?php if ($esAdmin) { ?>
            · <?php echo h($cliente['nombre']); ?> <span class="text-muted">(<?php echo h($cliente['email']); ?>)</span>
          <?php } ?>
          <div class="small text-muted"><?php echo h($pedido['fecha']); ?></div>
        </div>
        <span class="badge text-bg-<?php echo $colores[$estado]; ?> align-self-start"><?php echo h(ucfirst($estado)); ?></span>
      </div>
      <ul class="list-group list-group-flush">
        <?php for ($i = 0; $i < count($pedido['lineas']); $i++) { $l = $pedido['lineas'][$i]; ?>
          <li class="list-group-item d-flex justify-content-between align-items-center">
            <span>
              <img src="<?php echo h(imagenProducto($l['imagen'])); ?>" class="miniatura" alt="">
              <?php echo h($l['nombre']); ?> x<?php echo (int)$l['cantidad']; ?>
            </span>
            <span>
              <?php echo number_format($l['precio'] * $l['cantidad'], 2, ',', '.'); ?> €
              <?php if ($estado == 'pendiente') { ?>
                <form action="<?php echo $destino; ?>" method="post" class="d-inline" data-confirmar="¿Quitar esta línea? Las unidades volverán al stock.">
                  <input type="hidden" name="accion" value="quitar_linea">
                  <input type="hidden" name="pedido_id" value="<?php echo (string)$pedido['_id']; ?>">
                  <input type="hidden" name="producto_id" value="<?php echo (string)$l['producto_id']; ?>">
                  <button class="btn btn-sm btn-outline-danger ms-2" title="Quitar línea"><i class="bi bi-trash"></i></button>
                </form>
              <?php } ?>
            </span>
          </li>
        <?php } ?>
      </ul>
      <div class="card-footer d-flex justify-content-between align-items-center flex-wrap gap-2">
        <strong>Total: <?php echo number_format(totalPedido($pedido['lineas']), 2, ',', '.'); ?> €</strong>
        <div class="d-flex gap-2">
          <?php if ($esAdmin && $estado == 'pendiente') { ?>
            <form action="/admin/index.php" method="post">
              <input type="hidden" name="accion" value="aceptar">
              <input type="hidden" name="pedido_id" value="<?php echo (string)$pedido['_id']; ?>">
              <button class="btn btn-sm btn-success"><i class="bi bi-check-lg"></i> Aceptar</button>
            </form>
          <?php } ?>
          <?php if ($estado == 'pendiente' || ($esAdmin && $estado == 'aceptado')) { ?>
            <form action="<?php echo $destino; ?>" method="post" data-confirmar="¿Cancelar este pedido? Las unidades volverán al stock.">
              <input type="hidden" name="accion" value="cancelar_pedido">
              <input type="hidden" name="pedido_id" value="<?php echo (string)$pedido['_id']; ?>">
              <button class="btn btn-sm btn-outline-danger"><i class="bi bi-x-lg"></i> Cancelar pedido</button>
            </form>
          <?php } ?>
        </div>
      </div>
    </div>
    <?php
}
