<?php
class Conexion {
    // Método estático para conectar a la base de datos
    public static function conectar() {
        // Datos de conexión
        $servidor = "localhost";   // Dirección del servidor de BD
        $base_datos = "BDD";    // Nombre de la base de datos
        $usuario = "root";         // Usuario de la BD
        $clave = "";               // Contraseña del usuario

        try {
            // Crear conexión usando PDO
            $conexion = new PDO(
                "mysql:host=$servidor;dbname=$base_datos;charset=utf8mb4", 
                $usuario, 
                $clave, 
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,        // Mostrar errores como excepciones
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,  // Devolver resultados como arrays asociativos
                    PDO::ATTR_EMULATE_PREPARES => false,               // Usar consultas preparadas reales (más seguro)
                ]
            );

            return $conexion; // Retornar la conexión si es exitosa

        } catch (PDOException $excepcion) {
            // Capturar error y terminar ejecución
            die("Error de conexión: " . $excepcion->getMessage());
        }
    }
}
?>