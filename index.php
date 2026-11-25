<?php
// Ruta relativa a la carpeta actual: funciona tanto si el proyecto está en la
// raíz del servidor como si está en una subcarpeta (p. ej. /VendingMiniMarket).
header('Location: index/index.html', true, 302);
exit;