# Proyecto de API en PHP para Consultar Datos
API de solo consultas a una base de datos mysql, protegida con jwt.io



## Instalación de JWT (JSON Web Token) en PHP

 Los JSON Web Tokens (JWT) son una forma segura y eficiente de transmitir información entre dos partes en forma de objetos JSON. JWT se utiliza comúnmente para autenticar usuarios y proteger rutas en aplicaciones web y API.

En este repositorio, encontrarás un proyecto de ejemplo en PHP que utiliza la biblioteca firebase/php-jwt para trabajar con JWT. Sigue estos pasos para instalar y configurar la biblioteca en tu proyecto:

## Pasos de instalación:
1. Comienza clonando este repositorio en tu máquina local utilizando el siguiente comando:


```bash
  git clone https://github.com/mrdavis-dev/API_querys.git

```

2. Navega hasta el directorio de del proyecto y ejecuta el siguiente comando para instalar las dependencias requeridas:
```bash
  composer require firebase/php-jwt

```
Asegúrate de tener Composer instalado en tu sistema antes de ejecutar este comando. Puedes descargar Composer desde https://getcomposer.org/.

3. Configura tus credenciales: Antes de ejecutar el proyecto, asegúrate de configurar tus credenciales y otros detalles importantes. Revisa y modifica el archivo config.php y conn.php según los detalles de tu entorno.

```bash
  conn.php

<?php
    $servername = "********";
    $username = "**********";
    $password = "**********";
    $database = "**********";

    // Crear conexión
    $conn = new mysqli($servername, $username, $password, $database);

    // Verificar conexión
    if ($conn->connect_error) {
        die("Conexión fallida: " . $conn->connect_error);
    }
?>
```

```bash
  config.php

<?php

define('SECRET_KEY', '***clave secreta***');
define('ALGORITHM', 'HS256');

?>
```
4. Ejecuta el proyecto: Una vez que hayas completado los pasos anteriores, estás listo para ejecutar el proyecto. Puedes utilizar un servidor web local como XAMPP o WAMP para ejecutar la aplicación PHP.

5. Prueba la API JWT: Utiliza herramientas como Postman para probar las rutas protegidas por JWT. Primero, obtén un token de autenticación haciendo una solicitud a la ruta de inicio de sesión. Luego, incluye este token en el encabezado Authorization para acceder a rutas protegidas.

## Recursos adicionales:
firebase/php-jwt en GitHub: Puedes consultar la documentación oficial de la biblioteca firebase/php-jwt para obtener más detalles sobre su uso y opciones adicionales. https://github.com/firebase/php-jwt

JSON Web Tokens (JWT) Introduction: Aprende más sobre JSON Web Tokens y cómo funcionan en diferentes escenarios. https://jwt.io/