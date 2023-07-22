<?php
header('Content-Type: application/json');


// Establecer los encabezados de respuesta para permitir el acceso desde cualquier dominio (CORS)
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require 'conn.php';
require 'vendor/autoload.php';
require 'config.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

$key = SECRET_KEY;

// Función para verificar el token JWT
function verifyToken($token) {
    global $key;
    try {
        // Verificar token usando la clave secreta
        $decoded = JWT::decode($token, new Key($key, ALGORITHM));
        return $decoded;
    } catch (Exception $e) {
        // Si el token es inválido o ha expirado, enviar respuesta de error al cliente
        return null;
    }
}


// Función para verificar las credenciales del usuario en la tabla de usuarios en MySQL
function verifyCredentials($username, $password)
{
    global $conn;

    // Escapar las variables para evitar inyección de SQL (mejor usar sentencias preparadas)
    $username = mysqli_real_escape_string($conn, $username);
    $password = mysqli_real_escape_string($conn, $password);

    // Consulta SQL para verificar las credenciales del usuario
    $sql = "SELECT * FROM usuarios WHERE usuario = '$username' AND clave = '$password'";
    $result = $conn->query($sql);

    // Verificar si se encontró un usuario con las credenciales proporcionadas
    if ($result->num_rows === 1) {
        return true;
    }

    return false;
}



// Ruta para autenticar al usuario y obtener el token JWT
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['route']) && $_POST['route'] === 'login') {
    global $key;
    // Obtener los datos de inicio de sesión del usuario desde la solicitud
    $username = isset($_POST['usuario']) ? $_POST['usuario'] : '';
    $password = isset($_POST['clave']) ? $_POST['clave'] : '';

    // Verificar las credenciales del usuario
    if (verifyCredentials($username, $password)) {
        // Las credenciales son válidas, genera el token JWT
        $tokenData = array(
            "usuario" => $username,
            "exp" => time() + 3600 // El token expirará en 1 hora desde ahora
        );

        // Genera el token JWT utilizando la misma clave secreta utilizada en la verificación del token
        $jwt = JWT::encode($tokenData, $key, 'HS256');

        // Envía el token en la respuesta al cliente
        echo json_encode(["token" => $jwt]);
        exit();
    } else {
        // Las credenciales son inválidas
        http_response_code(401);
        echo json_encode(["message" => "Credenciales inválidas."]);
        exit();
    }
}



// Ruta para obtener todos los documentos con detalledocumentos
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['route']) && $_GET['route'] === 'documentos') {    

    // Obtener token del encabezado de la solicitud
    $jwt = $_SERVER['HTTP_AUTHORIZATION'];
    //echo $jwt;

    if ($jwt) {
        // Verificar el token
        $decodedToken = verifyToken($jwt);

        if ($decodedToken) {
            // El token es válido, puedes acceder a la información del payload
            // a través del objeto $decodedToken
            $userId = $decodedToken->usuario;

            // Aquí puedes realizar el acceso a los datos protegidos con el token JWT
            $start = isset($_GET['start']) ? intval($_GET['start']) : 0;
            $end = isset($_GET['end']) ? intval($_GET['end']) : 0;
            $fechaInicio = isset($_GET['fechainicio']) ? $_GET['fechainicio'] : '';
            $fechaFin = isset($_GET['fechafin']) ? $_GET['fechafin'] : '';

            $sql = "SELECT *
            FROM documentos
            INNER JOIN detalledocumentos ON documentos.IdDocumento = detalledocumentos.IdDocumento
                AND documentos.TipoDocumento = detalledocumentos.TipoDocumento
            WHERE documentos.Fecha BETWEEN '$fechaInicio' AND '$fechaFin' order by documentos.IdDocumento
            LIMIT $start, $end";

            $result = $conn->query($sql);

            if ($result->num_rows > 0) {
                $response = array();

                while ($row = $result->fetch_assoc()) {
                    $response[] = $row;
                }

                // Función para limpiar el array
                function array_clean($array) {
                    foreach ($array as &$value) {
                        if (is_array($value)) {
                            $value = array_clean($value);
                        } else {
                            $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
                        }
                    }
                    unset($value);

                    return $array;
                }

                // Limpiar el array de respuesta
                $response = array_clean($response);

                header('Content-Type: application/json');
                echo json_encode($response);
            } else {
                $response = array();
                echo json_encode($response);
            }
        } else {
            // Token inválido o expirado
            http_response_code(401);
            echo json_encode(["mensaje" => "Token inválido"]);
        }
    } else {
        // Si no se proporcionó ningún token, enviar respuesta de error al cliente
        http_response_code(401);
        echo json_encode(["mensaje" => "Token no proporcionado"]);
    }
}



// Ruta para obtener todos los proveedores
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['route']) && $_POST['route'] === 'proveedores') {
    $start = isset($_POST['start']) ? intval($_POST['start']) : 0;
    $end = isset($_POST['end']) ? intval($_POST['end']) : 0;
    //$idProveedor = isset($_POST['idProveedor']) ? $_POST['idProveedor'] : '';

    // Obtener token del encabezado de la solicitud
    $jwt = $_SERVER['HTTP_AUTHORIZATION'];
    //echo $jwt;

    if ($jwt) {
        // Verificar el token
        $decodedToken = verifyToken($jwt);

        if ($decodedToken) {

            $sql = "SELECT * FROM proveedores LIMIT $start, $end";
            $result = $conn->query($sql);
        
            if ($result->num_rows > 0) {
                $response = array();
        
                while ($row = $result->fetch_assoc()) {
                    $response[] = $row;
                }
        
                // Función para limpiar el array
                function array_clean($array) {
                    foreach ($array as &$value) {
                        if (is_array($value)) {
                            $value = array_clean($value);
                        } else {
                            $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
                        }
                    }
                    unset($value);
        
                    return $array;
                }
        
                // Limpiar el array de respuesta
                $response = array_clean($response);
        
                header('Content-Type: application/json');
                echo json_encode($response);
            } else {
                echo "No se encontraron resultados.";
            }

        }else {
            // Token inválido o expirado
            http_response_code(401);
            echo json_encode(["mensaje" => "Token inválido"]);
        }
    } else {
        // Si no se proporcionó ningún token, enviar respuesta de error al cliente
        http_response_code(401);
        echo json_encode(["mensaje" => "Token no proporcionado"]);
    }
}


// Ruta para obtener todas las bodegas
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['route']) && $_POST['route'] === 'bodegas') {
    $start = isset($_POST['start']) ? intval($_POST['start']) : 0;
    $end = isset($_POST['end']) ? intval($_POST['end']) : 0;
    $idBodega = isset($_POST['idBodega']) ? $_POST['idBodega'] : '';


    // Obtener token del encabezado de la solicitud
    $jwt = $_SERVER['HTTP_AUTHORIZATION'];
    //echo $jwt;

    if ($jwt) {
        // Verificar el token
        $decodedToken = verifyToken($jwt);

        if ($decodedToken) {

            $sql = "SELECT * FROM bodegas WHERE idBodega = '$idBodega' LIMIT $start, $end";
            $result = $conn->query($sql);
        
            if ($result->num_rows > 0) {
                $response = array();
        
                while ($row = $result->fetch_assoc()) {
                    $response[] = $row;
                }
        
                // Función para limpiar el array
                function array_clean($array) {
                    foreach ($array as &$value) {
                        if (is_array($value)) {
                            $value = array_clean($value);
                        } else {
                            $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
                        }
                    }
                    unset($value);
        
                    return $array;
                }
        
                // Limpiar el array de respuesta
                $response = array_clean($response);
                
                header('Content-Type: application/json');
                echo json_encode($response);
            } else {
                echo "No se encontraron resultados.";
            }

        }else {
            // Token inválido o expirado
            http_response_code(401);
            echo json_encode(["mensaje" => "Token inválido"]);
        }
    } else {
        // Si no se proporcionó ningún token, enviar respuesta de error al cliente
        http_response_code(401);
        echo json_encode(["mensaje" => "Token no proporcionado"]);
    }
}

// Ruta para obtener todos los artículos
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['route']) && $_POST['route'] === 'articulos') {
    $start = isset($_POST['start']) ? intval($_POST['start']) : 0;
    $end = isset($_POST['end']) ? intval($_POST['end']) : 0;
    $idArticulo = isset($_POST['idArticulo']) ? $_POST['idArticulo'] : '';
    $descripcion = isset($_POST['descripcion']) ? $_POST['descripcion'] : '';
    $descripcionCorta = isset($_POST['descripcionCorta']) ? $_POST['descripcionCorta'] : '';
    $cantidad = isset($_POST['cantidad']) ? $_POST['cantidad'] : '';

    // Obtener token del encabezado de la solicitud
    $jwt = $_SERVER['HTTP_AUTHORIZATION'];
    //echo $jwt;

    if ($jwt) {
        // Verificar el token
        $decodedToken = verifyToken($jwt);

        if ($decodedToken) {

            $sql = "SELECT * FROM articulos WHERE idArticulo = '$idArticulo' AND descripcion = '$descripcion' AND descripcionCorta = '$descripcionCorta' AND cantidad = '$cantidad' LIMIT $start, $end";
            $result = $conn->query($sql);
        
            if ($result->num_rows > 0) {
                $response = array();
        
                while ($row = $result->fetch_assoc()) {
                    $response[] = $row;
                }
        
                 // Función para limpiar el array
                 function array_clean($array) {
                    foreach ($array as &$value) {
                        if (is_array($value)) {
                            $value = array_clean($value);
                        } else {
                            $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
                        }
                    }
                    unset($value);
        
                    return $array;
                }
        
                // Limpiar el array de respuesta
                $response = array_clean($response);
        
                header('Content-Type: application/json');
                echo json_encode($response);
            } else {
                echo "No se encontraron resultados.";
            }

}else {
            // Token inválido o expirado
            http_response_code(401);
            echo json_encode(["mensaje" => "Token inválido"]);
        }
    } else {
        // Si no se proporcionó ningún token, enviar respuesta de error al cliente
        http_response_code(401);
        echo json_encode(["mensaje" => "Token no proporcionado"]);
    }
}


// Ruta para obtener todos los tipos de artículos
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['route']) && $_POST['route'] === 'tipoarticulos') {

    $start = isset($_POST['start']) ? intval($_POST['start']) : 0;
    $end = isset($_POST['end']) ? intval($_POST['end']) : 0;
    $sql = "SELECT * FROM tipoarticulos LIMIT $start, $end";
    $result = $conn->query($sql);

    
    if ($result->num_rows > 0) {
        $response = array();

        while ($row = $result->fetch_assoc()) {
            $response[] = $row;
        }

        // Función para limpiar el array
        function array_clean($array) {
            foreach ($array as &$value) {
                if (is_array($value)) {
                    $value = array_clean($value);
                } else {
                    $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
                }
            }
            unset($value);

            return $array;
        }

        // Limpiar el array de respuesta
        $response = array_clean($response);

        header('Content-Type: application/json');
        echo json_encode($response);
    } else {
        echo "No se encontraron resultados.";
    }
}




// Ruta para obtener todos los documentos por pagar junto con los detallesdocumentosporpagar
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['route']) && $_POST['route'] === 'documentosporpagar') {
    $fechaInicio = isset($_POST['fechaInicio']) ? $_POST['fechaInicio'] : '';
    $fechaFin = isset($_POST['fechaFin']) ? $_POST['fechaFin'] : '';
    $start = isset($_POST['start']) ? intval($_POST['start']) : 0;
    $end = isset($_POST['end']) ? intval($_POST['end']) : 0;

    // Obtener token del encabezado de la solicitud
    $jwt = $_SERVER['HTTP_AUTHORIZATION'];
    //echo $jwt;

    if ($jwt) {
        // Verificar el token
        $decodedToken = verifyToken($jwt);

        if ($decodedToken) {

            $sql = "SELECT documentosporpagar.*, detalledocumentosporpagar.*
            FROM documentosporpagar
            INNER JOIN detalledocumentosporpagar ON documentosporpagar.IdProveedor = detalledocumentosporpagar.IdProveedor
                AND documentosporpagar.IdDocumento = detalledocumentosporpagar.IdDocumento
                AND documentosporpagar.TipoDocumento = detalledocumentosporpagar.tipodocumento
            WHERE documentosporpagar.Fecha BETWEEN '$fechaInicio' AND '$fechaFin' order by documentosporpagar.IdDocumento limit $start, $end";

    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $response = array();

        while ($row = $result->fetch_assoc()) {
            $response[] = $row;
        }

        // Función para limpiar el array
        function array_clean($array) {
            foreach ($array as &$value) {
                if (is_array($value)) {
                    $value = array_clean($value);
                } else {
                    $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
                }
            }
            unset($value);

            return $array;
        }

        // Limpiar el array de respuesta
        $response = array_clean($response);

        header('Content-Type: application/json');
        echo json_encode($response);
    } else {
        echo "No se encontraron resultados.";
    }

}else {
            // Token inválido o expirado
            http_response_code(401);
            echo json_encode(["mensaje" => "Token inválido"]);
        }
    } else {
        // Si no se proporcionó ningún token, enviar respuesta de error al cliente
        http_response_code(401);
        echo json_encode(["mensaje" => "Token no proporcionado"]);
    }
}


// Ruta para obtener todas las subcategorías
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['route']) && $_POST['route'] === 'subcategoria') {
    $start = isset($_POST['start']) ? intval($_POST['start']) : 0;
    $end = isset($_POST['end']) ? intval($_POST['end']) : 0;


    // Obtener token del encabezado de la solicitud
    $jwt = $_SERVER['HTTP_AUTHORIZATION'];
    //echo $jwt;

    if ($jwt) {
        // Verificar el token
        $decodedToken = verifyToken($jwt);

        if ($decodedToken) {

            $sql = "SELECT * FROM subcategoria LIMIT $start, $end";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $response = array();

        while ($row = $result->fetch_assoc()) {
            $response[] = $row;
        }

        // Función para limpiar el array
        function array_clean($array) {
            foreach ($array as &$value) {
                if (is_array($value)) {
                    $value = array_clean($value);
                } else {
                    $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
                }
            }
            unset($value);

            return $array;
        }

        // Limpiar el array de respuesta
        $response = array_clean($response);

        header('Content-Type: application/json');
        echo json_encode($response);
    } else {
        echo "No se encontraron resultados.";
    }


}else {
            // Token inválido o expirado
            http_response_code(401);
            echo json_encode(["mensaje" => "Token inválido"]);
        }
    } else {
        // Si no se proporcionó ningún token, enviar respuesta de error al cliente
        http_response_code(401);
        echo json_encode(["mensaje" => "Token no proporcionado"]);
    }
}

// Ruta para obtener todas las categorías
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['route']) && $_POST['route'] === 'cat') {

    // Obtener token del encabezado de la solicitud
    $jwt = $_SERVER['HTTP_AUTHORIZATION'];
    //echo $jwt;

    if ($jwt) {
        // Verificar el token
        $decodedToken = verifyToken($jwt);

        if ($decodedToken) {

            $sql = "SELECT * FROM cat";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $response = array();

        while ($row = $result->fetch_assoc()) {
            $response[] = $row;
        }

        // Función para limpiar el array
        function array_clean($array) {
            foreach ($array as &$value) {
                if (is_array($value)) {
                    $value = array_clean($value);
                } else {
                    $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
                }
            }
            unset($value);

            return $array;
        }

        // Limpiar el array de respuesta
        $response = array_clean($response);

        header('Content-Type: application/json');
        echo json_encode($response);
    } else {
        echo "No se encontraron resultados.";
    }


}else {
            // Token inválido o expirado
            http_response_code(401);
            echo json_encode(["mensaje" => "Token inválido"]);
        }
    } else {
        // Si no se proporcionó ningún token, enviar respuesta de error al cliente
        http_response_code(401);
        echo json_encode(["mensaje" => "Token no proporcionado"]);
    }
}

// Ruta para obtener todas las marcas
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['route']) && $_POST['route'] === 'marcas') {
    $start = isset($_POST['start']) ? intval($_POST['start']) : 0;
    $end = isset($_POST['end']) ? intval($_POST['end']) : 0;

    // Obtener token del encabezado de la solicitud
    $jwt = $_SERVER['HTTP_AUTHORIZATION'];
    //echo $jwt;

    if ($jwt) {
        // Verificar el token
        $decodedToken = verifyToken($jwt);

        if ($decodedToken) {

            $sql = "SELECT * FROM marcas LIMIT $start, $end";
            $result = $conn->query($sql);
        
            if ($result->num_rows > 0) {
                $response = array();
        
                while ($row = $result->fetch_assoc()) {
                    $response[] = $row;
                }
        
                // Función para limpiar el array
                function array_clean($array) {
                    foreach ($array as &$value) {
                        if (is_array($value)) {
                            $value = array_clean($value);
                        } else {
                            $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
                        }
                    }
                    unset($value);
        
                    return $array;
                }
        
                // Limpiar el array de respuesta
                $response = array_clean($response);
        
                header('Content-Type: application/json');
                echo json_encode($response);
            } else {
                echo "No se encontraron resultados.";
            }

}else {
            // Token inválido o expirado
            http_response_code(401);
            echo json_encode(["mensaje" => "Token inválido"]);
        }
    } else {
        // Si no se proporcionó ningún token, enviar respuesta de error al cliente
        http_response_code(401);
        echo json_encode(["mensaje" => "Token no proporcionado"]);
    }
}

// Ruta para obtener todos los clientes
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['route']) && $_POST['route'] === 'clientes') {
    $start = isset($_POST['start']) ? intval($_POST['start']) : 0;
    $end = isset($_POST['end']) ? intval($_POST['end']) : 0;

    // Obtener token del encabezado de la solicitud
    $jwt = $_SERVER['HTTP_AUTHORIZATION'];
    //echo $jwt;

    if ($jwt) {
        // Verificar el token
        $decodedToken = verifyToken($jwt);

        if ($decodedToken) {

            $sql = "SELECT * FROM clientes LIMIT $start, $end";
            $result = $conn->query($sql);
        
            if ($result->num_rows > 0) {
                $response = array();
        
                while ($row = $result->fetch_assoc()) {
                    $response[] = $row;
                }
        
                // Función para limpiar el array
                function array_clean($array) {
                    foreach ($array as &$value) {
                        if (is_array($value)) {
                            $value = array_clean($value);
                        } else {
                            $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
                        }
                    }
                    unset($value);
        
                    return $array;
                }
        
                // Limpiar el array de respuesta
                $response = array_clean($response);
        
                header('Content-Type: application/json');
                echo json_encode($response);
            } else {
                echo "No se encontraron resultados.";
            }

}else {
            // Token inválido o expirado
            http_response_code(401);
            echo json_encode(["mensaje" => "Token inválido"]);
        }
    } else {
        // Si no se proporcionó ningún token, enviar respuesta de error al cliente
        http_response_code(401);
        echo json_encode(["mensaje" => "Token no proporcionado"]);
    }
}


// Cerrar conexión
$conn->close();
?>