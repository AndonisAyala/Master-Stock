// mainInventory.js - Script para gestionar el inventario con jQuery y AJAX

$(document).ready(function() {
    // URL del script PHP que procesará los datos
    const apiUrl = '../../controller/php/inventoryAPI.php';
    
    // Variables globales
    let editModal = null;
    
    // Inicializar modal de edición
    $(function() {
        editModal = new bootstrap.Modal(document.getElementById('editProductModal'));
    });
    
    // Función para formatear fecha
    function formatDate(dateString) {
        const date = new Date(dateString);
        return new Intl.DateTimeFormat('es-ES', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        }).format(date);
    }
    
    // Función para cargar productos desde el servidor
    function loadProducts() {
        showLoading(true);
        
        $.ajax({
            url: apiUrl + '?action=getProducts',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                showLoading(false);
                if (response.success) {
                    renderProducts(response.data);
                } else {
                    showAlert('Error al cargar productos: ' + response.message, 'danger');
                }
            },
            error: function(xhr, status, error) {
                showLoading(false);
                showAlert('Error de conexión: ' + error, 'danger');
            }
        });
    }
    
    // Función para mostrar/ocultar indicador de carga
    function showLoading(show) {
        if (show) {
            $('#productList').html(`
                <tr>
                    <td colspan="7" class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                        <p class="mt-2">Cargando productos...</p>
                    </td>
                </tr>
            `);
        }
    }
    
    // Función para renderizar productos en la tabla
    function renderProducts(products) {
        const $productList = $('#productList');
        const $emptyMessage = $('#emptyMessage');
        
        if (products.length === 0) {
            $productList.empty();
            $emptyMessage.removeClass('d-none');
            return;
        }
        
        $emptyMessage.addClass('d-none');
        $productList.empty();
        
        $.each(products, function(index, product) {
            const $row = $('<tr>');
            
            $row.html(`
                <td>${product.codigo}</td>
                <td>${product.descripcion}</td>
                <td>${product.marca}</td>
                <td>${product.posicion}</td>
                <td>${product.cantidad}</td>
                <td>
                    <div class="btn-group btn-group-sm" role="group">
                        <button class="btn btn-outline-primary edit-btn" data-id="${product.id}" title="Editar producto">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-outline-danger delete-btn" data-id="${product.id}" title="Eliminar producto">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </td>
            `);
            
            $productList.append($row);
        });
        
        // Agregar event listeners a los botones de eliminar
        $('.delete-btn').on('click', function() {
            const productId = $(this).data('id');
            deleteProduct(productId);
        });
        
        // Agregar event listeners a los botones de editar
        $('.edit-btn').on('click', function() {
            const productId = $(this).data('id');
            loadProductData(productId);
        });
    }
    
    // Función para cargar datos del producto en el modal de edición
    function loadProductData(productId) {
        $.ajax({
            url: apiUrl,
            type: 'POST',
            data: {
                action: 'getProduct',
                id: productId
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    const product = response.data;
                    $('#editProductId').val(product.id);
                    $('#editProductCode').val(product.codigo);
                    $('#editProductDescription').val(product.descripcion);
                    $('#editProductBrand').val(product.marca);
                    $('#editProductPosition').val(product.posicion);
                    $('#editProductQuantity').val(product.cantidad);
                    
                    // Mostrar modal de edición
                    editModal.show();
                } else {
                    showAlert('Error al cargar producto: ' + response.message, 'danger');
                }
            },
            error: function(xhr, status, error) {
                showAlert('Error de conexión: ' + error, 'danger');
            }
        });
    }
    
    // Función para agregar producto via AJAX
    function addProduct(productData) {
        $.ajax({
            url: apiUrl,
            type: 'POST',
            data: {
                action: 'addProduct',
                code: productData.code,
                description: productData.description,
                brand: productData.brand,
                position: productData.position,
                quantity: productData.quantity
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showAlert('Producto agregado correctamente', 'success');
                    loadProducts(); // Recargar la lista de productos
                } else {
                    showAlert('Error al agregar producto: ' + response.message, 'danger');
                }
            },
            error: function(xhr, status, error) {
                showAlert('Error de conexión: ' + error, 'danger');
            }
        });
    }
    
    // Función para actualizar producto via AJAX
    function updateProduct(productData) {
        $.ajax({
            url: apiUrl,
            type: 'POST',
            data: {
                action: 'updateProduct',
                id: productData.id,
                code: productData.code,
                description: productData.description,
                brand: productData.brand,
                position: productData.position,
                quantity: productData.quantity
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showAlert('Producto actualizado correctamente', 'success');
                    editModal.hide();
                    loadProducts(); // Recargar la lista de productos
                } else {
                    showAlert('Error al actualizar producto: ' + response.message, 'danger');
                }
            },
            error: function(xhr, status, error) {
                showAlert('Error de conexión: ' + error, 'danger');
            }
        });
    }
    
    // Función para eliminar producto
    function deleteProduct(productId) {
        if (confirm('¿Está seguro de que desea eliminar este producto?')) {
            $.ajax({
                url: apiUrl,
                type: 'POST',
                data: {
                    action: 'deleteProduct',
                    id: productId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        showAlert('Producto eliminado correctamente', 'success');
                        loadProducts(); // Recargar la lista de productos
                    } else {
                        showAlert('Error al eliminar producto: ' + response.message, 'danger');
                    }
                },
                error: function(xhr, status, error) {
                    showAlert('Error de conexión: ' + error, 'danger');
                }
            });
        }
    }
    
    // Función para mostrar alertas
    function showAlert(message, type) {
        // Eliminar alertas anteriores
        $('.alert-dismissible').remove();
        
        const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        const alertHtml = `
            <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;
        
        // Insertar la alerta después del encabezado
        $('.container.py-5').prepend(alertHtml);
        
        // Auto-ocultar la alerta después de 5 segundos
        setTimeout(function() {
            $('.alert-dismissible').alert('close');
        }, 5000);
    }
    
    // Event listener para el formulario de agregar producto
    $('#productForm').on('submit', function(e) {
        e.preventDefault();
        
        // Validar formulario
        const code = $('#productCode').val().trim();
        const description = $('#productDescription').val().trim();
        const brand = $('#productBrand').val().trim();
        const position = $('#productPosition').val().trim();
        const quantity = $('#productQuantity').val();
        
        if (!code || !description || !brand || !position || !quantity) {
            showAlert('Por favor, complete todos los campos', 'danger');
            return;
        }
        
        if (quantity < 0) {
            showAlert('La cantidad no puede ser negativa', 'danger');
            return;
        }
        
        const productData = {
            code: code,
            description: description,
            brand: brand,
            position: position,
            quantity: parseInt(quantity)
        };
        
        addProduct(productData);
        
        // Resetear formulario
        $(this).trigger('reset');
    });
    
    // Event listener para guardar cambios en edición
    $('#saveEditProduct').on('click', function() {
        // Validar formulario de edición
        const id = $('#editProductId').val();
        const code = $('#editProductCode').val().trim();
        const description = $('#editProductDescription').val().trim();
        const brand = $('#editProductBrand').val().trim();
        const position = $('#editProductPosition').val().trim();
        const quantity = $('#editProductQuantity').val();
        
        if (!code || !description || !brand || !position || !quantity) {
            showAlert('Por favor, complete todos los campos', 'danger');
            return;
        }
        
        if (quantity < 0) {
            showAlert('La cantidad no puede ser negativa', 'danger');
            return;
        }
        
        const productData = {
            id: id,
            code: code,
            description: description,
            brand: brand,
            position: position,
            quantity: parseInt(quantity)
        };
        
        updateProduct(productData);
    });
    
    // Cargar productos al iniciar la página
    loadProducts();
});