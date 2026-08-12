<style>
  /* Variables CSS para mejor mantenimiento */
  :root {
    --primary: #bb0000;
    --primary-hover: #8f0000;
    --surface: rgba(0, 0, 0, 0.03);
    --surface-hover: rgba(0, 0, 0, 0.08);
    --border: rgba(0, 0, 0, 0.06);
    --text-primary: #3a3b45;
    --text-secondary: #6e707e;
    --shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.08);
    --shadow-md: 0 6px 16px rgba(0, 0, 0, 0.12);
    --shadow-lg: 0 12px 24px rgba(58, 59, 69, 0.15);
    --transition: 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  }

  body.dark-mode {
    --surface: rgba(255, 255, 255, 0.08);
    --surface-hover: rgba(255, 255, 255, 0.14);
    --border: rgba(255, 255, 255, 0.12);
    --text-primary: #e0e0e0;
    --text-secondary: #a0a0a0;
    --shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.3);
    --shadow-md: 0 6px 16px rgba(0, 0, 0, 0.4);
    --shadow-lg: 0 12px 24px rgba(0, 0, 0, 0.5);
  }

  /* Estilos mejorados del navbar */
  .enhanced-navbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 100%;
    gap: 0.75rem;
  }

  .enhanced-navbar .left-group {
    display: flex;
    align-items: center;
    gap: 0.75rem;
  }

  /* Botones redondos mejorados (toggle sidebar y modo oscuro) */
  #sidebarToggleTop,
  #toggle-night-mode {
    width: 40px;
    height: 40px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 999px;
    background: var(--surface);
    color: var(--text-secondary);
    border: 1px solid var(--border);
    transition: all var(--transition);
    padding: 0;
    position: relative;
    overflow: hidden;
  }

  /* Efecto ripple al hacer clic */
  #sidebarToggleTop::before,
  #toggle-night-mode::before {
    content: '';
    position: absolute;
    inset: 0;
    background: radial-gradient(circle, var(--primary) 0%, transparent 70%);
    opacity: 0;
    transform: scale(0);
    transition: transform 0s, opacity 0.15s;
  }

  #sidebarToggleTop:active::before,
  #toggle-night-mode:active::before {
    opacity: 0.3;
    transform: scale(1);
    transition: transform 0.5s, opacity 0.1s;
  }

  #sidebarToggleTop i,
  #toggle-night-mode i {
    font-size: 1.1rem;
    position: relative;
    z-index: 1;
    transition: transform var(--transition);
  }

  #sidebarToggleTop:hover,
  #toggle-night-mode:hover {
    background: var(--surface-hover);
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
  }

  #sidebarToggleTop:hover i,
  #toggle-night-mode:hover i {
    transform: scale(1.1);
  }

  #sidebarToggleTop:focus,
  #toggle-night-mode:focus {
    outline: none;
    box-shadow: 0 0 0 3px rgba(46, 89, 217, 0.1);
  }

  /* Animación del icono de modo */
  #toggle-night-mode.switching i {
    animation: iconRotate 0.6s cubic-bezier(0.68, -0.55, 0.265, 1.55);
  }

  @keyframes iconRotate {
    0% {
      transform: rotate(0deg) scale(1);
    }

    50% {
      transform: rotate(180deg) scale(0.8);
    }

    100% {
      transform: rotate(360deg) scale(1);
    }
  }

  /* Contenedor de acciones (lado derecho) */
  .navbar-actions {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    list-style: none;
    margin: 0;
  }

  .navbar-actions .nav-item {
    display: flex;
    align-items: center;
  }

  /* Toggle de usuario como píldora mejorado */
  .navbar-actions .nav-link.dropdown-toggle {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    background: var(--surface);
    border: 1px solid var(--border);
    padding: 0.35rem 0.5rem;
    border-radius: 999px;
    color: var(--text-secondary);
    transition: all var(--transition);
    position: relative;
    overflow: hidden;
  }

  /* Efecto de fondo sutil en hover */
  .navbar-actions .nav-link.dropdown-toggle::before {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, var(--primary) 0%, transparent 100%);
    opacity: 0;
    transition: opacity var(--transition);
  }

  .navbar-actions .nav-link.dropdown-toggle:hover {
    background: var(--surface-hover);
    color: var(--primary);
    transform: translateY(-1px);
    box-shadow: var(--shadow-sm);
  }

  .navbar-actions .nav-link.dropdown-toggle:hover::before {
    opacity: 0.05;
  }

  /* Avatar mejorado con gradiente */
  .img-profile {
    width: 48px; /* Aumentado tamaño (antes 36px) */
    height: 48px; /* Aumentado tamaño (antes 36px) */
    border: 2px solid transparent;
    background: linear-gradient(white, white) padding-box,
      linear-gradient(135deg, var(--primary), #4e73df) border-box;
    box-shadow: var(--shadow-sm);
    transition: all var(--transition);
  }

  body.dark-mode .img-profile {
    background: linear-gradient(#1a1a1a, #1a1a1a) padding-box,
      linear-gradient(135deg, var(--primary), #4e73df) border-box;
  }

  .navbar-actions .nav-link.dropdown-toggle:hover .img-profile {
    transform: scale(1.08) rotate(3deg);
    box-shadow: var(--shadow-md);
  }

  /* Contenedor de información del usuario mejorado */
  .user-info-container {
    display: flex;
    align-items: center;
    margin-right: 0.75rem;
  }

  .user-details {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 0.15rem;
    text-align: right;
  }

  .user-name {
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--text-primary);
    line-height: 1.2;
    letter-spacing: -0.01em;
  }

  .user-role {
    font-size: 0.75rem;
    font-weight: 500;
    color: var(--text-secondary);
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.15rem 0.5rem;
    background: var(--surface);
    border-radius: 999px;
    border: 1px solid var(--border);
    line-height: 1;
  }

  .user-role i {
    font-size: 0.65rem;
    opacity: 0.8;
  }

  /* Hover effect en user info */
  .navbar-actions .nav-link.dropdown-toggle:hover .user-name {
    color: var(--primary);
  }

  .navbar-actions .nav-link.dropdown-toggle:hover .user-role {
    background: var(--surface-hover);
    border-color: var(--primary);
    color: var(--primary);
  }

  /* Dark mode para user info */
  body.dark-mode .user-name {
    color: var(--text-primary);
  }

  body.dark-mode .user-role {
    background: var(--surface);
    border-color: var(--border);
    color: var(--text-secondary);
  }

  body.dark-mode .navbar-actions .nav-link.dropdown-toggle:hover .user-name {
    color: #4e73df;
  }

  body.dark-mode .navbar-actions .nav-link.dropdown-toggle:hover .user-role {
    background: var(--surface-hover);
    border-color: #4e73df;
    color: #4e73df;
  }

  /* Dropdown estilizado con animación */
  .dropdown-menu {
    border-radius: 0.75rem;
    border: 1px solid var(--border);
    min-width: 12rem;
    box-shadow: var(--shadow-lg);
    padding: 0.5rem;
    animation: dropdownSlide 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
  }

  @keyframes dropdownSlide {
    from {
      opacity: 0;
      transform: translateY(-10px) scale(0.95);
    }

    to {
      opacity: 1;
      transform: translateY(0) scale(1);
    }
  }

  .dropdown-item {
    padding: 0.65rem 0.85rem;
    border-radius: 0.5rem;
    transition: all 0.15s ease;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    position: relative;
    overflow: hidden;
  }

  /* Barra lateral en hover */
  .dropdown-item::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 3px;
    background: var(--primary);
    transform: translateX(-3px);
    transition: transform 0.15s ease;
  }

  .dropdown-item i {
    transition: transform 0.15s ease;
  }

  .dropdown-item:hover {
    background-color: #f8f9fc;
    padding-left: 1rem;
  }

  .dropdown-item:hover::before {
    transform: translateX(0);
  }

  .dropdown-item:hover i {
    transform: translateX(3px) scale(1.1);
  }

  /* Modo oscuro (estilos específicos del navbar) */
  body.dark-mode #sidebarToggleTop,
  body.dark-mode #toggle-night-mode {
    background: var(--surface);
    color: var(--text-primary);
    border-color: var(--border);
  }

  body.dark-mode #sidebarToggleTop:hover,
  body.dark-mode #toggle-night-mode:hover {
    background: var(--surface-hover);
  }

  body.dark-mode .navbar-actions .nav-link.dropdown-toggle {
    background: var(--surface);
    border-color: var(--border);
    color: var(--text-primary);
  }

  body.dark-mode .navbar-actions .nav-link.dropdown-toggle:hover {
    background: var(--surface-hover);
  }

  body.dark-mode .dropdown-menu {
    background: #1a1a1a;
    border-color: var(--border);
    box-shadow: var(--shadow-lg);
  }

  body.dark-mode .dropdown-item {
    color: var(--text-primary);
  }

  body.dark-mode .dropdown-item:hover {
    background: var(--surface-hover);
    color: #ffffff;
  }

  /* Responsive */
  @media (max-width: 768px) {
    .enhanced-navbar .left-group {
      gap: 0.5rem;
    }

    .navbar-actions {
      gap: 0.5rem;
    }
  }
</style>

<div class="enhanced-navbar">
  <div class="left-group">
    <button id="sidebarToggleTop" class="btn btn-link me-1" title="Mostrar/Ocultar menú">
      <i id="sidebarToggleIcon" class="fa fa-bars"></i>
    </button>
  </div>


  <ul class="navbar-nav ms-auto navbar-actions">
    <li class="nav-item">
      <button id="toggle-night-mode" class="btn btn-link" title="Cambiar modo">
        <i id="mode-icon" class="fas fa-sun"></i>
      </button>
    </li>

    <li class="nav-item dropdown no-arrow">
      <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown"
        aria-haspopup="true" aria-expanded="false">
        <div class="user-info-container d-none d-lg-flex">
          <div class="user-details">
            <span class="user-name">{{ auth()->user()->name }}</span>
            <span class="user-role">
              <i class="fas fa-shield-alt me-1"></i>{{ auth()->user()->user_type }}
            </span>
          </div>
        </div>
        <img class="img-profile rounded-circle" src="/images/logo_ecommerce.png" alt="Usuario">
      </a>
      <!-- Dropdown - User Information -->
      <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="userDropdown">
        @can('SUPER_ADMIN')
          <a class="dropdown-item" href="{{ route('profiles.index') }}">
            <i class="fas fa-user fa-sm fa-fw me-2 text-gray-400"></i>
            Perfil
          </a>
        @endcan
        <div class="dropdown-divider"></div>
        <a class="dropdown-item" href="{{ route('logout') }}">
          <i class="fas fa-sign-out-alt fa-sm fa-fw me-2 text-gray-400"></i>
          Cerrar sesión
        </a>
      </div>
    </li>
  </ul>
</div>

<script>
  // Animación del icono al cambiar modo
  document.getElementById('toggle-night-mode')?.addEventListener('click', function () {
    this.classList.add('switching');
    setTimeout(() => this.classList.remove('switching'), 600);
  });
</script>
