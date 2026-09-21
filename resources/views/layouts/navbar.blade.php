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
  #toggle-night-mode,
  #supplyNotificationButton {
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
  #toggle-night-mode::before,
  #supplyNotificationButton::before {
    content: '';
    position: absolute;
    inset: 0;
    background: radial-gradient(circle, var(--primary) 0%, transparent 70%);
    opacity: 0;
    transform: scale(0);
    transition: transform 0s, opacity 0.15s;
  }

  #sidebarToggleTop:active::before,
  #toggle-night-mode:active::before,
  #supplyNotificationButton:active::before {
    opacity: 0.3;
    transform: scale(1);
    transition: transform 0.5s, opacity 0.1s;
  }

  #sidebarToggleTop i,
  #toggle-night-mode i,
  #supplyNotificationButton i {
    font-size: 1.1rem;
    position: relative;
    z-index: 1;
    transition: transform var(--transition);
  }

  #sidebarToggleTop:hover,
  #toggle-night-mode:hover,
  #supplyNotificationButton:hover {
    background: var(--surface-hover);
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
  }

  #sidebarToggleTop:hover i,
  #toggle-night-mode:hover i,
  #supplyNotificationButton:hover i {
    transform: scale(1.1);
  }

  #sidebarToggleTop:focus,
  #toggle-night-mode:focus,
  #supplyNotificationButton:focus {
    outline: none;
    box-shadow: 0 0 0 3px rgba(46, 89, 217, 0.1);
  }

  /* The unread counter lives outside the circular button and must not be clipped. */
  #supplyNotificationButton {
    width: 44px;
    height: 44px;
    overflow: visible;
    background: rgba(187, 0, 0, 0.09);
    border-color: rgba(187, 0, 0, 0.3);
    color: var(--primary);
  }

  #supplyNotificationButton i {
    font-size: 1.3rem;
  }

  #supplyNotificationButton:hover {
    background: rgba(187, 0, 0, 0.16);
    border-color: var(--primary);
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
  body.dark-mode #toggle-night-mode,
  body.dark-mode #supplyNotificationButton {
    background: var(--surface);
    color: var(--text-primary);
    border-color: var(--border);
  }

  body.dark-mode #sidebarToggleTop:hover,
  body.dark-mode #toggle-night-mode:hover,
  body.dark-mode #supplyNotificationButton:hover {
    background: var(--surface-hover);
  }

  body.dark-mode #supplyNotificationButton {
    background: rgba(255, 255, 255, 0.12);
    border-color: rgba(255, 255, 255, 0.25);
    color: #ffffff;
  }

  body.dark-mode #supplyNotificationButton:hover {
    background: rgba(187, 0, 0, 0.28);
    border-color: #ef5350;
    color: #ffffff;
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

  .notification-badge {
    position: absolute;
    top: -0.45rem;
    right: -0.45rem;
    z-index: 3;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 1.35rem;
    height: 1.35rem;
    padding: 0 0.3rem;
    border: 2px solid #fff;
    border-radius: 999px;
    background: var(--primary);
    color: #fff;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.3);
    font-size: 0.7rem;
    font-weight: 700;
    line-height: 1;
    pointer-events: none;
  }

  .notification-menu {
    width: min(23rem, calc(100vw - 1.5rem));
    min-width: min(23rem, calc(100vw - 1.5rem));
    padding: 0;
    overflow: hidden;
  }

  .notification-menu__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    padding: 0.9rem 1rem;
    border-bottom: 1px solid var(--border);
    color: var(--text-primary);
  }

  .notification-menu__header strong {
    font-size: 0.9rem;
  }

  .notification-menu__mark-all {
    border: 0;
    padding: 0;
    background: transparent;
    color: var(--primary);
    font-size: 0.75rem;
    font-weight: 700;
  }

  .notification-menu__list {
    max-height: 22rem;
    overflow-y: auto;
  }

  .notification-item {
    display: block;
    padding: 0.85rem 1rem;
    border-bottom: 1px solid var(--border);
    color: var(--text-primary);
    text-decoration: none;
  }

  .notification-item:hover,
  .notification-item:focus {
    background: var(--surface-hover);
    color: var(--text-primary);
  }

  .notification-item--unread {
    background: rgba(187, 0, 0, 0.055);
  }

  .notification-item__content {
    display: flex;
    gap: 0.75rem;
  }

  .notification-item__icon {
    display: inline-flex;
    flex: 0 0 2rem;
    align-items: center;
    justify-content: center;
    width: 2rem;
    height: 2rem;
    border-radius: 50%;
    background: var(--surface);
  }

  .notification-item__icon--success { color: #198754; }
  .notification-item__icon--warning { color: #b7791f; }
  .notification-item__icon--danger { color: #c53030; }
  .notification-item__icon--info { color: #2575b9; }

  .notification-item__title,
  .notification-item__message,
  .notification-item__time {
    display: block;
  }

  .notification-item__title {
    font-size: 0.84rem;
    font-weight: 700;
  }

  .notification-item__message {
    margin-top: 0.15rem;
    color: var(--text-secondary);
    font-size: 0.78rem;
    line-height: 1.35;
  }

  .notification-item__time {
    margin-top: 0.3rem;
    color: var(--text-secondary);
    font-size: 0.7rem;
  }

  .notification-menu__empty {
    padding: 2rem 1rem;
    color: var(--text-secondary);
    font-size: 0.85rem;
    text-align: center;
  }

  body.dark-mode .notification-badge {
    border-color: #1a1a1a;
  }

  body.dark-mode .notification-item--unread {
    background: rgba(255, 255, 255, 0.07);
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
    @if(auth()->user()->can('supplies.request') || auth()->user()->can('supplies.admin'))
      <li class="nav-item dropdown no-arrow">
        <button id="supplyNotificationButton" class="btn btn-link" type="button" data-bs-toggle="dropdown"
          data-bs-auto-close="outside" aria-expanded="false" aria-label="Notificaciones de Proveeduria">
          <i class="fas fa-bell"></i>
          <span id="supplyNotificationBadge" class="notification-badge" hidden>0</span>
        </button>
        <div class="dropdown-menu dropdown-menu-end notification-menu shadow animated--grow-in" aria-labelledby="supplyNotificationButton">
          <div class="notification-menu__header">
            <strong>Notificaciones de Proveeduria</strong>
            <button id="supplyNotificationsMarkAll" class="notification-menu__mark-all" type="button">Marcar leidas</button>
          </div>
          <div id="supplyNotificationsList" class="notification-menu__list" aria-live="polite">
            <div class="notification-menu__empty">Cargando notificaciones...</div>
          </div>
        </div>
      </li>
    @endif
    <li class="nav-item">
      <button id="toggle-night-mode" class="btn btn-link" type="button" title="Cambiar modo" aria-label="Cambiar modo visual">
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
        <a class="dropdown-item" href="{{ route('preferences.edit') }}">
          <i class="fas fa-sliders-h fa-sm fa-fw me-2 text-gray-400"></i>
          Preferencias
        </a>
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

@if(auth()->user()->can('supplies.request') || auth()->user()->can('supplies.admin'))
<script>
  document.addEventListener('DOMContentLoaded', function () {
    const button = document.getElementById('supplyNotificationButton');
    const badge = document.getElementById('supplyNotificationBadge');
    const list = document.getElementById('supplyNotificationsList');
    const markAll = document.getElementById('supplyNotificationsMarkAll');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    const endpoint = @json(route('supplies.notifications.index'));
    const markAllEndpoint = @json(route('supplies.notifications.read-all'));
    let unreadCount = null;

    if (!button || !badge || !list || !markAll) return;

    const escapeHtml = function (value) {
      const node = document.createElement('span');
      node.textContent = value || '';
      return node.innerHTML;
    };

    const updateBadge = function (count) {
      badge.hidden = count < 1;
      badge.textContent = count > 99 ? '99+' : String(count);
      button.setAttribute('aria-label', count > 0
        ? `Notificaciones de Proveeduria: ${count} sin leer`
        : 'Notificaciones de Proveeduria');
    };

    const currentAppUrl = function (value) {
      try {
        const url = new URL(value, window.location.origin);
        return `${url.pathname}${url.search}${url.hash}`;
      } catch (error) {
        return value || '/supplies/issues';
      }
    };

    const render = function (notifications) {
      if (!notifications.length) {
        list.innerHTML = '<div class="notification-menu__empty"><i class="far fa-bell d-block mb-2"></i>No tienes notificaciones de Proveeduria.</div>';
        return;
      }

      list.innerHTML = notifications.map(function (notification) {
        return `<a class="notification-item ${notification.read ? '' : 'notification-item--unread'}" href="${escapeHtml(currentAppUrl(notification.url))}" data-notification-id="${escapeHtml(notification.id)}">
          <span class="notification-item__content">
            <span class="notification-item__icon notification-item__icon--${escapeHtml(notification.level)}"><i class="fas ${escapeHtml(notification.icon)}"></i></span>
            <span>
              <span class="notification-item__title">${escapeHtml(notification.title)}</span>
              <span class="notification-item__message">${escapeHtml(notification.message)}</span>
              <span class="notification-item__time">${escapeHtml(notification.created_at)}</span>
            </span>
          </span>
        </a>`;
      }).join('');
    };

    const loadNotifications = async function (announce) {
      try {
        const response = await fetch(endpoint, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
        if (!response.ok) throw new Error('No fue posible cargar las notificaciones.');

        const payload = await response.json();
        if (announce && unreadCount !== null && payload.unread_count > unreadCount && window.AppAlerts) {
          window.AppAlerts.notify({ icon: 'info', title: 'Nueva alerta de Proveeduria', text: 'Tienes una solicitud o actualización pendiente por revisar.' });
        }
        unreadCount = payload.unread_count;
        updateBadge(payload.unread_count);
        render(payload.notifications);
      } catch (error) {
        list.innerHTML = '<div class="notification-menu__empty">No fue posible cargar las notificaciones.</div>';
      }
    };

    list.addEventListener('click', async function (event) {
      const item = event.target.closest('[data-notification-id]');
      if (!item) return;

      event.preventDefault();
      const notificationId = item.dataset.notificationId;
      try {
        await fetch(`${endpoint}/${notificationId}/read`, {
          method: 'PATCH',
          headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
          credentials: 'same-origin',
        });
      } finally {
        window.location.assign(item.href);
      }
    });

    markAll.addEventListener('click', async function () {
      try {
        const response = await fetch(markAllEndpoint, {
          method: 'PATCH',
          headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
          credentials: 'same-origin',
        });
        if (!response.ok) throw new Error('No fue posible marcar las notificaciones.');
        await loadNotifications(false);
      } catch (error) {
        window.AppAlerts?.notify({ icon: 'error', title: 'Error de notificaciones', text: 'No fue posible marcar las alertas como leidas.' });
      }
    });

    button.addEventListener('show.bs.dropdown', function () { loadNotifications(false); });
    loadNotifications(false);
    window.setInterval(function () { loadNotifications(true); }, 60000);
  });
</script>
@endif
