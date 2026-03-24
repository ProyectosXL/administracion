
<?php
// tesoreria/cobranzas/seleccionar_sucursal.php
session_start();

$cod_client_entrada = isset($_GET['cliente']) ? trim($_GET['cliente']) : '';

if (empty($cod_client_entrada)) {
    header('Location: login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seleccionar Sucursal — Cobranzas XL</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        :root {
            --cl-bg: #f7f7f5;
            --cl-surface: #ffffff;
            --cl-border: #e4e4e0;
            --cl-border-hover: #c8c8c0;
            --cl-text-primary: #1a1a18;
            --cl-text-secondary: #6b6b65;
            --cl-text-hint: #a8a8a0;
            --cl-accent: #1a1a18;
            --cl-accent-soft: #f0f0ec;
            --cl-danger: #c0392b;
            --cl-radius: 10px;
            --cl-radius-sm: 6px;
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Georgia', serif;
            background: var(--cl-bg);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
            color: var(--cl-text-primary);
        }

        .page-wrapper {
            width: 100%;
            max-width: 560px;
        }

        .brand-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 2.5rem;
        }

        .brand-mark {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .brand-mark-square {
            width: 32px;
            height: 32px;
            background: var(--cl-accent);
            border-radius: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.5px;
            font-family: sans-serif;
        }

        .brand-mark-name {
            font-family: sans-serif;
            font-size: 13px;
            font-weight: 500;
            color: var(--cl-text-secondary);
            letter-spacing: 0.3px;
        }

        .logout-link {
            font-family: sans-serif;
            font-size: 12px;
            color: var(--cl-text-hint);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: color 0.15s;
        }
        .logout-link:hover { color: var(--cl-text-secondary); }

        .main-card {
            background: var(--cl-surface);
            border: 1px solid var(--cl-border);
            border-radius: 14px;
            padding: 2rem 2rem 1.5rem;
        }

        .card-eyebrow {
            font-family: sans-serif;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--cl-text-hint);
            margin-bottom: 0.6rem;
        }

        .card-title {
            font-size: 1.5rem;
            font-weight: normal;
            color: var(--cl-text-primary);
            margin-bottom: 0.4rem;
            line-height: 1.3;
        }

        .card-subtitle {
            font-family: sans-serif;
            font-size: 13px;
            color: var(--cl-text-secondary);
            margin-bottom: 1.75rem;
            line-height: 1.5;
        }

        .divider {
            height: 1px;
            background: var(--cl-border);
            margin-bottom: 1.5rem;
        }

        .loading-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 12px;
            padding: 2rem 0;
            color: var(--cl-text-hint);
            font-family: sans-serif;
            font-size: 13px;
        }

        .spinner-ring {
            width: 24px;
            height: 24px;
            border: 2px solid var(--cl-border);
            border-top-color: var(--cl-accent);
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin { to { transform: rotate(360deg); } }

        .sucursal-list {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .sucursal-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 16px;
            border: 1px solid var(--cl-border);
            border-radius: var(--cl-radius);
            cursor: pointer;
            text-decoration: none;
            transition: border-color 0.15s, background 0.15s;
            background: transparent;
        }

        .sucursal-item:hover {
            border-color: var(--cl-border-hover);
            background: var(--cl-accent-soft);
            text-decoration: none;
        }

        .sucursal-item:active { background: #e8e8e4; }

        .sucursal-item-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .sucursal-avatar {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: var(--cl-accent-soft);
            border: 1px solid var(--cl-border);
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: sans-serif;
            font-size: 11px;
            font-weight: 600;
            color: var(--cl-text-secondary);
            letter-spacing: 0.3px;
            flex-shrink: 0;
        }

        .sucursal-info-name {
            font-family: sans-serif;
            font-size: 14px;
            font-weight: 500;
            color: var(--cl-text-primary);
            line-height: 1.3;
        }

        .sucursal-info-code {
            font-family: 'Courier New', monospace;
            font-size: 11px;
            color: var(--cl-text-hint);
            margin-top: 1px;
        }

        .sucursal-arrow {
            color: var(--cl-text-hint);
            font-size: 12px;
            flex-shrink: 0;
            transition: transform 0.15s, color 0.15s;
        }

        .sucursal-item:hover .sucursal-arrow {
            transform: translateX(3px);
            color: var(--cl-text-secondary);
        }

        .redirect-notice {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 14px;
            background: var(--cl-accent-soft);
            border: 1px solid var(--cl-border);
            border-radius: var(--cl-radius-sm);
            font-family: sans-serif;
            font-size: 13px;
            color: var(--cl-text-secondary);
        }

        .error-state {
            padding: 1rem 0;
            font-family: sans-serif;
            font-size: 13px;
            color: var(--cl-danger);
            display: flex;
            align-items: flex-start;
            gap: 8px;
        }

        .page-footer {
            margin-top: 1.5rem;
            text-align: center;
            font-family: sans-serif;
            font-size: 11px;
            color: var(--cl-text-hint);
        }

        .count-badge {
            font-family: sans-serif;
            font-size: 11px;
            font-weight: 600;
            background: var(--cl-accent-soft);
            border: 1px solid var(--cl-border);
            color: var(--cl-text-secondary);
            padding: 2px 8px;
            border-radius: 20px;
            margin-left: 8px;
            vertical-align: middle;
        }

        .section-label {
            font-family: sans-serif;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: var(--cl-text-hint);
            font-weight: 600;
            margin-bottom: 10px;
        }

        @media (max-width: 480px) {
            .main-card { padding: 1.5rem 1.25rem 1.25rem; }
            .card-title { font-size: 1.25rem; }
        }
    </style>
</head>
<body>
    <div class="page-wrapper">

        <div class="brand-row">
            <div class="brand-mark">
                <div class="brand-mark-square">XL</div>
                <span class="brand-mark-name">Portal de Cobranzas</span>
            </div>
            <a href="../../../sistemas/login.php?logout=1" class="logout-link">
                <i class="fa-solid fa-arrow-right-from-bracket" style="font-size: 11px;"></i>
                Salir
            </a>
        </div>

        <div class="main-card">
            <p class="card-eyebrow">Gestión de cobranzas</p>
            <h1 class="card-title" id="titulo-card">Seleccioná tu sucursal</h1>
            <p class="card-subtitle" id="subtitulo-card">
                Tu cuenta tiene acceso a múltiples locales. Elegí con cuál querés trabajar.
            </p>

            <div class="divider"></div>

            <div id="contenido">
                <div class="loading-state">
                    <div class="spinner-ring"></div>
                    <span>Cargando sucursales…</span>
                </div>
            </div>
        </div>

        <p class="page-footer">XL Extra Large — Sistema de Gestión</p>
    </div>

    <script>
        const COD_CLIENT = <?php echo json_encode($cod_client_entrada); ?>;

        function getIniciales(nombre) {
            if (!nombre) return '—';
            const palabras = nombre.trim().split(/\s+/).filter(Boolean);
            if (palabras.length === 1) return palabras[0].substring(0, 2).toUpperCase();
            return (palabras[0][0] + palabras[palabras.length - 1][0]).toUpperCase();
        }

        function renderError(msg) {
            document.getElementById('contenido').innerHTML = `
                <div class="error-state">
                    <i class="fa-solid fa-circle-exclamation" style="margin-top:1px; font-size:13px;"></i>
                    <span>${msg}</span>
                </div>`;
        }

        function renderSucursales(sucursales, nombreGrupo, esGrupo) {
            const contenido  = document.getElementById('contenido');
            const titulo     = document.getElementById('titulo-card');
            const subtitulo  = document.getElementById('subtitulo-card');

            if (esGrupo && nombreGrupo) {
                subtitulo.innerHTML = `Accedés como parte del grupo <strong>${nombreGrupo}</strong>. Elegí el local con el que querés operar.`;
            }

            if (!sucursales || sucursales.length === 0) {
                renderError('No se encontraron sucursales habilitadas para tu cuenta.');
                return;
            }

            // Una sola sucursal: redirigir automáticamente
            if (sucursales.length === 1) {
                titulo.textContent    = 'Accediendo…';
                subtitulo.textContent = 'Redireccionando a tu sucursal.';
                contenido.innerHTML   = `
                    <div class="redirect-notice">
                        <div class="spinner-ring" style="width:16px;height:16px;border-width:1.5px;"></div>
                        <span>Ingresando a <strong>${sucursales[0].desc_sucursal}</strong>…</span>
                    </div>`;
                setTimeout(() => {
                    window.location.href = 'portal_cliente.php?cliente=' + encodeURIComponent(sucursales[0].cod_client);
                }, 700);
                return;
            }

            let html = `<p class="section-label">Locales disponibles <span class="count-badge">${sucursales.length}</span></p>`;
            html += '<div class="sucursal-list">';

            sucursales.forEach(suc => {
                const iniciales = getIniciales(suc.desc_sucursal);
                html += `
                    <a href="portal_cliente.php?cliente=${encodeURIComponent(suc.cod_client)}"
                       class="sucursal-item">
                        <div class="sucursal-item-left">
                            <div class="sucursal-avatar">${iniciales}</div>
                            <div>
                                <div class="sucursal-info-name">${suc.desc_sucursal}</div>
                                <div class="sucursal-info-code">${suc.cod_client}</div>
                            </div>
                        </div>
                        <i class="fa-solid fa-chevron-right sucursal-arrow"></i>
                    </a>`;
            });

            html += '</div>';
            contenido.innerHTML = html;
        }

        // Llamamos al controller pasando el cod_client
        fetch('api/sucursales_grupo_controller.php?cod_client=' + encodeURIComponent(COD_CLIENT))
            .then(res => res.json())
            .then(data => {
                if (!data.success) {
                    renderError(data.message || 'Error al cargar sucursales.');
                    return;
                }
                renderSucursales(data.data, data.nombre_grupo, data.es_grupo);
            })
            .catch(() => {
                renderError('Error de conexión. Por favor recargá la página.');
            });
    </script>
</body>
</html>