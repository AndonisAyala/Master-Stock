<?php
echo"
<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Recoleccion de fotos</title>
    <link href='../../style/css/bootstrap.min.css' rel='stylesheet'>
    <link rel='stylesheet' href='../../style/css/bootstrap-icons.css'>
    <link rel='stylesheet' href='../../style/css/customInventory.css'>
</head>
<body class='bg-secondary'>";
include('../html/menu.php');
echo"
    <div class='container-fluid'>
        <div class='row flex-column align-items-start justify-content-center'>
            
            <div class='modal fade' id='modalOverlay' tabindex='-1' aria-hidden='true'>
                <div class='modal-dialog'>
                    <div class='modal-content'>
                        <div id='modalContent'>
                            <!-- Contenido dinámico se cargará aquí -->
                        </div>
                    </div>
                </div>
            </div>

            <nav class='col-12 navbar navbar-expand-lg sticky-top sticky-left flex-column bg-body-tertiary bg-light'>
                <form role='search' class='container-fluid d-flex'>
                    <div class='form-floating input-group'>
                        <input type='text' class='pt-0 pb-0 form-control' id='searchProductIP' placeholder='Codigo o descripción' name='' id=''>
                        <button class='btn btn-outline-success' id='searchProductBTN' type='button'>🔍</button>
                    </div>    
                </form>
            </nav>

            


            <div class='table-responsive-sm col-12 m-0 p-0'>
                <table class='table table-striped' style='width:100%' id='tablaProductos'>
                    <thead class='table-dark'>
                        <tr>
                            <th scope='col' id='Codigo'>Codigo</th>
                            <th scope='col' id='Descripción'>Descripción</th>
                            <th scope='col' id='Marca'>Marca</th>
                            <th scope='col' id='Cantidad'>Cantidad</th>
                            <th scope='col' id='av41'>av41</th>
                            <th scope='col' id='Moro'>Moro</th>
                            <th scope='col' id='Puesto'>puesto</th>
                            <th scope='col' id='Almacen'>almacen</th>
                            <th scope='col' id='Precio1'>Precio 1</th>
                            <th scope='col' id='Precio2'>Precio 2</th>
                            <th scope='col' id='Precio3'>Precio 3</th>
                        </tr>
                    </thead>
                    <tbody>
                    
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</body>
<script src='../../controller/js/jquery.min.js'></script>
<script src='../../controller/js/bootstrap.bundle.min.js'></script>
<script src='../../controller/js/mainSellerView.js'></script>
</html>";