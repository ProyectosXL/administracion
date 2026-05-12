-- Agrega la columna ID_PADRE a RO_T_IMPORTACIONES_ENCABEZADO
-- para implementar el patrón OC principal + OCs hijas por contenedor compartido.
-- NO migra datos históricos: los registros pre-existentes quedan con ID_PADRE = NULL
-- y funcionan como islas independientes (comportamiento actual sin cambios).

ALTER TABLE RO_T_IMPORTACIONES_ENCABEZADO
    ADD ID_PADRE INT NULL;
GO

-- FK auto-referencial: una OC hija apunta a la OC principal del mismo grupo
ALTER TABLE RO_T_IMPORTACIONES_ENCABEZADO
    ADD CONSTRAINT FK_RO_T_IMP_ENC_PADRE
    FOREIGN KEY (ID_PADRE) REFERENCES RO_T_IMPORTACIONES_ENCABEZADO(ID);
GO

-- Índice filtrado para acelerar las búsquedas de hijas (obtenerIdsDelGrupo, etc.)
CREATE INDEX IX_RO_T_IMP_ENC_ID_PADRE
    ON RO_T_IMPORTACIONES_ENCABEZADO(ID_PADRE)
    WHERE ID_PADRE IS NOT NULL;
GO
