<?php
header("Content-Type: application/json; charset=UTF-8");

$uploadDir = "../../uploads/";
$allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];

$idProducto = isset($_POST['codProduct']) ? $_POST['codProduct'] : null;
$peso = isset($_POST['peso']) ? $_POST['peso'] : null;

if (empty($idProducto)) {
    echo json_encode(['error' => 'ID de producto o peso no proporcionados']);
    exit;
}

include 'connectDataBase.php';
$connection->set_charset("utf8mb4");

if ($peso !== null) {
    $stmt = $connection->prepare("UPDATE producto SET peso = ? WHERE id = ?");
    $stmt->bind_param("ds", $peso, $idProducto);
    if (!$stmt->execute()) {
        echo json_encode(['error' => 'Error al actualizar el peso del producto: ' . $stmt->error]);
        exit;
    }
    $stmt->close();
}

if(!empty($_FILES['subirImagenes']['name'])){
    $totalFiles = count($_FILES['subirImagenes']['name']);
    $uploadedFiles = [];
    $errorFiles = [];

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    for ($i = 0; $i < $totalFiles; $i++) {
        $fileName = basename($_FILES['subirImagenes']['name'][$i]);
        $fileTmpName = $_FILES['subirImagenes']['tmp_name'][$i];
        $fileError = $_FILES['subirImagenes']['error'][$i];
        $fileType = $_FILES['subirImagenes']['type'][$i];

        if ($fileError !== UPLOAD_ERR_OK) {
            $errorFiles[] = "Error al subir el archivo: " . $fileName . " (Error code: " . $fileError . ")";
            continue;
        }
        if(!in_array($fileType, $allowedTypes)) {
            $errorFiles[] = "Tipo de archivo no permitido: " . $fileName;
            continue;
        }
        $fileExt = pathinfo($fileName, PATHINFO_EXTENSION);
        $newFileName = $idProducto . '_' . uniqid() . '.' . $fileExt;
        $uploadFilePath = $uploadDir . $newFileName;
        if (move_uploaded_file($fileTmpName, $uploadFilePath)) {
            $uploadedFiles[] = $newFileName;
        } else {
            $errorFiles[] = "Error al mover el archivo: " . $fileName;
        }
    }

    echo json_encode([
        'success' =>count($errorFiles) === 0,
        'uploadedFiles' => $uploadedFiles,
        'errorFiles' => $errorFiles,
        'message' => count($errorFiles) > 0 ? 'Algunos archivos no se pudieron subir.' : 'Todos los archivos se subieron correctamente.'
    ]);
    exit;
}

$connection->close();
echo json_encode(['error' => 'No se proporcionaron archivos para subir']);
