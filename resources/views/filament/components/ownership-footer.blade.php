<style>
    .aaa-ownership-footer {
        position: relative;
        z-index: 10;
        width: 100%;
        text-align: center;
        padding: 0.875rem 1rem;
        margin-top: 1rem;
        font-size: 0.75rem;
        font-weight: 500;
        letter-spacing: 0.01em;
        color: rgba(71, 85, 105, 0.72);
        border-top: 1px solid rgba(148, 163, 184, 0.2);
    }
    .dark .aaa-ownership-footer {
        color: rgba(148, 163, 184, 0.65);
        border-top-color: rgba(51, 65, 85, 0.45);
    }
    .aaa-ownership-footer--login {
        border-top: none;
        margin-top: 0.75rem;
        padding: 0.35rem 0.5rem 0;
        font-size: 0.7rem;
    }
</style>
<footer class="aaa-ownership-footer {{ ($compact ?? false) ? 'aaa-ownership-footer--login' : '' }}">
    <span>&copy; 2026 Guest Management System &bull; AAA-05</span>
</footer>
