<!DOCTYPE html>
<html lang="es">

<head>
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <meta name="description" content="">
  <meta name="author" content="">
  <title>Grupo Logistico Especializado</title>
  <link rel="icon" href="{{ asset('/images/certificacion.png') }}" type="image/png">
  <link href="{{ asset('admin_assets/vendor/fontawesome-free/css/all.min.css') }}" rel="stylesheet" type="text/css">
  <link href="{{asset('admin_assets/css/sb-admin-2.min.css') }}" rel="stylesheet">
  <link href="{{ asset('vendor/bootstrap/bootstrap.min.css') }}" rel="stylesheet">
  @stack('styles')

  <!-- CRITICAL: Script para aplicar tema ANTES de renderizar (evita flash blanco) -->
  <script>
    (function () {
      // Aplicar tema inmediatamente SOLO a html (body aún no existe)
      if (localStorage.getItem('dark-mode') === 'enabled') {
        document.documentElement.classList.add('dark-mode');
      }
    })();
  </script>

  <style>
    /* Sobreescritura corporativa global */
    :root {
      --corp-red: #bb0000;
      --corp-red-hover: #8f0000;
      --corp-black: #1a1a1a;
      --corp-black-light: #2d2d2d;
    }

    /* Reemplazar el primario azul por rojo corporativo */
    .bg-primary {
      background-color: var(--corp-red) !important;
    }
    
    .bg-gradient-primary {
      background-color: var(--corp-black) !important;
      background-image: none !important;
    }

    .text-primary {
      color: var(--corp-red) !important;
    }

    .btn-primary {
      background-color: var(--corp-red) !important;
      border-color: var(--corp-red) !important;
    }

    .btn-primary:hover, .btn-primary:focus {
      background-color: var(--corp-red-hover) !important;
      border-color: var(--corp-red-hover) !important;
    }
    
    .border-primary {
      border-color: var(--corp-red) !important;
    }

    /* Estilos generales del sidebar */
    #accordionSidebar {
      width: 96px;
      min-width: 96px;
      max-width: 96px;
      flex: 0 0 96px;
      /* Solo tamaño pequeño */
      background-color: #ffffff !important;
      background-image: none !important;
      display: flex;
      border-right: 1px solid #e3e6f0;
      overflow-x: visible;
      overflow-y: visible;
      transition: transform 0.25s ease;
      z-index: 1040;
      box-sizing: border-box;
    }

    #accordionSidebar.sidebar-visible {
      display: flex !important;
    }

    #sidebarBackdrop {
      position: fixed;
      inset: 0;
      background: rgba(15, 23, 42, 0.45);
      opacity: 0;
      visibility: hidden;
      transition: opacity 0.2s ease, visibility 0.2s ease;
      z-index: 1035;
    }

    #sidebarBackdrop.is-visible {
      opacity: 1;
      visibility: visible;
    }

    body.sidebar-desktop-hidden #accordionSidebar {
      display: none !important;
    }

    #accordionSidebar .nav-item {
      position: relative;
      width: 100%;
    }

    #accordionSidebar .nav-link {
      color: #1f2937;
      width: 100%;
      padding: 0.85rem 0.5rem;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      text-align: center;
      box-sizing: border-box;
    }

    #accordionSidebar .nav-link i {
      color: #4b5563;
      font-size: 1.5rem;
      display: block;
      margin: 0 auto;
    }

    #accordionSidebar .nav-link span {
      display: none;
      /* Ocultar texto en el sidebar pequeño */
    }

    #accordionSidebar .sidebar-brand {
      width: 96px;
      min-height: auto;
      height: auto;
      padding: 0.75rem 0 0.5rem;
      overflow: hidden;
    }

    #accordionSidebar .logo-container {
      width: 100%;
      padding: 0.25rem 0;
      margin: 0;
    }

    #accordionSidebar .logo-image {
      width: 72px;
      max-width: 72px;
      height: auto;
      display: inline-block;
    }

    #accordionSidebar .dropdown-menu {
      display: none;
      width: 200px;
      border: none;
      border-radius: 0;
      background-color: #ffffff;
      position: absolute;
      left: 96px;
      top: 0;
      box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.12);
      z-index: 1060;
    }

    #accordionSidebar .dropdown-menu .dropdown-item {
      display: flex;
      align-items: center;
      padding: 10px 20px;
      color: #1f2937;
      background-color: #ffffff;
      border-bottom: 1px solid #e5e7eb;
    }

    #accordionSidebar .dropdown-menu .dropdown-item:hover {
      background-color: var(--corp-red);
      color: #ffffff;
    }

    #accordionSidebar .dropdown-menu .dropdown-item i {
      margin-right: 10px;
      font-size: 1.1rem;
    }

    /* Estilo para el contenedor del logo */
    .logo-container {
      width: 100%;
      text-align: center;
      padding: 20px 0;
    }

    .logo-image {
      max-width: 80%;
      height: auto;
      display: inline-block;
    }

    /* Botón de alternar el sidebar */
    #toggleSidebarContainer {
      position: relative;
      width: 100%;
      padding: 10px;
      background-color: #ffffff;
      border-top: 1px solid #e3e6f0;
    }

    #toggleSidebar {
      background-color: transparent;
      border: none;
      cursor: pointer;
      font-size: 24px;
      color: #1f2937;
    }

    /* Base Styles */
    .table th,
    .table td {
      text-align: center;
      vertical-align: middle;
      font-size: 12px;
    }

    .table th {
      background-color: #d4edda;
    }

    .table td {
      min-width: 100px;
    }

    body {
      background-color: #f8f9fc;
      color: #1c1b1b;
      margin: 0;
    }

    /* Modo claro */
    #wrapper #content-wrapper #content {
      background-color: #f8f9fc;
      width: 100%;
      overflow-x: hidden;
    }

    /* Dark Mode Styles - Mejorado para mejor contraste */
    html.dark-mode,
    html.dark-mode body,
    .dark-mode {
      background-color: #1a1a1a;
      /* Gris oscuro en lugar de negro puro */
      color: #f0f0f0;
      /* Texto más claro para mejor contraste */
      transition: background-color 0.2s ease, color 0.2s ease;
    }

    .dark-mode .bg-white {
      background-color: #1a1a1a !important;
    }

    .dark-mode .text-gray-800 {
      color: #f0f0f0 !important;
      /* Texto claro en modo oscuro */
    }

    .dark-mode .container-fluid {
      background-color: #1a1a1a !important;
    }

    .dark-mode #wrapper {
      background-color: #1a1a1a !important;
    }

    .dark-mode #content-wrapper {
      background-color: #1a1a1a !important;
    }

    .dark-mode #wrapper #content-wrapper #content {
      background-color: #1a1a1a !important;
    }

    /* Sidebar en modo oscuro */
    .dark-mode #sidebar {
      background-color: #2d2d2d;
      /* Gris oscuro para mejor contraste */
      width: 200px;
    }

    .dark-mode #sidebar .nav-item .nav-link {
      color: #ffffff !important;
    }

    .dark-mode #sidebar .nav-item .nav-link:hover {
      background-color: #3a3a3a !important;
      color: #ffffff !important;
    }

    .dark-mode #sidebar .sidebar-heading {
      color: #f0f0f0 !important;
    }

    .dark-mode #sidebar.hidden {
      display: none;
    }

    #accordionSidebar .dropdown-toggle::after {
      display: none !important;
    }

    /* Tablas en modo oscuro */
    .dark-mode .table {
      background-color: #1e1e1e !important;
      color: #e0e0e0 !important;
    }

    .dark-mode .table th {
      background-color: #343a40 !important;
      color: #ffffff !important;
    }

    .dark-mode .table td {
      background-color: #1e1e1e !important;
      color: #e0e0e0 !important;
      border-color: #454d55 !important;
    }

    .dark-mode .table tbody tr:hover {
      background-color: #2c2c2c !important;
    }

    /* Tarjetas (Cards) en modo oscuro */
    .dark-mode .card {
      background-color: #1e1e1e !important;
      color: #e0e0e0 !important;
      border-color: #454d55 !important;
    }

    .dark-mode .card-header {
      background-color: #343a40 !important;
      color: #ffffff !important;
      border-bottom-color: #454d55 !important;
    }

    .dark-mode .card-footer {
      background-color: #343a40 !important;
      color: #ffffff !important;
      border-top-color: #454d55 !important;
    }

    /* Botones en modo oscuro */
    .dark-mode .btn-primary {
      background-color: #007bff !important;
      border-color: #007bff !important;
      color: #ffffff !important;
    }

    .dark-mode .btn-primary:hover {
      background-color: var(--corp-red-hover) !important;
      border-color: var(--corp-red-hover) !important;
    }

    .dark-mode .btn-secondary {
      background-color: #6c757d !important;
      border-color: #6c757d !important;
      color: #ffffff !important;
    }

    .dark-mode .btn-secondary:hover {
      background-color: #5a6268 !important;
      border-color: #5a6268 !important;
    }

    .dark-mode .btn-success {
      background-color: #28a745 !important;
      border-color: #28a745 !important;
      color: #ffffff !important;
    }

    .dark-mode .btn-success:hover {
      background-color: #218838 !important;
      border-color: #218838 !important;
    }

    .dark-mode .btn-danger {
      background-color: #dc3545 !important;
      border-color: #dc3545 !important;
      color: #ffffff !important;
    }

    .dark-mode .btn-danger:hover {
      background-color: #c82333 !important;
      border-color: #c82333 !important;
    }

    .dark-mode .btn-warning {
      background-color: #ffc107 !important;
      border-color: #ffc107 !important;
      color: #212529 !important;
    }

    .dark-mode .btn-warning:hover {
      background-color: #e0a800 !important;
      border-color: #e0a800 !important;
    }

    .dark-mode .btn-info {
      background-color: #17a2b8 !important;
      border-color: #17a2b8 !important;
      color: #ffffff !important;
    }

    .dark-mode .btn-info:hover {
      background-color: #138496 !important;
      border-color: #138496 !important;
    }

    /* Paginación en modo oscuro */
    .dark-mode .pagination .page-item .page-link {
      background-color: #343a40 !important;
      border-color: #454d55 !important;
      color: #ffffff !important;
    }

    .dark-mode .pagination .page-item.active .page-link {
      background-color: #007bff !important;
      border-color: #007bff !important;
      color: #ffffff !important;
    }

    .dark-mode .pagination .page-item.disabled .page-link {
      background-color: #343a40 !important;
      border-color: #454d55 !important;
      color: #6c757d !important;
    }

    /* Formularios en modo oscuro */
    .dark-mode .form-control {
      background-color: #343a40 !important;
      border-color: #454d55 !important;
      color: #e0e0e0 !important;
    }

    .dark-mode .form-control:focus {
      background-color: #454d55 !important;
      border-color: #007bff !important;
      color: #e0e0e0 !important;
      box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25) !important;
    }

    .dark-mode .form-control::placeholder {
      color: #adb5bd !important;
    }

    .dark-mode .form-select {
      background-color: #343a40 !important;
      border-color: #454d55 !important;
      color: #e0e0e0 !important;
    }

    .dark-mode .form-select:focus {
      background-color: #454d55 !important;
      border-color: #007bff !important;
      color: #e0e0e0 !important;
      box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25) !important;
    }

    .dark-mode .form-check-label {
      color: #e0e0e0 !important;
    }

    .form-check-input {
      accent-color: #dc2626;
    }

    .form-check-input:checked {
      background-color: #dc2626 !important;
      border-color: #dc2626 !important;
    }

    .dark-mode .form-check-input {
      accent-color: #dc2626;
    }

    .dark-mode .form-check-input:checked {
      background-color: #dc2626 !important;
      border-color: #dc2626 !important;
      box-shadow: 0 0 0 0.2rem rgba(220, 38, 38, 0.25) !important;
    }

    /* Alertas en modo oscuro */
    .dark-mode .alert {
      background-color: #343a40 !important;
      color: #e0e0e0 !important;
      border-color: #454d55 !important;
    }

    .dark-mode .alert-success {
      background-color: #1c4532 !important;
      color: #c3e6cb !important;
      border-color: #2f6c47 !important;
    }

    .dark-mode .alert-danger {
      background-color: #5a1c24 !important;
      color: #f5c6cb !important;
      border-color: #8b2a36 !important;
    }

    .dark-mode .alert-warning {
      background-color: #664d03 !important;
      color: #ffeeba !important;
      border-color: #997404 !important;
    }

    .dark-mode .alert-info {
      background-color: #0c5460 !important;
      color: #bee5eb !important;
      border-color: #117a8b !important;
    }

    /* Footer en modo oscuro */
    .dark-mode .navbar,
    .dark-mode .footer {
      background-color: #000000 !important;
    }

    /* Ajustes para que el footer esté al final */
    html,
    body {
      height: 100%;
      margin: 0;
    }

    #wrapper {
      display: flex;
      min-height: 100vh;
    }

    #content-wrapper {
      flex: 1;
      display: flex;
      flex-direction: column;
    }

    #content {
      flex: 1 0 auto;
    }

    .container-fluid {
      flex-grow: 1;
    }

    footer {
      flex-shrink: 0;
      width: 100%;
      text-align: center;
      padding: 1rem 0;
      background-color: #f8f9fc;
    }

    .dark-mode footer {
      background-color: #000000;
    }

    /* AGREGAR ESTAS REGLAS AL CSS EN app.blade.php - tienen mayor especificidad */

    /* Forzar fondo negro con selectores más específicos */
    .dark-mode #wrapper #accordionSidebar {
      background-color: #000000 !important;
      background: #000000 !important;
    }

    .dark-mode #wrapper .navbar-nav.sidebar {
      background-color: #000000 !important;
      background: #000000 !important;
    }

    .dark-mode .navbar-nav.bg-gradient-primary.sidebar {
      background: #000000 !important;
      background-color: #000000 !important;
    }

    .dark-mode .bg-gradient-primary {
      background: #000000 !important;
      background-color: #000000 !important;
    }

    /* También para el contenedor principal del sidebar */
    .dark-mode .sidebar.sidebar-dark {
      background-color: #000000 !important;
      background: #000000 !important;
    }

    /* Asegurar que todos los elementos del sidebar tengan fondo negro */
    .dark-mode #accordionSidebar,
    .dark-mode #accordionSidebar.sidebar,
    .dark-mode #accordionSidebar.navbar-nav,
    .dark-mode #accordionSidebar.bg-gradient-primary {
      background: #000000 !important;
      background-color: #000000 !important;
      background-image: none !important;
    }

    @media (max-width: 991.98px) {
      #accordionSidebar {
        display: flex !important;
        position: fixed;
        top: 0;
        left: 0;
        bottom: 0;
        z-index: 1040;
        width: 96px;
        min-width: 96px;
        max-width: 96px;
        flex: 0 0 96px;
        overflow-x: visible;
        overflow-y: visible;
        transform: translateX(-100%);
        transition: transform 0.25s ease;
        box-shadow: 0 10px 28px rgba(0, 0, 0, 0.18);
      }

      #accordionSidebar .sidebar-brand {
        width: 96px;
        padding-top: 0.65rem;
      }

      #accordionSidebar .logo-image {
        width: 72px;
        max-width: 72px;
      }

      body.sidebar-mobile-open #accordionSidebar,
      #accordionSidebar.sidebar-visible {
        transform: translateX(0);
      }

      body.sidebar-mobile-open {
        overflow: hidden;
      }
    }

    @media (min-width: 992px) {
      #accordionSidebar {
        position: relative;
        top: auto;
        left: auto;
        bottom: auto;
        transform: none !important;
        box-shadow: none;
      }

      #sidebarBackdrop {
        display: none !important;
      }

      body.sidebar-desktop-hidden #accordionSidebar {
        display: none !important;
      }
    }
  </style>
</head>

<body id="page-top">
  <div id="wrapper">
    @include('layouts.sidebar')
    <div id="sidebarBackdrop" aria-hidden="true"></div>
    <div id="content-wrapper" class="d-flex flex-column">
      <div id="content">
        <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow" id="topbar">
          @include('layouts.navbar')
        </nav>
        <div class="container-fluid">
          <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">@yield('title')</h1>
          </div>
          @yield('contents')
        </div>
      </div>
      @include('layouts.footer')
    </div>
  </div>
  <a class="scroll-to-top rounded" href="#page-top">
    <i class="fas fa-angle-up"></i>
  </a><!-- jQuery primero -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"
    integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>

  <!-- Scripts dependientes de jQuery -->
  <script src="{{ asset('admin_assets/vendor/jquery-easing/jquery.easing.min.js') }}"></script>
  <script src="{{ asset('admin_assets/js/sb-admin-2.min.js') }}"></script>

  <!-- Bootstrap (incluye modal.js y Popper.js) -->
  <script src="{{ asset('vendor/bootstrap/bootstrap.bundle.min.js') }}"></script>

  <!-- Otros scripts -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
  <script src="{{ asset('admin_assets/vendor/chart.js/Chart.min.js') }}"></script>

  <!-- Tu script personalizado -->
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      const sidebarToggleTop = document.getElementById('sidebarToggleTop');
      const toggleSidebar = document.getElementById('toggleSidebar');
      const sidebar = document.getElementById('accordionSidebar');
      const sidebarToggleIcon = document.getElementById('sidebarToggleIcon');
      const sidebarBackdrop = document.getElementById('sidebarBackdrop');
      const body = document.body;
      const mobileBreakpoint = 991.98;

      if (!sidebar) {
        return;
      }

      const isMobileViewport = () => window.innerWidth <= mobileBreakpoint;

      const updateSidebarIcon = (isOpen) => {
        if (!sidebarToggleIcon) {
          return;
        }

        sidebarToggleIcon.classList.toggle('fa-bars', !isOpen);
        sidebarToggleIcon.classList.toggle('fa-times', isOpen);
      };

      const closeMobileSidebar = () => {
        body.classList.remove('sidebar-mobile-open');
        sidebar.classList.remove('sidebar-visible');
        sidebarBackdrop?.classList.remove('is-visible');
      };

      const openMobileSidebar = () => {
        body.classList.add('sidebar-mobile-open');
        sidebar.classList.add('sidebar-visible');
        sidebarBackdrop?.classList.add('is-visible');
      };

      const setDesktopSidebar = (isOpen) => {
        body.classList.toggle('sidebar-desktop-hidden', !isOpen);
        sidebar.classList.remove('sidebar-visible');
      };

      const toggleSidebarState = () => {
        if (isMobileViewport()) {
          const mobileOpen = body.classList.contains('sidebar-mobile-open');

          if (mobileOpen) {
            closeMobileSidebar();
            updateSidebarIcon(false);
            return;
          }

          openMobileSidebar();
          updateSidebarIcon(true);
          return;
        }

        const desktopOpen = !body.classList.contains('sidebar-desktop-hidden');
        setDesktopSidebar(!desktopOpen);
        updateSidebarIcon(!desktopOpen);
      };

      if (sidebarToggleTop) {
        sidebarToggleTop.addEventListener('click', toggleSidebarState);
      }

      if (toggleSidebar) {
        toggleSidebar.addEventListener('click', () => {
          if (isMobileViewport()) {
            closeMobileSidebar();
            updateSidebarIcon(false);
            return;
          }

          setDesktopSidebar(false);
          updateSidebarIcon(false);
        });
      }

      sidebarBackdrop?.addEventListener('click', () => {
        closeMobileSidebar();
        updateSidebarIcon(false);
      });

      window.addEventListener('resize', () => {
        if (isMobileViewport()) {
          body.classList.remove('sidebar-desktop-hidden');
          closeMobileSidebar();
          updateSidebarIcon(false);
          return;
        }

        closeMobileSidebar();
        setDesktopSidebar(true);
        updateSidebarIcon(true);
      });

      if (isMobileViewport()) {
        closeMobileSidebar();
        updateSidebarIcon(false);
      } else {
        setDesktopSidebar(true);
        updateSidebarIcon(true);
      }

      const sidebarDropdownToggles = sidebar.querySelectorAll('.dropdown-toggle');

      sidebarDropdownToggles.forEach((toggle) => {
        toggle.addEventListener('click', (event) => {
          event.preventDefault();
          event.stopPropagation();

          const dropdownMenu = toggle.nextElementSibling;

          sidebar.querySelectorAll('.dropdown-menu').forEach((menu) => {
            if (menu !== dropdownMenu) {
              menu.style.display = 'none';
            }
          });

          if (!dropdownMenu) {
            return;
          }

          dropdownMenu.style.display = dropdownMenu.style.display === 'block' ? 'none' : 'block';
        });
      });

      document.addEventListener('click', (event) => {
        if (event.target.closest('#accordionSidebar .dropdown')) {
          return;
        }

        sidebar.querySelectorAll('.dropdown-menu').forEach((menu) => {
          menu.style.display = 'none';
        });
      });

      return;

      if (sidebarToggleTop) {
        sidebarToggleTop.addEventListener('click', () => {
          if (sidebar && (sidebar.style.display === 'none' || sidebar.style.display === '')) {
            sidebar.style.display = 'block';
            if (sidebarToggleIcon) {
              sidebarToggleIcon.classList.remove('fa-bars');
              sidebarToggleIcon.classList.add('fa-times');
            }
          } else if (sidebar) {
            sidebar.style.display = 'none';
            if (sidebarToggleIcon) {
              sidebarToggleIcon.classList.remove('fa-times');
              sidebarToggleIcon.classList.add('fa-bars');
            }
          }
        });
      }

      if (toggleSidebar) {
        toggleSidebar.addEventListener('click', () => {
          if (sidebar) sidebar.style.display = 'none';
          if (sidebarToggleIcon) {
            sidebarToggleIcon.classList.remove('fa-times');
            sidebarToggleIcon.classList.add('fa-bars');
          }
        });
      }

      // Asegúrate de que el sidebar esté oculto al cargar la página
      sidebar.style.display = 'none';
    });

    document.addEventListener('DOMContentLoaded', () => {
      const toggleButton = document.getElementById('toggle-night-mode');
      const modeIcon = document.getElementById('mode-icon');
      const topbar = document.getElementById('topbar');
      const body = document.body;
      const html = document.documentElement;
      const sidebar = document.getElementById('accordionSidebar');
      const contentWrapper = document.getElementById('content-wrapper');

      // Sincronizar body con html (que ya tiene la clase del script inline)
      if (html.classList.contains('dark-mode')) {
        body.classList.add('dark-mode');
        topbar.classList.add('navbar-dark', 'bg-dark');
        topbar.classList.remove('navbar-light', 'bg-white');
        modeIcon.classList.remove('fa-sun');
        modeIcon.classList.add('fa-moon');
        if (sidebar) sidebar.classList.add('bg-dark');
        if (contentWrapper) contentWrapper.classList.add('bg-dark');
      }

      if (toggleButton) {
        toggleButton.addEventListener('click', () => {
          // Toggle en html y body
          const isDarkMode = html.classList.toggle('dark-mode');
          body.classList.toggle('dark-mode', isDarkMode);

          // Toggle navbar
          if (topbar) {
            topbar.classList.toggle('navbar-dark', isDarkMode);
            topbar.classList.toggle('bg-dark', isDarkMode);
            topbar.classList.toggle('navbar-light', !isDarkMode);
            topbar.classList.toggle('bg-white', !isDarkMode);
          }

          // Toggle sidebar y content
          if (sidebar) sidebar.classList.toggle('bg-dark', isDarkMode);
          if (contentWrapper) contentWrapper.classList.toggle('bg-dark', isDarkMode);

          // Actualizar icono y localStorage
          if (isDarkMode) {
            localStorage.setItem('dark-mode', 'enabled');
            if (modeIcon) {
              modeIcon.classList.remove('fa-sun');
              modeIcon.classList.add('fa-moon');
            }
          } else {
            localStorage.setItem('dark-mode', 'disabled');
            if (modeIcon) {
              modeIcon.classList.remove('fa-moon');
              modeIcon.classList.add('fa-sun');
            }
          }
        });
      }
    });
  </script>
  @stack('scripts')
  @yield('scripts')
</body>

</html>
