<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @if(app()->getLocale() === 'ar') dir="rtl" @endif>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="Content-Security-Policy" content="default-src 'self'; style-src 'self' 'unsafe-inline'; script-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self' data:; frame-ancestors 'self'; base-uri 'self'; form-action 'self'">
    <meta name="referrer" content="strict-origin-when-cross-origin">
    <title>{{ $title ?? __('PV Module') }} · {{ config('app.name', 'Application') }}</title>
    <style>
        :root {
            --pvm-bg: #f4f6fb;
            --pvm-surface: #ffffff;
            --pvm-surface-alt: #f8fafc;
            --pvm-border: #e5eaf1;
            --pvm-border-strong: #cbd5e1;
            --pvm-text: #0f172a;
            --pvm-text-soft: #475569;
            --pvm-text-muted: #94a3b8;
            --pvm-primary: #1d4ed8;
            --pvm-primary-strong: #1e3a8a;
            --pvm-primary-soft: #eff6ff;
            --pvm-success: #059669;
            --pvm-success-strong: #047857;
            --pvm-success-soft: #ecfdf5;
            --pvm-danger: #dc2626;
            --pvm-danger-strong: #b91c1c;
            --pvm-danger-soft: #fef2f2;
            --pvm-warning: #d97706;
            --pvm-warning-soft: #fffbeb;
            --pvm-info: #0e7490;
            --pvm-info-soft: #ecfeff;
            --pvm-radius: 12px;
            --pvm-radius-sm: 8px;
            --pvm-shadow-sm: 0 1px 2px rgb(15 23 42 / .05);
            --pvm-shadow: 0 1px 3px rgb(15 23 42 / .06), 0 6px 16px rgb(15 23 42 / .06);
            --pvm-shadow-lg: 0 4px 12px rgb(15 23 42 / .10);
            --pvm-font: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", sans-serif;
            --pvm-nav-h: 60px;
        }
        * { box-sizing: border-box; }
        html { -webkit-text-size-adjust: 100%; }
        body {
            margin: 0;
            font-family: var(--pvm-font);
            font-size: 14px;
            line-height: 1.55;
            background: var(--pvm-bg);
            color: var(--pvm-text);
        }
        a { color: var(--pvm-primary); }
        :focus-visible { outline: 2px solid var(--pvm-primary); outline-offset: 2px; border-radius: 4px; }
        ::selection { background: var(--pvm-primary); color: #fff; }

        /* ---------- Barre de navigation ---------- */
        .pvm-nav {
            position: sticky; top: 0; z-index: 40;
            background: #0f172a;
            color: #fff;
            min-height: var(--pvm-nav-h);
            display: flex; align-items: center; gap: 4px;
            padding: 0 clamp(12px, 3vw, 28px);
        }
        .pvm-nav .pvm-brand {
            display: inline-flex; align-items: center; gap: 10px;
            font-weight: 700; font-size: 15px; color: #fff; text-decoration: none;
            margin-inline-end: 18px; letter-spacing: .2px;
        }
        .pvm-brand-mark {
            width: 28px; height: 28px; border-radius: 8px;
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            display: inline-flex; align-items: center; justify-content: center;
            font-size: 13px; color: #fff; flex: none;
            box-shadow: inset 0 0 0 1px rgb(255 255 255 / .25);
        }
        .pvm-nav a.pvm-nav-link {
            color: #cbd5e1; text-decoration: none; font-size: 14px;
            padding: 8px 12px; border-radius: var(--pvm-radius-sm);
            transition: background .15s, color .15s;
        }
        .pvm-nav a.pvm-nav-link:hover { background: #1e293b; color: #fff; }
        .pvm-nav a.pvm-nav-link.is-active { background: #1e40af; color: #fff; font-weight: 600; }
        .pvm-nav-right { margin-inline-start: auto; display: flex; align-items: center; gap: 10px; }
        .pvm-bell-wrap { position: relative; }
        .pvm-bell {
            position: relative;
            width: 38px; height: 38px;
            border-radius: 999px;
            border: 0;
            background: #1e293b;
            color: #e2e8f0;
            font-size: 18px; line-height: 1;
            cursor: pointer;
            display: inline-flex; align-items: center; justify-content: center;
            transition: background .15s;
        }
        .pvm-bell:hover { background: #334155; color: #fff; }
        .pvm-bell-count {
            position: absolute; inset-inline-end: -3px; top: -3px;
            min-width: 17px; height: 17px; padding: 0 4px;
            border-radius: 999px; background: #ef4444; color: #fff;
            font-size: 11px; font-weight: 700;
            display: inline-flex; align-items: center; justify-content: center;
        }
        .pvm-bell-panel {
            position: absolute; inset-inline-end: 0; top: calc(100% + 10px);
            width: min(340px, 80vw);
            background: #fff; color: var(--pvm-text);
            border: 1px solid var(--pvm-border); border-radius: var(--pvm-radius);
            box-shadow: var(--pvm-shadow-lg);
            padding: 8px;
            display: none;
            z-index: 60;
        }
        .pvm-bell-panel.is-open { display: block; }
        .pvm-bell-panel-head {
            display: flex; align-items: center; justify-content: space-between; gap: 8px;
            padding: 6px 8px 10px; border-bottom: 1px solid var(--pvm-border);
            font-weight: 700; font-size: 13px;
        }
        .pvm-bell-panel-head .pvm-bell-mark {
            background: none; border: 0; color: var(--pvm-primary);
            font-size: 12px; cursor: pointer; padding: 0;
        }
        .pvm-bell-list { list-style: none; margin: 0; padding: 6px 0 0; max-height: 340px; overflow-y: auto; }
        .pvm-bell-list li { margin-bottom: 2px; }
        .pvm-bell-list a {
            display: block; padding: 9px 10px; border-radius: var(--pvm-radius-sm);
            text-decoration: none; color: var(--pvm-text); font-size: 13px;
            position: relative;
        }
        .pvm-bell-list a:hover { background: var(--pvm-surface-alt); }
        .pvm-bell-list strong { display: block; font-weight: 600; padding-inline-end: 12px; }
        .pvm-bell-list .pvm-bell-msg { color: var(--pvm-text-soft); font-size: 12px; margin-top: 2px; }
        .pvm-bell-list .pvm-bell-time { color: var(--pvm-text-muted); font-size: 11px; margin-top: 3px; }
        .pvm-bell-list li.is-unread::before {
            content: ""; position: absolute;
        }
        .pvm-bell-list .is-unread { box-shadow: inset 3px 0 0 var(--pvm-primary); }
        .pvm-bell-empty { padding: 18px 10px; text-align: center; color: var(--pvm-text-muted); font-size: 13px; }
        .pvm-user-chip {
            display: inline-flex; align-items: center; gap: 8px;
            background: #1e293b; border: 1px solid #334155; color: #e2e8f0;
            padding: 4px 12px 4px 6px; border-radius: 999px; font-size: 13px;
        }
        .pvm-user-chip .pvm-avatar {
            width: 24px; height: 24px; border-radius: 50%;
            background: linear-gradient(135deg, #3b82f6, #6366f1);
            display: inline-flex; align-items: center; justify-content: center;
            font-size: 11px; font-weight: 700; color: #fff; text-transform: uppercase;
        }
        .pvm-nav-toggle { display: none; background: none; border: 0; color: #e2e8f0; font-size: 20px; cursor: pointer; }

        @media (max-width: 720px) {
            .pvm-nav { flex-wrap: wrap; padding-block: 8px; }
            .pvm-nav-toggle { display: inline-flex; }
            .pvm-nav-links {
                display: none; flex-direction: column; width: 100%;
                gap: 2px; padding: 8px 0 4px;
            }
            .pvm-nav-links.is-open { display: flex; }
            .pvm-nav-right { margin-inline-start: auto; }
        }

        /* ---------- Conteneur ---------- */
        .pvm-container { max-width: 1100px; margin: 0 auto; padding: 24px 16px 56px; }
        .pvm-page-head {
            display: flex; align-items: flex-start; justify-content: space-between;
            gap: 16px; flex-wrap: wrap; margin-bottom: 20px;
        }
        .pvm-title { margin: 0; font-size: 22px; font-weight: 700; letter-spacing: -.2px; }
        .pvm-subtitle { color: var(--pvm-text-soft); font-size: 14px; margin-top: 4px; }

        /* ---------- Cartes ---------- */
        .pvm-card {
            background: var(--pvm-surface);
            border: 1px solid var(--pvm-border);
            border-radius: var(--pvm-radius);
            box-shadow: var(--pvm-shadow);
            padding: 22px 24px;
            margin-bottom: 20px;
        }
        .pvm-card + .pvm-card { margin-top: 0; }
        .pvm-card-header {
            display: flex; align-items: center; justify-content: space-between;
            gap: 12px; flex-wrap: wrap;
            margin: -22px -24px 18px; padding: 14px 24px;
            background: var(--pvm-surface-alt);
            border-bottom: 1px solid var(--pvm-border);
            border-radius: var(--pvm-radius) var(--pvm-radius) 0 0;
        }
        .pvm-card-header h2, .pvm-card-header h3 { margin: 0; font-size: 15px; font-weight: 700; }
        .pvm-card-title { margin: 0 0 14px; font-size: 16px; font-weight: 700; }
        .pvm-muted { color: var(--pvm-text-muted); font-size: 13px; }
        .pvm-soft { color: var(--pvm-text-soft); }

        /* ---------- Alertes ---------- */
        .pvm-alert {
            position: relative;
            padding: 11px 42px 11px 14px;
            border-radius: var(--pvm-radius-sm);
            margin-bottom: 16px;
            font-size: 14px;
            border: 1px solid transparent;
        }
        .pvm-alert .pvm-alert-close {
            position: absolute; inset-inline-end: 10px; top: 50%; transform: translateY(-50%);
            background: none; border: 0; cursor: pointer; font-size: 15px; line-height: 1;
            color: inherit; opacity: .55; padding: 4px;
        }
        .pvm-alert .pvm-alert-close:hover { opacity: 1; }
        .pvm-alert-success { background: var(--pvm-success-soft); border-color: #a7f3d0; color: #065f46; }
        .pvm-alert-error { background: var(--pvm-danger-soft); border-color: #fecaca; color: #991b1b; }
        .pvm-alert-warning { background: var(--pvm-warning-soft); border-color: #fde68a; color: #92400e; }
        .pvm-alert-info { background: var(--pvm-info-soft); border-color: #cffafe; color: #155e75; }
        .pvm-action-hint { margin: 8px 0 0; font-size: 13px; color: inherit; }
        .pvm-action-hint a { color: var(--pvm-primary); font-weight: 700; text-decoration: none; }
        .pvm-action-hint a:hover { text-decoration: underline; }

        /* ---------- Boutons ---------- */
        .pvm-btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 6px;
            padding: 9px 16px;
            border: 1px solid transparent; border-radius: var(--pvm-radius-sm);
            background: var(--pvm-primary); color: #fff;
            font-size: 14px; font-weight: 600; font-family: inherit;
            cursor: pointer; text-decoration: none; line-height: 1.2;
            transition: background .15s, border-color .15s, transform .05s, box-shadow .15s;
            box-shadow: var(--pvm-shadow-sm);
        }
        .pvm-btn:hover { background: var(--pvm-primary-strong); }
        .pvm-btn:active { transform: translateY(1px); }
        .pvm-btn[disabled] { opacity: .5; pointer-events: none; }
        .pvm-btn-secondary { background: #64748b; }
        .pvm-btn-secondary:hover { background: #475569; }
        .pvm-btn-success { background: var(--pvm-success); box-shadow: inset 0 0 0 1px rgb(255 255 255/.15); }
        .pvm-btn-success:hover { background: var(--pvm-success-strong); }
        .pvm-btn-danger { background: var(--pvm-danger); }
        .pvm-btn-danger:hover { background: var(--pvm-danger-strong); }
        .pvm-btn-line {
            background: var(--pvm-surface); color: var(--pvm-primary-strong);
            border-color: var(--pvm-border-strong);
            box-shadow: none;
        }
        .pvm-btn-line:hover { background: var(--pvm-primary-soft); border-color: var(--pvm-primary); }
        .pvm-btn-danger-line {
            background: var(--pvm-surface); color: var(--pvm-danger);
            border-color: #fecaca; box-shadow: none;
        }
        .pvm-btn-danger-line:hover { background: var(--pvm-danger-soft); border-color: var(--pvm-danger); }
        .pvm-btn-sm { padding: 5px 10px; font-size: 13px; border-radius: 6px; }
        .pvm-btn-block { width: 100%; }

        /* ---------- Tableaux ---------- */
        .pvm-table-wrap { overflow-x: auto; border: 1px solid var(--pvm-border); border-radius: var(--pvm-radius-sm); }
        .pvm-table { width: 100%; border-collapse: collapse; font-size: 14px; background: var(--pvm-surface); }
        .pvm-table th, .pvm-table td {
            text-align: start; padding: 11px 12px;
            border-bottom: 1px solid var(--pvm-border);
            vertical-align: middle;
        }
        .pvm-table tbody tr:last-child td { border-bottom: 0; }
        .pvm-table th {
            background: var(--pvm-surface-alt); color: var(--pvm-text-soft);
            font-size: 11px; text-transform: uppercase; letter-spacing: .5px; font-weight: 700;
            white-space: nowrap;
        }
        .pvm-table tbody tr:hover td { background: #fbfcfe; }
        .pvm-table .pvm-row-muted td { opacity: .65; }

        /* ---------- Badges ---------- */
        .pvm-badge {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 4px 10px; border-radius: 999px;
            font-size: 12px; font-weight: 600; white-space: nowrap;
            border: 1px solid transparent;
        }
        .pvm-badge::before {
            content: ""; width: 6px; height: 6px; border-radius: 50%;
            background: currentColor; flex: none;
        }
        .pvm-badge-brouillon { background: #f1f5f9; color: #475569; border-color: #e2e8f0; }
        .pvm-badge-en_attente { background: var(--pvm-warning-soft); color: #92400e; border-color: #fde68a; }
        .pvm-badge-valide { background: var(--pvm-success-soft); color: #065f46; border-color: #a7f3d0; }
        .pvm-badge-rejete { background: var(--pvm-danger-soft); color: #991b1b; border-color: #fecaca; }
        .pvm-badge-en_attente::before, .pvm-badge-brouillon::before { animation: pvm-pulse 2s infinite; }
        @keyframes pvm-pulse { 0%,100% { opacity: 1; } 50% { opacity: .35; } }

        /* ---------- Formulaires ---------- */
        .pvm-form label { display: block; font-weight: 600; margin: 14px 0 4px; font-size: 13px; color: var(--pvm-text-soft); }
        .pvm-form input[type=text],
        .pvm-form input[type=datetime-local],
        .pvm-form input[type=number],
        .pvm-form input[type=date],
        .pvm-form input[type=color],
        .pvm-form select,
        .pvm-form textarea {
            width: 100%;
            padding: 9px 12px;
            border: 1px solid var(--pvm-border-strong);
            border-radius: var(--pvm-radius-sm);
            font-size: 14px; font-family: inherit; color: var(--pvm-text);
            background: var(--pvm-surface);
            transition: border-color .15s, box-shadow .15s;
        }
        .pvm-form input:focus, .pvm-form select:focus, .pvm-form textarea:focus {
            outline: none; border-color: var(--pvm-primary);
            box-shadow: 0 0 0 3px rgb(29 78 216 / .15);
        }
        .pvm-form textarea { min-height: 180px; resize: vertical; line-height: 1.6; }
        .pvm-form input[type=checkbox], .pvm-form input[type=radio] {
            width: 16px; height: 16px; accent-color: var(--pvm-primary); flex: none;
        }
        .pvm-field { margin-bottom: 4px; }
        .pvm-field-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 14px; }
        .pvm-checkboxes {
            display: grid; grid-template-columns: repeat(auto-fill, minmax(210px, 1fr));
            gap: 6px; margin-top: 8px; max-height: 240px; overflow-y: auto;
            background: var(--pvm-surface-alt); border: 1px solid var(--pvm-border);
            border-radius: var(--pvm-radius-sm); padding: 10px;
        }
        .pvm-checkboxes label { font-weight: 400; font-size: 13px; display: flex; gap: 8px; align-items: center; margin: 0; }
        .pvm-help { color: var(--pvm-text-muted); font-size: 12px; margin-top: 6px; }
        .pvm-errors { color: var(--pvm-danger); font-size: 13px; margin-top: 5px; }
        .pvm-errors li { margin-inline-start: 18px; }

        /* ---------- Actions ---------- */
        .pvm-actions { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 18px; align-items: center; }
        .pvm-actions-sticky {
            position: sticky; bottom: 12px; z-index: 20;
            background: var(--pvm-surface); border: 1px solid var(--pvm-border);
            border-radius: var(--pvm-radius); box-shadow: var(--pvm-shadow-lg);
            padding: 12px 14px; display: flex; gap: 10px; flex-wrap: wrap; justify-content: flex-end;
        }

        /* ---------- Signature ---------- */
        .pvm-signature-canvas {
            border: 2px dashed var(--pvm-border-strong);
            border-radius: var(--pvm-radius);
            background: #fff; touch-action: none; display: block; width: 100%; max-width: 560px;
        }
        .pvm-signature-preview img { max-height: 90px; background: #fff; border: 1px solid var(--pvm-border); border-radius: var(--pvm-radius-sm); padding: 6px; }

        /* ---------- Fil d'Ariane ---------- */
        .pvm-breadcrumb {
            display: flex; align-items: center; gap: 6px; flex-wrap: wrap;
            font-size: 13px; color: var(--pvm-text-muted); margin-bottom: 14px;
        }
        .pvm-breadcrumb a { color: var(--pvm-text-soft); text-decoration: none; }
        .pvm-breadcrumb a:hover { color: var(--pvm-primary); }
        .pvm-breadcrumb-sep { opacity: .6; }

        /* ---------- Progression ---------- */
        .pvm-progress { height: 8px; background: #e2e8f0; border-radius: 999px; overflow: hidden; }
        .pvm-progress > span { display: block; height: 100%; background: linear-gradient(90deg, #3b82f6, var(--pvm-primary)); border-radius: 999px; transition: width .4s ease; }
        .pvm-progress.complete > span { background: linear-gradient(90deg, #10b981, var(--pvm-success)); }

        /* ---------- Timeline ---------- */
        .pvm-timeline { list-style: none; margin: 0; padding: 0; }
        .pvm-timeline li { position: relative; padding: 0 0 18px 26px; }
        .pvm-timeline li::before {
            content: ""; position: absolute; inset-inline-start: 6px; top: 20px; bottom: -2px;
            width: 2px; background: var(--pvm-border-strong);
        }
        .pvm-timeline li:last-child { padding-bottom: 0; }
        .pvm-timeline li:last-child::before { display: none; }
        .pvm-timeline-dot {
            position: absolute; inset-inline-start: 0; top: 2px;
            width: 14px; height: 14px; border-radius: 50%;
            background: var(--pvm-surface); border: 2px solid var(--pvm-border-strong);
            display: flex; align-items: center; justify-content: center;
        }
        .pvm-timeline-dot.ok { border-color: var(--pvm-success); background: var(--pvm-success); }
        .pvm-timeline-dot.no { border-color: var(--pvm-danger); background: var(--pvm-danger); }
        .pvm-timeline-dot.wait { border-color: var(--pvm-warning); background: var(--pvm-warning); }

        /* ---------- Onglets-filter ---------- */
        .pvm-tabs { display: flex; gap: 6px; flex-wrap: wrap; background: var(--pvm-surface); border: 1px solid var(--pvm-border); border-radius: 999px; padding: 4px; width: fit-content; max-width: 100%; }
        .pvm-tabs a {
            text-decoration: none; color: var(--pvm-text-soft); font-weight: 600; font-size: 13px;
            padding: 6px 14px; border-radius: 999px; display: inline-flex; align-items: center; gap: 7px;
            border: 1px solid transparent; transition: all .15s;
        }
        .pvm-tabs a:hover { color: var(--pvm-primary); }
        .pvm-tabs a.is-active { background: var(--pvm-primary); color: #fff; box-shadow: var(--pvm-shadow-sm); }
        .pvm-tabs .pvm-tab-count { font-size: 11px; background: #e2e8f0; color: var(--pvm-text-soft); border-radius: 999px; padding: 1px 7px; font-weight: 700; }
        .pvm-tabs a.is-active .pvm-tab-count { background: rgb(255 255 255/.25); color: #fff; }

        /* ---------- Speed dial (actions flottantes) ---------- */
        .pvm-speed-dial { position: fixed; inset-inline-end: 22px; bottom: 22px; z-index: 70; display: flex; flex-direction: column; align-items: flex-end; gap: 10px; }
        .pvm-speed-dial-main {
            width: 54px; height: 54px; border-radius: 999px; border: 0;
            background: var(--pvm-primary); color: #fff; cursor: pointer;
            display: inline-flex; align-items: center; justify-content: center;
            font-size: 24px; line-height: 1; box-shadow: var(--pvm-shadow-lg);
            transition: background .15s, transform .2s;
        }
        .pvm-speed-dial-main:hover { background: var(--pvm-primary-strong); }
        .pvm-speed-dial-main.is-open { transform: rotate(45deg); }
        .pvm-speed-list {
            display: flex; flex-direction: column; align-items: flex-end; gap: 8px;
            opacity: 0; visibility: hidden; transform: translateY(8px);
            transition: opacity .18s, transform .18s, visibility .18s;
        }
        .pvm-speed-dial.is-open .pvm-speed-list { opacity: 1; visibility: visible; transform: translateY(0); }
        .pvm-speed-item {
            display: inline-flex; align-items: center; gap: 8px;
            background: var(--pvm-surface); color: var(--pvm-text);
            border: 1px solid var(--pvm-border); border-radius: 999px;
            padding: 8px 14px; font-size: 13px; font-weight: 600;
            text-decoration: none; box-shadow: var(--pvm-shadow);
            transition: border-color .15s, color .15s;
        }
        .pvm-speed-item:hover { border-color: var(--pvm-primary); color: var(--pvm-primary); }
        .pvm-speed-item.is-danger { color: var(--pvm-danger); }
        .pvm-speed-item.is-danger:hover { border-color: var(--pvm-danger); color: var(--pvm-danger); }
        [dir="rtl"] .pvm-speed-dial { left: 22px; right: auto; }

        /* ---------- Speed dial par ligne (liste Mes PV) ---------- */
        .pvm-row-dial { position: relative; display: inline-flex; }
        .pvm-row-dial-main {
            width: 34px; height: 34px; border-radius: 999px;
            border: 1px solid var(--pvm-border-strong);
            background: var(--pvm-surface); color: var(--pvm-text-soft);
            cursor: pointer; font-size: 16px; line-height: 1;
            display: inline-flex; align-items: center; justify-content: center;
            transition: border-color .15s, color .15s, background .15s;
        }
        .pvm-row-dial-main:hover { border-color: var(--pvm-primary); color: var(--pvm-primary); background: var(--pvm-primary-soft); }
        .pvm-row-dial-main.is-open { transform: rotate(90deg); }
        .pvm-row-dial-list {
            position: absolute; inset-inline-end: 0; bottom: calc(100% + 6px);
            min-width: 132px; background: var(--pvm-surface);
            border: 1px solid var(--pvm-border); border-radius: var(--pvm-radius);
            box-shadow: var(--pvm-shadow-lg); padding: 6px; z-index: 60;
            display: flex; flex-direction: column; gap: 2px;
            opacity: 0; visibility: hidden; transform: translateY(4px);
            transition: opacity .15s, transform .15s, visibility .15s;
        }
        .pvm-row-dial.is-open .pvm-row-dial-list { opacity: 1; visibility: visible; transform: translateY(0); }
        .pvm-row-dial-item {
            display: block; padding: 7px 10px; border-radius: var(--pvm-radius-sm);
            font-size: 13px; font-weight: 600; color: var(--pvm-text);
            text-decoration: none; white-space: nowrap;
        }
        .pvm-row-dial-item:hover { background: var(--pvm-primary-soft); color: var(--pvm-primary); }

        /* ---------- Menu hamburger ---------- */
        .pvm-hamburger-wrap { position: relative; }
        .pvm-hamburger {
            background: var(--pvm-surface); color: var(--pvm-text-soft);
            border: 1px solid var(--pvm-border-strong); border-radius: var(--pvm-radius-sm);
            width: 38px; height: 38px; cursor: pointer;
            display: inline-flex; align-items: center; justify-content: center;
            font-size: 16px; line-height: 1; transition: border-color .15s, color .15s;
        }
        .pvm-hamburger:hover { border-color: var(--pvm-primary); color: var(--pvm-primary); }
        .pvm-hamburger-menu {
            position: absolute; inset-inline-end: 0; top: calc(100% + 6px);
            min-width: 220px; background: var(--pvm-surface);
            border: 1px solid var(--pvm-border); border-radius: var(--pvm-radius);
            box-shadow: var(--pvm-shadow-lg); padding: 8px;
            display: none; z-index: 60;
        }
        .pvm-hamburger-menu.is-open { display: block; }
        .pvm-hamburger-menu-title {
            font-size: 11px; font-weight: 700; text-transform: uppercase;
            letter-spacing: .4px; color: var(--pvm-text-muted);
            padding: 6px 8px 8px; border-bottom: 1px solid var(--pvm-border);
            margin-bottom: 4px;
        }
        .pvm-hamburger-item { display: flex; gap: 8px; align-items: center; padding: 7px 8px; border-radius: var(--pvm-radius-sm); font-size: 13px; font-weight: 400; cursor: pointer; margin: 0; }
        .pvm-hamburger-item:hover { background: var(--pvm-surface-alt); }

        /* ---------- Misc ---------- */
        .pvm-empty {
            text-align: center; padding: 40px 20px; color: var(--pvm-text-muted); font-size: 14px;
            border: 1px dashed var(--pvm-border-strong); border-radius: var(--pvm-radius);
            background: var(--pvm-surface-alt);
        }
        .pvm-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 16px; }
        .pvm-stat { display: flex; align-items: center; gap: 12px; }
        .pvm-stat-icon {
            width: 40px; height: 40px; border-radius: 10px; flex: none;
            display: flex; align-items: center; justify-content: center; font-size: 18px;
            background: var(--pvm-primary-soft); color: var(--pvm-primary);
        }
        .pvm-stat-value { font-size: 20px; font-weight: 800; line-height: 1.1; }
        .pvm-stat-label { color: var(--pvm-text-muted); font-size: 12px; }
        .pvm-inline-form { display: inline; }
        .pvm-no-print { }
        .pvm-mt-0 { margin-top: 0; }

        /* ---------- RTL ---------- */
        [dir="rtl"] .pvm-table th, [dir="rtl"] .pvm-table td { text-align: right; }
        [dir="rtl"] .pvm-timeline li { padding-right: 26px; padding-left: 0; }
        [dir="rtl"] .pvm-progress > span { background: linear-gradient(-90deg, #3b82f6, var(--pvm-primary)); }

        /* ---------- Print ---------- */
        @media print {
            .pvm-nav, .pvm-no-print, .pvm-alert, .pvm-actions-sticky, .pvm-tabs { display: none !important; }
            body { background: #fff; }
            .pvm-container { padding: 0; max-width: none; }
            .pvm-card { box-shadow: none; border: 0; padding: 0; margin-bottom: 0; }

        }
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { animation-duration: .01ms !important; transition-duration: .01ms !important; }
        }
    </style>
    @stack('styles')
</head>
<body>
    <nav class="pvm-nav">
        <button type="button" class="pvm-nav-toggle" aria-label="Menu" data-pvm-nav-toggle>☰</button>
        <a href="{{ route('pv-module.index') }}" class="pvm-brand">
            <span class="pvm-brand-mark">PV</span>
            <span>{{ __('PV Module') }}</span>
        </a>
        <div class="pvm-nav-links" data-pvm-nav-links>
            <a href="{{ route('pv-module.index') }}" class="pvm-nav-link @if(request()->routeIs('pv-module.index') || request()->routeIs('pv-module.show.*')) is-active @endif">{{ __('Mes PV') }}</a>
            <a href="{{ route('pv-module.create') }}" class="pvm-nav-link @if(request()->routeIs('pv-module.create', 'pv-module.store*')) is-active @endif">{{ __('Nouveau PV') }}</a>
            <a href="{{ route('pv-module.templates.index') }}" class="pvm-nav-link @if(request()->routeIs('pv-module.templates.*')) is-active @endif">{{ __('Modèles de documents') }}</a>
            <a href="{{ route('pv-module.signature.index') }}" class="pvm-nav-link @if(request()->routeIs('pv-module.signature.*')) is-active @endif">{{ __('Ma signature') }}</a>
        </div>
        <div class="pvm-nav-right">
            @auth
                @php
                    $unreadNotifications = null;
                    if (method_exists(auth()->user(), 'unreadNotifications')) {
                        try {
                            $unreadNotifications = auth()->user()->unreadNotifications()->latest()->limit(12)->get();
                        } catch (\Throwable $e) {
                            $unreadNotifications = collect();
                        }
                    }
                @endphp
                @if ($unreadNotifications !== null)
                    <div class="pvm-bell-wrap">
                        <button type="button" class="pvm-bell" data-pvm-bell aria-label="{{ __('Notifications') }}">
                            @if ($unreadNotifications->count() > 0)
                                <span class="pvm-bell-count">{{ $unreadNotifications->count() }}</span>
                            @endif
                            &#128276;
                        </button>
                        <div class="pvm-bell-panel" data-pvm-bell-panel>
                            <div class="pvm-bell-panel-head">
                                <span>{{ __('Notifications') }}</span>
                                @if ($unreadNotifications->count() > 0)
                                    <form method="POST" action="{{ route('pv-module.notifications.read-all') }}">
                                        @csrf
                                        <button type="submit" class="pvm-bell-mark">{{ __('Tout marquer lu') }}</button>
                                    </form>
                                @endif
                            </div>
                            @if ($unreadNotifications->isEmpty())
                                <div class="pvm-bell-empty">{{ __('Aucune notification.') }}</div>
                            @else
                                <ul class="pvm-bell-list">
                                    @foreach ($unreadNotifications as $notification)
                                        @php
                                            $data = $notification->data ?? [];
                                            $title = $data['titre'] ?? __('PV');
                                            $type = $data['type'] ?? '';
                                            $msg = $type === 'pv_validation_request'
                                                ? __('Signature requise')
                                                : ($type === 'pv_rejected' ? __('Rejeté') : __('Validé et signé'));
                                            $url = !empty($data['url']) ? $data['url'] : route('pv-module.show', $data['pv_id'] ?? 0);
                                        @endphp
                                        <li class="is-unread">
                                            <a href="{{ $url }}">
                                                <strong>{{ $title }}</strong>
                                                <span class="pvm-bell-msg">{{ $msg }}</span>
                                                <span class="pvm-bell-time">{{ $notification->created_at?->diffForHumans() }}</span>
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                            <div style="padding:8px 6px 2px;border-top:1px solid var(--pvm-border);">
                                <a href="{{ route('pv-module.notifications.index') }}" style="font-size:12px;text-decoration:none;">{{ __('Voir toutes les notifications') }} &rarr;</a>
                            </div>
                        </div>
                    </div>
                @endif
                <span class="pvm-user-chip" title="{{ auth()->user()->name ?? auth()->id() }}">
                    @php $initials = collect(preg_split('/\s+/', trim((string) (auth()->user()->name ?? ''))) )->reject(fn($p)=>$p==='')->take(2)->map(fn($p)=>mb_strtoupper(mb_substr($p,0,1)))->join(''); @endphp
                    <span class="pvm-avatar">{{ $initials ?: '•' }}</span>
                    {{ \Illuminate\Support\Str::limit(auth()->user()->name ?? (string) auth()->id(), 24) }}
                </span>
            @endauth
        </div>
    </nav>

    <main class="pvm-container">
        @if (session('success'))
            <div class="pvm-alert pvm-alert-success">{!! e(session('success')) !!}<button type="button" class="pvm-alert-close" data-pvm-dismiss aria-label="×">×</button></div>
        @endif
        @if (session('error'))
            <div class="pvm-alert pvm-alert-error">{!! e(session('error')) !!}<button type="button" class="pvm-alert-close" data-pvm-dismiss aria-label="×">×</button></div>
        @endif

        @yield('content')
    </main>

    <script>
        (function () {
            var toggle = document.querySelector('[data-pvm-nav-toggle]');
            var links = document.querySelector('[data-pvm-nav-links]');
            if (toggle && links) {
                toggle.addEventListener('click', function () { links.classList.toggle('is-open'); });
            }
            var bell = document.querySelector('[data-pvm-bell]');
            var panel = document.querySelector('[data-pvm-bell-panel]');
            if (bell && panel) {
                bell.addEventListener('click', function (e) {
                    e.stopPropagation();
                    panel.classList.toggle('is-open');
                });
                document.addEventListener('click', function (e) {
                    if (!panel.classList.contains('is-open')) return;
                    if (!panel.contains(e.target) && !bell.contains(e.target)) {
                        panel.classList.remove('is-open');
                    }
                });
            }
            document.querySelectorAll('[data-pvm-dismiss]').forEach(function (btn) {
                btn.addEventListener('click', function () { var a = btn.closest('.pvm-alert'); if (a) a.remove(); });
            });

            /* ----- Speed dial ----- */
            document.querySelectorAll('[data-pvm-speed-dial]').forEach(function (wrap) {
                var main = wrap.querySelector('[data-pvm-speed-main]');
                if (!main) return;
                main.addEventListener('click', function (e) {
                    e.stopPropagation();
                    wrap.classList.toggle('is-open');
                    main.classList.toggle('is-open');
                });
                document.addEventListener('click', function (e) {
                    if (!wrap.classList.contains('is-open')) return;
                    if (!wrap.contains(e.target)) {
                        wrap.classList.remove('is-open');
                        main.classList.remove('is-open');
                    }
                });
            });

            /* ----- Menus hamburger ----- */
            document.querySelectorAll('[data-pvm-hamburger]').forEach(function (wrap) {
                var btn = wrap.querySelector('[data-pvm-hamburger-btn]');
                if (!btn) return;
                btn.addEventListener('click', function (e) {
                    e.stopPropagation();
                    var menu = wrap.querySelector('.pvm-hamburger-menu');
                    var currentlyOpen = menu && menu.classList.contains('is-open');
                    document.querySelectorAll('.pvm-hamburger-menu.is-open').forEach(function (m) {
                        m.classList.remove('is-open');
                    });
                    if (menu && !currentlyOpen) menu.classList.add('is-open');
                });
                document.addEventListener('click', function (e) {
                    if (!wrap.contains(e.target)) {
                        var menu = wrap.querySelector('.pvm-hamburger-menu');
                        if (menu) menu.classList.remove('is-open');
                    }
                });
            });
        })();
    </script>
    @stack('scripts')
</body>
</html>