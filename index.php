<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Menu Master-Stocks</title>
    <link rel='stylesheet' href='/Master-Stocks/style/css/bootstrap.css'>
    <style>
        :root {
            --primary-color: #3483fa;
            --secondary-color: #f8f9fa;
            --accent-color: #2968c8;
            --text-dark: #2d2d2d;
            --text-light: #6c757d;
        }
        
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .navbar-brand {
            font-weight: 700;
            color: var(--primary-color) !important;
        }
        
        .hero-section {
            background: linear-gradient(to right, #2968c8, #3483fa);
            color: white;
            padding: 3rem 0;
            margin-bottom: 2rem;
            border-radius: 0 0 20px 20px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .card-menu {
            border: none;
            border-radius: 12px;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s, box-shadow 0.3s;
            margin-bottom: 1.5rem;
            overflow: hidden;
        }
        
        .card-menu:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 20px rgba(0, 0, 0, 0.15);
        }
        
        .card-menu .card-body {
            padding: 1.5rem;
        }
        
        .card-title {
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 0.75rem;
        }
        
        .card-text {
            color: var(--text-light);
            font-size: 0.9rem;
        }
        
        .menu-link {
            color: var(--text-dark);
            text-decoration: none;
            display: block;
            padding: 1rem;
            border-radius: 8px;
            transition: all 0.2s;
        }
        
        .menu-link:hover {
            background-color: rgba(52, 131, 250, 0.1);
            color: var(--primary-color);
            text-decoration: none;
        }
        
        .logo-container {
            padding: 2rem 0;
            text-align: center;
        }
        
        .logo {
            max-width: 180px;
            height: auto;
            filter: drop-shadow(0 4px 6px rgba(0, 0, 0, 0.1));
        }
        
        .feature-icon {
            font-size: 1.5rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }
        
        footer {
            background-color: var(--secondary-color);
            padding: 1.5rem 0;
            margin-top: 2rem;
            border-top: 1px solid rgba(0, 0, 0, 0.05);
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="#">
                <img src="/Master-Stocks/style/img/logo.png" alt="Logo" height="30" class="d-inline-block align-text-top me-2">
                Master-Stocks
            </a>
        </div>
    </nav>

    <div class="hero-section">
        <div class="container text-center">
            <h1 class="display-4 fw-bold">Sistema Master-Stocks</h1>
            <p class="lead">Plataforma de gestión integral de productos</p>
        </div>
    </div>

    <div class="container">
        <div class="row">
            <div class="col-md-6 col-lg-4">
                <div class="card card-menu">
                    <div class="card-body text-center">
                        <div class="feature-icon">📦</div>
                        <h5 class="card-title">Gestión de Productos</h5>
                        <p class="card-text">Administra información completa de productos</p>
                        <a href='/Master-Stocks/model/php/mlViewA2.php' class="btn btn-primary mt-2">Acceder</a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 col-lg-4">
                <div class="card card-menu">
                    <div class="card-body text-center">
                        <div class="feature-icon">👤</div>
                        <h5 class="card-title">Gestión de Vendedores</h5>
                        <p class="card-text">Consulta y administra información de vendedores</p>
                        <div class="d-grid gap-2">
                            <a href='/Master-Stocks/model/php/sellerView.php' class="btn btn-outline-primary">Vendedores</a>
                            <a href='/Master-Stocks/model/php/sellerViewA2.php' class="btn btn-outline-primary">Vendedores A2</a>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 col-lg-4">
                <div class="card card-menu">
                    <div class="card-body text-center">
                        <div class="feature-icon">🖼️</div>
                        <h5 class="card-title">Visualización Cliente</h5>
                        <p class="card-text">Consulta imágenes de productos para clientes</p>
                        <a href='/Master-Stocks/model/php/clientViewA2.php' class="btn btn-primary mt-2">Acceder</a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 col-lg-4">
                <div class="card card-menu">
                    <div class="card-body text-center">
                        <div class="feature-icon">➕</div>
                        <h5 class="card-title">Inventario</h5>
                        <p class="card-text">Registro de productos nuevos en el sistema</p>
                        <a href='/Master-Stocks/model/php/inventoryProduct.php' class="btn btn-primary mt-2">Acceder</a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 col-lg-4">
                <div class="card card-menu">
                    <div class="card-body text-center">
                        <div class="feature-icon">📺</div>
                        <h5 class="card-title">Publicidad TV</h5>
                        <p class="card-text">Gestión de publicidad para SmartTV</p>
                        <a href='/PublicidadTV/model/php/publicidad.php' class="btn btn-primary mt-2">Acceder</a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 col-lg-4">
                <div class="card card-menu">
                    <div class="card-body text-center">
                        <div class="feature-icon">📊</div>
                        <h5 class="card-title">Estadísticas</h5>
                        <p class="card-text">Visualiza métricas y reportes del sistema</p>
                        <a href='#' class="btn btn-outline-secondary mt-2">Próximamente</a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="logo-container">
            <img src='/Master-Stocks/style/img/logo.png' class="logo" alt='Logo Master-Stocks'>
        </div>
    </div>
    
    <footer class="text-center">
        <div class="container">
            <p class="mb-0 text-muted">Sistema Master-Stocks &copy; <?php echo date('Y'); ?> - Todos los derechos reservados</p>
        </div>
    </footer>
    
    <script src='/Master-Stocks/controller/js/jquery.min.js'></script>
    <script src='/Master-Stocks/controller/js/bootstrap.bundle.min.js'></script>
</body>
</html>