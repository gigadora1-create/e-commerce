<?php

return [
    'groups' => [
        'dashboard' => ['label' => 'Inicio y panel', 'icon' => 'fa-chart-line'],
        'inventory' => ['label' => 'Inventario', 'icon' => 'fa-boxes'],
        'locations' => ['label' => 'Ubicaciones', 'icon' => 'fa-map-marker-alt'],
        'operations' => ['label' => 'Operaciones', 'icon' => 'fa-dolly'],
        'warehouse' => ['label' => 'Bodega y trazabilidad', 'icon' => 'fa-warehouse'],
        'supplies' => ['label' => 'Proveeduria', 'icon' => 'fa-clipboard-list'],
        'catalogs' => ['label' => 'Catalogos', 'icon' => 'fa-folder-open'],
        'communications' => ['label' => 'Comunicaciones', 'icon' => 'fa-comment-dots'],
    ],

    'permissions' => [
        'dashboard.view' => ['group' => 'dashboard', 'label' => 'Ver panel principal'],

        'inventories.view' => ['group' => 'inventory', 'label' => 'Consultar inventario'],
        'inventories.manage' => ['group' => 'inventory', 'label' => 'Crear, editar y ajustar inventario'],
        'inventories.import' => ['group' => 'inventory', 'label' => 'Importar inventario'],
        'inventories.export' => ['group' => 'inventory', 'label' => 'Exportar inventario'],
        'inventory.outputs.view' => ['group' => 'inventory', 'label' => 'Consultar salidas'],
        'inventory.outputs.manage' => ['group' => 'inventory', 'label' => 'Registrar y editar salidas'],
        'inventory.outputs.import' => ['group' => 'inventory', 'label' => 'Importar salidas'],
        'inventory.outputs.export' => ['group' => 'inventory', 'label' => 'Exportar salidas'],

        'locations.view' => ['group' => 'locations', 'label' => 'Ver ubicaciones'],
        'locations.manage' => ['group' => 'locations', 'label' => 'Crear, editar, mover y eliminar ubicaciones'],

        'barcode.view' => ['group' => 'operations', 'label' => 'Consultar codigos de barras'],
        'barcode.manage' => ['group' => 'operations', 'label' => 'Registrar movimientos por codigo de barras'],
        'picking.view' => ['group' => 'operations', 'label' => 'Consultar picking'],
        'picking.manage' => ['group' => 'operations', 'label' => 'Importar, completar y cancelar picking'],

        'warehouse.view' => ['group' => 'warehouse', 'label' => 'Ver bodega y guias'],
        'warehouse.manage' => ['group' => 'warehouse', 'label' => 'Gestionar guias y ubicaciones de bodega'],
        'warehouse.export' => ['group' => 'warehouse', 'label' => 'Exportar reportes de bodega'],

        'supplies.admin' => ['group' => 'supplies', 'label' => 'Administrar catalogo y solicitudes de Proveeduria'],
        'supplies.request' => ['group' => 'supplies', 'label' => 'Crear y consultar solicitudes de Proveeduria'],

        'items.view' => ['group' => 'catalogs', 'label' => 'Ver productos'],
        'items.manage' => ['group' => 'catalogs', 'label' => 'Crear, editar y eliminar productos'],
        'cities.view' => ['group' => 'catalogs', 'label' => 'Ver bodegas'],
        'cities.manage' => ['group' => 'catalogs', 'label' => 'Gestionar bodegas'],
        'customers.view' => ['group' => 'catalogs', 'label' => 'Ver clientes'],
        'customers.manage' => ['group' => 'catalogs', 'label' => 'Gestionar clientes'],

        'messages.manage' => ['group' => 'communications', 'label' => 'Administrar mensajeria'],
    ],
];
