<?php
// Conexión con MongoDB Atlas, sesión y funciones que usan varias páginas

date_default_timezone_set('Europe/Madrid');
define('RAIZ', dirname(__DIR__));
require __DIR__ . '/../../vendor/autoload.php';
session_start();


// CONEXIÓN 
// Los datos de conexión están en el archivo .env 
try {
    $cliente = new MongoDB\Client(getenv('MONGODB_URI'), [], [
        // Pido que los documentos lleguen como arrays normales de PHP
        'typeMap' => ['root' => 'array', 'document' => 'array', 'array' => 'array']
    ]);
    $nombreBD = getenv('MONGODB_DB');
    if (!$nombreBD) {
        $nombreBD = 'tiendadeportes';
    }
    $db = $cliente->selectDatabase($nombreBD);
    // Compruebo que la conexión funciona
    $cliente->selectDatabase('admin')->command(['ping' => 1]);
} catch (Exception $error) {
    die('No se pudo conectar con MongoDB Atlas. Revisa el archivo .env, tu IP en Network Access de Atlas y que el cluster no esté pausado.');
}


// DATOS DE EJEMPLO (solo se crean la primera vez si la base de datos está vacía) 
if ($db->usuarios->countDocuments() == 0) {
    // El email no se puede repetir y cada usuario solo puede tener 1 carrito
    $db->usuarios->createIndex(['email' => 1], ['unique' => true]);
    $db->pedidos->createIndex(['usuario_id' => 1], ['unique' => true, 'partialFilterExpression' => ['estado' => 'carrito']]);

    $db->usuarios->insertOne([
        'nombre' => 'Administrador', 'email' => 'admin@sports.com',
        'password' => password_hash('admin123', PASSWORD_DEFAULT), 'rol' => 'admin', 'foto' => ''
    ]);
    $db->usuarios->insertOne([
        'nombre' => 'Ares', 'email' => 'ares@correo.com',
        'password' => password_hash('ares123', PASSWORD_DEFAULT), 'rol' => 'comprador', 'foto' => ''
    ]);
}
if ($db->productos->countDocuments() == 0) {
    $lista = [
        ['Balón de fútbol Pro Match', 'Balón oficial talla 5, cosido a máquina.', 24.95, 14, 'balon'],
        ['Zapatillas running Aero', 'Ligeras y con amortiguación para correr.', 69.90, 9, 'zapatillas'],
        ['Camiseta técnica Dry-Fit', 'Tejido transpirable que seca rápido.', 19.99, 25, 'camiseta'],
        ['Pala de pádel Carbon X', 'Fibra de carbono y gran control.', 129.00, 4, 'pala'],
        ['Mochila deportiva 30L', 'Con compartimento para zapatillas.', 34.50, 12, 'mochila'],
        ['Mancuernas ajustables 20 kg', 'Pareja de mancuernas con discos.', 54.00, 6, 'mancuernas'],
        ['Esterilla de yoga Flex', 'Antideslizante y de 6 mm de grosor.', 17.90, 18, 'esterilla'],
        ['Guantes de boxeo Fight', 'Guantes de 12 oz con velcro.', 39.90, 0, 'guantes'],
        ['Casco de ciclismo Speed', 'Ventilado, ajustable y homologado.', 44.95, 7, 'casco'],
        ['Botella térmica 750 ml', 'Mantiene el frío 24 horas.', 15.50, 2, 'botella']
    ];
    for ($i = 0; $i < count($lista); $i++) {
        $db->productos->insertOne([
            'nombre' => $lista[$i][0], 'descripcion' => $lista[$i][1],
            'precio' => $lista[$i][2], 'stock' => $lista[$i][3],
            'imagen' => 'img/' . $lista[$i][4] . '.jpg'
        ]);
    }
}


// CARGO EL RESTO DE ARCHIVOS COMUNES 
require_once __DIR__ . '/funciones.php';   // helpers, usuario con sesión, permisos e imágenes
require_once __DIR__ . '/stock.php';       // carrito, stock y pedidos
