<?php

include '../connectDataBase.php';
require_once '../connectDataBaseA2.php';


$sql = "SELECT id, idE, descripcion, peso FROM producto order by idE asc";
$result = $connection->query($sql);

$db_bdisam = flex_db_bdisam();

if ($result->num_rows > 0) {
    $productos = [];
    while ($producto = $result->fetch_assoc()) {
        $sql_bdisam = "UPDATE Sinventario SET FI_PESOPRODUCTO = ".$producto['peso'];
        $result_bdisam = $db_bdisam->executeQuery($sql_bdisam);
        if($result_bdisam->execute()){
            echo "El peso ". $productos['peso']." fue artualizado de la idE". $productos['idE'];
            break;
        }
    }
} else {
    echo json_encode(['error' => 'Producto no encontrado']);
}