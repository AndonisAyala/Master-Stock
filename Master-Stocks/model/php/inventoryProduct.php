<?php
echo"
<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Sistema de Inventario</title>
    <link href='../../style/css/bootstrap.min.css' rel='stylesheet'>
    <link rel='stylesheet' href='../../style/css/bootstrap-icons.css'>
    <link rel='stylesheet' href='../../style/css/customInventory.css'>
</head>
<body class='bg-light'>";
include('../html/menu.php');
echo"
    <div class='container py-5'>
        <div class='row'>
            <div class='col-12 text-center mb-4'>
                <h1 class='text-primary'><i class='bi bi-boxes me-2'></i>Sistema de Inventario</h1>
                <p class='text-muted'>Gestión de productos con jQuery y Bootstrap</p>
            </div>
        </div>

        <div class='row'>
            <!-- Formulario de registro -->
            <div class='col-lg-5 mb-4'>
                <div class='card shadow-sm'>
                    <div class='card-header bg-primary text-white'>
                        <h5 class='card-title mb-0'><i class='bi bi-plus-circle me-2'></i>Agregar Nuevo Producto</h5>
                    </div>
                    <div class='card-body'>
                        <form id='productForm'>
                            <div class='mb-3'>
                                <label for='productCode' class='form-label'>Código</label>
                                <input type='text' class='form-control' id='productCode' required>
                            </div>
                            <div class='mb-3'>
                                <label for='productDescription' class='form-label'>Descripción</label>
                                <textarea class='form-control' id='productDescription' rows='2' required></textarea>
                            </div>
                            <div class='mb-3'>
                                <label for='productBrand' class='form-label'>Marca</label>
                                <input type='text' class='form-control' id='productBrand' required>
                            </div>
                            <div class='mb-3'>
                                <label for='productPosition' class='form-label'>Posición</label>
                                <input type='text' class='form-control' id='productPosition' required>
                            </div>
                            <div class='mb-3'>
                                <label for='productQuantity' class='form-label'>Cantidad</label>
                                <input type='number' class='form-control' id='productQuantity' min='0' required>
                            </div>
                            <button type='submit' class='btn btn-primary w-100'>
                                <i class='bi bi-check-circle me-2'></i>Registrar Producto
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Lista de productos -->
            <div class='col-lg-7'>
                <div class='card shadow-sm'>
                    <div class='card-header bg-success text-white'>
                        <h5 class='card-title mb-0'><i class='bi bi-table me-2'></i>Productos Registrados</h5>
                    </div>
                    <div class='card-body'>
                        <div class='d-flex justify-content-between align-items-center mb-3'>
                            <span class='text-muted'>Los productos más recientes aparecen primero</span>
                        </div>
                        
                        <div class='table-responsive'>
                            <table class='table table-hover' id='productsTable'>
                                <thead class='table-light'>
                                    <tr>
                                        <th>Código</th>
                                        <th>Descripción</th>
                                        <th>Marca</th>
                                        <th>Posición</th>
                                        <th>Cantidad</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id='productList'>
                                    <!-- Los productos se mostrarán aquí dinámicamente -->
                                </tbody>
                            </table>
                        </div>
                        <div id='emptyMessage' class='text-center py-4 d-none'>
                            <i class='bi bi-inbox display-4 text-muted'></i>
                            <p class='text-muted mt-2'>No hay productos registrados</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para editar producto -->
    <div class='modal fade' id='editProductModal' tabindex='-1' aria-labelledby='editProductModalLabel' aria-hidden='true'>
        <div class='modal-dialog'>
            <div class='modal-content'>
                <div class='modal-header bg-primary text-white'>
                    <h5 class='modal-title' id='editProductModalLabel'><i class='bi bi-pencil-square me-2'></i>Editar Producto</h5>
                    <button type='button' class='btn-close btn-close-white' data-bs-dismiss='modal' aria-label='Close'></button>
                </div>
                <div class='modal-body'>
                    <form id='editProductForm'>
                        <input type='hidden' id='editProductId'>
                        <div class='mb-3'>
                            <label for='editProductCode' class='form-label'>Código</label>
                            <input type='text' class='form-control' id='editProductCode' required>
                        </div>
                        <div class='mb-3'>
                            <label for='editProductDescription' class='form-label'>Descripción</label>
                            <textarea class='form-control' id='editProductDescription' rows='2' required></textarea>
                        </div>
                        <div class='mb-3'>
                            <label for='editProductBrand' class='form-label'>Marca</label>
                            <input type='text' class='form-control' id='editProductBrand' required>
                        </div>
                        <div class='mb-3'>
                            <label for='editProductPosition' class='form-label'>Posición</label>
                            <input type='text' class='form-control' id='editProductPosition' required>
                        </div>
                        <div class='mb-3'>
                            <label for='editProductQuantity' class='form-label'>Cantidad</label>
                            <input type='number' class='form-control' id='editProductQuantity' min='0' required>
                        </div>
                    </form>
                </div>
                <div class='modal-footer'>
                    <button type='button' class='btn btn-secondary' data-bs-dismiss='modal'>Cancelar</button>
                    <button type='button' class='btn btn-primary' id='saveEditProduct'>Guardar Cambios</button>
                </div>
            </div>
        </div>
    </div>

    <script src='../../controller/js/jquery.min.js'></script>
    <script src='../../controller/js/bootstrap.bundle.js'></script>
    <script src='../../controller/js/mainInventory.js'></script>
</body>
</html>";