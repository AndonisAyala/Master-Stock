<?php
require_once '../connectDataBaseA2.php'; // Para BDISAM

// Configuración de exportación
header('Content-Type: text/plain; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// Función para escape de valores CSV
function escapeCsvValue($value, $delimiter = ';') {
    $value = str_replace('"', '""', $value ?? '');
    if (preg_match('/['.$delimiter.'"\r\n]/', $value)) {
        $value = '"'.$value.'"';
    }
    return $value;
}

try {
    // 1. Conectar a BDISAM
    $db = flex_db_bdisam();
    
    // 2. Conectar a MySQL para las imágenes
    $mysql = new mysqli("localhost", "root", "", "fotosmercadolibre");
    if ($mysql->connect_error) {
        throw new Exception("Error conectando a MySQL: ".$mysql->connect_error);
    }
    $mysql->set_charset("utf8");

    // 3. Consulta productos desde BDISAM
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
                      FROM Sinventario";
    
    $resultadoInventario = $db->executeQuery($sqlInventario);
    $productos = $db->fetchAll($resultadoInventario);
    
    if (empty($productos)) {
        die("No se encontraron productos activos para exportar");
    }
    
    // 4. Prepara consultas adicionales en BDISAM
    $codigos = array_map(function($p) { return "'".trim($p['codigo'])."'"; }, $productos);
    $listaCodigos = implode(',', $codigos);
    
    // Consulta existencias
    $sqlSinvDep = "SELECT FT_CODIGOPRODUCTO AS codigo, FT_PUESTO AS puesto, FT_EXISTENCIA AS existencia
                   FROM SinvDep WHERE FT_CODIGOPRODUCTO IN ($listaCodigos)";
    $existencias = [];
    foreach ($db->fetchAll($db->executeQuery($sqlSinvDep)) as $item) {
        $existencias[$item['codigo']] = $item;
    }
    
    // Consulta precios
    $sqlPrecios = "SELECT FIC_CODEITEM AS codigo, FIC_COSTOACTEXTRANJERO AS costo,
                          FIC_P01PRECIOTOTALEXT AS precio1, FIC_P02PRECIOTOTALEXT AS precio2,
                          FIC_P03PRECIOTOTALEXT AS precio3
                   FROM a2InvCostosPrecios WHERE FIC_CODEITEM IN ($listaCodigos)";
    $precios = [];
    foreach ($db->fetchAll($db->executeQuery($sqlPrecios)) as $item) {
        $precios[$item['codigo']] = $item;
    }
    
    // 5. Consulta imágenes desde MySQL (MODIFICACIÓN IMPORTANTE)
    $imagenes = [];
    $sqlImagenes = "SELECT idProducto, url FROM imagenes WHERE idProducto IN ($listaCodigos)";
    if ($result = $mysql->query($sqlImagenes)) {
        while ($row = $result->fetch_assoc()) {
            $codigo = trim($row['idProducto']);
            if (!isset($imagenes[$codigo])) {
                $imagenes[$codigo] = [];
            }
            $imagenes[$codigo][] = $row['url'];
        }
        $result->close();
    } else {
        throw new Exception("Error en consulta de imágenes: ".$mysql->error);
    }
    
    // 6. Generar archivo CSV
    header('Content-Disposition: attachment; filename="export_productos_'.date('Ymd_His').'.txt"');
    
    // Encabezados
    echo implode(';', [
        'ID', 'Código', 'Descripción', 'Modelo/Autos', 'Marca', 'Departamento',
        'Categoría', 'Puesto', 'Existencia', 'Peso', 'Costo', 'Precio 1 (IVA)',
        'Precio 2 (IVA)', 'Precio 3 (IVA)', 'Imágenes', 'Activo'
    ])."\r\n";
    
    // Datos
    foreach ($productos as $producto) {
        $codigo = trim($producto['codigo']);
        $urls = isset($imagenes[$codigo]) ? implode(',', $imagenes[$codigo]) : '';
        
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
            escapeCsvValue($producto['peso'] ?? 0),
            escapeCsvValue($precios[$codigo]['costo'] ?? 0),
            escapeCsvValue(round(($precios[$codigo]['precio1'] * 1.16), 2) ?? 0),
            escapeCsvValue(round(($precios[$codigo]['precio2'] * 1.16), 2) ?? 0),
            escapeCsvValue(round(($precios[$codigo]['precio3'] * 1.16), 2) ?? 0),
            escapeCsvValue($urls),
            escapeCsvValue($producto['activo'] ? 'SI' : 'NO')
        ];
        
        echo implode(';', $fila)."\r\n";
    }

    $mysql->close();

} catch (Exception $e) {
    if (isset($mysql)) $mysql->close();
    error_log("Error en exportación: ".$e->getMessage());
    die("Error al generar el archivo: ".$e->getMessage());
}
?>