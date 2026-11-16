<?php
// Iniciar sesión o crear una cuenta
require_once 'includes/conexion.php';

if ($usuario != null) {
    irA('/tienda.php');
}

$error = '';
$pestana = 'entrar';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $accion = dato('accion');
    $email = strtolower(dato('email'));
    $password = '';
    if (isset($_POST['password']) && is_string($_POST['password'])) {
        $password = $_POST['password'];
    }

    //  CREAR CUENTA 
    if ($accion == 'registro') {
        $pestana = 'registro';
        $nombre = dato('nombre');
        $repetir = '';
        if (isset($_POST['repetir']) && is_string($_POST['repetir'])) {
            $repetir = $_POST['repetir'];
        }

        if ($nombre == '' || mb_strlen($nombre) > 50) {
            $error = 'Escribe tu nombre (máximo 50 caracteres).';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'El email no es válido.';
        } elseif (strlen($password) < 6 || strlen($password) > 72) {
            $error = 'La contraseña debe tener entre 6 y 72 caracteres.';
        } elseif ($password != $repetir) {
            $error = 'Las contraseñas no coinciden.';
        } elseif ($db->usuarios->findOne(['email' => $email]) != null) {
            $error = 'Ese email ya está registrado.';
        } else {
            // Todas las cuentas nuevas deben ser de comprador
            try {
                $db->usuarios->insertOne([
                    'nombre' => $nombre, 'email' => $email,
                    'password' => password_hash($password, PASSWORD_DEFAULT),
                    'rol' => 'comprador', 'foto' => ''
                ]);
            } catch (Exception $e) {
                $error = 'Ese email ya está registrado.';
            }
        }
    }

    //  INICIAR SESIÓN 
    if ($error == '') {
        $encontrado = $db->usuarios->findOne(['email' => $email]);
        if ($encontrado != null && password_verify($password, $encontrado['password'])) {
            session_regenerate_id(true);
            $_SESSION['usuario_id'] = (string)$encontrado['_id'];
            avisar('success', '¡Bienvenido, ' . $encontrado['nombre'] . '!');
            irA('/tienda.php');
        }
        $error = 'Email o contraseña incorrectos.';
    }
}

include 'includes/cabecera.php';
?>

<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
      <div class="card shadow-sm">
        <div class="card-body p-4">
          <h1 class="h3 text-center mb-4">Accede a SportZone</h1>

          <?php if ($error != '') { ?>
            <div class="alert alert-danger"><?php echo h($error); ?></div>
          <?php } ?>

          <ul class="nav nav-pills nav-fill mb-4">
            <li class="nav-item"><button type="button" class="nav-link <?php if ($pestana == 'entrar') { echo 'active'; } ?>" data-bs-toggle="pill" data-bs-target="#panelEntrar">Entrar</button></li>
            <li class="nav-item"><button type="button" class="nav-link <?php if ($pestana == 'registro') { echo 'active'; } ?>" data-bs-toggle="pill" data-bs-target="#panelRegistro">Crear cuenta</button></li>
          </ul>

          <div class="tab-content">
            <!-- Entrar -->
            <div class="tab-pane fade <?php if ($pestana == 'entrar') { echo 'show active'; } ?>" id="panelEntrar">
              <form method="post" class="validar" novalidate>
                <input type="hidden" name="accion" value="login">
                <div class="mb-3">
                  <label class="form-label">Email</label>
                  <input type="email" name="email" class="form-control" required>
                  <div class="invalid-feedback">Escribe un email válido.</div>
                </div>
                <div class="mb-3">
                  <label class="form-label">Contraseña</label>
                  <input type="password" name="password" class="form-control" required>
                  <div class="invalid-feedback">Escribe tu contraseña.</div>
                </div>
                <button class="btn btn-primary w-100">Entrar</button>
              </form>
            </div>

            <!-- Crear cuenta -->
            <div class="tab-pane fade <?php if ($pestana == 'registro') { echo 'show active'; } ?>" id="panelRegistro">
              <form method="post" class="validar" novalidate>
                <input type="hidden" name="accion" value="registro">
                <div class="mb-3">
                  <label class="form-label">Nombre</label>
                  <input type="text" name="nombre" class="form-control" maxlength="50" required>
                  <div class="invalid-feedback">Escribe tu nombre.</div>
                </div>
                <div class="mb-3">
                  <label class="form-label">Email</label>
                  <input type="email" name="email" class="form-control" maxlength="100" required>
                  <div class="invalid-feedback">Escribe un email válido.</div>
                </div>
                <div class="mb-3">
                  <label class="form-label">Contraseña</label>
                  <input type="password" name="password" class="form-control campo-password" minlength="6" maxlength="72" required>
                  <div class="invalid-feedback">Mínimo 6 caracteres.</div>
                </div>
                <div class="mb-3">
                  <label class="form-label">Repite la contraseña</label>
                  <input type="password" name="repetir" class="form-control campo-repetir" required>
                  <div class="invalid-feedback">Las contraseñas no coinciden.</div>
                </div>
                <button class="btn btn-primary w-100">Crear cuenta y entrar</button>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include 'includes/pie.php'; ?>
