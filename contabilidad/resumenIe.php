<div class="modal fade bd-example-modal-lg" id="modalResumen" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h4 class="modal-title" id="exampleModalLabel"><i class="fa fa-edit" aria-hidden="true" style="font-size: 25px;"></i> Ventas Brutas Por Sucursal</h4>
          </button>
        </div>
        <div class="modal-body">

          <!-- Aca se debe mostrar la tabla que arroja el SP RO_SP_ARTICULOS_SIN_COSTO_NAC -->

          <div class="table-responsive" id="divResumen">
            <table class="table table-hover table-condensed table-striped text-center" id="tablaResumen">
              <thead class="thead-dark" style="font-size: small;">
                <th scope="col" style="width: 6%">PERIODO</th>
                <th scope="col" style="width: 15%">NRO.SUCURSAL</th>
                <th scope="col" style="width: 8%">DESC.SUCURSAL </th>
                <th scope="col" style="width: 8%">COD.RUBRO</th>
                <th scope="col" style="width: 8%">RUBRO.CONTABLE</th>
                <th scope="col" style="width: 8%">IMPORTE</th>
              </thead>

              <tbody id="resumenBody" style="font-size: small;">

              </tbody>
            </table>
          </div>
          <div class="modal-footer">
            <button class="btn btn-success btn_exportar" id="btnExport" > Exportar<i class="bi bi-file-earmark-excel"></i></button>
            <button type="button" class="btn btn-primary" data-dismiss="modal" onclick="marcarControlado()">Confirmar <i class="bi bi-check2-square"></i></button>

          </div>
        </div>
      </div>
    </div>
  </div>
