<?php
include 'connectDataBaseA2.php';
header("Content-Type: application/json; charset=UTF-8");

// Configuración
$uploadDir = "../../uploads/";
$allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
$maxFileSize = 25 * 1024 * 1024; // 25MB

$response = [
    'success' => false,
    'message' => '',
    'uploaded_files' => []
];



try {
    $db = flex_db_bdisam();

    // Verificar método de petición
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    // Validar código de producto
    $codProducto = $_POST['codProduct'];
    if (empty($codProducto)) {
        throw new Exception('Código de producto requerido');
    }

    $sqlVerificar = "SELECT FI_DESCRIPCION as descripcion, FI_REFERENCIA as id, FI_MARCA as marca, S.FI_PESOPRODUCTO as peso, SD.FT_PUESTO as puesto, SD.FT_EXISTENCIA as cantidad FROM Sinventario as S 
    INNER JOIN SinvDep AS SD ON S.FI_CODIGO = SD.FT_CODIGOPRODUCTO 
    WHERE S.FI_CODIGO = '$codProducto'";

    $resultadoVerificando = $db->executeQuery($sqlVerificar);
    $resultadoRaw = $db->fetchOne($resultadoVerificando);

    // Validar y procesar el peso si está presente
    $peso = $_POST['peso'] ?? null;
    $puesto = $_POST['puesto'] ?? null;
    $cantidad = $_POST['cantidad'] ?? null;
    $id = $_POST['id'] ?? null;
    $descripcion = $_POST['descripcion'] ?? null;
    $marca = $_POST['marca'] ?? null;
    $checkProducto = $_POST['checkProducto'] ?? null;

    if ($descripcion !== null && $descripcion !== '' && $descripcion !== $resultadoRaw['descripcion']) {

        if ($db->conectar()) {
            throw new Exception('Error de conexión: ' . $db);
        }

        // Antes de ejecutar
        if (empty($codProducto)) {
            throw new Exception('ID de producto inválido');
        }
        try {
            // Preparar y ejecutar la consulta
            $sql = "UPDATE Sinventario SET FI_DESCRIPCION = '$descripcion' WHERE FI_CODIGO = '$codProducto'";
            $stmt = $db->executeNonQuery($sql);
        } catch (Exception $e) {
            throw $e; // Relanzar la excepción para manejo superior
        }
    }

    if ($marca !== null && $marca !== '' && $marca !== $resultadoRaw['marca']) {

        if ($db->conectar()) {
            throw new Exception('Error de conexión: ' . $db);
        }

        // Antes de ejecutar
        if (empty($codProducto)) {
            throw new Exception('ID de producto inválido');
        }

        try {
            // Preparar y ejecutar la consulta
            $sql = "UPDATE Sinventario SET FI_MARCA = '$marca' WHERE FI_CODIGO = '$codProducto'";
            $stmt = $db->executeNonQuery($sql);
        } catch (Exception $e) {
            throw $e; // Relanzar la excepción para manejo superior
        }
    }

    if ($id !== null && $id !== '' && $id !== $resultadoRaw['id']) {


        if ($db->conectar()) {
            throw new Exception('Error de conexión: ' . $db);
        }

        // Antes de ejecutar
        if (empty($codProducto)) {
            throw new Exception('ID de producto inválido');
        }

        try {
            // Preparar y ejecutar la consulta
            $sql = "UPDATE Sinventario SET FI_REFERENCIA = '$id' WHERE FI_CODIGO = '$codProducto'";
            $stmt = $db->executeNonQuery($sql);
        } catch (Exception $e) {
            throw $e; // Relanzar la excepción para manejo superior
        }
    }

    if ($peso !== null && $peso !== '' && $peso !== $resultadoRaw['peso']) {


        if ($db->conectar()) {
            throw new Exception('Error de conexión: ' . $db);
        }

        // Antes de ejecutar
        if (empty($codProducto)) {
            throw new Exception('ID de producto inválido');
        }

        // Convertir peso a formato numérico
        $pesoNumerico = (float) str_replace(',', '.', $peso);


        try {
            // Preparar y ejecutar la consulta
            $sql = "UPDATE Sinventario SET FI_PESOPRODUCTO = $peso WHERE FI_CODIGO = '$codProducto'";
            $stmt = $db->executeNonQuery($sql);
        } catch (Exception $e) {
            throw $e; // Relanzar la excepción para manejo superior
        }
    }

    if ($cantidad !== null && $cantidad !== ''  && $cantidad !== $resultadoRaw['cantidad']) {

        $db = flex_db_bdisam();

        if ($db->conectar()) {
            throw new Exception('Error de conexión: ' . $db);
        }

        // Antes de ejecutar
        if (empty($codProducto)) {
            throw new Exception('ID de producto inválido');
        }

        try {
            // Preparar y ejecutar la consulta
            $sql = "UPDATE SinvDep SET FT_EXISTENCIA = $cantidad WHERE FT_CODIGOPRODUCTO = '$codProducto'";
            $stmt = $db->executeNonQuery($sql);
        } catch (Exception $e) {
            throw $e; // Relanzar la excepción para manejo superior
        }
    }

    if ($checkProducto !== null && $checkProducto !== '') {
        include('connectDataBase.php'); // Conexión MySQL

        if ($connection->connect_error) {
            throw new Exception('Error de conexión: ' . $connection->connect_error);
        }

        if (empty($codProducto) || !is_numeric($codProducto)) {
            throw new Exception('ID de producto inválido');
        }

        $valorCheck = (int)$checkProducto;

        try {
            // Insertar si no existe, actualizar si ya existe
            $stmt = $connection->prepare("
            INSERT INTO checkproducto (idProduct, statusCheck, dateCheck)
            VALUES (?, ?, NOW())
            ON DUPLICATE KEY UPDATE 
                statusCheck = VALUES(statusCheck),
                dateCheck = NOW()
        ");

            if (!$stmt) {
                throw new Exception('Error al preparar la consulta: ' . $connection->error);
            }

            $stmt->bind_param("ii", $codProducto, $valorCheck);

            if (!$stmt->execute()) {
                throw new Exception('Error al guardar el chequeo: ' . $stmt->error);
            }

            $stmt->close();
        } catch (Exception $e) {
            if (isset($stmt)) $stmt->close();
            throw $e;
        } finally {
            $connection->close();
        }
    }

    if ($puesto !== null && $puesto !== ''  && $puesto !== $resultadoRaw['puesto']) {

        $db = flex_db_bdisam();

        if ($db->conectar()) {
            throw new Exception('Error de conexión: ' . $db);
        }

        // Antes de ejecutar
        if (empty($codProducto)) {
            throw new Exception('ID de producto inválido');
        }

        try {
            // Preparar y ejecutar la consulta
            $sql = "UPDATE SinvDep SET FT_PUESTO = '$puesto' WHERE FT_CODIGOPRODUCTO = '$codProducto'";
            $stmt = $db->executeNonQuery($sql);
        } catch (Exception $e) {
            throw $e; // Relanzar la excepción para manejo superior
        }
    }


    // Verificar si se subieron archivos
    if (!isset($_FILES['subirImagenes'])) {
        $response['success'] = true;
        $response['message'] = 'Datos actualizados correctamente';

        echo json_encode($response);
        exit;
    }

    // Crear directorio si no existe
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            throw new Exception('No se pudo crear el directorio de uploads');
        }
    }

    // Procesar cada archivo
    foreach ($_FILES['subirImagenes']['tmp_name'] as $key => $tmpName) {
        // Validar errores de subida
        if ($_FILES['subirImagenes']['error'][$key] !== UPLOAD_ERR_OK) {
            throw new Exception('Error en archivo: ' . $_FILES['subirImagenes']['name'][$key]);
        }

        // Validar tipo de archivo
        $fileType = mime_content_type($tmpName);
        if (!in_array($fileType, $allowedTypes)) {
            throw new Exception('Tipo no permitido: ' . $_FILES['subirImagenes']['name'][$key]);
        }

        // Validar tamaño
        if ($_FILES['subirImagenes']['size'][$key] > $maxFileSize) {
            throw new Exception('Archivo demasiado grande: ' . $_FILES['subirImagenes']['name'][$key]);
        }

        // Generar nombre único
        $extension = pathinfo($_FILES['subirImagenes']['name'][$key], PATHINFO_EXTENSION);
        $newFilename = $codProducto . '_' . uniqid() . '.' . strtolower($extension);
        $destination = $uploadDir . $newFilename;

        // Mover archivo
        if (!move_uploaded_file($tmpName, $destination)) {
            throw new Exception('Error al guardar: ' . $_FILES['subirImagenes']['name'][$key]);
        }

        $response['uploaded_files'][] = $newFilename;
    }

    $response['success'] = true;
    $response['message'] = isset($peso) ? 'Peso actualizado y ' : '';
    $response['message'] .= count($response['uploaded_files']) . ' archivos subidos correctamente';
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    // Limpiar archivos subidos parcialmente en caso de error
    if (!empty($response['uploaded_files'])) {
        foreach ($response['uploaded_files'] as $file) {
            @unlink($uploadDir . $file);
        }
    }
}

echo json_encode($response);
