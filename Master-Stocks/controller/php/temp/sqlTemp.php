<?php
require_once 'connectDataBaseA2.php';
echo "<meta http-equiv='Content-Type' content='text/html; charset=utf-8'>";

// Función auxiliar para manejar valores null en htmlspecialchars()
function safeHtml($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

try {
    $db = connectODBC();
    $codigoInicio = '00000001';
    $codigoFin = '10000000';
    $longitudCampo = 30;

    // 1. Consulta a Sinventario
    $sqlInventario = "SELECT 
                        FI_CODIGO AS codigo, 
                        FI_DESCRIPCION AS descripcion,
                        FI_MODELO AS modelo,
                        FI_REFERENCIA AS referencia,
                        FI_MARCA AS marca,
                        FI_CATEGORIA AS departamento1,
                        FI_SUBCATEGORIA AS categoria
                      FROM Sinventario
                      WHERE FI_CODIGO BETWEEN '$codigoInicio' AND '$codigoFin'";
    
    $stmtInventario = $db->query($sqlInventario);
    $productos = $stmtInventario->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($productos)) {
        // 2. Consulta a SinvDep
        $codigos = array_map(function($p) { 
            return "'" . trim($p['codigo']) . "'"; 
        }, $productos);
        $listaCodigos = implode(',', $codigos);
        
        $sqlSinvDep = "SELECT 
                          FT_CODIGOPRODUCTO AS codigo,
                          FT_PUESTO AS puesto,
                          FT_EXISTENCIA AS existencia
                       FROM SinvDep
                       WHERE FT_CODIGOPRODUCTO IN ($listaCodigos)";
        
        $stmtSinvDep = $db->query($sqlSinvDep);
        $existencias = $stmtSinvDep->fetchAll(PDO::FETCH_GROUP|PDO::FETCH_ASSOC);

        // 3. Consulta a Scategoria
        $departamentos = array_unique(array_column($productos, 'departamento1'));
        $listaDepartamentos = implode(",", array_map(function($d) { 
            return "'" . trim($d) . "'"; 
        }, $departamentos));
        
        $sqlScategoria = "SELECT 
                            FD_CODIGO AS codigo,
                            FD_DESCRIPCION AS departamento
                          FROM Scategoria
                          WHERE FD_CODIGO IN ($listaDepartamentos)";

        $stmtScategoria = $db->query($sqlScategoria);
        $categoria = $stmtScategoria->fetchAll(PDO::FETCH_GROUP|PDO::FETCH_ASSOC);

        // 4. Consulta a a2InvCostosPrecios
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

        // 5. Mostrar resultados
        echo '<table border="1">';
        echo '<tr>
                <th>Id</th>
                <th>Descripción</th>
                <th>Autos</th>
                <th>Código</th>
                <th>Marca</th>
                <th>Departamento</th>
                <th>Categoria</th>
                <th>Puesto</th>
                <th>Existencia</th>
                <th>Costo</th>
                <th>Precio1</th>
                <th>Precio2</th>
                <th>Precio3</th>
              </tr>';
        
        foreach ($productos as $producto) {
            $codigo = trim($producto['codigo']);
            $codigoDepartamento = trim($producto['departamento1']);
            
            echo '<tr>';
            echo '<td>' . safeHtml($codigo) . '</td>';
            echo '<td>' . safeHtml($producto['descripcion']) . '</td>';
            echo '<td>' . safeHtml($producto['modelo']) . '</td>';
            echo '<td>' . safeHtml($producto['referencia']) . '</td>';
            echo '<td>' . safeHtml($producto['marca']) . '</td>';
            echo '<td>' . safeHtml($categoria[$codigoDepartamento][0]['departamento'] ?? null) . '</td>';
            echo '<td>' . safeHtml($producto['categoria']) . '</td>';
            echo '<td>' . safeHtml($existencias[$codigo][0]['puesto'] ?? null) . '</td>';
            echo '<td>' . safeHtml($existencias[$codigo][0]['existencia'] ?? null) . '</td>';
            echo '<td>' . safeHtml($precios[$codigo][0]['costo'] ?? null) . '</td>';
            echo '<td>' . safeHtml($precios[$codigo][0]['precio1'] ?? null) . '</td>';
            echo '<td>' . safeHtml($precios[$codigo][0]['precio2'] ?? null) . '</td>';
            echo '<td>' . safeHtml($precios[$codigo][0]['precio3'] ?? null) . '</td>';
            echo '</tr>';
        }
        
        echo '</table>';
    } else {
        echo '<p>No se encontraron productos</p>';
    }

} catch (PDOException $e) {
    echo '<div style="color:red;">Error: ' . safeHtml($e->getMessage()) . '</div>';
    error_log("Error DBISAM: " . date('Y-m-d H:i:s') . " - " . $e->getMessage());
}
?>