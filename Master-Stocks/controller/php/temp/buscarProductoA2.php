<?php
require_once 'connectDataBaseA2.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

function safeHtml($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

$stopwords = ['de', 'para', 'el', 'la', 'los', 'las', 'y', 'en', 'a', 'con', 'por', 'del', 'al'];

try {
    $db = connectODBC();
    
    // 1. Obtener y procesar el término de búsqueda
    $terminoBusqueda = isset($_POST['codProduct']) ? trim($_POST['codProduct']) : '';
    
    if (empty($terminoBusqueda)) {
        echo json_encode([]);
        exit;
    }

    // Convertir a mayúsculas manteniendo la Ñ (sin conversión de caracteres)
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
                        FI_SUBCATEGORIA AS categoria
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
                             OR FI_MARCA LIKE '%$palabraEscaped%')";
        }
    }
    
    if (!empty($conditions)) {
        $sqlInventario .= " WHERE " . implode(" AND ", $conditions);
    }
    
    // 3. Ejecutar consulta principal con buffer completo
    $stmtInventario = $db->query($sqlInventario);
    if ($stmtInventario === false) {
        $error = $db->errorInfo();
        throw new Exception("Error en consulta: " . print_r($error, true));
    }
    
    // Forzar la obtención de todos los resultados
    $productos = $stmtInventario->fetchAll(PDO::FETCH_ASSOC);
    
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
        
        $stmtSinvDep = $db->query($sqlSinvDep);
        $existenciasRaw = $stmtSinvDep ? $stmtSinvDep->fetchAll(PDO::FETCH_ASSOC) : [];
        
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
        
        $stmtPrecios = $db->query($sqlPrecios);
        $preciosRaw = $stmtPrecios ? $stmtPrecios->fetchAll(PDO::FETCH_ASSOC) : [];
        
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
                'departamento' => safeHtml($producto['departamento1']),
                'categoria' => safeHtml($producto['categoria']),
                'puesto' => $existencias[$codigo]['puesto'] ?? null,
                'cantidad' => $existencias[$codigo]['existencia'] ?? null,
                'costo' => $precios[$codigo]['costo'] ?? null,
                'precio1' => $precios[$codigo]['precio1'] ?? null,
                'precio2' => $precios[$codigo]['precio2'] ?? null,
                'precio3' => $precios[$codigo]['precio3'] ?? null
            ];
        }
    }
    
    echo json_encode($resultados, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log("Error en búsqueda: " . $e->getMessage());
    echo json_encode(['error' => 'Error en el sistema: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
?>