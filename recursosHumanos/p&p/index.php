
<?php
// Iniciar sesión para gestión de usuarios (si se implementa)
session_start();

// Incluir la clase Politica
require_once 'Class/Politica.php';

// Crear instancia de la clase
$politicaObj = new Politica();

// Obtener datos para la página principal
$sectores = $politicaObj->obtenerSectores();

// Obtener estadísticas
$estadisticas = $politicaObj->obtenerEstadisticas();

// Filtros para documentos recientes
$filtros = [];
if (isset($_GET['sector']) && !empty($_GET['sector'])) {
    $filtros['sector_id'] = $_GET['sector'];
}
if (isset($_GET['tipo']) && !empty($_GET['tipo'])) {
    $filtros['tipo'] = $_GET['tipo'];
}
if (isset($_GET['busqueda']) && !empty($_GET['busqueda'])) {
    $filtros['busqueda'] = $_GET['busqueda'];
}

// Obtener documentos recientes
$documentos_recientes = $politicaObj->obtenerTodos($filtros);

// Obtener términos del glosario
$glosario = $politicaObj->obtenerGlosario();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Gestión de Políticas y Procedimientos</title>
    <link rel="icon" type="image/jpg" href="../../image/icono.jpg">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="css/chatbot.css">
    <!-- PDF.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.4.120/pdf.min.js"></script>
    <script>
        // Configurar el worker de PDF.js
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.4.120/pdf.worker.min.js';
    </script>
    
    <!-- Auto-inicio del servicio RAG y auto-indexación -->
    <script>
        // Ejecutar auto-inicio en segundo plano cuando cargue la página
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🚀 Iniciando sistema RAG automáticamente...');
            
            fetch('Controller/autostart_rag.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        console.log('✅ Sistema RAG iniciado:', data.mensaje);
                        console.log('📊 Detalles:', data.detalles);
                    } else {
                        console.warn('⚠️ Sistema RAG:', data.mensaje);
                        console.log('📊 Detalles:', data.detalles);
                    }
                })
                .catch(error => {
                    console.error('❌ Error al iniciar sistema RAG:', error);
                });
        });
    </script>
    
</head>
<body>
    <div class="container">
        <aside class="sidebar">
            <div class="logo">
                <h1>DocuGest</h1>
                <button class="toggle-btn" id="toggleSidebar">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
            
            <ul class="sidebar-menu">
                <li>
                    <a href="#" class="active" onclick="showTab('overview')">
                        <i class="fas fa-home"></i>
                        <span>Inicio</span>
                    </a>
                </li>
                <li>
                    <a href="#" onclick="showTab('sectors')">
                        <i class="fas fa-building"></i>
                        <span>Sectores</span>
                    </a>
                </li>
                <li>
                    <a href="#" onclick="showTab('policies')">
                        <i class="fas fa-file-alt"></i>
                        <span>Políticas</span>
                    </a>
                </li>
                <li>
                    <a href="#" onclick="showTab('procedures')">
                        <i class="fas fa-tasks"></i>
                        <span>Procedimientos</span>
                    </a>
                </li>
                <li>
                    <a href="#" onclick="showGlossary()">
                        <i class="fas fa-book"></i>
                        <span>Glosario</span>
                    </a>
                </li>
                <li>
                    <a href="#" onclick="showTab('statistics')">
                        <i class="fas fa-chart-pie"></i>
                        <span>Estadísticas</span>
                    </a>
                </li>
                <li>
                    <a href="#" onclick="toggleChatbot()" id="chatbotMenuBtn">
                        <i class="fas fa-robot"></i>
                        <span>Asistente IA</span>
                    </a>
                </li>
                <li>
                    <a href="#" onclick="showTab('upload')">
                        <i class="fas fa-upload"></i>
                        <span>Subir Documento</span>
                    </a>
                </li>
                <li>
                    <a href="#" onclick="showTab('update')">
                        <i class="fas fa-sync-alt"></i>
                        <span>Actualizar Documento</span>
                    </a>
                </li>
                <li>
                    <a href="#" onclick="showTab('settings')">
                        <i class="fas fa-cog"></i>
                        <span>Configuración</span>
                    </a>
                </li>
            </ul>
        </aside>
        
        <main class="main-content">
            <header class="header">
                <div class="search-container">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" class="search-input" id="searchInput" placeholder="Buscar políticas, procedimientos o términos...">
                </div>
                
                <div class="user-menu">
                    <div class="notifications">
                        <i class="fas fa-bell"></i>
                        <span class="badge">3</span>
                    </div>
                    
                    <div class="user-profile">
                        <div class="avatar">
                            <span>AB</span>
                        </div>
                        <div class="user-info">
                            <h4>Admin</h4>
                            <p>Administrador</p>
                        </div>
                    </div>
                </div>
            </header>
            
            <div class="section-header">
                <h2>Panel de Control</h2>
                <p>Bienvenido al sistema de gestión de políticas y procedimientos</p>
            </div>
            
            <!-- Estadísticas generales -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="icon blue">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo isset($estadisticas['total_politicas']) ? $estadisticas['total_politicas'] : 0; ?></h3>
                        <p>Políticas Totales</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="icon red">
                        <i class="fas fa-tasks"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo isset($estadisticas['total_procedimientos']) ? $estadisticas['total_procedimientos'] : 0; ?></h3>
                        <p>Procedimientos</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="icon green">
                        <i class="fas fa-building"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo count($sectores); ?></h3>
                        <p>Sectores</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="icon orange">
                        <i class="fas fa-book"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo count($glosario); ?></h3>
                        <p>Términos en Glosario</p>
                    </div>
                </div>
            </div>
            
            <!-- Tabs de navegación -->
            <div class="tabs">
                <div class="tab active" onclick="showTab('overview')">Vista General</div>
                <div class="tab" onclick="showTab('sectors')">Sectores</div>
                <div class="tab" onclick="showTab('policies')">Políticas</div>
                <div class="tab" onclick="showTab('procedures')">Procedimientos</div>
            </div>
            
            <!-- Contenido principal - Vista general -->
            <div id="overview-content" class="tab-content">
                <div class="content-grid">
                    <div class="left-column">
                        <div class="card">
                            <div class="card-header">
                                <h3>Documentos Recientes</h3>
                                <a href="#" class="see-all" onclick="showTab('all-documents')">Ver todos <i class="fas fa-chevron-right"></i></a>
                            </div>
                            
                            <ul class="policy-list">
                                <?php if (empty($documentos_recientes)): ?>
                                <li class="no-documents">
                                    <p>No hay documentos disponibles.</p>
                                </li>
                                <?php else: ?>
                                    <?php foreach (array_slice($documentos_recientes, 0, 5) as $doc): ?>
                                    <li class="policy-item">
                                        <div class="policy-info">
                                            <div class="policy-icon">
                                                <i class="fas fa-file-pdf"></i>
                                            </div>
                                            <div class="policy-details">
                                                <h4><?php echo htmlspecialchars($doc['titulo']); ?></h4>
                                                <p>Sector <?php echo htmlspecialchars($doc['sector_nombre']); ?> - 
                                                Actualizado: <?php echo isset($doc['fecha_actualizacion']) ? date('d/m/Y', is_string($doc['fecha_actualizacion']) ? strtotime($doc['fecha_actualizacion']) : strtotime($doc['fecha_actualizacion']->format('Y-m-d'))) : 'Fecha no disponible'; ?></p>

                                            </div>
                                        </div>
                                        <div class="policy-actions">
                                            <button class="action-btn view" onclick="viewDocument(<?php echo $doc['id']; ?>)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="action-btn download" onclick="downloadDocument(<?php echo $doc['id']; ?>)">
                                                <i class="fas fa-download"></i>
                                            </button>
                                            <button class="action-btn star">
                                                <i class="far fa-star"></i>
                                            </button>
                                        </div>
                                    </li>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="right-column">
                        <div class="card">
                            <div class="card-header">
                                <h3>Términos Populares</h3>
                                <a href="#" class="see-all" onclick="showGlossary()">Ver glosario <i class="fas fa-chevron-right"></i></a>
                            </div>
                            
                            <ul class="glossary-list">
                                <?php if (empty($glosario)): ?>
                                <li class="no-glossary">
                                    <p>No hay términos en el glosario.</p>
                                </li>
                                <?php else: ?>
                                    <?php foreach (array_slice($glosario, 0, 4) as $term): ?>
                                    <li class="glossary-item">
                                        <h4><i class="fas fa-info-circle"></i> <?php echo htmlspecialchars($term['termino']); ?></h4>
                                        <p><?php echo htmlspecialchars($term['definicion']); ?></p>
                                    </li>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Vista de Sectores -->
            <div id="sectors-content" class="tab-content" style="display: none;">
                <div class="section-header">
                    <h2>Sectores</h2>
                    <p>Navegue por los diferentes sectores de la organización</p>
                </div>
                
                <div class="sectors-grid">
                    <?php if (empty($sectores)): ?>
                    <div class="no-sectors">
                        <p>No hay sectores disponibles.</p>
                    </div>
                    <?php else: ?>
                        <?php foreach ($sectores as $sector): ?>
                        <div class="sector-card" onclick="filterBySection(<?php echo $sector['id']; ?>)">
                            <div class="icon">
                                <i class="fas <?php echo htmlspecialchars($sector['icono'] ? $sector['icono'] : 'fa-folder'); ?>"></i>
                            </div>
                            <h3><?php echo htmlspecialchars($sector['nombre']); ?></h3>
                            <p><?php echo htmlspecialchars($sector['descripcion']); ?></p>
                            
                            <?php
                            // Contar documentos por sector
                            $count = 0;
                            foreach ($documentos_recientes as $doc) {
                                if ($doc['sector_id'] == $sector['id']) {
                                    $count++;
                                }
                            }
                            ?>
                            <span class="count"><?php echo $count; ?> documentos</span>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Vista de Políticas -->
            <div id="policies-content" class="tab-content" style="display: none;">
                <div class="section-header">
                    <h2>Políticas</h2>
                    <p>Todas las políticas de la organización</p>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h3>Políticas Corporativas</h3>
                        <div class="filter-options">
                            <select class="filter-select" id="sectorFilterPolicies" onchange="filterPolicies()">
                                <option value="">Todos los sectores</option>
                                <?php foreach ($sectores as $sector): ?>
                                <option value="<?php echo $sector['id']; ?>"><?php echo htmlspecialchars($sector['nombre']); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button class="upload-btn" onclick="showTab('upload')">
                                <i class="fas fa-upload"></i> Subir Política
                            </button>
                        </div>
                    </div>
                    
                    <ul class="policy-list" id="policiesList">
                        <?php
                        $policies = array_filter($documentos_recientes, function($doc) {
                            return $doc['tipo'] == 'politica';
                        });
                        
                        if (empty($policies)): ?>
                        <li class="no-documents">
                            <p>No hay políticas disponibles.</p>
                        </li>
                        <?php else: ?>
                            <?php foreach ($policies as $policy): ?>
                            <li class="policy-item">
                                <div class="policy-info">
                                    <div class="policy-icon">
                                        <i class="fas fa-file-pdf"></i>
                                    </div>
                                    <div class="policy-details">
                                        <h4><?php echo htmlspecialchars($policy['titulo']); ?></h4>
                                        <p>Sector <?php echo htmlspecialchars($policy['sector_nombre']); ?> - 
                                           Actualizado: <?php echo date('d/m/Y', strtotime($policy['fecha_actualizacion'])); ?></p>
                                    </div>
                                </div>
                                <div class="policy-actions">
                                    <button class="action-btn view" onclick="viewDocument(<?php echo $policy['id']; ?>)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="action-btn download" onclick="downloadDocument(<?php echo $policy['id']; ?>)">
                                        <i class="fas fa-download"></i>
                                    </button>
                                    <button class="action-btn star">
                                        <i class="far fa-star"></i>
                                    </button>
                                </div>
                            </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
            
            <!-- Vista de Procedimientos -->
            <div id="procedures-content" class="tab-content" style="display: none;">
                <div class="section-header">
                    <h2>Procedimientos</h2>
                    <p>Todos los procedimientos documentados</p>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h3>Procedimientos Operativos</h3>
                        <div class="filter-options">
                            <select class="filter-select" id="sectorFilterProcedures" onchange="filterProcedures()">
                                <option value="">Todos los sectores</option>
                                <?php foreach ($sectores as $sector): ?>
                                <option value="<?php echo $sector['id']; ?>"><?php echo htmlspecialchars($sector['nombre']); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button class="upload-btn" onclick="showTab('upload')">
                                <i class="fas fa-upload"></i> Subir Procedimiento
                            </button>
                        </div>
                    </div>
                    
                    <ul class="policy-list" id="proceduresList">
                        <?php
                        $procedures = array_filter($documentos_recientes, function($doc) {
                            return $doc['tipo'] == 'procedimiento';
                        });
                        
                        if (empty($procedures)): ?>
                        <li class="no-documents">
                            <p>No hay procedimientos disponibles.</p>
                        </li>
                        <?php else: ?>
                            <?php foreach ($procedures as $procedure): ?>
                            <li class="policy-item">
                                <div class="policy-info">
                                    <div class="policy-icon">
                                        <i class="fas fa-file-pdf"></i>
                                    </div>
                                    <div class="policy-details">
                                        <h4><?php echo htmlspecialchars($procedure['titulo']); ?></h4>
                                        <p>Sector <?php echo htmlspecialchars($procedure['sector_nombre']); ?> - 
                                        Actualizado: <?php 
                                                        echo isset($procedure['fecha_actualizacion']) 
                                                            ? date('d/m/Y', is_string($procedure['fecha_actualizacion']) 
                                                                ? strtotime($procedure['fecha_actualizacion']) 
                                                                : strtotime($procedure['fecha_actualizacion']->format('Y-m-d'))) 
                                                            : 'Fecha no disponible'; 
                                                        ?>
                                                        </p>
                                    </div>
                                </div>
                                <div class="policy-actions">
                                    <button class="action-btn view" onclick="viewDocument(<?php echo $procedure['id']; ?>)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="action-btn download" onclick="downloadDocument(<?php echo $procedure['id']; ?>)">
                                        <i class="fas fa-download"></i>
                                    </button>
                                    <button class="action-btn star">
                                        <i class="far fa-star"></i>
                                    </button>
                                </div>
                            </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
            
            <!-- Vista de Estadísticas -->
            <div id="statistics-content" class="tab-content" style="display: none;">
                <div class="section-header">
                    <h2>Estadísticas</h2>
                    <p>Análisis de uso y actividad del sistema</p>
                </div>
                
                <div class="stats-container">
                    <div class="card">
                        <div class="card-header">
                            <h3>Documentos más vistos</h3>
                        </div>
                        <ul class="stats-list">
                            <?php if (isset($estadisticas['mas_vistos']) && !empty($estadisticas['mas_vistos'])): ?>
                                <?php foreach ($estadisticas['mas_vistos'] as $doc): ?>
                                <li class="stats-item">
                                    <div class="stats-info">
                                        <h4><?php echo htmlspecialchars($doc['titulo']); ?></h4>
                                        <div class="stats-bar">
                                            <div class="stats-progress" style="width: <?php echo min(100, ($doc['vistas'] / 100) * 100); ?>%"></div>
                                        </div>
                                    </div>
                                    <div class="stats-count"><?php echo $doc['vistas']; ?> vistas</div>
                                </li>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <li class="no-stats">
                                    <p>No hay datos disponibles.</p>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h3>Documentos más descargados</h3>
                        </div>
                        <ul class="stats-list">
                            <?php if (isset($estadisticas['mas_descargados']) && !empty($estadisticas['mas_descargados'])): ?>
                                <?php foreach ($estadisticas['mas_descargados'] as $doc): ?>
                                <li class="stats-item">
                                    <div class="stats-info">
                                        <h4><?php echo htmlspecialchars($doc['titulo']); ?></h4>
                                        <div class="stats-bar">
                                            <div class="stats-progress" style="width: <?php echo min(100, ($doc['descargas'] / 50) * 100); ?>%"></div>
                                        </div>
                                    </div>
                                    <div class="stats-count"><?php echo $doc['descargas']; ?> descargas</div>
                                </li>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <li class="no-stats">
                                    <p>No hay datos disponibles.</p>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Vista de Carga de Documentos -->
            <div id="upload-content" class="tab-content" style="display: none;">
                <div class="section-header">
                    <h2>Subir Documento</h2>
                    <p>Añadir nuevo documento al sistema de políticas y procedimientos</p>
                </div>
                
                <div class="upload-container">
                    <!-- Información sobre el proceso -->
                    <div class="upload-info">
                        <h4><i class="fas fa-info-circle"></i> Información sobre la carga de documentos</h4>
                        <ul>
                            <li>Sólo se permiten archivos en formato PDF.</li>
                            <li>El tamaño máximo permitido es de 10 MB.</li>
                            <li>Procure usar nombres descriptivos para facilitar la búsqueda.</li>
                            <li>Las etiquetas ayudan a categorizar y encontrar más fácilmente el documento.</li>
                        </ul>
                    </div>
                    
                    <!-- Formulario de carga -->
                    <div class="upload-form">
                        <form id="uploadForm" action="Controller/procesar_documento.php" method="post" enctype="multipart/form-data">
                            <div class="form-group">
                                <label for="docTitle">Título del documento <span class="required">*</span></label>
                                <input type="text" id="docTitle" name="titulo" required placeholder="Ingrese un título descriptivo">
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="docType">Tipo de documento <span class="required">*</span></label>
                                    <select id="docType" name="tipo" required>
                                        <option value="" disabled selected>Seleccione un tipo</option>
                                        <option value="politica">Política</option>
                                        <option value="procedimiento">Procedimiento</option>
                                    </select>
                                </div>
                                
                                <div class="form-group col-md-6">
                                    <label for="docSector">Sector <span class="required">*</span></label>
                                    <select id="docSector" name="sector_id" required>
                                        <option value="" disabled selected>Seleccione un sector</option>
                                        <?php foreach ($sectores as $sector): ?>
                                        <option value="<?php echo $sector['id']; ?>"><?php echo htmlspecialchars($sector['nombre']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="docDescription">Descripción</label>
                                <textarea id="docDescription" name="descripcion" rows="4" placeholder="Describa brevemente el contenido del documento"></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="docTags">Etiquetas (separadas por comas)</label>
                                <input type="text" id="docTags" name="tags" placeholder="ej. seguridad, finanzas, auditoría">
                                
                                <div class="tags-container" id="tagsContainer">
                                    <!-- Las etiquetas se mostrarán aquí dinámicamente -->
                                </div>
                                
                                <div class="tag-suggestions">
                                    <span onclick="addTag('importante')">importante</span>
                                    <span onclick="addTag('nuevo')">nuevo</span>
                                    <span onclick="addTag('urgente')">urgente</span>
                                    <span onclick="addTag('actualizado')">actualizado</span>
                                    <span onclick="addTag('confidencial')">confidencial</span>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label>Archivo PDF <span class="required">*</span></label>
                                <div class="file-upload-container">
                                    <div class="file-upload-button" id="fileUploadBtn">
                                        <i class="fas fa-cloud-upload-alt"></i>
                                        <div class="main-text">Arrastre y suelte su archivo aquí</div>
                                        <p>o haga clic para seleccionar un archivo</p>
                                        <p class="file-types">Solo archivos PDF (máx. 10 MB)</p>
                                    </div>
                                    <input type="file" id="docFile" name="archivo" accept=".pdf" required class="file-upload-input">
                                    
                                    <div class="file-info" id="fileInfo">
                                        <i class="fas fa-file-pdf"></i>
                                        <span class="file-name" id="fileName">nombre_del_archivo.pdf</span>
                                        <span class="file-size" id="fileSize">0 KB</span>
                                        <i class="fas fa-times remove-file" onclick="removeFile()"></i>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-actions">
                                <button type="button" class="btn secondary" onclick="showTab('overview')">Cancelar</button>
                                <button type="submit" class="btn primary">
                                    <i class="fas fa-upload"></i> Subir Documento
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Vista de Actualización de Documentos -->
<div id="update-content" class="tab-content" style="display: none;">
    <div class="section-header">
        <h2>Actualizar Documento</h2>
        <p>Cargar una nueva versión de un documento existente</p>
    </div>
    
    <div class="upload-container">
        <!-- Información sobre el proceso -->
        <div class="upload-info">
            <h4><i class="fas fa-info-circle"></i> Información sobre la actualización de documentos</h4>
            <ul>
                <li>Seleccione el documento que desea actualizar de la lista.</li>
                <li>Sólo podrá modificar el archivo y opcionalmente la descripción y etiquetas.</li>
                <li>La nueva versión reemplazará a la anterior pero se mantendrá un historial.</li>
                <li>Sólo se permiten archivos en formato PDF.</li>
            </ul>
        </div>
        
        <!-- Formulario de selección de documento -->
        <div class="upload-form">
            <div class="upload-form-title">
                <h3>Paso 1: Seleccione el documento a actualizar</h3>
            </div>
            
            <div class="form-group">
                <label for="documentSelect">Documento:</label>
                <select id="documentSelect" name="documento_id" required onchange="loadDocumentDetails(this.value)">
                    <option value="" disabled selected>Seleccione un documento</option>
                    <?php
                    // Obtener todos los documentos para mostrarlos en el selector
                    $todos_documentos = $politicaObj->obtenerTodos();
                    foreach ($todos_documentos as $doc) {
                        echo '<option value="'.$doc['id'].'">' . htmlspecialchars($doc['titulo']) . ' ('. htmlspecialchars($doc['sector_nombre']) .')</option>';
                    }
                    ?>
                </select>
            </div>
            
            <!-- Área donde se mostrarán los detalles del documento seleccionado -->
            <div id="documentDetails" style="display: none;" class="document-details">
                <div class="document-info-card">
                    <h4>Información del documento</h4>
                    <div class="info-row">
                        <span class="label">Título:</span>
                        <span id="docDetailTitle" class="value"></span>
                    </div>
                    <div class="info-row">
                        <span class="label">Tipo:</span>
                        <span id="docDetailType" class="value"></span>
                    </div>
                    <div class="info-row">
                        <span class="label">Sector:</span>
                        <span id="docDetailSector" class="value"></span>
                    </div>
                    <div class="info-row">
                        <span class="label">Versión actual:</span>
                        <span id="docDetailVersion" class="value"></span>
                        <button type="button" class="btn-link" onclick="showVersionHistory(document.getElementById('updateDocId').value)">
                            <i class="fas fa-history"></i> Ver historial
                        </button>
                    </div>
                    <div class="info-row">
                        <span class="label">Última actualización:</span>
                        <span id="docDetailDate" class="value"></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal para historial de versiones -->
        <div id="versionHistoryModal" class="modal">
            <div class="modal-content history-modal">
                <span class="close-modal" onclick="closeVersionHistory()">&times;</span>
                <h3>Historial de Versiones</h3>
                <div id="versionHistoryContent">
                    <p>Cargando historial...</p>
                </div>
            </div>
        </div>
        
                    <!-- Formulario de actualización -->
                    <div id="updateForm" class="upload-form" style="display: none;">
                        <div class="upload-form-title">
                            <h3>Paso 2: Actualizar documento</h3>
                        </div>
                        
                        <form id="documentUpdateForm" action="Controller/actualizar_documento.php" method="post" enctype="multipart/form-data">
                            <input type="hidden" id="updateDocId" name="documento_id" value="">
                            
                            <div class="form-group">
                                <label for="updateVersion">Nueva versión:</label>
                                <input type="text" id="updateVersion" name="version" placeholder="Ej: 2.0" required>
                                <span class="field-info">La versión actual se mostrará como referencia</span>
                            </div>
                            
                            <div class="form-group">
                                <label for="updateDesc">Descripción (opcional):</label>
                                <textarea id="updateDesc" name="descripcion" rows="3" placeholder="Describa los cambios realizados en esta versión"></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="updateTags">Etiquetas (opcional):</label>
                                <input type="text" id="updateTags" name="tags" placeholder="Separadas por comas">
                                <div class="tags-container" id="updateTagsContainer"></div>
                            </div>
                            
                            <div class="form-group">
                                <label>Archivo PDF <span class="required">*</span></label>
                                <div class="file-upload-container">
                                    <div class="file-upload-button" id="updateFileUploadBtn">
                                        <i class="fas fa-cloud-upload-alt"></i>
                                        <div class="main-text">Arrastre y suelte la nueva versión aquí</div>
                                        <p>o haga clic para seleccionar un archivo</p>
                                        <p class="file-types">Solo archivos PDF (máx. 10 MB)</p>
                                    </div>
                                    <input type="file" id="updateDocFile" name="archivo" accept=".pdf" required class="file-upload-input">
                                    
                                    <div class="file-info" id="updateFileInfo">
                                        <i class="fas fa-file-pdf"></i>
                                        <span class="file-name" id="updateFileName">nombre_del_archivo.pdf</span>
                                        <span class="file-size" id="updateFileSize">0 KB</span>
                                        <i class="fas fa-times remove-file" onclick="removeUpdateFile()"></i>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-actions">
                                <button type="button" class="btn secondary" onclick="cancelUpdate()">Cancelar</button>
                                <button type="submit" class="btn primary">
                                    <i class="fas fa-sync-alt"></i> Actualizar Documento
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
                        
            <!-- Vista de Configuración -->
            <div id="settings-content" class="tab-content" style="display: none;">
                <div class="section-header">
                    <h2>Configuración</h2>
                    <p>Administrar sectores y configuración del sistema</p>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h3>Gestión de Sectores</h3>
                        <button class="btn primary" id="newSectorBtn">
                            <i class="fas fa-plus"></i> Nuevo Sector
                        </button>
                    </div>
                    
                    <div id="newSectorForm" style="display: none;" class="settings-form">
                        <form id="sectorForm" action="procesar_sector.php" method="post">
                            <div class="form-group">
                                <label for="sectorName">Nombre del sector:</label>
                                <input type="text" id="sectorName" name="nombre" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="sectorDesc">Descripción:</label>
                                <textarea id="sectorDesc" name="descripcion" rows="2"></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="sectorIcon">Icono:</label>
                                <select id="sectorIcon" name="icono">
                                    <option value="fa-folder">📁 Carpeta</option>
                                    <option value="fa-building">🏢 Edificio</option>
                                    <option value="fa-users">👥 Personas</option>
                                    <option value="fa-laptop-code">💻 Programación</option>
                                    <option value="fa-coins">💰 Finanzas</option>
                                    <option value="fa-shopping-cart">🛒 Ventas</option>
                                    <option value="fa-cogs">⚙️ Operaciones</option>
                                    <option value="fa-calculator">🧮 Contabilidad</option>
                                    <option value="fa-bullhorn">📣 Marketing</option>
                                    <option value="fa-balance-scale">⚖️ Legal</option>
                                </select>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn primary">Guardar Sector</button>
                                <button type="button" class="btn secondary" id="cancelSectorBtn">Cancelar</button>
                            </div>
                        </form>
                    </div>
                    
                    <ul class="sectors-list">
                        <?php if (empty($sectores)): ?>
                        <li class="no-sectors">
                            <p>No hay sectores disponibles.</p>
                        </li>
                        <?php else: ?>
                            <?php foreach ($sectores as $sector): ?>
                            <li class="sector-item">
                                <div class="sector-info">
                                    <div class="sector-icon">
                                        <i class="fas <?php echo htmlspecialchars($sector['icono'] ? $sector['icono'] : 'fa-folder'); ?>"></i>
                                    </div>
                                    <div class="sector-details">
                                        <h4><?php echo htmlspecialchars($sector['nombre']); ?></h4>
                                        <p><?php echo htmlspecialchars($sector['descripcion']); ?></p>
                                    </div>
                                </div>
                                <div class="sector-actions">
                                    <button class="action-btn edit" onclick="editSector(<?php echo $sector['id']; ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="action-btn delete" onclick="confirmDeleteSector(<?php echo $sector['id']; ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h3>Gestión del Glosario</h3>
                        <button class="btn primary" id="newTermBtn">
                            <i class="fas fa-plus"></i> Nuevo Término
                        </button>
                    </div>
                    
                    <div id="newTermForm" style="display: none;" class="settings-form">
                        <form id="termForm" action="procesar_glosario.php" method="post">
                            <div class="form-group">
                                <label for="termName">Término:</label>
                                <input type="text" id="termName" name="termino" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="termDefinition">Definición:</label>
                                <textarea id="termDefinition" name="definicion" rows="3" required></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="termSector">Sector (opcional):</label>
                                <select id="termSector" name="sector_id">
                                    <option value="">Sin sector específico</option>
                                    <?php foreach ($sectores as $sector): ?>
                                    <option value="<?php echo $sector['id']; ?>"><?php echo htmlspecialchars($sector['nombre']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn primary">Guardar Término</button>
                                <button type="button" class="btn secondary" id="cancelTermBtn">Cancelar</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Modal para visor de PDF -->
    <div id="pdfViewerModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closePdfViewer()">&times;</span>
            <h3 id="pdfTitle">Título del Documento</h3>
            <div class="pdf-container">
                <!-- Aquí se cargará el PDF -->
                <div id="pdfViewer">
                    <p>Visor de PDF cargando...</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal para el glosario -->
    <div id="glossaryModal" class="modal">
        <div class="modal-content glossary-modal">
            <span class="close-modal" onclick="closeGlossary()">&times;</span>
            
            <div class="glossary-search-container">
                <h3>Glosario de Términos</h3>
                <input type="text" class="glossary-search" id="glossarySearch" placeholder="Buscar término...">
            </div>
            
            <div class="glossary-content">
                <?php
                // Agrupar términos por letra inicial
                $glossaryByLetter = [];
                foreach ($glosario as $term) {
                    $firstLetter = strtoupper(substr($term['termino'], 0, 1));
                    if (!isset($glossaryByLetter[$firstLetter])) {
                        $glossaryByLetter[$firstLetter] = [];
                    }
                    $glossaryByLetter[$firstLetter][] = $term;
                }
                
                // Ordenar letras alfabéticamente
                ksort($glossaryByLetter);
                
                // Mostrar términos por letra
                foreach ($glossaryByLetter as $letter => $terms) {
                    echo '<div class="glossary-letter">' . $letter . '</div>';
                    echo '<ul class="glossary-list">';
                    foreach ($terms as $term) {
                        echo '<li class="glossary-item">';
                        echo '<h4><i class="fas fa-info-circle"></i> ' . htmlspecialchars($term['termino']) . '</h4>';
                        echo '<p>' . htmlspecialchars($term['definicion']) . '</p>';
                        echo '</li>';
                    }
                    echo '</ul>';
                }
                
                // Si no hay términos
                if (empty($glosario)) {
                    echo '<div class="no-results-message">';
                    echo '<i class="fas fa-book"></i>';
                    echo '<p>No hay términos en el glosario.</p>';
                    echo '</div>';
                }
                ?>
                
                <div class="no-results-message" style="display: none;">
                    <i class="fas fa-search"></i>
                    <p>No se encontraron términos que coincidan con la búsqueda.</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Contenedor de notificaciones -->
    <div class="notification-container"></div>
    
    <!-- ==================== CHATBOT RAG - ASISTENTE IA ==================== -->
    <div id="chatbot-container" class="chatbot-container">
        <div class="chatbot-header">
            <div class="chatbot-header-content">
                <i class="fas fa-robot"></i>
                <div>
                    <h3>Asistente DocuGest</h3>
                    <p class="chatbot-status">
                        <span id="chatbot-status-icon" class="status-dot"></span>
                        <span id="chatbot-status-text">Verificando...</span>
                    </p>
                </div>
            </div>
            <button class="chatbot-close-btn" onclick="toggleChatbot()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="chatbot-messages" id="chatbot-messages">
            <div class="chatbot-message bot">
                <div class="message-avatar">
                    <i class="fas fa-robot"></i>
                </div>
                <div class="message-content">
                    <p>👋 ¡Hola! Soy tu asistente virtual de DocuGest.</p>
                    <p>Puedo ayudarte a encontrar información en las políticas y procedimientos usando lenguaje natural.</p>
                    <p><strong>Ejemplos de preguntas:</strong></p>
                    <ul>
                        <li>¿Cuál es el proceso de solicitud de vacaciones?</li>
                        <li>¿Qué dice la política sobre trabajo remoto?</li>
                        <li>¿Cómo se realiza el reporte de gastos?</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="chatbot-input-container">
            <textarea 
                id="chatbot-input" 
                class="chatbot-input" 
                placeholder="Escribe tu pregunta aquí..."
                rows="1"
            ></textarea>
            <button id="chatbot-send-btn" class="chatbot-send-btn" onclick="sendChatbotMessage()">
                <i class="fas fa-paper-plane"></i>
            </button>
        </div>
    </div>
    
    <button id="chatbot-toggle-btn" class="chatbot-toggle-btn" onclick="toggleChatbot()">
        <i class="fas fa-robot"></i>
        <span class="chatbot-badge" id="chatbot-badge" style="display: none;">1</span>
    </button>
    <!-- ==================================================================== -->
 
    <!-- Inclusión de archivos JavaScript al final del body -->
    <script src="js/search.js"></script>
    <script src="js/document-viewer.js"></script>
    <script src="js/glossary.js"></script>
    <script src="js/upload-form.js"></script>
    <script src="js/sectors.js"></script>
    <script src="js/update-document.js"></script>
    <script src="js/chatbot.js"></script>
    <script src="js/main.js"></script>
 
</body>
</html>