# Módulo de recomendaciones de productos

## Uso de OpenAI

OpenAI se usa únicamente cuando un administrador o gerente pulsa **Generar explicación con IA** en una recomendación. Las reglas de rotación se calculan en Vending y no se delegan al modelo. La IA solo explica los datos existentes; no cambia estados ni ejecuta acciones de catálogo, inventario, precios o compras.

La integración usa la API Responses de OpenAI desde PHP. La clave nunca se envía al navegador ni se almacena en la base de datos o el repositorio. Antes de probar la función, configure estas variables en el entorno que ejecuta Apache/PHP y reinicie Apache:

- `OPENAI_API_KEY`: clave de un proyecto de OpenAI con API habilitada y facturación/configuración correspondiente.
- `OPENAI_MODEL`: opcional; modelo permitido para el proyecto. Si no se define, se usa `gpt-4.1-mini`.

No escriba la clave en los archivos PHP versionados ni en este documento. La API solo recibe nombre del producto, categoría, tienda, periodo, cantidades agregadas y ventas diarias; no recibe identificadores personales. La solicitud usa `store: false`, pero la política y los controles de datos de la cuenta/proyecto de OpenAI siguen aplicando.

La explicación se genera bajo demanda para evitar llamadas y costos automáticos. Se guarda en la recomendación junto con el modelo, la fecha y el usuario que la solicitó. Volver a pulsar **Regenerar explicación** realiza otra llamada. Sin `OPENAI_API_KEY`, la función muestra un error claro y el resto del módulo sigue operando.

En Windows, configure `OPENAI_API_KEY` y opcionalmente `OPENAI_MODEL` en **Configuración avanzada del sistema → Variables de entorno → Variables de usuario**, cierre y vuelva a abrir el Panel de Control de XAMPP y reinicie Apache para que PHP reciba las variables. En DigitalOcean, agréguelas en la configuración de variables de entorno del servicio y vuelva a desplegar. Nunca publique el valor de la clave en capturas, chats o commits.

## Historias cubiertas parcialmente

- **HU-54 — Baja rotación:** analiza las unidades vendidas por producto y tienda durante los 30 días que terminan en la fecha local actual. El umbral es global, editable por Administración o Gerencia y se inicializa en 5. Se recomienda cuando las ventas son estrictamente menores que el umbral.
- **HU-55 — Posible sustitución:** presenta la posibilidad de considerar la sustitución; no busca ni propone un producto alternativo.
- **HU-56 — Gestión:** incluye un panel con resumen, filtros por tienda y estado, ordenamiento, paginación, historial y auditoría de cambios.

## Componentes

- `basededatos/RecomendacionesProductos.sql`: crea la configuración, las recomendaciones y el historial.
- `api/recomendaciones/recomendaciones.php`: consulta y genera recomendaciones, guarda el umbral, cuenta estados y procesa cambios de estado.
- `recomendaciones/recomendaciones.php`: interfaz de Administración y Gerencia.
- `config/layout.php` y `js/app.js`: agregan la entrada Recomendaciones a los menús laterales de páginas PHP y HTML.

La entrada que antes se mostraba en Reportes se retiró; el acceso está en el menú lateral.

## Regla y datos utilizados

1. Las ventas manuales cuentan solo si tienen un movimiento de inventario confirmado. Si existe `public.venta_nayax`, también se cuentan sus ventas confirmadas.
2. Se suman unidades por tienda, producto y día.
3. La consulta analiza productos activos que tengan ventas en el periodo o inventario registrado en una ubicación activa. Productos sin ventas y sin existencia registrada no forman parte de los candidatos.
4. Se guarda el periodo, las unidades, el umbral aplicado, las ventas diarias, la existencia actual agregada y las ubicaciones.
5. Se evita crear otra recomendación pendiente o revisada para el mismo producto y tienda. Una recomendación aceptada o descartada no bloquea una futura recomendación nueva.
6. La generación se inicia manualmente con **Analizar ahora**; no hay una tarea programada para este módulo.

## Estados y revisión

Estados almacenados: `PENDIENTE`, `REVISADA`, `ACEPTADA` y `DESCARTADA`.

- Una recomendación pendiente solo puede pasar a revisada.
- La revisión exige una observación.
- Una recomendación revisada puede aceptarse o descartarse.
- Cada transición almacena usuario, fecha, comentario y estados anterior/nuevo en el historial y registra un evento en `bitacora_auditoria`.
- Aceptar o descartar solo registra una decisión. No crea pedidos, ajustes, tareas ni modifica productos, inventario o precios.

La interfaz actualmente conserva las opciones **Aceptar** y **Descartar** para recomendaciones revisadas y los filtros para consultar esos estados. El resumen visible muestra el total, pendientes y revisadas; no muestra contadores de aceptadas o descartadas.

## Instalación y uso

1. Configurar la conexión local de Vending mediante `config/database.local.php` o las variables de entorno que use el entorno de ejecución. No subir credenciales al repositorio.
2. Aplicar `basededatos/RecomendacionesProductos.sql` una vez en la misma base de datos que usa la aplicación. La migración requiere que existan las tablas base de seguridad, catálogo, ventas e inventario.
3. Iniciar sesión como Administrador o Gerente General.
4. Abrir **Recomendaciones** en el menú lateral.
5. Ajustar el umbral global si hace falta y pulsar **Guardar umbral**.
6. Pulsar **Analizar ahora** para generar las recomendaciones.
7. Añadir una observación y marcar una recomendación pendiente como revisada. Luego, si corresponde, aceptar o descartar.

La migración inserta el valor inicial 5 solo si no existe configuración; volver a ejecutarla no reemplaza el umbral ya configurado.

La misma migración agrega las columnas de explicación IA con `ADD COLUMN IF NOT EXISTS`, por lo que también se debe volver a ejecutar tras desplegar la integración para actualizar una base existente.

## Pendiente antes de considerar completas las HU

- Programar la generación automática y decidir cómo actualizar recomendaciones cuando cambien las ventas o el umbral.
- Definir acciones de seguimiento posteriores a una decisión aceptada. Actualmente no se ejecutan acciones.
- Probar la migración y el ciclo completo contra la base de datos objetivo, incluida una llamada real a OpenAI con una clave configurada. La sintaxis de PHP y JavaScript se valida localmente, pero no sustituye la prueba de integración.
