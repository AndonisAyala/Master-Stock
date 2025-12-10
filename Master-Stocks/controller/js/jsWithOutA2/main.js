let classColorM;
var a;

function searchProducts(req) {
    console.log("Searching for product with code:", req);
    let html = "";
    var parameters = {
        codProduct: req
    };
    var petition = $.ajax({
        url: "../../controller/php/buscarProducto.php",
        method: "POST",
        data: parameters,
        dataType: "json",
        success: function (response) {
            for (let i = 0; i < response.length; i++) {
                html += '<tr>' +
                        '<td><a href="#" class="codigo-link" data-codigo="' + response[i].id + '">' + response[i].codigo + '</a></td>' +
                        '<td><a href="#" class="descripcion-link" data-codigo="' + response[i].id + '">' + response[i].descripcion + '</a></td>' +
                        '<td>' + response[i].marca + '</td>' +
                        '<td>' + response[i].cantidad + '</td>' +
                        '<td>' + response[i].puesto + '</td>' +
                        '<td>' + response[i].precio1 + '</td>' +
                        '<td>' + response[i].precio2 + '</td>' +
                        '<td>' + response[i].precio3 + '</td>' +
                    '</tr>'
            }
            $("#tablaProductos tbody").html(html);
        },
        error: function (xhr, status, error) {
            console.error("Error en la petición:", error);
            alert("Error al buscar el producto. Por favor, inténtelo de nuevo más tarde.");
        }
    });
}


function showModalForm(codigo) {
    if (!codigo) {
        console.error("Código de producto no proporcionado");
        return;
    }

    $("#modalContent").html(`
        <div class="text-center p-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
            <p>Cargando información del producto...</p>
        </div>
    `);

    var modal = new bootstrap.Modal(document.getElementById('modalOverlay'));
    modal.show();

    $.ajax({
        url: "../../model/php/overlayEditProduct.php",
        method: "POST",
        data: { codProduct: codigo },
        dataType: "html",
        success: function (response) {
            $("#modalContent").html(response);
            $("#idInput").val("response.id");
            $("#codigoInput").val("response.codigo");
            $("#descripcionInput").val("response.descripcion");
            $("#pesoInput").val("response.peso");
            searchProductsId(codigo);
        },
        error: function (xhr, status, error) {
            console.error("Error en AJAX:", status, error);
            $("#modalContent").html(`
                <div class="alert alert-danger m-3">
                    <h5>Error al cargar el formulario</h5>
                    <p>Por favor, inténtelo de nuevo más tarde.</p>
                    <button class="btn btn-sm btn-outline-secondary" onclick="$('#modalOverlay').modal('hide')">
                        Cerrar
                    </button>
                </div>
            `);
        }
    });
}

function showProductImagesModal(codigo) {
    if (!codigo) {
        console.error("Código de producto no proporcionado");
        return;
    }

    $("#modalContent").html(`
        <div class="text-center p-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
            <p>Cargando imágenes del producto...</p>
        </div>
    `);

    var modal = new bootstrap.Modal(document.getElementById('modalOverlay'));
    modal.show();

    $.ajax({
        url: "../../controller/php/buscarImagenProducto.php", // Nuevo archivo PHP que buscará las imágenes
        method: "POST",
        data: { codProduct: codigo },
        dataType: "json",
        success: function (response) {
            if (response.images && response.images.length > 0) {
                let imagesHtml = `<div class="container">
                    <h4 class="mb-3 text-center">Imágenes del producto ${codigo}</h4>
                    <div class="row g-3">`;
                
                response.images.forEach(image => {
                    imagesHtml += `
                    <div class="col-md-4 col-6 m-1">
                        <div class="card h-100">
                            <img src="../../ready/${image}" class="card-img-top img-thumbnail" alt="Imagen del producto">
                            <div class="card-footer bg-transparent border-top-0">
                                <button class="btn btn-sm btn-outline-danger" onclick="deleteImage('${image}')">
                                    <i class="bi bi-trash"></i> Eliminar
                                </button>
                            </div>
                        </div>
                    </div>`;
                });
                
                imagesHtml += `</div></div>`;
                $("#modalContent").html(imagesHtml);
            } else {
                $("#modalContent").html(`
                    <div class="alert alert-info m-3">
                        <h5>No hay imágenes para este producto</h5>
                        <p>No se encontraron imágenes asociadas al producto ${codigo}.</p>
                        <button class="btn btn-sm btn-outline-secondary" onclick="$('#modalOverlay').modal('hide')">
                            Cerrar
                        </button>
                    </div>
                `);
            }
        },
        error: function (xhr, status, error) {
            console.error("Error en AJAX:", status, error);
            $("#modalContent").html(`
                <div class="alert alert-danger m-3">
                    <h5>Error al cargar las imágenes</h5>
                    <p>Por favor, inténtelo de nuevo más tarde.</p>
                    <button class="btn btn-sm btn-outline-secondary" onclick="$('#modalOverlay').modal('hide')">
                        Cerrar
                    </button>
                </div>
            `);
        }
    });
}

function deleteImage(imageName) {
    if (!confirm("¿Estás seguro de eliminar esta imagen?")) return;

    // Mostrar spinner de carga
    $('#modalContent').html(`
        <div class="text-center p-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Eliminando...</span>
            </div>
            <p>Eliminando imagen...</p>
        </div>
    `);

    $.ajax({
        url: "../../controller/php/borraImagenProducto.php",
        method: "POST",
        data: { imageName: imageName },
        dataType: "json",
        success: (response) => {
            const codigo = imageName.split('_')[0];
            
            // 1. Cerrar el modal actual correctamente
            const modal = bootstrap.Modal.getInstance(document.getElementById('modalOverlay'));
            modal.hide();

            // 2. Limpiar completamente el backdrop
            const cleanModalArtifacts = () => {
                $('.modal-backdrop').remove();
                $('body').removeClass('modal-open');
                $('body').css('padding-right', '');
                $('#modalOverlay').off('hidden.bs.modal');
            };

            // 3. Esperar a que termine la animación de cierre
            setTimeout(() => {
                cleanModalArtifacts();
                
                // 4. Volver a abrir solo si la eliminación fue exitosa
                if (response.success) {
                    setTimeout(() => {
                        showProductImagesModal(codigo);
                    }, 50); // Pequeño delay para asegurar la limpieza
                }
            }, 300); // Tiempo de animación de Bootstrap
        },
        error: (error) => {
            console.error("Error:", error);
            $('#modalContent').html(`
                <div class="alert alert-danger">
                    Error al eliminar la imagen
                    <button onclick="showProductImagesModal('${imageName.split('_')[0]}')" 
                            class="btn btn-sm btn-secondary">
                        Reintentar
                    </button>
                </div>
            `);
        }
    });
}

function searchProductsId(req) {
    var parameters = {
        codProduct: req
    };
    var petition = $.ajax({
        url: "../../controller/php/buscarProductoId.php",
        method: "POST",
        data: parameters,
        dataType: "json",
        success: function (response) {
            $("#idInput").val(response[0].id);
            $("#codigoInput").val(response[0].codigo);
            $("#descripcionInput").val(response[0].descripcion);
            $("#pesoInput").val(response[0].peso);
        },
        error: function (xhr, status, error) {
            console.error("Error en la petición:", error);
            alert("Error al buscar el producto. Por favor, inténtelo de nuevo más tarde.");
        }
    });
}

function setupModalCloseTriggers(modalInstance) {
    $('#modalOverlay').off('click').on('click', function (e) {
        if ($(e.target).hasClass('modal')) {
            modalInstance.hide();
        }
    });
    $(document).off('fotosSubidas').on('fotosSubidas', function () {
        modalInstance.hide();
    });
    $('[data-dismiss="modal"]').off('click').on('click', function () {
        modalInstance.hide();
    });
}

function uploadImages(codigo, inputElement) {
    // Obtener elementos usando jQuery
    var $inputImagenes = $("#subirImagenesIP");
    var codigo = $("#idInput").val();
    var peso = $("#pesoInput").val(); // Obtener el valor del peso
    
    // Validaciones
    if (!$inputImagenes.length || $inputImagenes[0].files.length === 0) {
        alert('Por favor selecciona al menos una imagen');
        return;
    }

    if (!codigo) {
        alert("Código de producto no proporcionado");
        return;
    }

    // Preparar FormData
    var formData = new FormData();
    formData.append("codProduct", codigo);
    
    // Agregar el peso si existe
    if (peso) {
        formData.append("peso", peso);
    }
    
    // Agregar archivos
    $.each($inputImagenes[0].files, function(i, file) {
        formData.append("subirImagenes[]", file);
    });

    // Mostrar loader (opcional)
    $("#modalContent").html(`
        <div class="text-center p-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Subiendo imágenes...</span>
            </div>
            <p>Subiendo imágenes, por favor espere...</p>
        </div>
    `);

    // Petición AJAX
    $.ajax({
        url: "../../controller/php/subirImagenes.php",
        method: "POST",
        data: formData,
        processData: false,
        contentType: false,
        dataType: "json",
        success: function(response) {
            console.log("Respuesta del servidor:", response);
            if (response.success) {
                // Cerrar el modal y limpiar
                $('#modalOverlay').modal('hide');
                $("#modalContent").empty();
                
                // Mostrar mensaje de éxito
                alert(response.message || "Imágenes subidas correctamente");
                
                // Disparar evento personalizado si es necesario
                $(document).trigger('fotosSubidas');

            } else {
                alert("Error: " + (response.message || "Error desconocido al subir imágenes"));
            }
        },
        error: function(xhr, status, error) {
            console.error("Error en la petición:", error);
            alert("Error de conexión al servidor. Por favor, inténtelo de nuevo.");
            
            // Restaurar el formulario en caso de error
            showModalForm(codigo);
        }
    });
}

function checkNumber(box) {
    $("input." + box).keyup(function (event) {
        if (
            (event.which != 8 && event.which != 0 && event.which < 48) ||
            event.which > 57
        ) {
            $(this).val(function (index, value) {
                return value.replace(/\D/g, "");
            });
        }
    });
}

$(document).ready(function () {
    $("#searchProductBTN").click(function () {
        a = $("#searchProductIP").val();
        searchProducts($("#searchProductIP").val());
    });

    $("#searchProductIP").on("keypress", function (e) {
        if (e.which === 13) {
            e.preventDefault();
            a = $(this).val();
            console.log(a);
            searchProducts(a);
        }
    });

    $(document).on("click", ".codigo-link", function (e) {
        console.log("Link clicked:", $(this).data("codigo"));
        e.preventDefault();
        var codigo = $(this).data("codigo");
        showModalForm(codigo);
    });

    $(document).on('click', '.descripcion-link', function(e) {
        e.preventDefault();
        var codigo = $(this).data('codigo');
        showProductImagesModal(codigo);
    });

    $("#modalOverlay").on("hidden.bs.modal", function () {
        $("#modalContent").empty();
    });

    $(document).on("click", "#subirImagenesBTN", function () {
        uploadImages($("#idInput").val(), document.getElementById("subirImagenesIP"));
    });

    checkNumber("pesoInput");
});