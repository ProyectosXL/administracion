<?php
/**
 * Parámetros. La sirve Controller/TabController.php, que ya verificó
 * ra.tab.parametros.
 *
 * La grilla la arma Js/parametros.js desde la tabla, así que un parámetro
 * nuevo aparece solo. El botón de editar lo dibuja el JS según
 * data-puede-editar; lo que impide guardar sin permiso es el 403 del servidor.
 */
$puedeEditar = AuthReportes::puedeEditar('parametros');
?>
<section class="ra-pestana" id="raParametros" data-tab="parametros"<?php echo AuthReportes::atributoEdicion('parametros'); ?>>
    <div class="ra-card">
        <div class="ra-card-titulo"><i class="bi bi-sliders"></i> Parámetros de los reportes</div>

        <?php if (!$puedeEditar): ?>
            <div class="alert alert-light border small py-2 mb-3">
                <i class="bi bi-eye"></i> Podés consultar los parámetros, pero tu rol no tiene permiso para
                modificarlos (<code><?php echo htmlspecialchars(AuthReportes::claveEdicion('parametros')); ?></code>).
            </div>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table ra-tabla align-middle mb-0" id="raTablaParametros">
                <thead>
                    <tr>
                        <th>Parámetro</th>
                        <th class="num">Valor vigente</th>
                        <th>Última modificación</th>
                        <th class="text-end"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td colspan="4" class="ra-cargando">Cargando…</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</section>
