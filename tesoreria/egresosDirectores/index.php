<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal de        <div class="portal-header">
            <h1>
                <i class="bi bi-building"></i> Sistema de Gestión de Egresos
            </h1>
            <p>Portal Administrativo para Gestión de Solicitudes y Pagos Empresariales</p>
        </div>s Directores</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons - Versión más reciente -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Fallback para Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.0/font/bootstrap-icons.min.css" crossorigin="anonymous">
    
    <style>
        /* Mejorar soporte para emojis e iconos */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif, 'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol', 'Noto Color Emoji';
            background-color: #dcdcdcff;
            min-height: 100vh;
            padding: 2rem 0;
        }
        
        .portal-container {
            max-width: 1200px;
            width: 100%;
            padding: 2rem;
            margin: 0 auto;
        }
        
        .portal-header {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 3rem;
            padding: 2rem;
            background: white;
            border-radius: 0.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .portal-header h1 {
            font-size: 2.5rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #2c3e50;
        }
        
        .portal-header p {
            font-size: 1.1rem;
            color: #6c757d;
            margin-bottom: 0;
        }
        
        .portal-card {
            background: white;
            border-radius: 0.5rem;
            padding: 2rem;
            box-shadow: 0 2px 15px rgba(0,0,0,0.08);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            height: 100%;
            cursor: pointer;
            border: 1px solid #e9ecef;
        }
        
        .portal-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 25px rgba(0,0,0,0.12);
            border-color: #007bff;
        }
        
        .portal-card .icon {
            font-size: 3rem;
            margin-bottom: 1.5rem;
            display: block;
            line-height: 1;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            font-family: 'Bootstrap Icons', sans-serif !important;
            font-weight: normal !important;
            font-style: normal !important;
        }
        
        /* Forzar carga de Bootstrap Icons */
        .bi {
            font-family: 'Bootstrap Icons' !important;
            speak: never;
            font-style: normal;
            font-weight: normal;
            font-variant: normal;
            text-transform: none;
            line-height: 1;
        }
        
        .portal-card.directores .icon {
            color: #007bff;
        }
        
        .portal-card.proveedores .icon {
            color: #28a745;
        }
        
        .portal-card.tesoreria .icon {
            color: #17a2b8;
        }
        
        .portal-card h3 {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: #2c3e50;
        }
        
        .portal-card p {
            color: #6c757d;
            margin-bottom: 1.5rem;
            line-height: 1.6;
        }
        
        .portal-card .btn {
            width: 100%;
            padding: 0.75rem;
            font-weight: 500;
            font-size: 1rem;
            border-radius: 0.375rem;
        }
        
        .portal-card.directores .btn {
            background: #007bff;
            border-color: #007bff;
            color: white;
        }
        
        .portal-card.proveedores .btn {
            background: #28a745;
            border-color: #28a745;
            color: white;
        }
        
        .portal-card.tesoreria .btn {
            background: #17a2b8;
            border-color: #17a2b8;
            color: white;
        }
        
        .portal-card .btn:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }
        
        @media (max-width: 768px) {
            .portal-header h1 {
                font-size: 2rem;
            }
            
            .portal-card {
                padding: 1.5rem;
                margin-bottom: 1.5rem;
            }
            
            .portal-container {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="portal-container">
        <div class="portal-header">
            <h1>
                <i class="bi bi-building"></i>
                Sistema de Egresos Directores
            </h1>
            <p>Portal Administrativo para Gestión de Solicitudes y Pagos Empresariales</p>
        </div>
        
        <div class="row g-4">
            <!-- Portal Directores -->
            <div class="col-md-4">
                <div class="portal-card directores" onclick="window.location.href='directores.php'">
                    <i class="bi bi-person-badge icon"></i>
                    <h3>Directores</h3>
                    <p>
                        Gestión de solicitudes de egresos.
                        Crear y monitorear solicitudes de compras personales y retiros operativos.
                    </p>
                    <a href="directores.php" class="btn btn-primary">
                        <i class="bi bi-arrow-right-circle"></i> Acceder
                    </a>
                </div>
            </div>
            
            <!-- Portal Proveedores -->
            <div class="col-md-4">
                <div class="portal-card proveedores" onclick="window.location.href='proveedores.php'">
                    <i class="bi bi-building icon"></i>
                    <h3>Proveedores</h3>
                    <p>
                        Gestión de facturación y órdenes de compra.
                        Administrar documentación pendiente y autorizar procesos de pago.
                    </p>
                    <a href="proveedores.php" class="btn btn-primary">
                        <i class="bi bi-arrow-right-circle"></i> Acceder
                    </a>
                </div>
            </div>
            
            <!-- Portal Tesorería -->
            <div class="col-md-4">
                <div class="portal-card tesoreria" onclick="window.location.href='tesoreria.php'">
                    <i class="bi bi-cash-stack icon"></i>
                    <h3>Tesorería</h3>
                    <p>
                        Centro de control financiero para procesamiento de pagos.
                        Gestionar documentación y validaciones.
                    </p>
                    <a href="tesoreria.php" class="btn btn-primary">
                        <i class="bi bi-arrow-right-circle"></i> Acceder
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Footer con información -->
        <div class="text-center mt-5">
            <div class="card">
                <div class="card-body">
                    <p class="text-muted mb-2">
                        <i class="bi bi-info-circle"></i>
                        <strong>Sistema de Gestión de Egresos</strong>
                    </p>
                    <p class="text-muted mb-0">
                        <small>
                            Selecciona el portal correspondiente según tu función empresarial.
                            <br>
                            Acceso directo a cada módulo - Sistema integrado de administración.
                        </small>
                    </p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Script para verificar carga de iconos Bootstrap -->
    <script>
        // Verificar si Bootstrap Icons se cargaron correctamente
        window.addEventListener('load', function() {
            setTimeout(function() {
                const testIcon = document.createElement('i');
                testIcon.className = 'bi bi-check';
                testIcon.style.position = 'absolute';
                testIcon.style.left = '-9999px';
                document.body.appendChild(testIcon);
                
                const computedStyle = window.getComputedStyle(testIcon, ':before');
                const content = computedStyle.getPropertyValue('content');
                
                // Si los iconos no cargan, usar texto como fallback
                if (!content || content === 'none' || content === '""') {
                    console.log('Bootstrap Icons no se cargaron, usando fallback...');
                    
                    // Aplicar fallbacks
                    const iconMappings = {
                        'bi-person-badge': '👤',
                        'bi-building': '🏢', 
                        'bi-cash-stack': '💰',
                        'bi-arrow-right-circle': '→',
                        'bi-info-circle': 'ℹ'
                    };
                    
                    Object.keys(iconMappings).forEach(function(iconClass) {
                        const icons = document.querySelectorAll('.' + iconClass);
                        icons.forEach(function(icon) {
                            icon.innerHTML = iconMappings[iconClass];
                            icon.style.fontFamily = 'Apple Color Emoji, Segoe UI Emoji, sans-serif';
                        });
                    });
                }
                
                document.body.removeChild(testIcon);
            }, 100);
        });
    </script>
</body>
</html>