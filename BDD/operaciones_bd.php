<?php
class OperacionesBD {
    private PDO $pdo;

    // constructor recibe la conexión PDO
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    // valida nombres de tabla o columna
    private function validarIdentificador(string $identificador): void {
        if (!preg_match('/^[A-Za-z0-9_]+$/', $identificador)) {
            throw new InvalidArgumentException("Identificador inválido: {$identificador}");
        }
    }

    // inserta un registro
    public function insertar(string $tabla, array $datos): int {
        $this->validarIdentificador($tabla);
        foreach (array_keys($datos) as $campo) {
            $this->validarIdentificador($campo);
        }

        $campos = implode(', ', array_keys($datos));
        $marcadores = ':' . implode(', :', array_keys($datos));
        $sql = "INSERT INTO {$tabla} ({$campos}) VALUES ({$marcadores})";

        $sentencia = $this->pdo->prepare($sql);
        if (!$sentencia->execute($datos)) {
            throw new Exception("Error al insertar en {$tabla}");
        }

        return (int) $this->pdo->lastInsertId();
    }

    // actualiza registros
    public function actualizar(string $tabla, array $datos, string $condicion, array $parametrosCondicion = []): int {
        $this->validarIdentificador($tabla);

        $asignaciones = [];
        foreach ($datos as $campo => $valor) {
            $this->validarIdentificador($campo);
            $asignaciones[] = "{$campo} = :{$campo}";
        }

        $sql = "UPDATE {$tabla} SET " . implode(', ', $asignaciones) . " WHERE {$condicion}";
        $sentencia = $this->pdo->prepare($sql);

        if (!$sentencia->execute(array_merge($datos, $parametrosCondicion))) {
            throw new Exception("Error al actualizar en {$tabla}");
        }

        return $sentencia->rowCount();
    }

    // elimina registros
    public function eliminar(string $tabla, string $condicion, array $parametrosCondicion = []): int {
        $this->validarIdentificador($tabla);

        $sql = "DELETE FROM {$tabla} WHERE {$condicion}";
        $sentencia = $this->pdo->prepare($sql);

        if (!$sentencia->execute($parametrosCondicion)) {
            throw new Exception("Error al eliminar en {$tabla}");
        }

        return $sentencia->rowCount();
    }

    // hace consulta
    public function seleccionar(string $tabla, $campos = '*', string $condicion = '1', array $parametrosCondicion = []): array {
        $this->validarIdentificador($tabla);

        if (is_array($campos)) {
            foreach ($campos as $campo) {
                $this->validarIdentificador($campo);
            }
            $campos = implode(', ', $campos);
        }

        $sql = "SELECT {$campos} FROM {$tabla} WHERE {$condicion}";
        $sentencia = $this->pdo->prepare($sql);

        if (!$sentencia->execute($parametrosCondicion)) {
            throw new Exception("Error al seleccionar en {$tabla}");
        }

        return $sentencia->fetchAll();
    }

    // obtiene último ID insertado
    public function obtenerUltimoId(): int {
        return (int) $this->pdo->lastInsertId();
    }
}
?>