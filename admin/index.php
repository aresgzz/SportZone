<?php
// Panel del administrador: pedidos, productos y mensajes
require_once '../includes/conexion.php';
exigirAdmin();

// Lee y valida el precio y el stock que vienen de un formulario. Devuelve '' si todo está bien
function validarProducto($nombre, $descripcion, $precio, $stock) {
    if ($nombre == '' || mb_strlen($nombre) > 60) {
        return 'El nombre es obligatorio (máximo 60 caracteres).';
    }
    if (mb_strlen($descripcion) > 300) {
        return 'La descripción no puede pasar de 300 caracteres.';
    }
    if (!is_numeric($precio) || $precio <= 0 || $precio > 100000) {
        return 'El precio debe ser un número mayor que 0.';
    }
    if (!ctype_digit($stock) || (int)$stock > 100000) {
        return 'El stock debe ser un número entero de 0 o más.';
    }
    return '';
}

$pestana = 'pedidos';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $accion = dato('accion');

    //  PEDIDOS 
    if ($accion == 'aceptar') {
        $r = $db->pedidos->updateOne(
            ['_id' => oid(dato('pedido_id')), 'estado' => 'pendiente'],
            ['$set' => ['estado' => 'aceptado']]
        );
        if ($r->getModifiedCount() == 1) {
            avisar('success', 'Pedido aceptado.');
        } else {
            avisar('danger', 'Ese pedido ya no está pendiente.');
        }
        irA('/admin/index.php?p=pedidos');
    }

    if ($accion == 'cancelar_pedido') {
        // El admin puede cancelar pedidos pendientes y aceptados (por algo es el admin)
        if (cancelarPedido($db, oid(dato('pedido_id')), ['pendiente', 'aceptado'], null)) {
            avisar('success', 'Pedido cancelado. Las unidades han vuelto al stock.');
        } else {
            avisar('danger', 'Ese pedido ya no se puede cancelar.');
        }
        irA('/admin/index.php?p=pedidos');
    }

    if ($accion == 'quitar_linea') {
        if (quitarLinea($db, oid(dato('pedido_id')), oid(dato('producto_id')), ['pendiente'], null)) {
            avisar('success', 'Línea eliminada. Las unidades han vuelto al stock.');
        } else {
            avisar('danger', 'No se puede quitar esa línea.');
        }
        irA('/admin/index.php?p=pedidos');
    }

    //  PRODUCTOS 
    if ($accion == 'nuevo_producto') {
        $nombre = dato('nombre');
        $descripcion = dato('descripcion');
        $precio = str_replace(',', '.', dato('precio'));
        $stock = dato('stock');
        $error = validarProducto($nombre, $descripcion, $precio, $stock);

        $imagen = '';
        if ($error == '' && isset($_FILES['imagen']) && $_FILES['imagen']['error'] != UPLOAD_ERR_NO_FILE) {
            $imagen = guardarImagen($_FILES['imagen'], 'producto');
            if ($imagen == '') {
                $error = 'La imagen debe ser JPG, PNG, GIF o WEBP de máximo 2 MB.';
            }
        }

        if ($error != '') {
            avisar('danger', $error);
        } else {
            $db->productos->insertOne([
                'nombre' => $nombre, 'descripcion' => $descripcion,
                'precio' => (float)$precio, 'stock' => (int)$stock, 'imagen' => $imagen
            ]);
            avisar('success', 'Producto añadido.');
        }
        irA('/admin/index.php?p=productos');
    }

    if ($accion == 'editar_producto') {
        $id = oid(dato('producto_id'));
        $producto = $db->productos->findOne(['_id' => $id]);
        if ($producto == null) {
            avisar('danger', 'Ese producto no existe.');
            irA('/admin/index.php?p=productos');
        }
        $nombre = dato('nombre');
        $descripcion = dato('descripcion');
        $precio = str_replace(',', '.', dato('precio'));
        $stock = dato('stock');
        $error = validarProducto($nombre, $descripcion, $precio, $stock);

        $imagen = $producto['imagen'];
        if ($error == '' && isset($_FILES['imagen']) && $_FILES['imagen']['error'] != UPLOAD_ERR_NO_FILE) {
            $imagen = guardarImagen($_FILES['imagen'], 'producto');
            if ($imagen == '') {
                $error = 'La imagen debe ser JPG, PNG, GIF o WEBP de máximo 2 MB.';
            }
        }

        if ($error != '') {
            avisar('danger', $error);
        } else {
            $db->productos->updateOne(['_id' => $id], ['$set' => [
                'nombre' => $nombre, 'descripcion' => $descripcion,
                'precio' => (float)$precio, 'stock' => (int)$stock, 'imagen' => $imagen
            ]]);
            if ($imagen != $producto['imagen']) {
                borrarImagen($producto['imagen']);
            }
            avisar('success', 'Producto actualizado.');
        }
        irA('/admin/index.php?p=productos');
    }

    if ($accion == 'borrar_producto') {
        $id = oid(dato('producto_id'));
        $producto = $db->productos->findOne(['_id' => $id]);
        if ($producto != null) {
            $db->productos->deleteOne(['_id' => $id]);
            borrarImagen($producto['imagen']);
            avisar('success', 'Producto eliminado. Los pedidos antiguos conservan su nombre y su precio.');
        }
        irA('/admin/index.php?p=productos');
    }

    //  MENSAJES 
    if ($accion == 'borrar_mensaje') {
        $db->mensajes->deleteOne(['_id' => oid(dato('mensaje_id'))]);
        avisar('success', 'Mensaje eliminado.');
        irA('/admin/index.php?p=mensajes');
    }

    irA('/admin/index.php');
}

include '../includes/cabecera.php';

// Pestaña que se muestra 
if (isset($_GET['p']) && ($_GET['p'] == 'productos' || $_GET['p'] == 'mensajes')) {
    $pestana = $_GET['p'];
}

// LOs datos de cada pestaña 
$pedidos = $db->pedidos->find(['estado' => ['$ne' => 'carrito']], ['sort' => ['fecha' => -1]])->toArray();
$productos = $db->productos->find([], ['sort' => ['nombre' => 1]])->toArray();
$mensajes = $db->mensajes->find([], ['sort' => ['fecha' => -1]])->toArray();

// Para escribir el nombre del cliente en cada pedido buscamos todos los usuarios una vez 
$clientes = [];
foreach ($db->usuarios->find([])->toArray() as $u) {
    $clientes[(string)$u['_id']] = $u;
}
$pendientes = 0;
foreach ($pedidos as $p) {
    if ($p['estado'] == 'pendiente') {
        $pendientes++;
    }
}
?>

<section class="cabecera-pagina text-white py-4">
  <div class="container">
    <h1 class="h2 mb-0"><i class="bi bi-speedometer2"></i> Panel de administración</h1>
  </div>
</section>

<div class="container py-4">
  <ul class="nav nav-tabs mb-4">
    <li class="nav-item">
      <a class="nav-link <?php if ($pestana == 'pedidos') echo 'active'; ?>" href="/admin/index.php?p=pedidos">
        Pedidos <?php if ($pendientes > 0) { ?><span class="badge text-bg-warning"><?php echo $pendientes; ?></span><?php } ?>
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link <?php if ($pestana == 'productos') echo 'active'; ?>" href="/admin/index.php?p=productos">Productos y stock</a>
    </li>
    <li class="nav-item">
      <a class="nav-link <?php if ($pestana == 'mensajes') echo 'active'; ?>" href="/admin/index.php?p=mensajes">
        Mensajes <span class="badge text-bg-secondary"><?php echo count($mensajes); ?></span>
      </a>
    </li>
  </ul>

  <?php if ($pestana == 'pedidos') { ?>
    <?php if (count($pedidos) == 0) { ?>
      <p class="text-muted text-center py-4">Todavía no hay pedidos.</p>
    <?php } ?>
    <?php foreach ($pedidos as $pedido) { ?>
      <?php
        $cliente = ['nombre' => '(usuario eliminado)', 'email' => ''];
        if (isset($clientes[(string)$pedido['usuario_id']])) {
            $cliente = $clientes[(string)$pedido['usuario_id']];
        }
        mostrarPedido($pedido, true, $cliente);
      ?>
    <?php } ?>
  <?php } ?>

  <?php if ($pestana == 'productos') { ?>

    <div class="card shadow-sm mb-4">
      <div class="card-body">
        <h2 class="h5 mb-3"><i class="bi bi-plus-circle"></i> Añadir producto</h2>
        <form method="post" enctype="multipart/form-data" class="validar row g-3" novalidate>
          <input type="hidden" name="accion" value="nuevo_producto">
          <div class="col-md-6">
            <label class="form-label">Nombre</label>
            <input type="text" name="nombre" class="form-control" maxlength="60" required>
            <div class="invalid-feedback">Escribe el nombre.</div>
          </div>
          <div class="col-md-3">
            <label class="form-label">Precio (€)</label>
            <input type="number" name="precio" class="form-control" step="0.01" min="0.01" required>
            <div class="invalid-feedback">Precio mayor que 0.</div>
          </div>
          <div class="col-md-3">
            <label class="form-label">Stock</label>
            <input type="number" name="stock" class="form-control" step="1" min="0" value="0" required>
            <div class="invalid-feedback">Stock de 0 o más.</div>
          </div>
          <div class="col-md-8">
            <label class="form-label">Descripción</label>
            <input type="text" name="descripcion" class="form-control" maxlength="300">
          </div>
          <div class="col-md-4">
            <label class="form-label">Imagen</label>
            <input type="file" name="imagen" class="form-control" accept="image/jpeg,image/png,image/gif,image/webp">
          </div>
          <div class="col-12">
            <button class="btn btn-primary"><i class="bi bi-plus-lg"></i> Añadir producto</button>
          </div>
        </form>
      </div>
    </div>

    <div class="row g-3">
      <?php foreach ($productos as $p) { ?>
        <div class="col-md-6">
          <div class="card shadow-sm h-100">
            <div class="card-body">
              <form method="post" enctype="multipart/form-data" class="validar" novalidate>
                <input type="hidden" name="accion" value="editar_producto">
                <input type="hidden" name="producto_id" value="<?php echo (string)$p['_id']; ?>">
                <div class="d-flex gap-3 mb-2">
                  <img src="<?php echo h(imagenProducto($p['imagen'])); ?>" class="miniatura-grande" alt="">
                  <div class="flex-grow-1">
                    <input type="text" name="nombre" class="form-control mb-2" maxlength="60" value="<?php echo h($p['nombre']); ?>" required>
                    <input type="text" name="descripcion" class="form-control" maxlength="300" value="<?php echo h($p['descripcion']); ?>">
                  </div>
                </div>
                <div class="row g-2 mb-2">
                  <div class="col-6">
                    <label class="form-label small mb-0">Precio (€)</label>
                    <input type="number" name="precio" class="form-control" step="0.01" min="0.01" value="<?php echo h($p['precio']); ?>" required>
                  </div>
                  <div class="col-6">
                    <label class="form-label small mb-0">Stock</label>
                    <input type="number" name="stock" class="form-control" step="1" min="0" value="<?php echo (int)$p['stock']; ?>" required>
                  </div>
                </div>
                <label class="form-label small mb-0">Cambiar imagen</label>
                <input type="file" name="imagen" class="form-control form-control-sm mb-3" accept="image/jpeg,image/png,image/gif,image/webp">
                <button class="btn btn-sm btn-primary"><i class="bi bi-save"></i> Guardar</button>
              </form>
              <form method="post" class="mt-2" data-confirmar="¿Eliminar este producto de la tienda?">
                <input type="hidden" name="accion" value="borrar_producto">
                <input type="hidden" name="producto_id" value="<?php echo (string)$p['_id']; ?>">
                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i> Eliminar</button>
              </form>
            </div>
          </div>
        </div>
      <?php } ?>
    </div>
  <?php } ?>

  <?php if ($pestana == 'mensajes') { ?>
    <?php if (count($mensajes) == 0) { ?>
      <p class="text-muted text-center py-4">No hay mensajes.</p>
    <?php } ?>
    <?php foreach ($mensajes as $m) { ?>
      <div class="card shadow-sm mb-3">
        <div class="card-body">
          <div class="d-flex justify-content-between gap-2">
            <div>
              <h2 class="h6 mb-0"><?php echo h($m['asunto']); ?></h2>
              <div class="small text-muted"><?php echo h($m['nombre']); ?> · <?php echo h($m['email']); ?> · <?php echo h($m['fecha']); ?></div>
            </div>
            <form method="post" data-confirmar="¿Eliminar este mensaje?">
              <input type="hidden" name="accion" value="borrar_mensaje">
              <input type="hidden" name="mensaje_id" value="<?php echo (string)$m['_id']; ?>">
              <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
            </form>
          </div>
          <p class="mt-2 mb-0"><?php echo nl2br(h($m['mensaje'])); ?></p>
        </div>
      </div>
    <?php } ?>
  <?php } ?>
</div>

<?php include '../includes/pie.php'; ?>
