<?php
require_once '../connectDataBase.php';
require_once '../connectDataBaseA2.php';

// Configuración
$modoPrueba = true;  // Si es true, solo muestra lo que haría (sin ejecutar)
$mostrarDetalles = true;

try {
    // 1. Iniciar transacción en MySQL (para consistencia)
    $connection->autocommit(false);
    
    // 2. Obtener productos de MySQL (idE y peso)
    $sql_mysql = "SELECT idE, peso FROM producto WHERE idE IS NOT NULL";
    $result_mysql = $connection->query($sql_mysql);
    
    if (!$result_mysql) {
        throw new Exception("Error al leer productos MySQL: " . $connection->error);
    }

    // 3. Conectar a BDISAM (ODBC)
    $db_bdisam = flex_db_bdisam();
    
    $stats = [
        'actualizados' => 0,
        'errores' => 0
    ];

    // 4. Procesar cada registro
    while ($producto_mysql = $result_mysql->fetch_assoc()) {
        $idE = $producto_mysql['idE'];
        $peso = $producto_mysql['peso'];
        
        if ($modoPrueba) {
            echo "[MODO PRUEBA] Actualizaría ODBC: FI_CODIGO=$idE con PESO=".floatval($peso/1000)."<br>";
            continue;
        }

        // 5. Ejecutar actualización directa en ODBC (Sin prepare)
        $sql_update = "UPDATE Sinventario SET FI_PESOPRODUCTO = " . floatval($peso/1000) . " WHERE FI_CODIGO = '" . $idE . "'";
        $result_odbc = $db_bdisam->executeNonQuery($sql_update);
        
        if ($result_odbc) {
            $stats['actualizados']++;
            if ($mostrarDetalles) {
                echo "ACTUALIZADO: FI_CODIGO $idE con PESO $peso<br>";
            }
            //odbc_free_result($result_odbc);
        } else {
            $stats['errores']++;
            //error_log("Error al actualizar ODBC (FI_CODIGO=$idE): " . odbc_errormsg($db_bdisam->getConnection()));
        }
    }

    // 6. Confirmar transacción (MySQL)
    $connection->commit();
    
    // 7. Resultados
    echo "<h3>RESULTADOS</h3>";
    echo "<ul>
            <li>Registros actualizados en ODBC: {$stats['actualizados']}</li>
            <li>Errores: {$stats['errores']}</li>
          </ul>";

} catch (Exception $e) {
    $connection->rollback();
    die("<h2>Error: " . htmlspecialchars($e->getMessage()) . "</h2>");
} finally {
    if (isset($connection)) {
        $connection->autocommit(true);
        $connection->close();
    }
    // No cerramos $db_bdisam manualmente (se cierra en su destructor)
}
?>