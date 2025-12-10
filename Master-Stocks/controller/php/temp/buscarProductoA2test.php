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
    //$terminoBusqueda = isset($_POST['codProduct']) ? trim($_POST['codProduct']) : '';
    $terminoBusqueda = '1-A'; // Ejemplo de prueba
    
    if (empty($terminoBusqueda)) {
        echo json_encode([]);
        exit;
    }

    // Convertir a mayúsculas manteniendo la Ñ
    $terminoBusqueda = mb_strtoupper($terminoBusqueda);
    $palabras = preg_split('/\s+/', $terminoBusqueda);
    
    // 2. Verificar si el término coincide con un formato de puesto (ej: "1-A", "29-F")
    $esBusquedaPorPuesto = false;
    $puestoBusqueda = null;
    
    if (preg_match('/^\d+-[A-Z]$/', $terminoBusqueda)) {
        $esBusquedaPorPuesto = true;
        $puestoBusqueda = $terminoBusqueda;
    }
    
    // 3. Si es búsqueda por puesto, consultar SinvDep primero
    if ($esBusquedaPorPuesto) {
        $sqlPuestos = "SELECT 
                          FT_CODIGOPRODUCTO AS codigo,
                          FT_PUESTO AS puesto,
                          FT_EXISTENCIA AS existencia
                       FROM SinvDep
                       WHERE FT_PUESTO = '$puestoBusqueda'";
        
        $resultadoPuestos = $db->executeQuery($sqlPuestos);
        $productosEnPuesto = $db->fetchAll($resultadoPuestos);
        
        if (empty($productosEnPuesto)) {
            echo json_encode([]);
            exit;
        }
        
        // Obtener códigos de productos en ese puesto
        $codigos = [];
        foreach ($productosEnPuesto as $item) {
            $codigos[] = "'" . str_replace("'", "''", trim($item['codigo'])) . "'";
        }
        $listaCodigos = implode(',', $codigos);
        
        // Consultar información completa de los productos
        $sqlInventario = "SELECT 
                            FI_CODIGO AS codigo, 
                            FI_DESCRIPCION AS descripcion,
                            FI_MODELO AS modelo,
                            FI_REFERENCIA AS referencia,
                            FI_MARCA AS marca,
                            FI_CATEGORIA AS departamento1,
                            FI_SUBCATEGORIA AS categoria,
                            FI_PESOPRODUCTO AS peso
                          FROM Sinventario
                          WHERE FI_CODIGO IN ($listaCodigos) AND FI_STATUS = TRUE";
        
        $resultadoInventario = $db->executeQuery($sqlInventario);
        $productos = $db->fetchAll($resultadoInventario);
        
        // Consultar precios
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
        
        // Construir resultados
        $resultados = [];
        foreach ($productos as $producto) {
            $codigo = trim($producto['codigo']);
            
            // Buscar la existencia específica para este producto en el puesto
            $existencia = 0;
            foreach ($productosEnPuesto as $item) {
                if ($item['codigo'] == $codigo) {
                    $existencia = $item['existencia'];
                    break;
                }
            }
            
            $resultados[] = [
                'id' => $codigo,
                'codigo' => safeHtml($producto['referencia']),
                'descripcion' => safeHtml($producto['descripcion']),
                'autos' => safeHtml($producto['modelo']),
                'marca' => safeHtml($producto['marca']),
                'peso' => safeHtml($producto['peso']),
                'departamento' => safeHtml($producto['departamento1']),
                'categoria' => safeHtml($producto['categoria']),
                'puesto' => $puestoBusqueda, 
                'cantidad' => $existencia,
                'costo' => $precios[$codigo]['costo'] ?? 0,
                'precio1' => isset($precios[$codigo]['precio1']) ? round(($precios[$codigo]['precio1'] * 1.16), 2) : 0,
                'precio2' => isset($precios[$codigo]['precio2']) ? round(($precios[$codigo]['precio2'] * 1.16), 2) : 0,
                'precio3' => isset($precios[$codigo]['precio3']) ? round(($precios[$codigo]['precio3'] * 1.16), 2) : 0
            ];
        }
        
        echo json_encode($resultados, JSON_UNESCAPED_UNICODE);
        exit;
    }
 /***************************************************************************** */   
    // 4. Si NO es búsqueda por puesto, hacer búsqueda normal (original)
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
    
    // Ejecutar consulta principal
    $resultadoInventario = $db->executeQuery($sqlInventario);
    $productos = $db->fetchAll($resultadoInventario);
    
    $resultados = [];
    
    if (!empty($productos)) {
        // Obtener códigos para consultas adicionales
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
        
        // Construir resultados finales
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
                'precio1' => isset($precios[$codigo]['precio1']) ? round(($precios[$codigo]['precio1'] * 1.16), 2) : 0,
                'precio2' => isset($precios[$codigo]['precio2']) ? round(($precios[$codigo]['precio2'] * 1.16), 2) : 0,
                'precio3' => isset($precios[$codigo]['precio3']) ? round(($precios[$codigo]['precio3'] * 1.16), 2) : 0
            ];
        }
    }
    
    echo json_encode($resultados, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log("Error en búsqueda: " . $e->getMessage());
    echo json_encode(['error' => 'Error en el sistema: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
?>