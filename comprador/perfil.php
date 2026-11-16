<?php
// Perfil: cambiar nombre, foto y contraseña (tanto para comprador como administrador)
require_once '../includes/conexion.php';
exigirLogin();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $accion = dato('accion');

    //  GUARDAR NOMBRE Y FOTO 
    if ($accion == 'datos') {
        $nombre = dato('nombre');
        if ($nombre == '' || mb_strlen($nombre) > 50) {
            avisar('danger', 'Escribe tu nombre (máximo 50 caracteres).');
            irA('/comprador/perfil.php');
        }

        $foto = $usuario['foto'];
        $fotoNueva = '';
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] != UPLOAD_ERR_NO_FILE) {
            $fotoNueva = guardarImagen($_FILES['foto'], 'usuario');
            if ($fotoNueva == '') {
                avisar('danger', 'La foto debe ser una imagen JPG, PNG, GIF o WEBP de máximo 2 MB.');
                irA('/comprador/perfil.php');
            }
            $foto = $fotoNueva;
        } elseif (dato('quitar_foto') == '1') {
            $foto = '';
        }

        $db->usuarios->updateOne(['_id' => $usuario['_id']], ['$set' => ['nombre' => $nombre, 'foto' => $foto]]);
        // Si hay foto nueva, borro la anterior del disco
        if ($foto != $usuario['foto']) {
            borrarImagen($usuario['foto']);
        }
        avisar('success', 'Perfil actualizado.');
        irA('/comprador/perfil.php');
    }

    //  CAMBIAR  CONTRASEÑA 
    if ($accion == 'password') {
        $actual = isset($_POST['actual']) && is_string($_POST['actual']) ? $_POST['actual'] : '';
        $nueva = isset($_POST['nueva']) && is_string($_POST['nueva']) ? $_POST['nueva'] : '';
        $repetir = isset($_POST['repetir']) && is_string($_POST['repetir']) ? $_POST['repetir'] : '';

        if (!password_verify($actual, $usuario['password'])) {
            avisar('danger', 'La contraseña actual no es correcta.');
        } elseif (strlen($nueva) < 6 || strlen($nueva) > 72) {
            avisar('danger', 'La nueva contraseña debe tener entre 6 y 72 caracteres.');
        } elseif ($nueva != $repetir) {
            avisar('danger', 'Las contraseñas nuevas no coinciden.');
        } else {
            $db->usuarios->updateOne(['_id' => $usuario['_id']], ['$set' => ['password' => password_hash($nueva, PASSWORD_DEFAULT)]]);
            avisar('success', 'Contraseña cambiada correctamente.');
        }
        irA('/comprador/perfil.php');
    }
    irA('/comprador/perfil.php');
}

include '../includes/cabecera.php';
?>

<section class="cabecera-pagina text-white py-4">
  <div class="container d-flex align-items-center gap-3">
    <?php if ($usuario['foto'] != '') { ?>
      <img src="<?php echo '/' . h($usuario['foto']); ?>" class="avatar avatar-grande" alt="">
    <?php } else { ?>
      <span class="avatar avatar-grande avatar-letra"><?php echo h(strtoupper(mb_substr($usuario['nombre'], 0, 1))); ?></span>
    <?php } ?>
    <div>
      <h1 class="h2 mb-0"><?php echo h($usuario['nombre']); ?></h1>
      <div class="opacity-75"><?php echo h($usuario['email']); ?></div>
    </div>
  </div>
</section>

<div class="container py-4">
  <div class="row g-4">

    <div class="col-lg-7">
      <div class="card shadow-sm">
        <div class="card-body p-4">
          <h2 class="h5 mb-3">Mis datos</h2>
          <form method="post" enctype="multipart/form-data" class="validar" novalidate>
            <input type="hidden" name="accion" value="datos">
            <div class="mb-3">
              <label class="form-label">Email</label>
              <input class="form-control" value="<?php echo h($usuario['email']); ?>" disabled>
            </div>
            <div class="mb-3">
              <label class="form-label">Nombre</label>
              <input type="text" name="nombre" class="form-control" maxlength="50" value="<?php echo h($usuario['nombre']); ?>" required>
              <div class="invalid-feedback">Escribe tu nombre.</div>
            </div>
            <div class="mb-3">
              <label class="form-label">Foto de perfil</label>
              <input type="file" name="foto" id="campoFoto" class="form-control" accept="image/jpeg,image/png,image/gif,image/webp">
              <div class="form-text">JPG, PNG, GIF o WEBP de máximo 2 MB.</div>
              <img id="vistaFoto" class="avatar avatar-grande mt-2 d-none" alt="Vista previa">
              <?php if ($usuario['foto'] != '') { ?>
                <div class="form-check mt-2">
                  <input class="form-check-input" type="checkbox" name="quitar_foto" value="1" id="quitarFoto">
                  <label class="form-check-label" for="quitarFoto">Quitar mi foto</label>
                </div>
              <?php } ?>
            </div>
            <button class="btn btn-primary"><i class="bi bi-save"></i> Guardar cambios</button>
          </form>
        </div>
      </div>
    </div>

    <div class="col-lg-5">
      <div class="card shadow-sm">
        <div class="card-body p-4">
          <h2 class="h5 mb-3">Cambiar contraseña</h2>
          <form method="post" class="validar" novalidate>
            <input type="hidden" name="accion" value="password">
            <div class="mb-3">
              <label class="form-label">Contraseña actual</label>
              <input type="password" name="actual" class="form-control" required>
              <div class="invalid-feedback">Escribe tu contraseña actual.</div>
            </div>
            <div class="mb-3">
              <label class="form-label">Nueva contraseña</label>
              <input type="password" name="nueva" class="form-control campo-password" minlength="6" maxlength="72" required>
              <div class="invalid-feedback">Mínimo 6 caracteres.</div>
            </div>
            <div class="mb-3">
              <label class="form-label">Repite la nueva contraseña</label>
              <input type="password" name="repetir" class="form-control campo-repetir" required>
              <div class="invalid-feedback">Las contraseñas no coinciden.</div>
            </div>
            <button class="btn btn-outline-primary"><i class="bi bi-key"></i> Cambiar contraseña</button>
          </form>
        </div>
      </div>
    </div>

  </div>
</div>

<?php include '../includes/pie.php'; ?>
