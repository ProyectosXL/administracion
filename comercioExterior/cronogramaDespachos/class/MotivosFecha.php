<?php
/**
 * Motivos predefinidos para un cambio de fecha en el cronograma.
 *
 * No van a tabla: son una lista corta y estable, y tenerlos aca permite que
 * la validacion server-side y el combo del front consuman exactamente la
 * misma lista. El front la recibe por el endpoint de despachos.
 *
 * RECALCULO_AUTOMATICO no se ofrece en el combo: lo usa el backend para
 * marcar las filas de historial que genera en cascada al recalcular
 * FECHA_DISTRI, y por eso queda fuera de listar().
 */
class MotivosFecha
{
    const RECALCULO_AUTOMATICO = 'RECALCULO_AUTOMATICO';

    private static $motivos = [
        'DEMORA_NAVIERA'          => 'Demora naviera',
        'CONGESTION_PORTUARIA'    => 'Congestion portuaria',
        'REPROGRAMACION_EMBARQUE' => 'Reprogramacion de embarque',
        'DEMORA_ADUANA'           => 'Demora en aduana',
        'CORRECCION_CARGA'        => 'Correccion de carga',
        'OTRO'                    => 'Otro',
    ];

    /**
     * Lista para el combo del front: [['codigo' => ..., 'label' => ...], ...]
     */
    public static function listar()
    {
        $salida = [];
        foreach (self::$motivos as $codigo => $label) {
            $salida[] = ['codigo' => $codigo, 'label' => $label];
        }
        return $salida;
    }

    /**
     * Valida un motivo recibido del front. RECALCULO_AUTOMATICO no es valido
     * como entrada: solo lo escribe el backend.
     */
    public static function esValido($codigo)
    {
        return is_string($codigo) && isset(self::$motivos[$codigo]);
    }

    public static function label($codigo)
    {
        if ($codigo === self::RECALCULO_AUTOMATICO) {
            return 'Recalculo automatico';
        }
        return isset(self::$motivos[$codigo]) ? self::$motivos[$codigo] : $codigo;
    }
}
