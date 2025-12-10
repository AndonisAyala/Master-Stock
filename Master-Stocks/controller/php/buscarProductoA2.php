<?php
require_once 'connectDataBaseA2.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

function safeHtml($value)
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

$stopwords = ['de', 'para', 'el', 'la', 'los', 'las', 'y', 'en', 'a', 'con', 'por', 'del', 'al'];



try {
    $db = flex_db_bdisam();

    // 1. Obtener y procesar el término de búsqueda
    $terminoBusqueda = isset($_POST['codProduct']) ? trim($_POST['codProduct']) : '';
    //$terminoBusqueda = 'p:12'; // Ejemplo de prueba
    //$terminoBusqueda = 'p:12-A'; // Ejemplo de prueba
    //$terminoBusqueda = 'p:A-1'; // Ejemplo de prueba
    //$terminoBusqueda = 'p:A'; // Ejemplo de prueba

    if (empty($terminoBusqueda)) {
        echo json_encode([]);
        exit;
    }

    // 2. Verificar si el término tiene el prefijo "p:" para búsqueda por puesto
    $esBusquedaPorPuesto = false;
    $puestoBusqueda = null;
    $tipoBusqueda = ''; // 'exacta' o 'parcial'

    // Consultar valor de la moneda
    $sqlFactorCambio = "SELECT 
                          FM_CODE AS codigo,
                          FM_DESCRIPCION AS moneda,
                          FM_FACTOR AS cambio
                       FROM Smoneda
                       WHERE FM_CODE = 2";

    $resultadoFactorCambio = $db->executeQuery($sqlFactorCambio);
    $factorCambioRaw = $db->fetchAll($resultadoFactorCambio);
    // Organizar factor Cambio
    $factorCambio = [];
    foreach ($factorCambioRaw as $item) {
        $factorCambio[$item['codigo']] = $item;
        
    }

    if (preg_match('/^p:(.+)$/i', $terminoBusqueda, $matches)) {
        $esBusquedaPorPuesto = true;
        $puestoBusqueda = mb_strtoupper(trim($matches[1]));

        // Determinar el tipo de búsqueda
        if (strpos($puestoBusqueda, '-') !== false) {
            // Si tiene guión, búsqueda exacta (ej: "12-A", "A-1")
            $tipoBusqueda = 'exacta';
        } else {
            // Si no tiene guión, búsqueda parcial (ej: "12", "A")
            $tipoBusqueda = 'parcial';
        }
    }

    // 3. Si es búsqueda por puesto, consultar SinvDep primero
    if ($esBusquedaPorPuesto) {
        // Construir la condición WHERE según el tipo de búsqueda
        if ($tipoBusqueda === 'exacta') {
            // Búsqueda exacta: p:12-A, p:A-1, etc.
            $condicionWhere = "FT_PUESTO = '$puestoBusqueda'";
        } else {
            // Búsqueda parcial: p:12, p:A, etc.
            // Busca puestos que comiencen OR terminen con el valor
            $condicionWhere = "FT_PUESTO LIKE '$puestoBusqueda-%'";
        }

        $sqlPuestos = "SELECT 
                          FT_CODIGOPRODUCTO AS codigo,
                          FT_PUESTO AS puesto,
                          FT_EXISTENCIA AS existencia
                       FROM SinvDep
                       WHERE $condicionWhere";

        $resultadoPuestos = $db->executeQuery($sqlPuestos);
        $productosEnPuesto = $db->fetchAll($resultadoPuestos);

        if (empty($productosEnPuesto)) {
            echo json_encode([]);
            exit;
        }

        // Obtener códigos de productos en esos puestos
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

        // Construir resultados
        $resultados = [];
        foreach ($productos as $producto) {
            $codigo = trim($producto['codigo']);

            // Buscar la existencia específica para este producto en el puesto
            $existencia = 0;
            $puestoEspecifico = '';
            foreach ($productosEnPuesto as $item) {
                if ($item['codigo'] == $codigo) {
                    $existencia = $item['existencia'];
                    $puestoEspecifico = $item['puesto'];
                    break;
                }
            }

            // --- NUEVO: conexión MySQL para chequeos ---
            require_once 'connectDataBase.php';
            $mysql = new Database();
            $mysqlConn = $mysql->getConnection();

            // Consulta si el producto está chequeado (último registro)
            $sqlCheck = "SELECT statusCheck, dateCheck 
                 FROM checkproducto 
                 WHERE idProduct = ? 
                 ORDER BY dateCheck DESC 
                 LIMIT 1";

            $stmtCheck = $mysqlConn->prepare($sqlCheck);
            $stmtCheck->bind_param("i", $codigo);
            $stmtCheck->execute();
            $resCheck = $stmtCheck->get_result();
            $rowCheck = $resCheck->fetch_assoc();
            $chequeado = $rowCheck ? intval($rowCheck['statusCheck']) : 0;
            $fechaChequeo = $rowCheck ? $rowCheck['dateCheck'] : null;
            $stmtCheck->close();

            $mysqlConn->close();
            // ------------------------------------------

            $resultados[] = [
                'id' => $codigo,
                'codigo' => safeHtml($producto['referencia']),
                'descripcion' => safeHtml($producto['descripcion']),
                'autos' => safeHtml($producto['modelo']),
                'marca' => safeHtml($producto['marca']),
                'peso' => safeHtml($producto['peso']),
                'departamento' => safeHtml($producto['departamento1']),
                'categoria' => safeHtml($producto['categoria']),
                'puesto' => $puestoEspecifico,
                'cantidad' => $existencia,
                'costo' => $precios[$codigo]['costo'] ?? 0,
                'precio1' => isset($precios[$codigo]['precio1']) ? number_format((($precios[$codigo]['precio1'] * 1.16) * floatval($factorCambio['2']['cambio'])), 2, ',', '.') : 0,
                'precio2' => isset($precios[$codigo]['precio2']) ? number_format((($precios[$codigo]['precio2'] * 1.16) * floatval($factorCambio['2']['cambio'])), 2, ',', '.') : 0,
                'precio3' => isset($precios[$codigo]['precio3']) ? number_format((($precios[$codigo]['precio3'] * 1.16) * floatval($factorCambio['2']['cambio'])), 2, ',', '.') : 0,
                'chequeado' => $chequeado,
                'fechaChequeo' => $fechaChequeo
            ];
        }


        echo json_encode($resultados, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 4. Si NO es búsqueda por puesto, hacer búsqueda normal (original)
    // Convertir a mayúsculas manteniendo la Ñ para búsqueda normal
    $terminoBusqueda = mb_strtoupper($terminoBusqueda);
    $palabras = preg_split('/\s+/', $terminoBusqueda);

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

            // --- NUEVO: conexión MySQL para chequeos ---
            require_once 'connectDataBase.php';
            $mysql = new Database();
            $mysqlConn = $mysql->getConnection();

            // Consulta si el producto está chequeado (último registro)
            $sqlCheck = "SELECT statusCheck, dateCheck 
                        FROM checkproducto 
                        WHERE idProduct = ? 
                        ORDER BY dateCheck DESC 
                        LIMIT 1";

            $stmtCheck = $mysqlConn->prepare($sqlCheck);
            $stmtCheck->bind_param("i", $codigo);
            $stmtCheck->execute();
            $resCheck = $stmtCheck->get_result();
            $rowCheck = $resCheck->fetch_assoc();
            $chequeado = $rowCheck ? intval($rowCheck['statusCheck']) : 0;
            $fechaChequeo = $rowCheck ? $rowCheck['dateCheck'] : null;
            $stmtCheck->close();

            $mysqlConn->close();
            // ------------------------------------------

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
                'precio1' => isset($precios[$codigo]['precio1']) ? number_format((($precios[$codigo]['precio1'] * 1.16) * floatval($factorCambio['2']['cambio'])), 2, ',', '.') : 0,
                'precio2' => isset($precios[$codigo]['precio2']) ? number_format((($precios[$codigo]['precio2'] * 1.16) * floatval($factorCambio['2']['cambio'])), 2, ',', '.') : 0,
                'precio3' => isset($precios[$codigo]['precio3']) ? number_format((($precios[$codigo]['precio3'] * 1.16) * floatval($factorCambio['2']['cambio'])), 2, ',', '.') : 0,
                'chequeado' => $chequeado,
                'fechaChequeo' => $fechaChequeo
            ];
        }
    }

    echo json_encode($resultados, JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    error_log("Error en búsqueda: " . $e->getMessage());
    echo json_encode(['error' => 'Error en el sistema: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
