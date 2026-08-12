<style>
    .supplies-shell,
    .supplies-themed-modal {
        --sup-bg: #f8fafc;
        --sup-card: #ffffff;
        --sup-border: #e5e7eb;
        --sup-text: #111827;
        --sup-muted: #6b7280;
        --sup-soft: #f3f4f6;
        --sup-input: #ffffff;
        --sup-accent: #bb0000;
        color: var(--sup-text);
    }

    html.dark-mode .supplies-shell,
    html.dark-mode .supplies-themed-modal,
    .dark-mode .supplies-shell,
    .dark-mode .supplies-themed-modal {
        --sup-bg: #18181b;
        --sup-card: #232326;
        --sup-border: #3a3a3f;
        --sup-text: #f3f4f6;
        --sup-muted: #cbd5e1;
        --sup-soft: #111214;
        --sup-input: #16181d;
    }

    .supplies-shell,
    .supplies-shell .card,
    .supplies-shell .modal-content,
    .supplies-shell .table,
    .supplies-shell .table td,
    .supplies-shell .table th,
    .supplies-shell .nav-link,
    .supplies-shell .form-label,
    .supplies-shell h1,
    .supplies-shell h2,
    .supplies-shell h3,
    .supplies-shell h4,
    .supplies-shell h5,
    .supplies-shell h6,
    .supplies-shell p,
    .supplies-shell span,
    .supplies-shell div,
    .supplies-shell label,
    .supplies-shell small {
        color: var(--sup-text);
    }

    .supplies-shell .text-muted,
    .supplies-shell .small.text-muted {
        color: var(--sup-muted) !important;
    }

    .supplies-themed-modal,
    .supplies-shell .card,
    .supplies-shell .modal-content,
    .supplies-themed-modal .modal-content,
    .supplies-shell .table,
    .supplies-shell .nav-tabs,
    .supplies-shell .alert {
        background: var(--sup-card);
        border-color: var(--sup-border) !important;
    }

    .supplies-themed-modal {
        color: var(--sup-text);
        --bs-modal-bg: var(--sup-card);
        --bs-modal-color: var(--sup-text);
        --bs-modal-border-color: var(--sup-border);
        --bs-body-bg: var(--sup-card);
        --bs-body-color: var(--sup-text);
    }

    .supplies-shell .modal-content,
    .supplies-themed-modal .modal-content,
    .supplies-shell .modal-header,
    .supplies-themed-modal .modal-header,
    .supplies-shell .modal-body,
    .supplies-themed-modal .modal-body,
    .supplies-shell .modal-footer {
        background: var(--sup-card) !important;
        color: var(--sup-text) !important;
        border-color: var(--sup-border) !important;
    }

    .supplies-themed-modal .modal-footer {
        background: var(--sup-card) !important;
        color: var(--sup-text) !important;
        border-color: var(--sup-border) !important;
    }

    .supplies-shell .modal-title,
    .supplies-themed-modal .modal-title,
    .supplies-shell .modal-header .btn-close,
    .supplies-themed-modal .modal-header .btn-close,
    .supplies-shell .modal-body label,
    .supplies-themed-modal .modal-body label,
    .supplies-shell .modal-body .form-label,
    .supplies-themed-modal .modal-body .form-label,
    .supplies-shell .modal-body .form-check-label,
    .supplies-themed-modal .modal-body .form-check-label,
    .supplies-shell .modal-body .text-muted,
    .supplies-themed-modal .modal-body .text-muted,
    .supplies-shell .modal-footer,
    .supplies-themed-modal .modal-footer,
    .supplies-shell .modal-footer .btn,
    .supplies-themed-modal .modal-footer .btn,
    .supplies-shell .modal-body,
    .supplies-themed-modal .modal-body,
    .supplies-shell .modal-header {
        color: var(--sup-text) !important;
    }

    .supplies-themed-modal .modal-header {
        color: var(--sup-text) !important;
    }

    html.dark-mode .supplies-shell .modal-header .btn-close,
    html.dark-mode .supplies-themed-modal .modal-header .btn-close,
    .dark-mode .supplies-shell .modal-header .btn-close {
        filter: invert(1) grayscale(1);
        opacity: 0.85;
    }

    .dark-mode .supplies-themed-modal .modal-header .btn-close {
        filter: invert(1) grayscale(1);
        opacity: 0.85;
    }

    .supplies-shell .modal-body .text-muted {
        color: var(--sup-muted) !important;
    }

    .supplies-themed-modal .modal-body .text-muted {
        color: var(--sup-muted) !important;
    }

    .supplies-shell .modal-body .alert-light {
        background: var(--sup-soft) !important;
        color: var(--sup-text) !important;
        border-color: var(--sup-border) !important;
    }

    .supplies-themed-modal .modal-body .alert-light {
        background: var(--sup-soft) !important;
        color: var(--sup-text) !important;
        border-color: var(--sup-border) !important;
    }

    .supplies-shell .form-check-input {
        border-color: var(--sup-border);
        background-color: var(--sup-input);
    }

    .supplies-themed-modal .form-check-input {
        border-color: var(--sup-border);
        background-color: var(--sup-input);
    }

    .supplies-shell .form-check-input:checked {
        background-color: var(--sup-accent);
        border-color: var(--sup-accent);
    }

    .supplies-themed-modal .form-check-input:checked {
        background-color: var(--sup-accent);
        border-color: var(--sup-accent);
    }

    html.dark-mode .supplies-themed-modal .modal-content,
    html.dark-mode .supplies-themed-modal .modal-header,
    html.dark-mode .supplies-themed-modal .modal-body,
    html.dark-mode .supplies-themed-modal .modal-footer,
    .dark-mode .supplies-themed-modal .modal-content,
    .dark-mode .supplies-themed-modal .modal-header,
    .dark-mode .supplies-themed-modal .modal-body,
    .dark-mode .supplies-themed-modal .modal-footer {
        background: #121316 !important;
        color: #f3f4f6 !important;
        border-color: #30343b !important;
    }

    html.dark-mode .supplies-themed-modal .modal-body .form-control,
    html.dark-mode .supplies-themed-modal .modal-body textarea,
    html.dark-mode .supplies-themed-modal .modal-body input,
    .dark-mode .supplies-themed-modal .modal-body .form-control,
    .dark-mode .supplies-themed-modal .modal-body textarea,
    .dark-mode .supplies-themed-modal .modal-body input {
        background: #2f353d !important;
        color: #f9fafb !important;
        border-color: #404854 !important;
    }

    html.dark-mode .supplies-themed-modal .modal-body .form-control::placeholder,
    html.dark-mode .supplies-themed-modal .modal-body textarea::placeholder,
    .dark-mode .supplies-themed-modal .modal-body .form-control::placeholder,
    .dark-mode .supplies-themed-modal .modal-body textarea::placeholder {
        color: #cbd5e1 !important;
    }

    html.dark-mode .supplies-themed-modal .modal-title,
    html.dark-mode .supplies-themed-modal .modal-body label,
    html.dark-mode .supplies-themed-modal .modal-body .form-label,
    html.dark-mode .supplies-themed-modal .modal-body .form-check-label,
    html.dark-mode .supplies-themed-modal .modal-body,
    html.dark-mode .supplies-themed-modal .modal-footer,
    .dark-mode .supplies-themed-modal .modal-title,
    .dark-mode .supplies-themed-modal .modal-body label,
    .dark-mode .supplies-themed-modal .modal-body .form-label,
    .dark-mode .supplies-themed-modal .modal-body .form-check-label,
    .dark-mode .supplies-themed-modal .modal-body,
    .dark-mode .supplies-themed-modal .modal-footer {
        color: #f3f4f6 !important;
    }

    html.dark-mode .supplies-themed-modal .modal-backdrop,
    .dark-mode .supplies-themed-modal .modal-backdrop {
        --bs-backdrop-opacity: 0.7;
    }

    .supplies-shell .table {
        --bs-table-bg: transparent;
        --bs-table-color: var(--sup-text);
        --bs-table-border-color: var(--sup-border);
        margin-bottom: 0;
    }

    .supplies-shell .table thead th {
        background: var(--sup-soft);
        color: var(--sup-text);
        border-bottom-color: var(--sup-border);
        white-space: nowrap;
    }

    .supplies-shell .table td {
        border-color: var(--sup-border);
        color: var(--sup-text);
        vertical-align: middle;
    }

    .supplies-shell .form-control,
    .supplies-shell .form-select,
    .supplies-shell textarea,
    .supplies-shell input {
        background: var(--sup-input);
        color: var(--sup-text);
        border-color: var(--sup-border);
    }

    .supplies-shell .form-control::placeholder,
    .supplies-shell textarea::placeholder {
        color: var(--sup-muted);
    }

    .supplies-shell .form-control:focus,
    .supplies-shell .form-select:focus {
        background: var(--sup-input);
        color: var(--sup-text);
        border-color: rgba(187, 0, 0, 0.4);
        box-shadow: 0 0 0 0.2rem rgba(187, 0, 0, 0.14);
    }

    .supplies-shell .nav-tabs {
        border-bottom-color: var(--sup-border);
        gap: 0.35rem;
        overflow-x: auto;
        flex-wrap: nowrap;
        scrollbar-width: thin;
    }

    .supplies-shell .nav-tabs .nav-link {
        border: 1px solid transparent;
        color: var(--sup-muted);
        white-space: nowrap;
    }

    .supplies-shell .nav-tabs .nav-link.active {
        background: var(--sup-card);
        color: var(--sup-text);
        border-color: var(--sup-border) var(--sup-border) var(--sup-card);
    }

    .supplies-shell .supplies-toolbar {
        display: flex;
        gap: 1rem;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
    }

    .supplies-shell .supplies-toolbar form {
        display: flex;
        gap: 0.75rem;
        align-items: center;
        flex-wrap: wrap;
    }

    .supplies-shell .supplies-toolbar > :last-child {
        width: 100%;
        max-width: 420px;
    }

    .supplies-shell .supplies-toolbar > :last-child .form-control,
    .supplies-shell .supplies-toolbar > :last-child .btn {
        min-height: 42px;
    }

    .supplies-shell .supplies-stats-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 1rem;
    }

    .supplies-shell .supplies-kpi {
        background: linear-gradient(180deg, rgba(187, 0, 0, 0.05), transparent 55%), var(--sup-card);
        border: 1px solid var(--sup-border);
        border-radius: 1rem;
        padding: 1.1rem 1.2rem;
        height: 100%;
        transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
        cursor: default;
    }

    .supplies-shell .supplies-kpi:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(187, 0, 0, 0.08);
        border-color: rgba(187, 0, 0, 0.25);
    }

    html.dark-mode .supplies-shell .supplies-kpi,
    .dark-mode .supplies-shell .supplies-kpi {
        background: linear-gradient(180deg, rgba(255, 70, 70, 0.08), transparent 55%), var(--sup-card);
    }

    html.dark-mode .supplies-shell .supplies-kpi:hover,
    .dark-mode .supplies-shell .supplies-kpi:hover {
        box-shadow: 0 6px 20px rgba(255, 70, 70, 0.12);
    }

    .supplies-shell .supplies-kpi-header {
        display: flex;
        align-items: center;
        gap: 0.55rem;
        margin-bottom: 0.4rem;
    }

    .supplies-shell .supplies-kpi-icon {
        width: 34px;
        height: 34px;
        border-radius: 0.6rem;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.85rem;
        flex-shrink: 0;
    }

    .supplies-shell .supplies-kpi-icon.kpi-red {
        background: rgba(187, 0, 0, 0.1);
        color: #bb0000;
    }

    .supplies-shell .supplies-kpi-icon.kpi-blue {
        background: rgba(59, 130, 246, 0.1);
        color: #3b82f6;
    }

    .supplies-shell .supplies-kpi-icon.kpi-green {
        background: rgba(22, 163, 74, 0.1);
        color: #16a34a;
    }

    .supplies-shell .supplies-kpi-icon.kpi-amber {
        background: rgba(245, 158, 11, 0.1);
        color: #d97706;
    }

    .supplies-shell .supplies-kpi-label {
        color: var(--sup-muted);
        font-size: 0.82rem;
        font-weight: 500;
        letter-spacing: 0.01em;
    }

    .supplies-shell .supplies-kpi-value {
        font-size: clamp(1.7rem, 2vw, 2.3rem);
        font-weight: 800;
        line-height: 1;
        color: var(--sup-text);
    }

    .supplies-shell .supplies-kpi-note {
        color: var(--sup-muted);
        font-size: 0.82rem;
        margin-top: 0.45rem;
        font-weight: 400;
    }

    .supplies-shell .supplies-panel {
        background: var(--sup-card);
        border: 1px solid var(--sup-border);
        border-radius: 1rem;
        padding: 1rem;
    }

    .supplies-shell .supplies-panel-grid {
        display: grid;
        grid-template-columns: 1.3fr 1fr;
        gap: 1rem;
    }

    .supplies-shell .supplies-traffic-light {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        font-weight: 600;
        white-space: nowrap;
    }

    .supplies-shell .supplies-light-dot {
        width: 0.75rem;
        height: 0.75rem;
        border-radius: 999px;
        display: inline-block;
        box-shadow: 0 0 0 4px rgba(255, 255, 255, 0.02);
    }

    .supplies-shell .supplies-light-dot.is-good { background: #16a34a; }
    .supplies-shell .supplies-light-dot.is-warning { background: #f59e0b; }
    .supplies-shell .supplies-light-dot.is-critical { background: #ef4444; }

    .supplies-shell .supplies-stock-table td,
    .supplies-shell .supplies-stock-table th {
        font-size: 0.9rem;
    }

    .supplies-shell .product-search-results,
    .supplies-shell .client-search-results {
        background: var(--sup-card);
        border-color: var(--sup-border);
    }

    .supplies-shell .product-result-option,
    .supplies-shell .client-result-option {
        color: var(--sup-text);
    }

    html.dark-mode .supplies-shell .product-result-option:hover,
    html.dark-mode .supplies-shell .client-result-option:hover,
    .dark-mode .supplies-shell .product-result-option:hover,
    .dark-mode .supplies-shell .client-result-option:hover {
        background: rgba(187, 0, 0, 0.16);
        color: #fff;
    }

    .supplies-shell .table-responsive {
        border-radius: 0.9rem;
        overflow-x: auto;
        overflow-y: visible;
    }

    .supplies-shell .table-responsive table {
        min-width: 720px;
    }

    .supplies-shell .modal-xl,
    .supplies-shell .modal-lg {
        max-width: min(1140px, calc(100vw - 1rem));
    }

    .supplies-shell .card-body,
    .supplies-shell .modal-body {
        overflow-wrap: anywhere;
    }

    .supplies-shell .table tbody tr {
        transition: background 0.15s ease;
    }

    .supplies-shell .table tbody tr:hover {
        background: var(--sup-soft);
    }

    .supplies-shell .table .badge {
        font-weight: 600;
        font-size: 0.78rem;
        letter-spacing: 0.02em;
        padding: 0.35em 0.65em;
    }

    .supplies-shell .supplies-panel {
        background: var(--sup-card);
        border: 1px solid var(--sup-border);
        border-radius: 1rem;
        padding: 1rem;
    }

    .supplies-shell .supplies-panel-header {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 0.75rem;
    }

    .supplies-shell .supplies-panel-header h2,
    .supplies-shell .supplies-panel-header h5 {
        margin-bottom: 0;
    }

    .supplies-shell .supplies-section-title {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-weight: 700;
        font-size: 1rem;
        color: var(--sup-text);
        margin-bottom: 0.75rem;
    }

    .supplies-shell .supplies-section-title i {
        color: var(--sup-accent);
        font-size: 0.9rem;
    }

    @media (max-width: 1199px) {
        .supplies-shell .supplies-stats-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .supplies-shell .supplies-panel-grid {
            grid-template-columns: 1fr;
        }
    }

    .supplies-shell .card {
        transition: box-shadow 0.2s ease, border-color 0.2s ease;
    }

    .supplies-shell .card:hover {
        box-shadow: 0 4px 16px rgba(15, 23, 42, 0.06);
    }

    .supplies-shell .btn {
        transition: all 0.15s ease;
    }

    .supplies-shell .btn-danger {
        background: var(--sup-accent);
        border-color: var(--sup-accent);
    }

    .supplies-shell .btn-danger:hover {
        background: #950000;
        border-color: #950000;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(187, 0, 0, 0.25);
    }

    .supplies-shell .btn-outline-danger {
        color: var(--sup-accent);
        border-color: var(--sup-accent);
    }

    .supplies-shell .btn-outline-danger:hover {
        background: var(--sup-accent);
        border-color: var(--sup-accent);
        color: #ffffff !important;
        transform: translateY(-1px);
    }

    .supplies-shell .btn-outline-danger:hover i,
    .supplies-shell .btn-outline-danger:focus i,
    .supplies-shell .btn-outline-danger:hover span,
    .supplies-shell .btn-outline-danger:focus span {
        color: #ffffff !important;
    }

    .supplies-shell .supplies-empty-state {
        text-align: center;
        padding: 3rem 1rem;
        color: var(--sup-muted);
    }

    .supplies-shell .supplies-empty-state i {
        font-size: 2.5rem;
        opacity: 0.3;
        margin-bottom: 0.75rem;
    }

    .supplies-shell .supplies-page-header {
        position: relative;
        padding-bottom: 0.25rem;
    }

    .supplies-shell .supplies-page-header::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        width: 40px;
        height: 3px;
        background: var(--sup-accent);
        border-radius: 2px;
    }

    .supplies-shell .dataTables_wrapper .dataTables_length,
    .supplies-shell .dataTables_wrapper .dataTables_info {
        color: var(--sup-muted);
        font-size: 0.88rem;
    }

    .supplies-shell .dataTables_wrapper .dataTables_length select,
    .supplies-shell .dataTables_wrapper .dataTables_filter input {
        background: var(--sup-input);
        color: var(--sup-text);
        border: 1px solid var(--sup-border);
        border-radius: 0.5rem;
        padding: 0.3rem 0.6rem;
    }

    .supplies-shell .dataTables_wrapper .dataTables_filter input:focus {
        border-color: rgba(187, 0, 0, 0.4);
        box-shadow: 0 0 0 0.2rem rgba(187, 0, 0, 0.14);
        outline: none;
    }

    .supplies-shell .dataTables_wrapper .dataTables_paginate {
        padding-top: 0.75rem;
    }

    .supplies-shell .dataTables_wrapper .dataTables_paginate .paginate_button {
        color: var(--sup-text) !important;
        border: 1px solid var(--sup-border) !important;
        border-radius: 0.4rem !important;
        margin: 0 0.15rem;
        transition: all 0.15s ease;
    }

    .supplies-shell .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
        background: var(--sup-soft) !important;
        border-color: var(--sup-accent) !important;
        color: var(--sup-accent) !important;
    }

    .supplies-shell .dataTables_wrapper .dataTables_paginate .paginate_button.current {
        background: var(--sup-accent) !important;
        border-color: var(--sup-accent) !important;
        color: #fff !important;
    }

    .supplies-shell .dataTables_wrapper .dataTables_paginate .paginate_button.disabled {
        opacity: 0.4;
        cursor: not-allowed;
    }

    .supplies-shell div.dataTables_wrapper div.dataTables_length label,
    .supplies-shell div.dataTables_wrapper div.dataTables_filter label {
        color: var(--sup-muted);
        font-size: 0.88rem;
    }

    .supplies-shell .dataTables_wrapper .dataTables_empty {
        color: var(--sup-muted);
        text-align: center;
        padding: 2rem 1rem;
    }

    @media (max-width: 767px) {
        .supplies-shell {
            padding-left: 0.2rem;
            padding-right: 0.2rem;
        }

        .supplies-shell .supplies-stats-grid {
            grid-template-columns: 1fr;
        }

        .supplies-shell .display-6,
        .supplies-shell .supplies-kpi-value {
            font-size: 1.9rem;
        }

        .supplies-shell .table th,
        .supplies-shell .table td {
            font-size: 0.82rem;
            min-width: 100px;
        }

        .supplies-shell .table-responsive table {
            min-width: 640px;
        }

        .supplies-shell .supplies-toolbar,
        .supplies-shell .supplies-toolbar form,
        .supplies-shell .modal-footer {
            flex-direction: column;
            align-items: stretch;
        }

        .supplies-shell .supplies-toolbar > :last-child {
            max-width: 100%;
        }

        .supplies-shell .modal-dialog {
            margin: 0.5rem;
        }

        .supplies-shell .btn {
            width: 100%;
        }

        .supplies-shell .d-flex.flex-wrap.gap-2 > .btn,
        .supplies-shell .modal-footer > .btn {
            width: 100%;
        }

        .supplies-shell .supply-request-table-wrap {
            overflow-x: auto;
        }
    }
</style>
