<?
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recoleccion de fotos</title>
</head>
<body class="bg-secondary">
    <div class="container">
        <div class="row align-items-start">
            <div class="col-5 input-group mb-3 form-floating">
                <div class="m-3 col-11">
                    <label for="floatingInputGroup1" class="">Id</label>
                    <input class="form-control" type="text" disabled name="" id="idInput">
                </div>
                <div class="m-3 col-11">
                    <label for="floatingInputGroup1" class="">Codigo</label>
                    <input class="form-control" type="text" name="" id="codigoInput">
                </div>
                <div class="m-3 col-11">
                    <label for="floatingInputGroup1" class="">Descripción</label>
                    <input class="form-control" type="text" name="" id="descripcionInput">
                </div>
                <div class="m-3 col-11">
                    <label for="floatingInputGroup1" class="">Marca</label>
                    <input class="form-control" type="text" name="" id="marcaInput">
                </div>
                <div class="m-3 col-11">
                    <label for="floatingInputGroup1" class="">Posición</label>
                    <input class="form-control" type="text"  name="" id="posicionInput">
                </div>
                <div class="m-3 col-11">
                    <label for="floatingInputGroup1" class="">Cantidad</label>
                    <input class="form-control" type="text"  name="" id="cantidadInput">
                </div>
                <div class="m-3 col-11">
                    <label for="floatingInputGroup1" class="pesoInput">Peso</label>
                    <input class="form-control" type="text" name="" id="pesoInput">
                </div>
                <div class="m-3 col-11 form-check">
                    <input class="form-check-input" type="checkbox" name="" id="checkProducto">
                    <label for="floatingInputGroup1" class="form-check-label">¿El producto esta chequeado?</label>
                </div>
                <div class="m-3 col-11">
                    <label for="floatingInputGroup1" class="">Subir Imagenes</label>
                    <input type="file" id="subirImagenesIP" name="subirImagenes[]" multiple accept="image/*">
                </div>
                <div class="m-3 col-11 align-items-center">
                    <input type="button" value="Subir" class="btn btn-primary" id="subirImagenesBTN">
                </div>
            </div>
        </div>
    </div>
</body>

</html>