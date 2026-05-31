<?php
/**
 * Shared layout for Approval By Mail public pages.
 *
 * Expected variables:
 * - $pageTitle (string)
 * - $pageContent (string)
 */

$pageTitle = $pageTitle ?? __('Aprovação por e-mail', 'mailaprove');

global $CFG_GLPI;
$abmLangCode = (string) ($_SESSION['glpilanguage'] ?? ($CFG_GLPI['language'] ?? 'pt_BR'));
$abmHtmlLang = str_replace('_', '-', $abmLangCode !== '' ? $abmLangCode : 'pt_BR');

if (!headers_sent()) {
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Referrer-Policy: no-referrer');
    header('X-Robots-Tag: noindex, nofollow');
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($abmHtmlLang, ENT_QUOTES, 'UTF-8') ?>">
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
            border-radius: 18px;
            border: 1px solid rgba(148, 163, 184, 0.28);
            background: var(--abm-surface);
            box-shadow: 0 30px 80px rgba(30, 41, 99, 0.26), 0 4px 12px rgba(15, 23, 42, 0.08);
        }

        .abm-header {
            position: relative;
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 30px 30px 28px;
            color: #fff;
            background:
                radial-gradient(circle at 88% -20%, rgba(255, 255, 255, 0.28), transparent 42%),
                linear-gradient(135deg, #4338ca 0%, #2563eb 52%, #0e7490 100%);
        }

        .abm-logo {
            width: 58px;
            height: 58px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 16px;
            font-size: 28px;
            color: #fff;
            background: rgba(255, 255, 255, 0.18);
            border: 1px solid rgba(255, 255, 255, 0.35);
            box-shadow: 0 10px 26px rgba(15, 23, 42, 0.28);
            backdrop-filter: blur(2px);
            flex: 0 0 auto;
        }

        .abm-brand {
            min-width: 0;
        }

        .abm-brand__eyebrow {
            margin: 0;
            color: rgba(255, 255, 255, 0.82);
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.16em;
            text-transform: uppercase;
        }

        .abm-brand__title {
            margin: 4px 0 0;
            font-size: 23px;
            font-weight: 850;
            letter-spacing: -0.01em;
            color: #fff;
        }

        .abm-body {
            padding: 38px 32px 32px;
        }

        .abm-icon {
            width: 92px;
            height: 92px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 22px;
            border-radius: 22px;
            font-size: 46px;
            font-weight: 800;
            box-shadow: 0 14px 30px rgba(15, 23, 42, 0.12);
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
            margin: 0 0 12px;
            color: var(--abm-ink);
            font-size: 29px;
            line-height: 1.15;
            font-weight: 850;
            text-align: center;
            letter-spacing: -0.02em;
        }

        .abm-message {
            max-width: 520px;
            margin: 0 auto 22px;
            color: var(--abm-muted);
            font-size: 16px;
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
            gap: 10px;
            min-height: 56px;
            width: 100%;
            padding: 15px 22px;
            border: 0;
            border-radius: 14px;
            color: #fff;
            font: inherit;
            font-size: 17px;
            font-weight: 850;
            letter-spacing: 0.01em;
            text-decoration: none;
            cursor: pointer;
            box-shadow: 0 12px 26px rgba(37, 99, 235, 0.28);
            transition: transform 0.16s ease, box-shadow 0.16s ease, filter 0.16s ease;
        }

        .abm-btn:hover {
            transform: translateY(-2px);
            filter: brightness(1.05);
            box-shadow: 0 18px 34px rgba(15, 23, 42, 0.24);
        }

        .abm-btn:active {
            transform: translateY(0);
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
            gap: 10px;
            margin: 14px 0 6px;
            direction: rtl;
        }

        .abm-stars input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .abm-stars label {
            width: 58px;
            height: 58px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1.5px solid var(--abm-border);
            border-radius: 14px;
            background: var(--abm-surface);
            color: #cbd5e1;
            font-size: 34px;
            cursor: pointer;
            transition: color 0.15s ease, border-color 0.15s ease, background 0.15s ease, transform 0.15s ease, box-shadow 0.15s ease;
        }

        .abm-stars label:hover,
        .abm-stars label:hover ~ label,
        .abm-stars input:checked ~ label {
            color: #f59e0b;
            border-color: rgba(245, 158, 11, 0.55);
            background: rgba(245, 158, 11, 0.12);
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(245, 158, 11, 0.25);
        }

        .abm-stars input:focus-visible + label {
            outline: 2px solid var(--abm-primary);
            outline-offset: 2px;
            color: #f59e0b;
        }

        .abm-rating-live {
            min-height: 18px;
            margin: 4px 0 0;
            text-align: center;
            font-size: 13px;
            font-weight: 700;
            color: var(--abm-primary-dark);
        }

        .abm-sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }

        .abm-btn[disabled] {
            cursor: progress;
            opacity: 0.72;
            transform: none;
            box-shadow: none;
            filter: none;
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
                font-size: 24px;
            }

            .abm-stars {
                gap: 7px;
            }

            .abm-stars label {
                width: 48px;
                height: 48px;
                font-size: 28px;
            }
        }

        @media (prefers-color-scheme: dark) {
            :root {
                --abm-primary: #3b82f6;
                --abm-primary-dark: #93c5fd;
                --abm-ink: #e5e7eb;
                --abm-muted: #9aa4b2;
                --abm-border: #2b3344;
                --abm-soft: #161b26;
                --abm-surface: #1e2430;
            }

            body {
                color: var(--abm-ink);
                background:
                    radial-gradient(circle at top left, rgba(59, 130, 246, 0.22), transparent 36%),
                    linear-gradient(135deg, #0b1220 0%, #0e1525 50%, #0b1a18 100%);
            }

            .abm-container {
                background: var(--abm-surface);
                border-color: var(--abm-border);
                box-shadow: 0 26px 70px rgba(0, 0, 0, 0.5);
            }

            .abm-title {
                color: var(--abm-ink);
            }

            .abm-form-group label {
                color: #cbd5e1;
            }

            .abm-form-group textarea,
            .abm-form-group input[type="text"],
            .abm-form-group input[type="number"],
            .abm-stars label {
                background: #141a24;
                color: var(--abm-ink);
                border-color: var(--abm-border);
            }

            .abm-stars label {
                color: #475569;
            }

            .abm-footer {
                background: var(--abm-soft);
            }

            .abm-confirm-box {
                background: var(--abm-soft);
                border-color: var(--abm-border);
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
                    <h1 class="abm-brand__title"><?= htmlspecialchars(__('Aprovação por e-mail', 'mailaprove'), ENT_QUOTES, 'UTF-8') ?></h1>
                </div>
            </header>
            <div class="abm-body">
                <?= $pageContent ?? '' ?>
            </div>
            <footer class="abm-footer">
                <p><?= htmlspecialchars(__('Processado com segurança pelo GLPI', 'mailaprove'), ENT_QUOTES, 'UTF-8') ?></p>
            </footer>
        </section>
    </main>
    <script>
        (function () {
            // Pesquisa de satisfação: feedback da nota escolhida (mouse + teclado)
            document.querySelectorAll('.abm-stars').forEach(function (group) {
                var scope = group.closest('.abm-form-group') || group.parentElement;
                var live = scope ? scope.querySelector('[data-abm-rating-live]') : null;
                group.addEventListener('change', function () {
                    var checked = group.querySelector('input:checked');
                    if (!checked || !live) {
                        return;
                    }
                    var label = group.querySelector('label[for="' + checked.id + '"]');
                    var text = label ? (label.getAttribute('title') || label.getAttribute('aria-label') || '') : '';
                    if (text) {
                        live.textContent = text;
                    }
                });
            });

            // Evita envio duplicado e sinaliza progresso ao confirmar a ação
            document.querySelectorAll('form.abm-form').forEach(function (form) {
                form.addEventListener('submit', function () {
                    var btn = form.querySelector('button[type="submit"]');
                    if (!btn || btn.dataset.abmBusy) {
                        return;
                    }
                    btn.dataset.abmBusy = '1';
                    // Mantém a submissão nativa e bloqueia novos cliques logo em seguida.
                    window.setTimeout(function () {
                        btn.disabled = true;
                    }, 0);
                });
            });
        })();
    </script>
</body>
</html>
