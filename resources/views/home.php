<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'MVC Base Framework' ?></title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- HTMX -->
    <script src="https://unpkg.com/htmx.org@2.0.2"></script>

    <style>
        :root {
            --bg-color: #0f172a;
            --surface-color: #1e293b;
            --primary: #38bdf8;
            --primary-glow: rgba(56, 189, 248, 0.4);
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--bg-color);
            color: var(--text-main);
            min-height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
        }

        /* Animated Background Pattern */
        .bg-grid {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: 
                linear-gradient(var(--surface-color) 1px, transparent 1px),
                linear-gradient(90deg, var(--surface-color) 1px, transparent 1px);
            background-size: 40px 40px;
            opacity: 0.2;
            z-index: 0;
        }

        /* Glowing Orbs */
        .glowing-orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(100px);
            z-index: 0;
            animation: float 10s ease-in-out infinite alternate;
        }

        .orb-1 {
            width: 300px;
            height: 300px;
            background: rgba(56, 189, 248, 0.15);
            top: 10%;
            left: 15%;
        }

        .orb-2 {
            width: 400px;
            height: 400px;
            background: rgba(168, 85, 247, 0.15);
            bottom: 10%;
            right: 15%;
            animation-delay: -5s;
        }

        @keyframes float {
            0% { transform: translateY(0) scale(1); }
            100% { transform: translateY(-30px) scale(1.1); }
        }

        .container {
            position: relative;
            z-index: 10;
            max-width: 600px;
            width: 90%;
            text-align: center;
        }

        .glass-card {
            background: rgba(30, 41, 59, 0.6);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 24px;
            padding: 3rem 2rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            animation: slideUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
            opacity: 0;
            transform: translateY(30px);
        }

        @keyframes slideUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .logo-icon {
            font-size: 3.5rem;
            color: var(--primary);
            margin-bottom: 1rem;
            display: inline-block;
            filter: drop-shadow(0 0 15px var(--primary-glow));
            animation: pulse 3s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); filter: drop-shadow(0 0 15px var(--primary-glow)); }
            50% { transform: scale(1.05); filter: drop-shadow(0 0 25px var(--primary-glow)); }
            100% { transform: scale(1); filter: drop-shadow(0 0 15px var(--primary-glow)); }
        }

        h1 {
            font-size: 2.25rem;
            font-weight: 800;
            margin: 0 0 1.5rem 0;
            letter-spacing: -0.02em;
            background: linear-gradient(to right, #f8fafc, #cbd5e1);
            background-clip: text;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        p {
            font-size: 1.1rem;
            line-height: 1.6;
            color: var(--text-muted);
            margin-bottom: 2.5rem;
            font-weight: 300;
        }

        .actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.875rem 1.75rem;
            border-radius: 9999px;
            font-weight: 600;
            font-size: 1rem;
            text-decoration: none;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .btn-primary {
            background-color: var(--primary);
            color: #0284c7; /* Darker blue text for contrast */
            box-shadow: 0 4px 14px 0 var(--primary-glow);
        }

        .btn-primary:empty, .btn-primary { color: #0f172a; }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px 0 var(--primary-glow);
            filter: brightness(1.1);
        }

        .btn-secondary {
            background-color: transparent;
            color: var(--text-main);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .btn-secondary:hover {
            background-color: rgba(255, 255, 255, 0.05);
            border-color: rgba(255, 255, 255, 0.2);
        }

        .sys-info {
            margin-top: 2.5rem;
            display: flex;
            justify-content: center;
            gap: 1.5rem;
            font-size: 0.875rem;
            color: var(--text-muted);
        }

        .sys-badge {
            background: rgba(0, 0, 0, 0.2);
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            border: 1px solid rgba(255, 255, 255, 0.05);
            display: flex;
            align-items: center;
            gap: 0.375rem;
        }

        .sys-badge i {
            color: var(--primary);
        }
    </style>
</head>
<body>

    <div class="bg-grid"></div>
    <div class="glowing-orb orb-1"></div>
    <div class="glowing-orb orb-2"></div>

    <div class="container">
        <div class="glass-card">
            <div class="logo-icon">
                <i class="bi bi-rocket-takeoff-fill"></i>
            </div>
            
            <h1><?= $title ?? 'Sistema Online.' ?></h1>
            
            <p>
                A arquitetura MVC Base foi carregada com sucesso. Você está pronto para construir 
                sistemas de alta performance com a elegância do PHP moderno.
            </p>

            <div class="actions">
                <a href="https://github.com/FelipeOropeza/mvc-estrutura/blob/main/docs/framework.md" target="_blank" class="btn btn-primary">
                    <i class="bi bi-journal-code"></i> Documentação
                </a>
                <a href="https://github.com/FelipeOropeza/mvc-estrutura" target="_blank" class="btn btn-secondary">
                    <i class="bi bi-github"></i> Repositório
                </a>
            </div>

            <div class="sys-info">
                <div class="sys-badge">
                    <i class="bi bi-cpu"></i> PHP <?= PHP_VERSION ?>
                </div>
                <div class="sys-badge">
                    <i class="bi bi-lightning-charge"></i> Stateless
                </div>
            </div>
        </div>
    </div>

</body>
</html>