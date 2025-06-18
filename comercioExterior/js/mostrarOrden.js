
const verDetalle = (id,prov,orden,codProv,valorFobPeso)=>{
    window.location = "editarOrden.php?idEncabezado="+id+"&proveedor="+prov+"&ordenDeCompra="+orden+"&codProveedor="+codProv+"&valorFobPeso="+valorFobPeso;
}
const imprimir=(id)=>{
    window.location = "imprimir.php?idEncabezado="+id
}
$("#btnExport").click(function() {

        $("#tableDinamic").table2excel({
            // exclude CSS class
            exclude: ".noExport",
            name: "excel Document ",
            filename: "Excel", //do not include extension
            fileext: ".xlsx" // file extension
        });
    })
    

//Búsqueda rápida table//

$('#tableDinamic').DataTable({
        "bLengthChange": true,
        "language": {
                    "lengthMenu": "mostrar _MENU_ registros",
                    "info":           "Mostrando registros del _START_ al _END_ de un total de  _TOTAL_ registros",
                    "paginate": {
                        "next":       "Siguiente",
                        "previous":   "Anterior"
                    },

        },
    
        
        "bInfo": true,
        "aaSorting": false,
        'columnDefs': [
            {
                "targets": "_all", 
                "className": "text-center",
                "sortable": false,
         
            },
        ],
        "oLanguage": {
    
            "sSearch": "Busqueda rapida:",
            "sSearchPlaceholder" : "Sobre cualquier campo"
            
    
        },
    });
