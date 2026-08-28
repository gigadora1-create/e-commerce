# Puente de proveeduria para req.glecolombia.com

## Instalacion en req

1. Copie `ReqSupplyCaseController.php` a `app/Http/Controllers/Api/`.
2. Agregue el contenido de `api.php` al archivo `routes/api.php` de req.
3. Agregue en `config/services.php` de req:

```php
'ecommerce_supply' => [
    'token' => env('ECOMMERCE_SUPPLY_TOKEN'),
],
```

4. Agregue en el `.env` de req un secreto largo y privado:

```env
ECOMMERCE_SUPPLY_TOKEN=CAMBIAR_POR_UN_SECRETO_LARGO_Y_UNICO
```

El mismo valor debe configurarse en el `.env` de E-commerce como `REQ_SUPPLY_TOKEN`.

No se requiere ninguna migracion ni cambio de estructura en la base de datos de req.

## Contrato

E-commerce hace `POST https://req.glecolombia.com/api/v1/proveeduria/cases` con `Authorization: Bearer <token>`.
E-commerce guarda el ID del caso creado y todos los intentos de envio. req no agrega campos, tablas ni relaciones para la integracion.

El caso se asigna al usuario existente de req usando `requester.email`. El proceso, regional y centro de costo se toman de `centros` dentro de req, por lo que son siempre los valores operativos del usuario registrado.

## Operacion

Una solicitud de compra se crea siempre en E-commerce. Si req no responde, la solicitud local sigue creada y la sincronizacion queda registrada como fallida, sin perder datos.

Como req no conserva una clave de idempotencia, no se debe reintentar automaticamente despues de un fallo de conexion o tiempo de espera: primero se debe verificar en req si el caso fue creado. Las respuestas HTTP de validacion y autenticacion no crean casos y se pueden reintentar despues de corregirlas.
