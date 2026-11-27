# Módulo de recomendaciones de productos

## Uso de IA (Groq)

Groq se usa únicamente cuando un administrador o gerente pulsa **Generar explicación con IA** en una recomendación. Las reglas de rotación se calculan en Vending y no se delegan al modelo. La IA solo explica los datos existentes; no cambia estados ni ejecuta acciones de catálogo, inventario, precios o compras.

La integración usa la API Responses de Groq (compatible con el formato de OpenAI) desde PHP, apuntando a `https://api.groq.com/openai/v1/responses`. Groq ofrece un nivel gratuito sin tarjeta de crédito, suficiente para este uso bajo demanda. La clave nunca se envía al navegador ni se almacena en la base de datos o el repositorio. Antes de probar la función, configure estas variables en el entorno que ejecuta Apache/PHP y reinicie Apache:

- `GROQ_API_KEY`: clave de una cuenta gratuita de Groq (https://console.groq.com/keys).
- `GROQ_MODEL`: opcional; modelo disponible en Groq. Si no se define, se usa `qwen/qwen3.8-27b` (no usa razonamiento oculto por defecto, a diferencia de los modelos `openai/gpt-oss-*`, por lo que no agota `max_output_tokens` antes de producir la explicación).

No escriba la clave en los archivos PHP versionados ni en este documento. La API solo recibe nombre del producto, categoría, tienda, periodo, cantidades agregadas y ventas diarias; no recibe identificadores personales. La solicitud usa `store: false`, pero la política y los controles de datos de la cuenta/proyecto de Groq siguen aplicando.

La explicación se genera bajo demanda para evitar llamadas y costos automáticos. Se guarda en la recomendación junto con el modelo, la fecha y el usuario que la solicitó. Volver a pulsar **Regenerar explicación** realiza otra llamada. Sin `GROQ_API_KEY`, la función muestra un error claro y el resto del módulo sigue operando.

En Windows, configure `GROQ_API_KEY` y opcionalmente `GROQ_MODEL` en **Configuración avanzada del sistema → Variables de entorno → Variables de usuario**, cierre y vuelva a abrir el Panel de Control de XAMPP y reinicie Apache para que PHP reciba las variables. En macOS/XAMPP, agréguelas como `SetEnv GROQ_API_KEY "..."` en `etc/extra/httpd-xampp.conf` y reinicie Apache. En DigitalOcean, agréguelas en la configuración de variables de entorno del servicio y vuelva a desplegar. Nunca publique el valor de la clave en capturas, chats o commits.

## Historias cubiertas

- **HU-54 — Baja rotación:** analiza las unidades vendidas por producto y tienda durante los 30 días que terminan en la fecha local actual. El umbral es global, editable por Administración o Gerencia y se inicializa en 5. Se recomienda cuando las ventas son estrictamente menores que el umbral.
- **HU-55 — Producto alternativo:** junto con cada recomendación de baja rotación nueva, el sistema busca dentro de la misma categoría y tienda un producto activo con mejor rotación (ventas ≥ umbral y mayores que las del producto original) y existencia disponible (> 0). Si encuentra candidatos, guarda el de mejor puntaje (diferencia de unidades vendidas, con la existencia como criterio de desempate) junto con una justificación basada en datos. La decisión (aceptar/descartar/dejar pendiente) se gestiona por separado de la recomendación de baja rotación y no modifica catálogo, inventario ni precios.
- **HU-56 — Gestión:** incluye un panel con resumen, filtros por tienda y estado, ordenamiento, paginación, historial y auditoría de cambios.

## Componentes

- `basededatos/RecomendacionesProductos.sql`: crea la configuración, las recomendaciones, el historial, las alternativas sugeridas (`recomendacion_producto_alternativa`) y su historial (`recomendacion_producto_alternativa_historial`).
- `api/recomendaciones/recomendaciones.php`: consulta y genera recomendaciones y sus alternativas, guarda el umbral, cuenta estados y procesa cambios de estado de recomendaciones y de alternativas.
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

## Alternativas de producto (HU-55)

Cada vez que se crea una recomendación de baja rotación nueva, el motor busca en la misma transacción un producto alternativo:

1. Debe pertenecer a la misma `categoria` que el producto original y estar activo.
2. Debe tener existencia agregada mayor que cero en la misma tienda.
3. Su rotación (unidades vendidas en el mismo periodo de 30 días) debe ser mayor o igual al umbral vigente y mayor que la del producto original; así no se sugiere otro producto que también tenga baja rotación.
4. Entre los candidatos que cumplen lo anterior, se elige el de mayor diferencia de unidades vendidas frente al original (y, en caso de empate, el de mayor existencia).
5. Si no hay candidatos, no se crea ninguna alternativa y la tarjeta de la recomendación lo indica explícitamente.

La alternativa se guarda en `recomendacion_producto_alternativa` con la rotación y existencia de ambos productos y una justificación en texto generada a partir de esos datos (sin IA). Su estado (`PENDIENTE`, `ACEPTADA`, `DESCARTADA`) se gestiona de forma independiente del estado de la recomendación de baja rotación: aceptar o descartar una alternativa solo registra la decisión (usuario, fecha y comentario, obligatorio al descartar) en su propio historial y en la bitácora de auditoría; no genera pedidos ni modifica catálogo, inventario o precios.

## Estados y revisión

Estados almacenados: `PENDIENTE`, `REVISADA`, `ACEPTADA` y `DESCARTADA`.

- Una recomendación pendiente solo puede pasar a revisada.
- La revisión exige una observación.
- Una recomendación revisada puede aceptarse o descartarse.
- Al marcarla como revisada, el panel selecciona automáticamente el filtro **Revisada** para mostrar las recomendaciones que esperan una decisión.
- Al cambiar el filtro de estado, la consulta solicita ese estado directamente a la API; así las pendientes no desplazan a las revisadas del máximo de registros de la lista.
- Aceptar registra la decisión y admite un comentario opcional. Descartar requiere un comentario que indique el motivo.
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
7. En una recomendación pendiente, añadir una observación y pulsar **Marcar revisada**. La lista selecciona el filtro **Revisada** automáticamente.
8. En el filtro **Revisada**, elegir **Aceptar** para registrar la aprobación (el comentario es opcional) o escribir el motivo y elegir **Descartar**. El motivo de descarte es obligatorio.
9. Consultar el historial de revisión y decisiones de la tarjeta para ver los comentarios, responsables y fechas. Las decisiones finales se pueden consultar con los filtros **Aceptada** y **Descartada**; no se pueden volver a cambiar.
10. Si el sistema encontró un producto alternativo en la misma categoría y tienda, la tarjeta lo muestra con su rotación, existencia y justificación. Elegir **Aceptar alternativa** (comentario opcional) o escribir el motivo y elegir **Descartar alternativa** (comentario obligatorio). Esta decisión es independiente de la de la recomendación de baja rotación y tampoco se puede volver a cambiar.

La migración inserta el valor inicial 5 solo si no existe configuración; volver a ejecutarla no reemplaza el umbral ya configurado.

La misma migración agrega las columnas de explicación IA con `ADD COLUMN IF NOT EXISTS`, por lo que también se debe volver a ejecutar tras desplegar la integración para actualizar una base existente.

## Pendiente antes de considerar completas las HU

- Programar la generación automática y decidir cómo actualizar recomendaciones (y sus alternativas) cuando cambien las ventas o el umbral.
- Definir acciones de seguimiento posteriores a una decisión aceptada (de la recomendación o de su alternativa): generar pedido, ajuste de inventario o tarea de revisión según el tipo (HU-56.3). Actualmente ninguna decisión ejecuta acciones automáticas; las tablas `pedido`/`pedido_detalle` ya existen y podrían reutilizarse para esto.
- Probar el ciclo completo contra la base de datos objetivo con datos reales de uso diario, incluida una llamada real a Groq con una clave configurada.
