<?php


// FUNCIONES ÚTILES 

// Sirve para convertit caracteres especiales en HTML y que nadie pueda colar código en la página
function h($texto) {
    return htmlspecialchars((string)$texto, ENT_QUOTES, 'UTF-8');
}

// Convierte un id (texto) en un ObjectId de MongoDB. Si el id no es válido devuelve uno que no existe, así no se rompe nada
function oid($id) {
    try {
        return new MongoDB\BSON\ObjectId((string)$id);
    } catch (Exception $error) {
        return new MongoDB\BSON\ObjectId('000000000000000000000000');
    }
}

// Lee un dato de un formulario. Solo aceptamos textos (nunca arrays)
function dato($nombre) {
    if (isset($_POST[$nombre]) && is_string($_POST[$nombre])) {
        return trim($_POST[$nombre]);
    }
    return '';
}

// Guarda un aviso para enseñarlo en la página siguiente. $tipo: success, danger, warning o info
function avisar($tipo, $texto) {
    $_SESSION['aviso'] = ['tipo' => $tipo, 'texto' => $texto];
}

// Redirige a otra página y para el script
function irA($pagina) {
    header('Location: ' . $pagina);
    exit;
}


// USUARIO Y PERMISOS 

// En la sesión sólo guardo el id. Los datos y rol se leen de la base de datos en cada página
$usuario = null;
if (isset($_SESSION['usuario_id'])) {
    $usuario = $db->usuarios->findOne(['_id' => oid($_SESSION['usuario_id'])]);
    if ($usuario == null) {
        unset($_SESSION['usuario_id']);
    }
}

function esAdmin() {
    global $usuario;
    return $usuario != null && $usuario['rol'] == 'admin';
}

function esComprador() {
    global $usuario;
    return $usuario != null && $usuario['rol'] == 'comprador';
}

function exigirLogin() {
    global $usuario;
    if ($usuario == null) {
        avisar('warning', 'Tienes que iniciar sesión para entrar ahí.');
        irA('/acceso.php');
    }
}

function exigirAdmin() {
    if (!esAdmin()) {
        avisar('danger', 'No tienes permiso para entrar en esa página.');
        irA('/index.php');
    }
}

function exigirComprador() {
    exigirLogin();
    if (!esComprador()) {
        avisar('danger', 'Esa página es solo para compradores.');
        irA('/index.php');
    }
}


// IMÁGENES 

// Guarda una imagen subida en la carpeta uploads y devuelve su ruta 
function guardarImagen($archivo, $prefijo) {
    if (!isset($archivo['error']) || $archivo['error'] != UPLOAD_ERR_OK) {
        return '';
    }
    if ($archivo['size'] > 2 * 1024 * 1024) {
        return '';
    }
    // getimagesize lee el contenido del archivo: si no es una imagen devuelve false
    $info = getimagesize($archivo['tmp_name']);
    if ($info == false) {
        return '';
    }
    // La extensión la elijo según el tipo real de imagen, no según el nombre que traiga
    $extension = '';
    if ($info[2] == IMAGETYPE_JPEG) {
        $extension = 'jpg';
    } elseif ($info[2] == IMAGETYPE_PNG) {
        $extension = 'png';
    } elseif ($info[2] == IMAGETYPE_GIF) {
        $extension = 'gif';
    } elseif ($info[2] == IMAGETYPE_WEBP) {
        $extension = 'webp';
    } else {
        return '';
    }
    $ruta = 'uploads/' . $prefijo . '_' . uniqid() . '.' . $extension;
    if (!move_uploaded_file($archivo['tmp_name'], RAIZ . '/' . $ruta)) {
        return '';
    }
    return $ruta;
}

// Borra una imagen subida 
function borrarImagen($ruta) {
    if ($ruta != '' && strpos($ruta, 'uploads/') === 0 && strpos($ruta, '..') === false && file_exists(RAIZ . '/' . $ruta)) {
        unlink(RAIZ . '/' . $ruta);
    }
}

// Si el producto no tiene imagen enseño una por defecto que he metido
function imagenProducto($ruta) {
    if ($ruta != '' && file_exists(RAIZ . '/' . $ruta)) {
        return '/' . $ruta;
    }
    return '/img/sin_imagen.svg';
}

