<?php
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$base = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
if ($base === '/' || $base === '\\' || $base === '.') {
    $base = '';
}

$statusCode = $statusCode ?? 500;
$title = $title ?? 'Erro interno';
$message = $message ?? 'Algo saiu do esperado.';
$actionLabel = $actionLabel ?? 'Voltar para o inicio';
$actionHref = $actionHref ?? ($base . '/');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e((string) $statusCode . ' | ' . $title) ?> | FlowDesk</title>
    <link rel="icon" href="<?= $base ?>/favicon.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            color-scheme: dark;
            --bg: #020617;
            --panel: rgba(15, 23, 42, 0.88);
            --panel-border: rgba(148, 163, 184, 0.16);
            --text: #e2e8f0;
            --muted: #94a3b8;
            --accent: #8b5cf6;
            --accent-strong: #7c3aed;
            --glow: rgba(139, 92, 246, 0.22);
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: 'Inter', sans-serif;
            color: var(--text);
            background:
                radial-gradient(circle at top left, rgba(59, 130, 246, 0.18), transparent 30%),
                radial-gradient(circle at bottom right, rgba(139, 92, 246, 0.22), transparent 34%),
                var(--bg);
            display: grid;
            place-items: center;
            padding: 24px;
        }

        .fd-error-shell {
            width: min(100%, 760px);
            border: 1px solid var(--panel-border);
            background: var(--panel);
            backdrop-filter: blur(18px);
            border-radius: 28px;
            padding: 40px;
            box-shadow: 0 30px 80px rgba(2, 6, 23, 0.55);
            position: relative;
            overflow: hidden;
        }

        .fd-error-shell::before {
            content: '';
            position: absolute;
            inset: -120px auto auto -120px;
            width: 240px;
            height: 240px;
            background: var(--glow);
            filter: blur(50px);
            border-radius: 999px;
        }

        .fd-error-code {
            margin: 0 0 12px;
            font-size: clamp(56px, 10vw, 110px);
            line-height: 0.95;
            letter-spacing: -0.05em;
        }

        .fd-error-title {
            margin: 0 0 10px;
            font-size: clamp(24px, 4vw, 38px);
            line-height: 1.05;
        }

        .fd-error-message {
            margin: 0;
            max-width: 56ch;
            color: var(--muted);
            font-size: 16px;
            line-height: 1.7;
        }

        .fd-error-actions {
            margin-top: 28px;
            display: flex;
            gap: 14px;
            flex-wrap: wrap;
        }

        .fd-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 46px;
            padding: 0 18px;
            border-radius: 14px;
            text-decoration: none;
            font-weight: 600;
            transition: 0.2s ease;
        }

        .fd-btn-primary {
            background: linear-gradient(135deg, var(--accent), var(--accent-strong));
            color: #fff;
            box-shadow: 0 16px 35px rgba(124, 58, 237, 0.28);
        }

        .fd-btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 20px 40px rgba(124, 58, 237, 0.35);
        }

        .fd-btn-secondary {
            border: 1px solid var(--panel-border);
            color: var(--text);
            background: rgba(15, 23, 42, 0.5);
        }

        .fd-error-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 18px;
            color: var(--muted);
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.22em;
            text-transform: uppercase;
        }

        @media (max-width: 640px) {
            .fd-error-shell {
                padding: 28px;
                border-radius: 22px;
            }
        }
    </style>
</head>
<body>
    <main class="fd-error-shell">
        <div class="fd-error-eyebrow">FlowDesk Workspace</div>
        <h1 class="fd-error-code"><?= e((string) $statusCode) ?></h1>
        <h2 class="fd-error-title"><?= e($title) ?></h2>
        <p class="fd-error-message"><?= e($message) ?></p>

        <div class="fd-error-actions">
            <a class="fd-btn fd-btn-primary" href="<?= e($actionHref) ?>"><?= e($actionLabel) ?></a>
            <a class="fd-btn fd-btn-secondary" href="<?= e($base . '/') ?>">Pagina inicial</a>
        </div>
    </main>
</body>
</html>
