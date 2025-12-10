<?php
    $idProducto = intval($_POST['codProduct']);
    if(!empty($idProducto)) {
        include 'connectDataBase.php';
        
        $sql = "SELECT id, codigo, descripcion, peso FROM producto WHERE id = $idProducto";
        $result = $connection->query($sql);

        if ($result->num_rows > 0) {
            $productos = [];
            while ($producto = $result->fetch_assoc()) {
                $productos[] = [
                    'id' => $producto['id'],
                    'codigo' => $producto['codigo'],
                    'descripcion' => $producto['descripcion'],
                    'peso' => $producto['peso'],
                ];
                // Aquí puedes procesar los datos del producto si es necesario
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode($productos, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            }
        } else {
            echo json_encode(['error' => 'Producto no encontrado']);
        }
    } else {    
        echo json_encode(['error' => 'ID de producto no proporcionado']);
    }