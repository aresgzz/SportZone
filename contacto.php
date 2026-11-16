<?php
// PÁGINA 3: EL FORMULARIO DE CONTACTO
require_once 'includes/conexion.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = dato('nombre');
    $email = dato('email');
    $asunto = dato('asunto');
    $texto = dato('mensaje');

    if ($nombre == '' || mb_strlen($nombre) > 50) {
        avisar('danger', 'Escribe tu nombre (máximo 50 caracteres).');
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        avisar('danger', 'El email no es válido.');
    } elseif ($asunto == '' || mb_strlen($asunto) > 100) {
        avisar('danger', 'Escribe un asunto (máximo 100 caracteres).');
    } elseif (mb_strlen($texto) < 10 || mb_strlen($texto) > 1000) {
        avisar('danger', 'El mensaje debe tener entre 10 y 1000 caracteres.');
    } else {
        $db->mensajes->insertOne([
            'nombre' => $nombre, 'email' => $email, 'asunto' => $asunto,
            'mensaje' => $texto, 'fecha' => date('Y-m-d H:i:s')
        ]);
        avisar('success', '¡Mensaje enviado! Te responderemos lo antes posible.');
    }
    irA('/contacto.php');
}

include 'includes/cabecera.php';

// Si ya tengo una sesión iniciada se rellena el nombre y el email
$nombreForm = '';
$emailForm = '';
if ($usuario != null) {
    $nombreForm = $usuario['nombre'];
    $emailForm = $usuario['email'];
}
?>

<section class="cabecera-pagina text-white py-4">
  <div class="container">
    <h1 class="h2 mb-1">Contacto</h1>
    <p class="mb-0 opacity-75">¿Dudas sobre un producto o un pedido? Escríbenos.</p>
  </div>
</section>

<div class="container py-4">
  <div class="row g-4">
    <div class="col-lg-7">
      <div class="card shadow-sm">
        <div class="card-body p-4">
          <form method="post" class="validar" novalidate>
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label">Nombre</label>
                <input type="text" name="nombre" class="form-control" maxlength="50" value="<?php echo h($nombreForm); ?>" required>
                <div class="invalid-feedback">Escribe tu nombre.</div>
              </div>
              <div class="col-md-6">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" maxlength="100" value="<?php echo h($emailForm); ?>" required>
                <div class="invalid-feedback">Escribe un email válido.</div>
              </div>
              <div class="col-12">
                <label class="form-label">Asunto</label>
                <input type="text" name="asunto" class="form-control" maxlength="100" required>
                <div class="invalid-feedback">Escribe un asunto.</div>
              </div>
              <div class="col-12">
                <label class="form-label">Mensaje</label>
                <textarea name="mensaje" rows="5" class="form-control" minlength="10" maxlength="1000" required></textarea>
                <div class="invalid-feedback">El mensaje debe tener entre 10 y 1000 caracteres.</div>
              </div>
              <div class="col-12">
                <button class="btn btn-primary"><i class="bi bi-send"></i> Enviar mensaje</button>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>

    <div class="col-lg-5">
      <div class="card shadow-sm h-100">
        <div class="card-body p-4">
          <h2 class="h5 mb-3">Información de la tienda</h2>
          <p><i class="bi bi-geo-alt text-primary"></i> Calle del Bicho, 7 · Madeira</p>
          <p><i class="bi bi-telephone text-primary"></i> 777 777 777</p>
          <p><i class="bi bi-envelope text-primary"></i> info@sportzone.com</p>
          <p class="mb-0"><i class="bi bi-clock text-primary"></i> Lunes a sábado, de 9:00 a 20:00</p>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include 'includes/pie.php'; ?>
