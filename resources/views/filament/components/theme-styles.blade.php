@php
    $settings = \App\Models\Setting::get();
@endphp
<meta name="author" content="AAA-05">
<meta name="copyright" content="Copyright (c) 2026 AAA-05. All rights reserved.">
<style>
    :root {
        --brand-primary: {{ $settings->primary_color ?? '#2f76b5' }};
        --brand-secondary: {{ $settings->secondary_color ?? '#1e4e79' }};
    }

    /* ==========================================================================
       1. SIDEBAR ENTERPRISE STYLING (Light & Dark Mode)
       ========================================================================== */
    /* Light Mode Sidebar: Rich Brand Gradient */
    aside.fi-sidebar {
        background: linear-gradient(180deg, var(--brand-primary) 0%, var(--brand-secondary) 100%) !important;
        border-right: 1px solid rgba(255, 255, 255, 0.12) !important;
        box-shadow: 4px 0 24px rgba(0, 0, 0, 0.08) !important;
    }

    aside.fi-sidebar .fi-sidebar-header {
        background: transparent !important;
        border-bottom: 1px solid rgba(255, 255, 255, 0.15) !important;
    }

    aside.fi-sidebar .fi-sidebar-header a,
    aside.fi-sidebar .fi-sidebar-header span,
    aside.fi-sidebar .fi-sidebar-header div {
        color: #ffffff !important;
    }

    aside.fi-sidebar .fi-sidebar-group-label {
        color: rgba(255, 255, 255, 0.72) !important;
        font-weight: 700 !important;
        font-size: 0.725rem !important;
        text-transform: uppercase !important;
        letter-spacing: 0.06em !important;
    }

    aside.fi-sidebar .fi-sidebar-item-button {
        color: rgba(255, 255, 255, 0.88) !important;
        border-radius: 10px !important;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
        margin: 2px 0 !important;
    }

    aside.fi-sidebar .fi-sidebar-item-icon {
        color: rgba(255, 255, 255, 0.82) !important;
    }

    aside.fi-sidebar .fi-sidebar-item-label {
        color: rgba(255, 255, 255, 0.92) !important;
        font-weight: 500 !important;
    }

    aside.fi-sidebar .fi-sidebar-item-button:hover {
        background: rgba(255, 255, 255, 0.14) !important;
        color: #ffffff !important;
        transform: translateX(2px) !important;
    }

    aside.fi-sidebar .fi-sidebar-item-button:hover .fi-sidebar-item-icon,
    aside.fi-sidebar .fi-sidebar-item-button:hover .fi-sidebar-item-label {
        color: #ffffff !important;
    }

    /* Active Menu Item (Light Mode) */
    aside.fi-sidebar .fi-sidebar-item.fi-active > .fi-sidebar-item-button,
    aside.fi-sidebar .fi-sidebar-item-active > .fi-sidebar-item-button {
        background: rgba(255, 255, 255, 0.2) !important;
        color: #ffffff !important;
        font-weight: 700 !important;
        border-left: 4px solid #ffffff !important;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.16) !important;
        backdrop-filter: blur(8px) !important;
    }

    aside.fi-sidebar .fi-sidebar-item.fi-active .fi-sidebar-item-icon,
    aside.fi-sidebar .fi-sidebar-item-active .fi-sidebar-item-icon,
    aside.fi-sidebar .fi-sidebar-item.fi-active .fi-sidebar-item-label,
    aside.fi-sidebar .fi-sidebar-item-active .fi-sidebar-item-label {
        color: #ffffff !important;
        font-weight: 700 !important;
    }

    /* Dark Mode Sidebar Override */
    html.dark aside.fi-sidebar,
    .dark aside.fi-sidebar {
        background: #0f172a !important;
        border-right: 1px solid rgba(255, 255, 255, 0.08) !important;
        box-shadow: 4px 0 24px rgba(0, 0, 0, 0.4) !important;
    }

    html.dark aside.fi-sidebar .fi-sidebar-header,
    .dark aside.fi-sidebar .fi-sidebar-header {
        background: rgba(15, 23, 42, 0.8) !important;
        border-bottom: 1px solid rgba(255, 255, 255, 0.08) !important;
    }

    html.dark aside.fi-sidebar .fi-sidebar-group-label,
    .dark aside.fi-sidebar .fi-sidebar-group-label {
        color: #94a3b8 !important;
    }

    html.dark aside.fi-sidebar .fi-sidebar-item-button,
    .dark aside.fi-sidebar .fi-sidebar-item-button {
        color: #cbd5e1 !important;
    }

    html.dark aside.fi-sidebar .fi-sidebar-item-icon,
    .dark aside.fi-sidebar .fi-sidebar-item-icon {
        color: #94a3b8 !important;
    }

    html.dark aside.fi-sidebar .fi-sidebar-item-label,
    .dark aside.fi-sidebar .fi-sidebar-item-label {
        color: #cbd5e1 !important;
    }

    html.dark aside.fi-sidebar .fi-sidebar-item-button:hover,
    .dark aside.fi-sidebar .fi-sidebar-item-button:hover {
        background: rgba(255, 255, 255, 0.06) !important;
        color: #f8fafc !important;
    }

    html.dark aside.fi-sidebar .fi-sidebar-item.fi-active > .fi-sidebar-item-button,
    html.dark aside.fi-sidebar .fi-sidebar-item-active > .fi-sidebar-item-button,
    .dark aside.fi-sidebar .fi-sidebar-item.fi-active > .fi-sidebar-item-button,
    .dark aside.fi-sidebar .fi-sidebar-item-active > .fi-sidebar-item-button {
        background: linear-gradient(135deg, var(--brand-primary) 0%, var(--brand-secondary) 100%) !important;
        color: #ffffff !important;
        border-left: 4px solid #38bdf8 !important;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.3) !important;
    }

    html.dark aside.fi-sidebar .fi-sidebar-item.fi-active .fi-sidebar-item-icon,
    html.dark aside.fi-sidebar .fi-sidebar-item.fi-active .fi-sidebar-item-label,
    .dark aside.fi-sidebar .fi-sidebar-item.fi-active .fi-sidebar-item-icon,
    .dark aside.fi-sidebar .fi-sidebar-item.fi-active .fi-sidebar-item-label {
        color: #ffffff !important;
    }

    /* ==========================================================================
       2. TOPBAR ENTERPRISE STYLING & PROFILE DROPDOWN FIX
       ========================================================================== */
    .fi-topbar {
        position: sticky !important;
        top: 0 !important;
        z-index: 40 !important;
        overflow: visible !important;
    }

    .fi-topbar nav {
        overflow: visible !important;
        border-top: 3px solid var(--brand-primary) !important;
        transition: background-color 0.2s ease, border-color 0.2s ease !important;
    }

    /* Light Mode Topbar */
    html:not(.dark) .fi-topbar nav {
        background: #ffffff !important;
        border-bottom: 1px solid rgba(226, 232, 240, 0.8) !important;
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.04) !important;
    }

    /* Dark Mode Topbar */
    html.dark .fi-topbar nav,
    .dark .fi-topbar nav {
        background: #0f172a !important;
        border-bottom: 1px solid rgba(255, 255, 255, 0.08) !important;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2) !important;
    }

    /* Fix Dropdown Panels (Profile Menu, Filter, Actions) */
    .fi-dropdown-panel {
        z-index: 9999 !important;
        position: absolute !important;
    }

    .fi-dropdown-panel,
    .fi-dropdown-panel * {
        visibility: visible !important;
    }

    html:not(.dark) .fi-dropdown-panel {
        background-color: #ffffff !important;
        border: 1px solid #e2e8f0 !important;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1) !important;
    }

    html:not(.dark) .fi-dropdown-header,
    html:not(.dark) .fi-dropdown-header-label,
    html:not(.dark) .fi-dropdown-list-item,
    html:not(.dark) .fi-dropdown-list-item-label {
        color: #0f172a !important;
    }

    html:not(.dark) .fi-dropdown-list-item-icon {
        color: #475569 !important;
    }

    html:not(.dark) .fi-theme-switcher-btn {
        color: #64748b !important;
    }

    html:not(.dark) .fi-theme-switcher-btn:hover,
    html:not(.dark) .fi-theme-switcher-btn[aria-pressed="true"],
    html:not(.dark) .fi-theme-switcher-btn.fi-active {
        color: var(--brand-primary) !important;
    }

    html.dark .fi-dropdown-panel,
    .dark .fi-dropdown-panel {
        background-color: #1e293b !important;
        border: 1px solid #334155 !important;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.55) !important;
    }

    html.dark .fi-dropdown-header,
    html.dark .fi-dropdown-header-label,
    html.dark .fi-dropdown-list-item,
    html.dark .fi-dropdown-list-item-label,
    .dark .fi-dropdown-header,
    .dark .fi-dropdown-header-label,
    .dark .fi-dropdown-list-item,
    .dark .fi-dropdown-list-item-label {
        color: #f8fafc !important;
    }

    html.dark .fi-dropdown-list-item-icon,
    .dark .fi-dropdown-list-item-icon {
        color: #94a3b8 !important;
    }

    html.dark .fi-theme-switcher-btn,
    .dark .fi-theme-switcher-btn {
        color: #94a3b8 !important;
    }

    html.dark .fi-theme-switcher-btn:hover,
    html.dark .fi-theme-switcher-btn[aria-pressed="true"],
    html.dark .fi-theme-switcher-btn.fi-active,
    .dark .fi-theme-switcher-btn:hover,
    .dark .fi-theme-switcher-btn[aria-pressed="true"],
    .dark .fi-theme-switcher-btn.fi-active {
        color: #f8fafc !important;
    }

    /* ==========================================================================
       3. MAIN CANVAS & PAGE BACKGROUND
       ========================================================================== */
    body.fi-body {
        min-height: 100vh !important;
    }

    /* Light Mode Background with subtle radial brand glow */
    html:not(.dark) body.fi-body,
    html:not(.dark) .fi-main {
        background: radial-gradient(circle at 100% 0%, rgba(47, 118, 181, 0.06) 0%, transparent 45%), #f8fafc !important;
    }

    /* Dark Mode Background */
    html.dark body.fi-body,
    .dark body.fi-body,
    html.dark .fi-main,
    .dark .fi-main {
        background: radial-gradient(circle at 100% 0%, rgba(47, 118, 181, 0.1) 0%, transparent 45%), #0b1120 !important;
    }

    /* ==========================================================================
       4. STAT CARDS, SECTIONS & TABLE CONTAINERS
       ========================================================================== */
    .fi-wi-stats-overview-stat,
    .fi-section,
    .fi-ta-ctn {
        border-radius: 14px !important;
        transition: transform 0.2s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
    }

    /* Light Mode Cards */
    html:not(.dark) .fi-wi-stats-overview-stat,
    html:not(.dark) .fi-section,
    html:not(.dark) .fi-ta-ctn {
        background: #ffffff !important;
        border: 1px solid rgba(226, 232, 240, 0.8) !important;
        box-shadow: 0 4px 20px -2px rgba(15, 23, 42, 0.06), 0 2px 6px -1px rgba(15, 23, 42, 0.04) !important;
    }

    /* Stat Cards Specific Polish */
    .fi-wi-stats-overview-stat {
        border-top: 4px solid var(--brand-primary) !important;
        overflow: hidden !important;
        position: relative !important;
    }

    .fi-wi-stats-overview-stat:hover {
        transform: translateY(-2px) !important;
        box-shadow: 0 12px 28px -4px rgba(15, 23, 42, 0.12), 0 4px 10px -2px rgba(15, 23, 42, 0.06) !important;
    }

    /* Dark Mode Cards */
    html.dark .fi-wi-stats-overview-stat,
    .dark .fi-wi-stats-overview-stat,
    html.dark .fi-section,
    .dark .fi-section,
    html.dark .fi-ta-ctn,
    .dark .fi-ta-ctn {
        background: #0f172a !important;
        border: 1px solid rgba(255, 255, 255, 0.08) !important;
        box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.4) !important;
    }

    html.dark .fi-wi-stats-overview-stat,
    .dark .fi-wi-stats-overview-stat {
        border-top: 4px solid var(--brand-primary) !important;
    }

    html.dark .fi-wi-stats-overview-stat:hover,
    .dark .fi-wi-stats-overview-stat:hover {
        transform: translateY(-2px) !important;
        box-shadow: 0 12px 28px -4px rgba(0, 0, 0, 0.6) !important;
    }

    /* ==========================================================================
       5. PRIMARY BUTTONS & INTERACTION ACCENTS
       ========================================================================== */
    .fi-btn-color-primary,
    button.fi-btn-color-primary {
        background: linear-gradient(135deg, var(--brand-primary) 0%, var(--brand-secondary) 100%) !important;
        color: #ffffff !important;
        border: none !important;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15) !important;
        border-radius: 8px !important;
        font-weight: 600 !important;
        transition: opacity 0.2s ease, transform 0.1s ease, box-shadow 0.2s ease !important;
    }

    .fi-btn-color-primary:hover,
    button.fi-btn-color-primary:hover {
        opacity: 0.94 !important;
        box-shadow: 0 6px 18px rgba(0, 0, 0, 0.22) !important;
        transform: translateY(-1px) !important;
    }

    .fi-btn-color-primary:active,
    button.fi-btn-color-primary:active {
        transform: translateY(0) !important;
    }

    .fi-btn-color-primary span,
    .fi-btn-color-primary svg {
        color: #ffffff !important;
    }

    /* Global Scrollbar Customization for clean look */
    ::-webkit-scrollbar {
        width: 8px;
        height: 8px;
    }
    ::-webkit-scrollbar-track {
        background: transparent;
    }
    ::-webkit-scrollbar-thumb {
        background: rgba(148, 163, 184, 0.4);
        border-radius: 4px;
    }
    ::-webkit-scrollbar-thumb:hover {
        background: rgba(100, 116, 139, 0.6);
    }
</style>
