<?php
// deleteProductImage.php

header('Content-Type: application/json');

// Verificar si se recibió el nombre de la imagen
if (!isset($_POST['imageName']) || empty($_POST['imageName'])) {
    echo json_encode(['success' => false, 'error' => 'Nombre de imagen no proporcionado']);
    exit;
}

// Obtener el nombre de la imagen
$imageName = $_POST['imageName'];

// Validar que el nombre solo contenga caracteres seguros
if (!preg_match('/^[a-zA-Z0-9_-]+\.[a-zA-Z0-9]+$/', $imageName)) {
    echo json_encode(['success' => false, 'error' => 'Nombre de imagen inválido']);
    exit;
}

// Rutas a los directorios de imágenes
$directories = [
    '../../ready/',
    '../../uploads/'
];

$results = [];
$successCount = 0;

foreach ($directories as $directory) {
    $imagePath = $directory . $imageName;
    
    // Verificar si el archivo existe
    if (!file_exists($imagePath)) {
        $results[$directory] = 'La imagen no existe en este directorio';
        continue;
    }

    // Verificar que es un archivo de imagen (opcional pero recomendado)
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($fileInfo, $imagePath);
    finfo_close($fileInfo);

    if (!in_array($mimeType, $allowedTypes)) {
        $results[$directory] = 'El archivo no es una imagen válida';
        continue;
    }

    // Intentar eliminar la imagen
    if (unlink($imagePath)) {
        $results[$directory] = 'Imagen eliminada correctamente';
        $successCount++;
    } else {
        $results[$directory] = 'No se pudo eliminar la imagen';
    }
}

// Verificar si se eliminó en al menos un directorio
if ($successCount > 0) {
    echo json_encode([
        'success' => true,
        'message' => 'Operación completada',
        'results' => $results
    ]);
} else {
    echo json_encode([
        'success' => false,
        'error' => 'No se pudo eliminar la imagen en ningún directorio',
        'results' => $results
    ]);
}
?>