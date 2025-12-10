<?php
if (isset($_POST['codProduct'])) {
    $codProduct = $_POST['codProduct'];
    $imageDirectory = '../../ready/';
    $images = array();
    
    // Buscar todas las imágenes que comiencen con el código del producto + "_"
    foreach (glob($imageDirectory . $codProduct . "_*") as $filename) {
        $images[] = basename($filename);
    }
    
    echo json_encode(['images' => $images]);
} else {
    echo json_encode(['error' => 'Código de producto no proporcionado']);
}
?>