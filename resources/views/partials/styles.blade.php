{{-- Package-contained styles: plain CSS, no build step. --}}
<style>
    :root {
        --sanity-bg: #0c1117;
        --sanity-bg-elevated: #121922;
        --sanity-panel: #161d27;
        --sanity-border: #2a3544;
        --sanity-text: #e8eef6;
        --sanity-muted: #8b9aac;
        --sanity-accent: #3d8bfd;
        --sanity-accent-hover: #6aa8ff;
        --sanity-success: #34d399;
        --sanity-warn: #fbbf24;
        --sanity-danger: #f87171;
        --sanity-ignored: #94a3b8;
        --sanity-font: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        --sanity-mono: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace;
        --sanity-radius: 12px;
        --sanity-focus: 0 0 0 3px rgba(61, 139, 253, 0.45);
    }
    .sanity-theme--light {
        --sanity-bg: #f1f5f9;
        --sanity-bg-elevated: #ffffff;
        --sanity-panel: #ffffff;
        --sanity-border: #e2e8f0;
        --sanity-text: #0f172a;
        --sanity-muted: #64748b;
        --sanity-accent: #2563eb;
        --sanity-accent-hover: #1d4ed8;
        --sanity-success: #059669;
        --sanity-warn: #d97706;
        --sanity-danger: #dc2626;
        --sanity-ignored: #64748b;
        --sanity-focus: 0 0 0 3px rgba(37, 99, 235, 0.35);
    }
    .sanity-check *, .sanity-check *::before, .sanity-check *::after { box-sizing: border-box; }
    .sanity-check {
        margin: 0;
        min-height: 100vh;
        font-family: var(--sanity-font);
        font-size: 15px;
        line-height: 1.55;
        color: var(--sanity-text);
        background: var(--sanity-bg);
        background-image: radial-gradient(ellipse 900px 480px at 15% -5%, rgba(61, 139, 253, 0.12), transparent 55%);
    }
    .sanity-theme--light.sanity-check {
        background-image: linear-gradient(180deg, #e0e7ff 0%, var(--sanity-bg) 42%);
    }
    .visually-hidden {
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
    .skip-link {
        position: absolute;
        left: 12px;
        top: -100px;
        z-index: 100;
        padding: 10px 16px;
        background: var(--sanity-panel);
        color: var(--sanity-text);
        border: 1px solid var(--sanity-border);
        border-radius: 8px;
        font-weight: 600;
        text-decoration: none;
    }
    .skip-link:focus {
        top: 12px;
        outline: none;
        box-shadow: var(--sanity-focus);
    }
    .wrap {
        max-width: 1160px;
        margin: 0 auto;
        padding: 0 20px 56px;
    }
    .sanity-header {
        border-bottom: 1px solid var(--sanity-border);
        background: rgba(18, 25, 34, 0.85);
        backdrop-filter: blur(10px);
    }
    .sanity-theme--light .sanity-header {
        background: rgba(255, 255, 255, 0.9);
    }
    .sanity-header__inner {
        padding: 20px 20px 18px;
    }
    .sanity-brand {
        display: flex;
        gap: 14px;
        align-items: flex-start;
    }
    .sanity-brand__mark {
        width: 10px;
        height: 40px;
        border-radius: 4px;
        background: linear-gradient(180deg, var(--sanity-accent), #22c55e);
        margin-top: 4px;
        flex-shrink: 0;
    }
    .sanity-brand__eyebrow {
        margin: 0;
        font-size: 0.72rem;
        font-weight: 600;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: var(--sanity-muted);
    }
    .sanity-brand__title {
        margin: 4px 0 0;
        font-size: 1.5rem;
        font-weight: 700;
        letter-spacing: -0.02em;
        line-height: 1.2;
    }
    .sanity-main { padding-top: 28px; }
    .sanity-alert {
        margin-bottom: 20px;
        padding: 12px 16px;
        border-radius: var(--sanity-radius);
        border: 1px solid var(--sanity-border);
        font-weight: 600;
    }
    .sanity-alert--danger {
        border-color: rgba(248, 113, 113, 0.55);
        background: rgba(248, 113, 113, 0.12);
        color: var(--sanity-danger);
    }
    .sanity-theme--light .sanity-alert--danger {
        background: rgba(220, 38, 38, 0.08);
    }
    .sanity-panel {
        background: var(--sanity-panel);
        border: 1px solid var(--sanity-border);
        border-radius: var(--sanity-radius);
        padding: 20px 22px;
        margin-bottom: 20px;
        box-shadow: 0 1px 0 rgba(255, 255, 255, 0.04) inset;
    }
    .sanity-theme--light .sanity-panel {
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.06);
    }
    .sanity-panel__title {
        margin: 0 0 12px;
        font-size: 1.05rem;
        font-weight: 650;
    }
    .sanity-panel__prose {
        margin: 0;
        color: var(--sanity-muted);
        max-width: 72ch;
    }
    .sanity-toolbar {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 22px;
    }
    .sanity-toolbar__actions {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        align-items: center;
    }
    .sanity-toolbar__nav {
        display: flex;
        flex-wrap: wrap;
        gap: 8px 14px;
        align-items: center;
        font-size: 0.9rem;
    }
    .sanity-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 11px 18px;
        border-radius: 10px;
        border: 1px solid var(--sanity-border);
        background: var(--sanity-bg-elevated);
        color: var(--sanity-text);
        font-weight: 600;
        font-size: 0.9rem;
        font-family: inherit;
        cursor: pointer;
        text-decoration: none;
        line-height: 1.2;
    }
    .sanity-btn:hover {
        border-color: var(--sanity-muted);
    }
    .sanity-btn:focus-visible {
        outline: none;
        box-shadow: var(--sanity-focus);
    }
    .sanity-btn--primary {
        background: linear-gradient(180deg, rgba(61, 139, 253, 0.35), rgba(61, 139, 253, 0.12));
        border-color: rgba(61, 139, 253, 0.5);
        color: var(--sanity-text);
    }
    .sanity-theme--light .sanity-btn--primary {
        background: linear-gradient(180deg, rgba(37, 99, 235, 0.2), rgba(37, 99, 235, 0.06));
        border-color: rgba(37, 99, 235, 0.45);
    }
    .sanity-btn--ghost {
        background: transparent;
    }
    .sanity-link {
        color: var(--sanity-accent);
        font-weight: 600;
        text-decoration: none;
    }
    .sanity-link:hover {
        color: var(--sanity-accent-hover);
        text-decoration: underline;
    }
    .sanity-link:focus-visible {
        outline: none;
        border-radius: 4px;
        box-shadow: var(--sanity-focus);
    }
    .sanity-breadcrumb {
        list-style: none;
        margin: 0 0 18px;
        padding: 0;
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        font-size: 0.88rem;
        color: var(--sanity-muted);
    }
    .sanity-breadcrumb li { display: flex; align-items: center; gap: 8px; }
    .sanity-breadcrumb li:not(:last-child)::after {
        content: '/';
        opacity: 0.45;
    }
    .sanity-alert {
        border-radius: var(--sanity-radius);
        padding: 14px 18px;
        margin-bottom: 20px;
        border: 1px solid var(--sanity-border);
        display: flex;
        gap: 12px;
        align-items: flex-start;
    }
    .sanity-alert--danger {
        background: rgba(248, 113, 113, 0.1);
        border-color: rgba(248, 113, 113, 0.45);
    }
    .sanity-theme--light .sanity-alert--danger {
        background: rgba(220, 38, 38, 0.08);
    }
    .sanity-alert__icon {
        font-size: 1.25rem;
        line-height: 1;
        flex-shrink: 0;
    }
    .sanity-alert__body { margin: 0; }
    .sanity-alert__body strong { display: block; margin-bottom: 4px; }
    .sanity-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
        gap: 12px;
        margin-bottom: 22px;
        list-style: none;
        padding: 0;
        margin-left: 0;
        margin-right: 0;
    }
    .sanity-stat {
        background: var(--sanity-panel);
        border: 1px solid var(--sanity-border);
        border-radius: var(--sanity-radius);
        padding: 16px 18px;
    }
    .sanity-stat__label {
        margin: 0;
        font-size: 0.7rem;
        font-weight: 650;
        letter-spacing: 0.07em;
        text-transform: uppercase;
        color: var(--sanity-muted);
    }
    .sanity-stat__value {
        margin: 6px 0 0;
        font-size: 1.65rem;
        font-weight: 700;
        font-variant-numeric: tabular-nums;
        line-height: 1.1;
    }
    .sanity-stat--ok .sanity-stat__value { color: var(--sanity-success); }
    .sanity-stat--r3 .sanity-stat__value { color: var(--sanity-warn); }
    .sanity-stat--r4 .sanity-stat__value { color: #fb923c; }
    .sanity-stat--r5 .sanity-stat__value { color: var(--sanity-danger); }
    .sanity-stat--ig .sanity-stat__value { color: var(--sanity-ignored); }
    .sanity-meta {
        display: grid;
        gap: 8px;
        margin: 0;
        font-size: 0.9rem;
        color: var(--sanity-muted);
    }
    .sanity-meta code {
        font-family: var(--sanity-mono);
        font-size: 0.84em;
        color: var(--sanity-text);
        word-break: break-all;
    }
    .sanity-filter {
        display: flex;
        flex-direction: column;
        gap: 14px;
    }
    @media (min-width: 720px) {
        .sanity-filter { flex-direction: row; flex-wrap: wrap; align-items: flex-end; justify-content: space-between; }
    }
    .sanity-filter__search {
        display: flex;
        flex-direction: column;
        gap: 6px;
        flex: 1;
        min-width: 200px;
    }
    .sanity-filter__search label {
        font-size: 0.75rem;
        font-weight: 650;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: var(--sanity-muted);
    }
    .sanity-input {
        padding: 10px 12px;
        border-radius: 8px;
        border: 1px solid var(--sanity-border);
        background: var(--sanity-bg);
        color: var(--sanity-text);
        font-family: inherit;
        font-size: 0.95rem;
        max-width: 420px;
        width: 100%;
    }
    .sanity-input:focus-visible {
        outline: none;
        box-shadow: var(--sanity-focus);
        border-color: var(--sanity-accent);
    }
    .sanity-chips {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        align-items: center;
    }
    .sanity-chips__legend {
        font-size: 0.75rem;
        font-weight: 650;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: var(--sanity-muted);
        margin-right: 4px;
    }
    .sanity-chip {
        display: inline-flex;
        align-items: center;
        padding: 6px 12px;
        border-radius: 999px;
        border: 1px solid var(--sanity-border);
        font-size: 0.82rem;
        font-weight: 600;
        text-decoration: none;
        color: var(--sanity-text);
        background: var(--sanity-bg-elevated);
    }
    .sanity-chip:hover { border-color: var(--sanity-muted); }
    .sanity-chip:focus-visible {
        outline: none;
        box-shadow: var(--sanity-focus);
    }
    .sanity-chip.is-active {
        border-color: var(--sanity-accent);
        background: rgba(61, 139, 253, 0.12);
    }
    .sanity-theme--light .sanity-chip.is-active {
        background: rgba(37, 99, 235, 0.1);
    }
    .sanity-status {
        font-size: 0.88rem;
        color: var(--sanity-muted);
        margin: 0 0 16px;
    }
    .sanity-table-wrap {
        overflow-x: auto;
        margin: 0 -4px;
    }
    .sanity-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.875rem;
    }
    .sanity-table caption {
        text-align: left;
        font-weight: 650;
        font-size: 1rem;
        padding: 0 4px 12px;
    }
    .sanity-table th,
    .sanity-table td {
        text-align: left;
        padding: 11px 10px;
        border-bottom: 1px solid var(--sanity-border);
        vertical-align: top;
    }
    .sanity-table th {
        font-size: 0.68rem;
        font-weight: 650;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: var(--sanity-muted);
    }
    .sanity-table tbody tr:hover td {
        background: rgba(255, 255, 255, 0.02);
    }
    .sanity-theme--light .sanity-table tbody tr:hover td {
        background: rgba(15, 23, 42, 0.03);
    }
    .sanity-pill {
        display: inline-block;
        padding: 2px 8px;
        border-radius: 999px;
        border: 1px solid var(--sanity-border);
        font-size: 0.7rem;
        font-weight: 650;
        font-family: var(--sanity-mono);
    }
    .sanity-note dt {
        margin: 0;
        font-size: 0.68rem;
        font-weight: 650;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--sanity-muted);
    }
    .sanity-note dd {
        margin: 2px 0 10px;
        color: var(--sanity-text);
    }
    .sanity-note dd:last-child { margin-bottom: 0; }
    .sanity-note--inline {
        font-size: 0.84rem;
        color: var(--sanity-muted);
    }
    .sanity-empty {
        margin: 0;
        padding: 12px 4px;
        color: var(--sanity-muted);
    }
    .sanity-pager {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        align-items: center;
        margin-top: 14px;
        font-size: 0.84rem;
        color: var(--sanity-muted);
    }
    .sanity-pager a { font-weight: 600; }
    .sanity-pager [aria-disabled="true"] { opacity: 0.45; }
    .sanity-footnote {
        margin-top: 28px;
        font-size: 0.8rem;
        color: var(--sanity-muted);
    }
    .sanity-error__main {
        padding-top: 80px;
        max-width: 520px;
    }
    .sanity-error__main h1 { margin-top: 0; }
    .sanity-error__lead { color: var(--sanity-muted); }
    .sanity-error__meta code { font-family: var(--sanity-mono); }
</style>
