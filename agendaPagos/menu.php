<?php
// agendaPagos/menu.php - Portal Principal de Control Financiero XL
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal de Control Financiero | XL Extra Large</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            min-height: 100vh;
            color: #f8fafc;
            display: flex;
            flex-direction: column;
        }

        .portal-header {
            padding: 3rem 1rem 2rem;
            text-align: center;
        }

        .brand-badge {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: #38bdf8;
            padding: 6px 18px;
            border-radius: 30px;
            font-size: 0.85rem;
            font-weight: 600;
            letter-spacing: 1px;
            text-transform: uppercase;
            display: inline-block;
            margin-bottom: 1rem;
        }

        .portal-title {
            font-size: 2.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, #ffffff 0%, #94a3b8 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.5rem;
        }

        .portal-subtitle {
            color: #94a3b8;
            font-size: 1.1rem;
            max-width: 600px;
            margin: 0 auto;
        }

        .menu-card {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 2.5rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            height: 100%;
            display: flex;
            flex-direction: column;
            position: relative;
            overflow: hidden;
        }

        .menu-card:hover {
            transform: translateY(-8px);
            border-color: rgba(56, 189, 248, 0.4);
            box-shadow: 0 20px 40px -15px rgba(0, 0, 0, 0.5), 0 0 30px rgba(56, 189, 248, 0.15);
        }

        .card-icon-wrapper {
            width: 70px;
            height: 70px;
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin-bottom: 1.5rem;
        }

        .icon-medios {
            background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
            color: #ffffff;
            box-shadow: 0 10px 20px -5px rgba(2, 132, 199, 0.5);
        }

        .icon-bancos {
            background: linear-gradient(135deg, #d97706 0%, #b45309 100%);
            color: #ffffff;
            box-shadow: 0 10px 20px -5px rgba(217, 119, 6, 0.5);
        }

        .card-heading {
            font-size: 1.5rem;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 0.75rem;
        }

        .card-description {
            color: #94a3b8;
            font-size: 0.95rem;
            line-height: 1.6;
            margin-bottom: 1.5rem;
            flex-grow: 1;
        }

        .feature-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 2rem;
        }

        .tag-pill {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #cbd5e1;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.78rem;
            font-weight: 500;
        }

        .btn-enter {
            width: 100%;
            padding: 12px 24px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .btn-medios {
            background: #0284c7;
            color: #ffffff;
            border: none;
        }

        .btn-medios:hover {
            background: #0369a1;
            color: #ffffff;
            box-shadow: 0 8px 20px rgba(2, 132, 199, 0.4);
        }

        .btn-bancos {
            background: #d97706;
            color: #ffffff;
            border: none;
        }

        .btn-bancos:hover {
            background: #b45309;
            color: #ffffff;
            box-shadow: 0 8px 20px rgba(217, 119, 6, 0.4);
        }

        .portal-footer {
            margin-top: auto;
            padding: 2rem 1rem;
            text-align: center;
            color: #64748b;
            font-size: 0.85rem;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
        }
    </style>
</head>
<body>

    <header class="portal-header">
        <div class="brand-badge">
            XL Extra Large • Administración
        </div>
        <h1 class="portal-title">Portal de Control Financiero</h1>
        <p class="portal-subtitle">Seleccione el módulo administrativo de auditoría y conciliación que desea operar.</p>
    </header>

    <main class="container pb-5">
        <div class="row g-4 justify-content-center">
            
            <!-- Módulo 1: Control de Medios de Pago -->
            <div class="col-12 col-md-6 col-lg-5">
                <div class="menu-card">
                    <div class="card-icon-wrapper icon-medios">
                        <i class="fa-solid fa-credit-card"></i>
                    </div>
                    <h2 class="card-heading">CONTROL MEDIOS DE PAGOS</h2>
                    <a href="index.php" class="btn-enter btn-medios">
                        Ingresar a Medios de Pago <i class="fa-solid fa-arrow-right"></i>
                    </a>
                </div>
            </div>

            <!-- Módulo 2: Control Promociones Banco -->
            <div class="col-12 col-md-6 col-lg-5">
                <div class="menu-card">
                    <div class="card-icon-wrapper icon-bancos">
                        <i class="fa-solid fa-building-columns"></i>
                    </div>
                    <h2 class="card-heading">CONTROL PROMOCIONES BANCO</h2>
                    <a href="controlPromocionesBanco.php" class="btn-enter btn-bancos">
                        Ingresar a Promociones Banco <i class="fa-solid fa-arrow-right"></i>
                    </a>
                </div>
            </div>

        </div>
    </main>

    <footer class="portal-footer">
        <p class="mb-0">XL Extra Large © <?php echo date('Y'); ?> • Sistema Integrado de Control y Conciliación Financiera</p>
    </footer>

</body>
</html>
