<?php
require_once 'connectDataBaseA2.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

function safeHtml($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

$stopwords = ['de', 'para', 'el', 'la', 'los', 'las', 'y', 'en', 'a', 'con', 'por', 'del', 'al'];

try {
    $db = flex_db_bdisam(); 
    
    // 1. Obtener y procesar el término de búsqueda
    $terminoBusqueda = isset($_POST['codProduct']) ? trim($_POST['codProduct']) : '';
    //$terminoBusqueda = 'muñon';
    
    if (empty($terminoBusqueda)) {
        echo json_encode([]);
        exit;
    }

    // Convertir a mayúsculas manteniendo la Ñ
    $terminoBusqueda = mb_strtoupper($terminoBusqueda);
    $palabras = preg_split('/\s+/', $terminoBusqueda);
    
    // 2. Construir consulta base con formato específico para DBISAM
    $sqlInventario = "SELECT 
                        FI_CODIGO AS codigo, 
                        FI_DESCRIPCION AS descripcion,
                        FI_MODELO AS modelo,
                        FI_REFERENCIA AS referencia,
                        FI_MARCA AS marca,
                        FI_CATEGORIA AS departamento1,
                        FI_SUBCATEGORIA AS categoria,
                        FI_PESOPRODUCTO AS peso
                      FROM Sinventario";
    
    $conditions = [];
    
    foreach ($palabras as $palabra) {
        $palabra = trim($palabra);
        if (!empty($palabra) && !in_array(strtolower($palabra), $stopwords)) {
            // Escapar comillas simples manualmente
            $palabraEscaped = str_replace("'", "''", $palabra);
            $conditions[] = "(FI_CODIGO LIKE '%$palabraEscaped%' 
                             OR FI_DESCRIPCION LIKE '%$palabraEscaped%'
                             OR FI_MODELO LIKE '%$palabraEscaped%'
                             OR FI_REFERENCIA LIKE '%$palabraEscaped%'
                             OR FI_MARCA LIKE '%$palabraEscaped%') AND (FI_STATUS = TRUE)";
        }
    }
    
    if (!empty($conditions)) {
        $sqlInventario .= " WHERE " . implode(" AND ", $conditions);
    }
    
    // 3. Ejecutar consulta principal
    $resultadoInventario = $db->executeQuery($sqlInventario);
    $productos = $db->fetchAll($resultadoInventario);
    
    $resultados = [];
    
    if (!empty($productos)) {
        // 4. Obtener códigos para consultas adicionales
        $codigos = [];
        foreach ($productos as $producto) {
            $codigos[] = "'" . str_replace("'", "''", trim($producto['codigo'])) . "'";
        }
        $listaCodigos = implode(',', $codigos);
        
        // Consulta existencias
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
        
        // Consulta precios
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
        
        // 5. Construir resultados finales
        foreach ($productos as $producto) {
            $codigo = trim($producto['codigo']);
            
            $resultados[] = [
                'id' => $codigo,
                'codigo' => safeHtml($producto['referencia']),
                'descripcion' => safeHtml($producto['descripcion']),
                'autos' => safeHtml($producto['modelo']),
                'marca' => safeHtml($producto['marca']),
                'peso' => safeHtml($producto['peso']),
                'departamento' => safeHtml($producto['departamento1']),
                'categoria' => safeHtml($producto['categoria']),
                'puesto' => $existencias[$codigo]['puesto'] ?? 'S/P',
                'cantidad' => $existencias[$codigo]['existencia'] ?? 0,
                'costo' => $precios[$codigo]['costo'] ?? 0,
                'precio1' => round(($precios[$codigo]['precio1'] * 1.16), 2) ?? 0,
                'precio2' => round(($precios[$codigo]['precio2'] * 1.16), 2) ?? 0,
                'precio3' => round(($precios[$codigo]['precio3'] * 1.16), 2) ?? 0
            ];
        }
    }
    
    echo json_encode($resultados, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log("Error en búsqueda: " . $e->getMessage());
    echo json_encode(['error' => 'Error en el sistema: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
?>