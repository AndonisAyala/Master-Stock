<?php
require_once 'connectDataBaseA2.php';
// Configuración de cabeceras y función de seguridad
//header('Content-Type: text/html; charset=utf-8');
/* function safeHtml($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
} */

// Stopwords para filtrar términos irrelevantes
$stopwords = ['de', 'para', 'el', 'la', 'los', 'las', 'y', 'en', 'a', 'con', 'por', 'del', 'al'];

try {
    $db = connectODBC();
    
    // 1. Obtener y procesar el término de búsqueda
    $terminoBusqueda = strtoupper(mb_convert_encoding($_POST['codProduct'] ?? '', 'UTF-8', 'auto'));
    //$terminoBusqueda = strtoupper(mb_convert_encoding('corsa' ?? '', 'UTF-8', 'auto'));
    $palabras = preg_split('/\s+/', trim($terminoBusqueda));
    $condiciones = [];

    // 2. Construir condiciones dinámicas de búsqueda
    foreach ($palabras as $palabra) {
        $palabra = trim($palabra);
        if (!empty($palabra) && !in_array(strtoupper($palabra, 'UTF-8'), $stopwords)) {
            $palabra = str_replace("'", "''", $palabra); // Escape para DBISAM
            $condiciones[] = "(FI_CODIGO LIKE '%$palabra%' 
                             OR FI_DESCRIPCION LIKE '%$palabra%'
                             OR FI_MODELO LIKE '%$palabra%'
                             OR FI_REFERENCIA LIKE '%$palabra%'
                             OR FI_MARCA LIKE '%$palabra%')";
        }
    }

    // 3. Definir el WHERE basado en la búsqueda
    $where = !empty($condiciones) ? 'WHERE ' . implode(' AND ', $condiciones) : '';

    // 4. Consulta principal a Sinventario
    $sqlInventario = "SELECT 
                        FI_CODIGO AS codigo, 
                        FI_DESCRIPCION AS descripcion,
                        FI_MODELO AS modelo,
                        FI_REFERENCIA AS referencia,
                        FI_MARCA AS marca,
                        FI_CATEGORIA AS departamento1,
                        FI_SUBCATEGORIA AS categoria
                      FROM Sinventario
                      $where";

    $stmtInventario = $db->query($sqlInventario);
    $productos = $stmtInventario->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($productos)) {
        // 5. Consultas adicionales para existencias y precios
        $codigos = array_map(fn($p) => "'" . trim($p['codigo']) . "'", $productos);
        $listaCodigos = implode(',', $codigos);

        // Consulta a SinvDep (existencias)
        $sqlSinvDep = "SELECT 
                          FT_CODIGOPRODUCTO AS codigo,
                          FT_PUESTO AS puesto,
                          FT_EXISTENCIA AS existencia
                       FROM SinvDep
                       WHERE FT_CODIGOPRODUCTO IN ($listaCodigos)";
        $stmtSinvDep = $db->query($sqlSinvDep);
        $existencias = $stmtSinvDep->fetchAll(PDO::FETCH_GROUP|PDO::FETCH_ASSOC);

        // Consulta a a2InvCostosPrecios
        $sqlPrecios = "SELECT 
                          FIC_CODEITEM AS codigo,
                          FIC_COSTOACTEXTRANJERO AS costo,
                          FIC_P01PRECIOTOTALEXT AS precio1,
                          FIC_P02PRECIOTOTALEXT AS precio2,
                          FIC_P03PRECIOTOTALEXT AS precio3
                       FROM a2InvCostosPrecios
                       WHERE FIC_CODEITEM IN ($listaCodigos)";
        $stmtPrecios = $db->query($sqlPrecios);
        $precios = $stmtPrecios->fetchAll(PDO::FETCH_GROUP|PDO::FETCH_ASSOC);

        foreach ($productos as $producto) {
            $codigo = trim($producto['codigo']);
            $codigoDepartamento = trim($producto['departamento1']);
            $result[] = [
                'id' => $codigo,
                'codigo' => $producto['referencia'],
                'descripcion' => $producto['descripcion'],
                'autos' => $producto['modelo'],
                'marca' => $producto['marca'],
                'departamento' => $producto['departamento1'],
                'categoria' => $producto['categoria'],
                'puesto' => $existencias[$codigo][0]['puesto'] ,
                'cantidad' => $existencias[$codigo][0]['existencia'] ?? [],
                'costo' => $precios[$codigo][0]['costo']  ?? [],
                'precio1' => $precios[$codigo][0]['precio1']  ?? [],
                'precio2' => $precios[$codigo][0]['precio2']  ?? [],
                'precio3' => $precios[$codigo][0]['precio3']  ?? []
            ];

            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        }
    } else {
            echo json_encode(['error' => 'Producto no encontrado']);
    }

} catch (PDOException $e) {
           echo json_encode(['error' => 'ID de producto no proporcionado']);
}
?>