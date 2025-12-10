<?php
require_once '../connectDataBaseA2.php'; // Asegúrate de incluir tu clase FlexDBBdisam

// Configuración de exportación
header('Content-Type: text/plain; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// Función para escape de valores CSV
function escapeCsvValue($value, $delimiter = ';') {
    $value = str_replace('"', '""', $value ?? '');
    // Si contiene el delimitador, comillas o saltos de línea, envolver en comillas
    if (preg_match('/['.$delimiter.'"\r\n]/', $value)) {
        $value = '"'.$value.'"';
    }
    return $value;
}

try {
    // 1. Conectar a BDISAM
    $db = flex_db_bdisam(); // Usa tu DSN real
    
    // 2. Consulta principal para obtener todos los productos activos
    $sqlInventario = "SELECT 
                        FI_CODIGO AS codigo, 
                        FI_DESCRIPCION AS descripcion,
                        FI_MODELO AS modelo,
                        FI_REFERENCIA AS referencia,
                        FI_MARCA AS marca,
                        FI_CATEGORIA AS departamento1,
                        FI_SUBCATEGORIA AS categoria,
                        FI_PESOPRODUCTO AS peso,
                        FI_STATUS AS activo
                      FROM Sinventario 
                      WHERE FI_PESOPRODUCTO > 0
                      ";
    
    $resultadoInventario = $db->executeQuery($sqlInventario);
    $productos = $db->fetchAll($resultadoInventario);
    
    if (empty($productos)) {
        die("No se encontraron productos activos para exportar");
    }
    
    // 3. Obtener códigos para consultas adicionales
    $codigos = [];
    foreach ($productos as $producto) {
        $codigos[] = "'" . str_replace("'", "''", trim($producto['codigo'])) . "'";
    }
    $listaCodigos = implode(',', $codigos);
    
    // 4. Consulta existencias
    $sqlSinvDep = "SELECT 
                      FT_CODIGOPRODUCTO AS codigo,
                      FT_PUESTO AS puesto,
                      FT_EXISTENCIA AS existencia
                   FROM SinvDep
                   WHERE FT_CODIGOPRODUCTO IN ($listaCodigos)";
    
    $resultadoSinvDep = $db->executeQuery($sqlSinvDep);
    $existenciasRaw = $db->fetchAll($resultadoSinvDep);
    
    // Organizar existencias
    $existencias = [];
    foreach ($existenciasRaw as $item) {
        $existencias[$item['codigo']] = $item;
    }
    
    // 5. Consulta precios
    $sqlPrecios = "SELECT 
                      FIC_CODEITEM AS codigo,
                      FIC_COSTOACTEXTRANJERO AS costo,
                      FIC_P01PRECIOTOTALEXT AS precio1,
                      FIC_P02PRECIOTOTALEXT AS precio2,
                      FIC_P03PRECIOTOTALEXT AS precio3
                   FROM a2InvCostosPrecios
                   WHERE FIC_CODEITEM IN ($listaCodigos)";
    
    $resultadoPrecios = $db->executeQuery($sqlPrecios);
    $preciosRaw = $db->fetchAll($resultadoPrecios);
    
    // Organizar precios
    $precios = [];
    foreach ($preciosRaw as $item) {
        $precios[$item['codigo']] = $item;
    }
    
    // 6. Configurar archivo de salida
    $nombreArchivo = 'export_productos_' . date('Ymd_His') . '.txt';
    header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
    
    // 7. Escribir encabezados
    $encabezados = [
        'ID',
        'Código',
        'Descripción',
        'Modelo/Autos',
        'Marca',
        'Departamento',
        'Categoría',
        'Puesto',
        'Existencia',
        'Peso',
        'Costo',
        'Precio 1 (IVA)',
        'Precio 2 (IVA)',
        'Precio 3 (IVA)',
        'Activo'
    ];
    
    echo implode(';', $encabezados) . "\r\n";
    
    // 8. Escribir datos
    foreach ($productos as $producto) {
        $codigo = trim($producto['codigo']);

        //------------------------
        require_once '../connectDataBase.php'; 
        $mysql = new Database();
        $mysqlConn = $mysql->getConnection();

        $sqlFindUrl = "SELECT GROUP_CONCAT(url SEPARATOR ', ') AS urls, DATE(fecha_registro) as fecha_registro 
                    FROM imagenes
                    WHERE idProducto = ? 
                    GROUP BY DATE(fecha_registro)
                    ";

        $stmtUrl = $mysqlConn->prepare($sqlFindUrl);
        $stmtUrl->bind_param("i", $codigo);
        $stmtUrl->execute();
        $resUrl = $stmtUrl->get_result();
       /* $rowUrl = $resUrl->fetch_assoc();
        $urls = $rowUrl['urls'];
        $recordDate = $rowUrl['fecha_registro']; */

        if ($rowUrl = $resUrl->fetch_assoc()) {
            // Si hay resultados, los usamos
            $urls = $rowUrl['urls'] ?? '';
            $recordDate = $rowUrl['fecha_registro'] ?? '';
        } else {
            // Si no hay resultados, mantenemos valores vacíos
            $urls = '';
            $recordDate = '';
        }

        $stmtUrl->close();

        $mysqlConn->close();

        //-------------------------
        if($recordDate != date('Y-m-d')) continue;
        $fila = [
            escapeCsvValue($codigo),
            escapeCsvValue($producto['referencia'] ?? ''),
            escapeCsvValue($producto['descripcion'] ?? ''),
            escapeCsvValue($producto['modelo'] ?? ''),
            escapeCsvValue($producto['marca'] ?? ''),
            escapeCsvValue($producto['departamento1'] ?? ''),
            escapeCsvValue($producto['categoria'] ?? ''),
            escapeCsvValue($existencias[$codigo]['puesto'] ?? 'S/P'),
            escapeCsvValue($existencias[$codigo]['existencia'] ?? 0),
            escapeCsvValue(($producto['peso'] / 1000) ?? 0),
            escapeCsvValue($precios[$codigo]['costo'] ?? 0),
            escapeCsvValue(round((($precios[$codigo]['precio2'] * 1.16) * 1.24), 2) ?? 0),
            escapeCsvValue($producto['activo'] ? 'SI' : 'NO'),
            escapeCsvValue($urls),
        ];
        
        echo implode(';', $fila) . "\r\n";
    }

} catch (Exception $e) {
    // Registrar error y mostrar mensaje
    error_log("Error en exportación: " . $e->getMessage());
    die("Error al generar el archivo de exportación: " . $e->getMessage());
}
?>