<?php
// inventoryAPI.php - API para gestionar el inventario

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

// Incluir el archivo de conexión a la base de datos
require_once 'connectDataBase.php';

// Respuesta por defecto
$response = [
    'success' => false,
    'message' => 'Acción no válida'
];

try {
    // Determinar la acción a realizar
    $action = $_POST['action'] ?? $_GET['action'] ?? '';

    switch ($action) {
        case 'getProducts':
            // Obtener todos los productos
            $query = "SELECT * FROM invProducto ORDER BY fecha_registro DESC";
            $result = $connection->query($query);
            
            if ($result) {
                $products = [];
                while ($row = $result->fetch_assoc()) {
                    $products[] = $row;
                }
                
                $response = [
                    'success' => true,
                    'data' => $products
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => 'Error al obtener productos: ' . $connection->error
                ];
            }
            break;

        case 'getProduct':
            // Obtener un producto específico
            if (isset($_POST['id'])) {
                $id = (int)$_POST['id'];
                
                $query = "SELECT * FROM invProducto WHERE id = $id";
                $result = $connection->query($query);
                
                if ($result && $result->num_rows > 0) {
                    $product = $result->fetch_assoc();
                    
                    $response = [
                        'success' => true,
                        'data' => $product
                    ];
                } else {
                    $response = [
                        'success' => false,
                        'message' => 'Producto no encontrado'
                    ];
                }
            } else {
                $response = [
                    'success' => false,
                    'message' => 'ID de producto no especificado'
                ];
            }
            break;

        case 'addProduct':
            // Validar y agregar un nuevo producto
            if (isset($_POST['code'], $_POST['description'], $_POST['brand'], $_POST['position'], $_POST['quantity'])) {
                $code = $connection->real_escape_string($_POST['code']);
                $description = $connection->real_escape_string($_POST['description']);
                $brand = $connection->real_escape_string($_POST['brand']);
                $position = $connection->real_escape_string($_POST['position']);
                $quantity = (int)$_POST['quantity'];
                
                // Verificar si el código ya existe
                $checkQuery = "SELECT id FROM invProducto WHERE codigo = '$code'";
                $checkResult = $connection->query($checkQuery);
                
                if ($checkResult->num_rows > 0) {
                    $response = [
                        'success' => false,
                        'message' => 'El código de producto ya existe'
                    ];
                } else {
                    $query = "INSERT INTO invProducto (codigo, descripcion, marca, posicion, cantidad) 
                              VALUES ('$code', '$description', '$brand', '$position', $quantity)";
                    
                    if ($connection->query($query)) {
                        $response = [
                            'success' => true,
                            'message' => 'Producto agregado correctamente',
                            'id' => $connection->insert_id
                        ];
                    } else {
                        $response = [
                            'success' => false,
                            'message' => 'Error al agregar producto: ' . $connection->error
                        ];
                    }
                }
            } else {
                $response = [
                    'success' => false,
                    'message' => 'Faltan campos obligatorios'
                ];
            }
            break;

        case 'updateProduct':
            // Actualizar un producto existente
            if (isset($_POST['id'], $_POST['code'], $_POST['description'], $_POST['brand'], $_POST['position'], $_POST['quantity'])) {
                $id = (int)$_POST['id'];
                $code = $connection->real_escape_string($_POST['code']);
                $description = $connection->real_escape_string($_POST['description']);
                $brand = $connection->real_escape_string($_POST['brand']);
                $position = $connection->real_escape_string($_POST['position']);
                $quantity = (int)$_POST['quantity'];
                
                // Verificar si el código ya existe en otro producto
                $checkQuery = "SELECT id FROM invProducto WHERE codigo = '$code' AND id != $id";
                $checkResult = $connection->query($checkQuery);
                
                if ($checkResult->num_rows > 0) {
                    $response = [
                        'success' => false,
                        'message' => 'El código de producto ya existe en otro producto'
                    ];
                } else {
                    $query = "UPDATE invProducto SET 
                              codigo = '$code', 
                              descripcion = '$description', 
                              marca = '$brand', 
                              posicion = '$position', 
                              cantidad = $quantity 
                              WHERE id = $id";
                    
                    if ($connection->query($query)) {
                        $response = [
                            'success' => true,
                            'message' => 'Producto actualizado correctamente'
                        ];
                    } else {
                        $response = [
                            'success' => false,
                            'message' => 'Error al actualizar producto: ' . $connection->error
                        ];
                    }
                }
            } else {
                $response = [
                    'success' => false,
                    'message' => 'Faltan campos obligatorios'
                ];
            }
            break;

        case 'deleteProduct':
            // Eliminar un producto
            if (isset($_POST['id'])) {
                $id = (int)$_POST['id'];
                
                $query = "DELETE FROM invProducto WHERE id = $id";
                
                if ($connection->query($query)) {
                    $response = [
                        'success' => true,
                        'message' => 'Producto eliminado correctamente'
                    ];
                } else {
                    $response = [
                        'success' => false,
                        'message' => 'Error al eliminar producto: ' . $connection->error
                    ];
                }
            } else {
                $response = [
                    'success' => false,
                    'message' => 'ID de producto no especificado'
                ];
            }
            break;

        default:
            $response = [
                'success' => false,
                'message' => 'Acción no válida'
            ];
            break;
    }

} catch (Exception $e) {
    $response = [
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ];
}

// Cerrar conexión
$connection->close();

// Devolver respuesta en formato JSON
echo json_encode($response);
?>