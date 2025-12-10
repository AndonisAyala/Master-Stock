<?php
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
    // Verificar método de petición
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    // Validar código de producto
    $codProducto = intval($_POST['codProduct']);
    if (empty($codProducto)) {
        throw new Exception('Código de producto requerido');
    }

    // Validar y procesar el peso si está presente
    $peso = $_POST['peso'] ?? null;
    $puesto = $_POST['puesto'] ?? null;
    $cantidad = $_POST['cantidad'] ?? null;
    
    if ($peso !== null && $peso !== '') {
        include 'connectDataBaseA2.php';

        // Verificar conexión
        if ($connection->connect_error) {
            throw new Exception('Error de conexión: ' . $connection->connect_error);
        }

        // Antes de ejecutar
        if (empty($codProducto) || !is_numeric($codProducto)) {
            throw new Exception('ID de producto inválido');
        }

        // Convertir peso a formato numérico
        $pesoNumerico = (float) str_replace(',', '.', $peso);

        try {
            // Preparar y ejecutar la consulta
            $stmt = $connection->prepare("UPDATE producto SET peso = ? WHERE id = ?");
            if (!$stmt) {
                throw new Exception('Error al preparar la consulta: ' . $connection->error);
            }

            $stmt->bind_param("ds", $pesoNumerico, $codProducto);

            if (!$stmt->execute()) {
                throw new Exception('Error al ejecutar la actualización: ' . $stmt->error);
            }

            // Verificación más inteligente
            if ($stmt->affected_rows === -1) {
                // Error en la consulta
                throw new Exception('Error en la operación de actualización');
            } elseif ($stmt->affected_rows === 0) {
                // Solo advertencia (no es necesariamente un error)
                error_log("Advertencia: El peso del producto $codProducto no cambió (mismo valor)");
            }

            $stmt->close();
        } catch (Exception $e) {
            if (isset($stmt))
                $stmt->close();
            throw $e; // Relanzar la excepción para manejo superior
        } finally {
            $connection->close();
        }
    }

    if ($puesto !== null && $puesto !== '') {
        include 'connectDataBaseA2.php';

        // Verificar conexión
        if ($connection->connect_error) {
            throw new Exception('Error de conexión: ' . $connection->connect_error);
        }

        // Antes de ejecutar
        if (empty($codProducto) || !is_numeric($codProducto)) {
            throw new Exception('ID de producto inválido');
        }

        try {
            // Preparar y ejecutar la consulta
            $stmt = $connection->prepare("UPDATE SinvDep SET FT_PUESTO = ? WHERE FT_CODIGOPRODUCTO = ?");
            if (!$stmt) {
                throw new Exception('Error al preparar la consulta: ' . $connection->error);
            }

            $stmt->bind_param("ds", $puesto, $codProducto);

            if (!$stmt->execute()) {
                throw new Exception('Error al ejecutar la actualización: ' . $stmt->error);
            }

            // Verificación más inteligente
            if ($stmt->affected_rows === -1) {
                // Error en la consulta
                throw new Exception('Error en la operación de actualización');
            } elseif ($stmt->affected_rows === 0) {
                // Solo advertencia (no es necesariamente un error)
                error_log("Advertencia: El puesto del producto $codProducto no cambió (mismo valor)");
            }

            $stmt->close();
        } catch (Exception $e) {
            if (isset($stmt))
                $stmt->close();
            throw $e; // Relanzar la excepción para manejo superior
        } finally {
            $connection->close();
        }
    }

    if ($cantidad !== null && $cantidad !== '') {
        include 'connectDataBaseA2.php';

        // Verificar conexión
        if ($connection->connect_error) {
            throw new Exception('Error de conexión: ' . $connection->connect_error);
        }

        // Antes de ejecutar
        if (empty($codProducto) || !is_numeric($codProducto)) {
            throw new Exception('ID de producto inválido');
        }

        try {
            // Preparar y ejecutar la consulta
            $stmt = $connection->prepare("UPDATE SinvDep SET FT_EXISTENCIA = ? WHERE FT_CODIGOPRODUCTO = ?");
            if (!$stmt) {
                throw new Exception('Error al preparar la consulta: ' . $connection->error);
            }

            $stmt->bind_param("ds", $cantidad, $codProducto);

            if (!$stmt->execute()) {
                throw new Exception('Error al ejecutar la actualización: ' . $stmt->error);
            }

            // Verificación más inteligente
            if ($stmt->affected_rows === -1) {
                // Error en la consulta
                throw new Exception('Error en la operación de actualización');
            } elseif ($stmt->affected_rows === 0) {
                // Solo advertencia (no es necesariamente un error)
                error_log("Advertencia: El puesto del producto $codProducto no cambió (mismo valor)");
            }

            $stmt->close();
        } catch (Exception $e) {
            if (isset($stmt))
                $stmt->close();
            throw $e; // Relanzar la excepción para manejo superior
        } finally {
            $connection->close();
        }
    }

    // Verificar si se subieron archivos
    if (!isset($_FILES['subirImagenes'])) {
        $response['success'] = true;
        $response['message'] = 'Peso actualizado correctamente';
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
?>