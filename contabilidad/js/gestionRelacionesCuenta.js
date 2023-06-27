const traerDescCuenta = (div) => {

    select = div.selectedIndex;
    let desc = div.querySelectorAll("option")[select].getAttribute('attr-desc-cuenta')
    document.querySelector('#descCuenta').textContent = desc;

}

const traerDescRubro = (div) => {

    select = div.selectedIndex;
    let desc = div.querySelectorAll("option")[select].getAttribute('attr-desc-rubro');
    document.querySelector('#rubroContable').textContent = desc;

}

const traerDescProrrateo = (div) => {

    select = div.selectedIndex;
    let desc = div.querySelectorAll("option")[select].getAttribute('attr-desc-prorrateo');
    document.querySelector('#descProrrateo').textContent = desc;

}

const agregar = () => {

    let codCuenta = document.querySelector('#codCuenta').value;
    let sector = document.querySelector('#sector').value;
    let codRubro = document.querySelector('#codRubro').value;
    let codProrrateo = document.querySelector('#codProrrateo').value;

    $.ajax({
        url: "Controller/gestionRelacionesController.php?accion=insert",
        method: "POST",
        data: {

            codCuenta: codCuenta,
            sector: sector,
            codRubro: codRubro,
            codProrrateo: codProrrateo,

        },
        success: function (data) {

            if(data == 1){

                Swal.fire({
                    icon: 'success',
                    title: 'Registro agregado correctamente',
                    showConfirmButton: true
                }).then((result) => {

                    location.reload();

                });

            }else {

                Swal.fire({
                    icon: 'error',
                    title: 'Ya existe un Registro con el Cod. Cuenta y Sector Seleccionados',
                    showConfirmButton: true
                })


            }
        }
      });
}