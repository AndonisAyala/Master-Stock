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
                        '<td>' + response[i].codigo + '</td>' +
                        '<td><a href="#" class="descripcion-link" data-codigo="' + response[i].id + '">' + response[i].descripcion + '</a></td>' +
                        '<td>' + response[i].marca + '</td>' +
                        '<td>' + response[i].autos + '</td>'
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

});