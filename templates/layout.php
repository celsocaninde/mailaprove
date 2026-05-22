<?php
/**
 * Shared layout for Approval By Mail public pages.
 *
 * Expected variables:
 * - $pageTitle (string)
 * - $pageContent (string)
 */

$pageTitle = $pageTitle ?? 'Aprovação por e-mail';
if (!headers_sent()) {
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Referrer-Policy: no-referrer');
    header('X-Robots-Tag: noindex, nofollow');
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?> - GLPI</title>
    <style>
        *, *::before, *::after {
            box-sizing: border-box;
        }

        :root {
            --abm-primary: #2563eb;
            --abm-primary-dark: #1d4ed8;
            --abm-success: #0f766e;
            --abm-warning: #b45309;
            --abm-danger: #b91c1c;
            --abm-ink: #111827;
            --abm-muted: #667085;
            --abm-border: #d9e1ec;
            --abm-soft: #f6f8fb;
            --abm-surface: #ffffff;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            color: var(--abm-ink);
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background:
                radial-gradient(circle at top left, rgba(37, 99, 235, 0.18), transparent 34%),
                linear-gradient(135deg, #eef4ff 0%, #f7fafc 44%, #edf8f6 100%);
        }

        .abm-shell {
            width: min(100%, 680px);
        }

        .abm-container {
            position: relative;
            overflow: hidden;
            border-radius: 10px;
            border: 1px solid rgba(148, 163, 184, 0.34);
            background: rgba(255, 255, 255, 0.94);
            box-shadow: 0 26px 70px rgba(15, 23, 42, 0.16);
        }

        .abm-container::before {
            content: "";
            position: absolute;
            inset: 0 0 auto;
            height: 4px;
            background: linear-gradient(90deg, var(--abm-primary), var(--abm-success));
        }

        .abm-header {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 24px 28px 18px;
            border-bottom: 1px solid var(--abm-border);
        }

        .abm-logo {
            width: 44px;
            height: 44px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 9px;
            color: #fff;
            background: linear-gradient(135deg, var(--abm-primary), var(--abm-success));
            box-shadow: 0 12px 24px rgba(37, 99, 235, 0.24);
            flex: 0 0 auto;
        }

        .abm-brand {
            min-width: 0;
        }

        .abm-brand__eyebrow {
            margin: 0;
            color: var(--abm-muted);
            font-size: 12px;
            font-weight: 750;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .abm-brand__title {
            margin: 3px 0 0;
            font-size: 18px;
            font-weight: 780;
            letter-spacing: 0;
        }

        .abm-body {
            padding: 32px 28px 28px;
        }

        .abm-icon {
            width: 72px;
            height: 72px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            border-radius: 10px;
            font-size: 34px;
            font-weight: 800;
        }

        .abm-icon.success {
            color: var(--abm-success);
            background: rgba(15, 118, 110, 0.1);
            border: 1px solid rgba(15, 118, 110, 0.22);
        }

        .abm-icon.warning {
            color: var(--abm-warning);
            background: rgba(180, 83, 9, 0.1);
            border: 1px solid rgba(180, 83, 9, 0.24);
        }

        .abm-icon.error {
            color: var(--abm-danger);
            background: rgba(185, 28, 28, 0.1);
            border: 1px solid rgba(185, 28, 28, 0.22);
        }

        .abm-icon.info {
            color: var(--abm-primary);
            background: rgba(37, 99, 235, 0.1);
            border: 1px solid rgba(37, 99, 235, 0.22);
        }

        .abm-title {
            margin: 0 0 10px;
            color: var(--abm-ink);
            font-size: 24px;
            line-height: 1.2;
            font-weight: 780;
            text-align: center;
            letter-spacing: 0;
        }

        .abm-message {
            max-width: 500px;
            margin: 0 auto 20px;
            color: var(--abm-muted);
            font-size: 15px;
            line-height: 1.6;
            text-align: center;
        }

        .abm-subtle {
            color: #98a2b3;
            font-size: 13px;
        }

        .abm-footer {
            padding: 16px 28px;
            border-top: 1px solid var(--abm-border);
            background: var(--abm-soft);
            text-align: center;
        }

        .abm-footer p {
            margin: 0;
            color: #98a2b3;
            font-size: 12px;
        }

        .abm-ticket-info {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: max-content;
            max-width: 100%;
            margin: 0 auto 22px;
            padding: 9px 13px;
            border: 1px solid rgba(37, 99, 235, 0.2);
            border-radius: 8px;
            background: rgba(37, 99, 235, 0.08);
            color: var(--abm-primary-dark);
            font-size: 14px;
            font-weight: 700;
        }

        .abm-form {
            display: grid;
            gap: 18px;
        }

        .abm-summary {
            display: grid;
            gap: 10px;
            margin: 0 0 22px;
            padding: 16px;
            border: 1px solid rgba(37, 99, 235, 0.18);
            border-radius: 10px;
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.08), rgba(15, 118, 110, 0.06));
        }

        .abm-summary__eyebrow {
            margin: 0;
            color: var(--abm-primary-dark);
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }

        .abm-summary__title {
            margin: 0;
            color: var(--abm-ink);
            font-size: 18px;
            font-weight: 780;
            line-height: 1.35;
            letter-spacing: 0;
            overflow-wrap: anywhere;
        }

        .abm-summary__meta {
            margin: 0;
            color: var(--abm-muted);
            font-size: 13px;
        }

        .abm-confirm-box {
            display: grid;
            gap: 14px;
            margin-top: 22px;
            padding: 16px;
            border: 1px solid var(--abm-border);
            border-radius: 10px;
            background: var(--abm-soft);
        }

        .abm-confirm-box p {
            margin: 0;
            color: var(--abm-muted);
            font-size: 14px;
            line-height: 1.55;
            text-align: center;
        }

        .abm-form-group {
            display: grid;
            gap: 8px;
        }

        .abm-form-group label {
            color: #344054;
            font-size: 14px;
            font-weight: 720;
        }

        .abm-form-group textarea,
        .abm-form-group input[type="text"],
        .abm-form-group input[type="number"] {
            width: 100%;
            min-height: 46px;
            padding: 12px 14px;
            border: 1px solid var(--abm-border);
            border-radius: 8px;
            background: #fff;
            color: var(--abm-ink);
            font: inherit;
            resize: vertical;
            transition: border-color 0.16s ease, box-shadow 0.16s ease;
        }

        .abm-form-group textarea:focus,
        .abm-form-group input:focus {
            outline: none;
            border-color: var(--abm-primary);
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.12);
        }

        .abm-form-error {
            margin-bottom: 16px;
            padding: 12px 14px;
            border: 1px solid rgba(185, 28, 28, 0.18);
            border-radius: 8px;
            background: rgba(185, 28, 28, 0.08);
            color: var(--abm-danger);
            font-size: 14px;
            font-weight: 650;
        }

        .abm-actions {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 4px;
        }

        .abm-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 46px;
            width: 100%;
            padding: 12px 18px;
            border: 0;
            border-radius: 8px;
            color: #fff;
            font: inherit;
            font-size: 15px;
            font-weight: 780;
            text-decoration: none;
            cursor: pointer;
            transition: transform 0.16s ease, box-shadow 0.16s ease, filter 0.16s ease;
        }

        .abm-btn:hover {
            transform: translateY(-1px);
            filter: brightness(1.03);
            box-shadow: 0 12px 22px rgba(15, 23, 42, 0.16);
        }

        .abm-btn-danger {
            background: linear-gradient(135deg, #dc2626, #b91c1c);
        }

        .abm-btn-primary {
            background: linear-gradient(135deg, var(--abm-primary), var(--abm-primary-dark));
        }

        .abm-btn-success {
            background: linear-gradient(135deg, #0f766e, #0d9488);
        }

        .abm-stars {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin: 12px 0 6px;
            direction: rtl;
        }

        .abm-stars input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .abm-stars label {
            width: 46px;
            height: 46px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid var(--abm-border);
            border-radius: 8px;
            background: #fff;
            color: #cbd5e1;
            font-size: 26px;
            cursor: pointer;
            transition: color 0.15s ease, border-color 0.15s ease, background 0.15s ease, transform 0.15s ease;
        }

        .abm-stars label:hover,
        .abm-stars label:hover ~ label,
        .abm-stars input:checked ~ label {
            color: #f59e0b;
            border-color: rgba(245, 158, 11, 0.35);
            background: rgba(245, 158, 11, 0.08);
            transform: translateY(-1px);
        }

        @media (max-width: 560px) {
            body {
                padding: 12px;
            }

            .abm-header,
            .abm-body,
            .abm-footer {
                padding-left: 20px;
                padding-right: 20px;
            }

            .abm-title {
                font-size: 21px;
            }

            .abm-stars {
                gap: 5px;
            }

            .abm-stars label {
                width: 40px;
                height: 40px;
                font-size: 23px;
            }
        }
    </style>
</head>
<body>
    <main class="abm-shell">
        <section class="abm-container">
            <header class="abm-header">
                <div class="abm-logo" aria-hidden="true">&#9993;</div>
                <div class="abm-brand">
                    <p class="abm-brand__eyebrow">GLPI</p>
                    <h1 class="abm-brand__title">Aprovação por e-mail</h1>
                </div>
            </header>
            <div class="abm-body">
                <?= $pageContent ?? '' ?>
            </div>
            <footer class="abm-footer">
                <p>Processado com segurança pelo GLPI</p>
            </footer>
        </section>
    </main>
</body>
</html>
