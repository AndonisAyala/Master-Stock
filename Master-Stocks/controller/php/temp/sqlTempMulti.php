<?php
require_once 'connectDataBaseA2.php';

header('Content-Type: text/html; charset=utf-8');

function safeHtml($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

$stopwords = ['de', 'para', 'el', 'la', 'los', 'las', 'y', 'en', 'a', 'con', 'por', 'del', 'al'];

try {
    $db = connectODBC();
    
    // Modo prueba directa
    $terminoBusqueda = 'terminal de aveo';
    // $terminoBusqueda = $_POST['busqueda'] ?? ''; // Para producción
    
    // Preparamos los términos de búsqueda
    $palabras = preg_split('/\s+/', trim($terminoBusqueda));
    $condiciones = [];
    
    foreach ($palabras as $palabra) {
        $palabra = trim($palabra);
        $palabraLower = mb_strtolower($palabra, 'UTF-8');
        
        if (!empty($palabra) && !in_array($palabraLower, $stopwords)) {
            // Escape especial para DBISAM que preserva caracteres
            $palabraBuscada = str_replace(
                ["'", "[", "%", "_"],
                ["''", "[[]", "[%]", "[_]"],
                $palabra
            );
            
            // Construimos condiciones de búsqueda más flexibles
            $condiciones[] = "(FI_DESCRIPCION LIKE '%$palabraBuscada%' ESCAPE '[')";
            $condiciones[] = "(FI_MODELO LIKE '%$palabraBuscada%' ESCAPE '[')";
            $condiciones[] = "(FI_MARCA LIKE '%$palabraBuscada%' ESCAPE '[')";
        }
    }

    // Construimos el WHERE
    $where = !empty($condiciones) ? 'WHERE (' . implode(' OR ', $condiciones) . ')' : '';
    
    // Consulta compatible con DBISAM
    $sqlInventario = "SELECT 
                        FI_CODIGO AS codigo, 
                        FI_DESCRIPCION AS descripcion,
                        FI_MODELO AS modelo,
                        FI_MARCA AS marca
                      FROM Sinventario
                      $where
                      ORDER BY FI_DESCRIPCION";

    // Mostramos la consulta para diagnóstico (eliminar en producción)
    echo "<div style='background:#f0f0f0;padding:10px;margin:10px 0;'>";
    echo "<strong>Consulta SQL:</strong><br>".safeHtml($sqlInventario);
    echo "</div>";

    $stmtInventario = $db->query($sqlInventario);
    $productos = $stmtInventario->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($productos)) {
        echo '<h3>Resultados para: '.safeHtml($terminoBusqueda).'</h3>';
        echo '<table border="1" style="width:100%;border-collapse:collapse;">';
        echo '<tr style="background-color:#f2f2f2;"><th>Código</th><th>Descripción</th><th>Modelo</th><th>Marca</th></tr>';
        
        foreach ($productos as $producto) {
            echo '<tr>';
            echo '<td style="padding:5px;">'.safeHtml($producto['codigo']).'</td>';
            echo '<td style="padding:5px;">'.safeHtml($producto['descripcion']).'</td>';
            echo '<td style="padding:5px;">'.safeHtml($producto['modelo']).'</td>';
            echo '<td style="padding:5px;">'.safeHtml($producto['marca']).'</td>';
            echo '</tr>';
        }
        
        echo '</table>';
    } else {
        echo '<div style="color:#666;padding:10px;background:#f9f9f9;border:1px solid #ddd;">';
        echo '<p>No se encontraron resultados para: <strong>'.safeHtml($terminoBusqueda).'</strong></p>';
        
        // Sugerencia de búsqueda alternativa
        $terminoSugerido = str_replace('de ', '', $terminoBusqueda);
        echo '<p>Intenta con: <a href="#" onclick="document.getElementById(\'busqueda\').value=\''.safeHtml($terminoSugerido).'\';return false;">'
             .safeHtml($terminoSugerido).'</a></p>';
        echo '</div>';
    }

} catch (PDOException $e) {
    echo '<div style="color:red;padding:10px;border:1px solid red;margin:10px;">';
    echo '<strong>Error:</strong> '.safeHtml($e->getMessage());
    echo '</div>';
}
?>