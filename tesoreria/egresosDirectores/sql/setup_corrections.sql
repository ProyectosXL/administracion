-- ============================================================================
-- SCRIPT SQL SIMPLIFICADO - SOLO LO ESENCIAL
-- ============================================================================

-- VISTA MÍNIMA NECESARIA (el código la referencia, así que es obligatoria)
-- ============================================================================
IF EXISTS (SELECT * FROM sys.views WHERE name = 'vw_solicitudes_completas')
    DROP VIEW vw_solicitudes_completas;
GO

CREATE VIEW vw_solicitudes_completas AS
SELECT 
    s.*,
    d.NOMBRE_DIRECTOR as nombre_director,
    -- Contar archivos adjuntos
    (SELECT COUNT(*) 
     FROM archivos_solicitud a 
     WHERE a.id_solicitud = s.id_solicitud) as cantidad_archivos
FROM solicitudes_egresos s
LEFT JOIN RO_T_DIRECTORES d ON s.id_director = d.ID_DIRECTOR;
GO

PRINT 'Vista vw_solicitudes_completas creada correctamente.';
PRINT 'Script completado - Solo lo esencial instalado.';