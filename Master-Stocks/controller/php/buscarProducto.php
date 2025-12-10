<?php
    $idProducto = $_POST['codProduct'];
    
    if(!empty($idProducto)) {
        include 'connectDataBase.php';
        
        $stopwords = array('de', 'para', 'el', 'la', 'los', 'las', 'y', 'en', 'a', 'con', 'por', 'del', 'al');
        $busqueda = preg_split('/\s+/', trim($idProducto));
        $condiciones = array();

        foreach ($busqueda as $palabra) {
            $palabra = trim($palabra);
            if (!empty($palabra) && !in_array(strtolower($palabra), $stopwords)) {
                $palabra = $connection->real_escape_string($palabra);
                $condiciones[] = "(P.id = '$palabra' OR P.codigo LIKE '%$palabra%' OR P.descripcion LIKE '%$palabra%' OR P.autos LIKE '%$palabra%' OR M.descripcion LIKE '%$palabra%')";
            }
        }

        if (!empty($condiciones)) {
            $where = implode(' AND ', $condiciones);
            $sql = "SELECT P.id, P.codigo, P.descripcion, P.autos, M.descripcion AS marca, P.cantidad, P.av, P.morocha,P.puesto, P.almacen, P.precio1, P.precio2, P.precio3 
                    FROM producto AS P 
                    INNER JOIN marca AS M ON P.marca = M.id 
                    WHERE $where 
                    ORDER BY P.descripcion ASC";
        } else {
            $sql = "SELECT P.id, P.codigo, P.descripcion, P.autos, M.descripcion AS marca, P.cantidad, P.av, P.morocha, P.puesto, P.almacen, P.precio1, P.precio2, P.precio3 
                    FROM producto AS P 
                    INNER JOIN marca AS M ON P.marca = M.id 
                    ORDER BY P.descripcion ASC";
        }


        $result = $connection->query($sql);
        if ($result->num_rows > 0) {
            $productos = [];
            // Fetch all products matching the criteria
            while ($producto = $result->fetch_assoc()) {
                $productos[] = [
                    'id' => $producto['id'],
                    'codigo' => $producto['codigo'],
                    'descripcion' => $producto['descripcion'],
                    'autos' => $producto['autos'],
                    'marca' => $producto['marca'],
                    'cantidad' => $producto['cantidad'],
                    'av' => $producto['av'],
                    'Moro' => $producto['morocha'],
                    'puesto' => $producto['puesto'],
                    'almacen' => $producto['almacen'],
                    'precio1' => $producto['precio1'],
                    'precio2' => $producto['precio2'],
                    'precio3' => $producto['precio3']
                ];
            }
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($productos, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        } else {
            echo json_encode(['error' => 'Producto no encontrado']);
        }
        $connection->close();
    } else {    
        echo json_encode(['error' => 'ID de producto no proporcionado']);
    }