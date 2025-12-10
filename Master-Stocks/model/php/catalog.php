<?
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recoleccion de fotos</title>
    <link rel="stylesheet" href="../../style/css/bootstrap.css">
    <link rel="stylesheet" href="">
</head>
<body class="bg-secondary">
    <div class="container-fluid">
        <div class="row flex-column align-items-start justify-content-center">
            
            <div class="modal fade" id="modalOverlay" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div id="modalContent">
                            <!-- Contenido dinámico se cargará aquí -->
                        </div>
                    </div>
                </div>
            </div>

            <nav class="col-12 navbar navbar-expand-lg sticky-top sticky-left flex-column bg-body-tertiary bg-light">
                <form role="search" class="container-fluid d-flex">
                    <div class="form-floating input-group">
                        <input type="text" class="pt-0 pb-0 form-control" id="searchProductIP" placeholder="Codigo o descripción" name="" id="">
                        <button class="btn btn-outline-success" id="searchProductBTN" type="button">🔍</button>
                    </div>    
                </form>
            </nav>

             <div class="col">
                <div class="card h-100 shadow-sm">
                    <img src="https://images.unsplash.com/photo-1598618826732-fb2fdf367775?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w0NzEyNjZ8MHwxfHNlYXJjaHw1fHxzbWFydHBob25lfGVufDB8MHx8fDE3MjEzMDU4NTZ8MA&ixlib=rb-4.0.3&q=80&w=1080" class="card-img-top" alt="Product 1">
                    <div class="card-body">
                        <h5 class="card-title">Product 1</h5>
                        <p class="card-text">A brief description of Product 1 and its features.</p>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="h5 mb-0">$19.99</span>
                            <button class="btn btn-outline-primary"><i class="bi bi-cart-plus"></i> Add to Cart</button>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</body>
<script src="../../controller/js/jquery.min.js"></script>
<script src="../../controller/js/bootstrap.bundle.min.js"></script>
<script src="../../controller/js/mainClientView.js"></script>
</html>