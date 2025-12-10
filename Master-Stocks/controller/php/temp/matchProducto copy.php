<?php
require_once '../connectDataBase.php';
require_once '../connectDataBaseA2.php';

// Configuración
$porcentajeMinimoSimilitud = 100;
$modoPrueba = true; // IMPORTANTE: Cambiado a false para actualizaciones reales
$mostrarDetalles = true;

function normalizarDescripcion($texto) {
    $texto = preg_replace('/[^\w\s]/u', '', $texto);
    $texto = preg_replace('/\s+/', ' ', trim($texto));
    return mb_strtoupper($texto);
}

try {
    // 1. Iniciar transacción
    $connection->autocommit(false);
    
    // 2. Obtener productos de MySQL que necesitan actualización
    $sql_mysql = "SELECT id, descripcion, idE, peso FROM producto WHERE id = idE";
    $result_mysql = $connection->query($sql_mysql);
    
    if (!$result_mysql) {
        throw new Exception("Error al leer productos MySQL: " . $connection->error);
    }
    require_once
    $mysql_products = [];
    while ($row = $result_mysql->fetch_assoc()) {
        $clave = normalizarDescripcion($row['descripcion']);
        $mysql_products[$clave] = $row;
    }

    // 3. Obtener productos de BDISAM
    $db_bdisam = flex_db_bdisam();
    $sql_bdisam = "SELECT CAST(FI_CODIGO AS CHAR(20)) AS FI_CODIGO, FI_DESCRIPCION FROM Sinventario";
    $result_bdisam = $db_bdisam->executeQuery($sql_bdisam);
    
    // 4. Procesar coincidencias
    $stats = [
        'exactas' => 0,
        'parciales' => 0,
        'actualizados' => 0,
        'errores' => 0
    ];
    
    // Preparar consulta de actualización
    $sql_update = "UPDATE producto SET idE = ? WHERE id = ?";
    $stmt = $connection->prepare($sql_update);
    if (!$stmt) {
        throw new Exception("Error al preparar consulta: " . $connection->error);
    }

    while ($producto_bdisam = $db_bdisam->fetchOne($result_bdisam)) {
        $desc_bdisam = normalizarDescripcion($producto_bdisam['FI_DESCRIPCION']);
        $codigo_bdisam = $producto_bdisam['FI_CODIGO'];
        
        // Coincidencia exacta
        if (isset($mysql_products[$desc_bdisam])) {
            $producto_mysql = $mysql_products[$desc_bdisam];
            $stats['exactas']++;
            
            // Verificar si realmente necesita actualización
            if ($producto_mysql['idE'] != $codigo_bdisam) {
                $stmt->bind_param("si", $codigo_bdisam, $producto_mysql['id']);
                if ($stmt->execute()) {
                    $stats['actualizados']++;
                    if ($mostrarDetalles) {
                        echo "ACTUALIZADO: ID {$producto_mysql['id']} con código $codigo_bdisam (Exacta)<br>";
                    }
                } else {
                    $stats['errores']++;
                    error_log("Error al actualizar ID {$producto_mysql['id']}: " . $stmt->error);
                }
            }
            continue;
        }
        
        // Coincidencia parcial
        $mejor_coincidencia = ['porcentaje' => 0];
        foreach ($mysql_products as $desc_mysql => $producto_mysql) {
            similar_text($desc_bdisam, $desc_mysql, $porcentaje);
            if ($porcentaje > $mejor_coincidencia['porcentaje']) {
                $mejor_coincidencia = [
                    'porcentaje' => $porcentaje,
                    'datos' => $producto_mysql
                ];
            }
        }
        
        if ($mejor_coincidencia['porcentaje'] > $porcentajeMinimoSimilitud) {
            $producto_mysql = $mejor_coincidencia['datos'];
            $stats['parciales']++;
            
            if ($producto_mysql['idE'] != $codigo_bdisam) {
                $stmt->bind_param("si", $codigo_bdisam, $producto_mysql['id']);
                if ($stmt->execute()) {
                    $stats['actualizados']++;
                    if ($mostrarDetalles) {
                        echo "ACTUALIZADO: ID {$producto_mysql['id']} con código $codigo_bdisam ({$mejor_coincidencia['porcentaje']}%)<br>";
                    }
                } else {
                    $stats['errores']++;
                    error_log("Error al actualizar ID {$producto_mysql['id']}: " . $stmt->error);
                }
            }
        }
    }

    // Confirmar transacción
    $connection->commit();
    
    // 5. Verificación final
    $sql_verificacion = "SELECT COUNT(*) AS actualizados FROM producto WHERE id = idE";
    $result = $connection->query($sql_verificacion);
    $row = $result->fetch_assoc();
    
    echo "<h3>RESULTADOS FINALES</h3>";
    echo "<ul>
            <li>Registros pendientes iniciales: ".count($mysql_products)."</li>
            <li>Coincidencias exactas: {$stats['exactas']}</li>
            <li>Coincidencias parciales: {$stats['parciales']}</li>
            <li>Registros actualizados: {$stats['actualizados']}</li>
            <li>Errores: {$stats['errores']}</li>
            <li>Total registros actualizados ahora: {$row['actualizados']}</li>
          </ul>";

} catch (Exception $e) {
    $connection->rollback();
    die("<h2>Error: " . htmlspecialchars($e->getMessage()) . "</h2>");
} finally {
    if (isset($stmt)) $stmt->close();
    if (isset($connection)) {
        $connection->autocommit(true);
        $connection->close();
    }
    if (isset($db_bdisam)) $db_bdisam->close();
}
?>