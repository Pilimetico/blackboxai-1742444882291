<?php
class Database {
    private static $instance = null;
    private $connection;

    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $this->connection = new PDO($dsn, DB_USER, DB_PASS);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->connection->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        } catch (PDOException $e) {
            error_log("Error de conexión: " . $e->getMessage());
            throw new Exception("Error de conexión a la base de datos");
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->connection;
    }

    public function query($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Error en la consulta: " . $e->getMessage());
            throw new Exception("Error al ejecutar la consulta");
        }
    }

    public function insert($table, $data) {
        try {
            $fields = array_keys($data);
            $values = array_fill(0, count($fields), '?');
            
            $sql = "INSERT INTO " . $table . " (" . implode(", ", $fields) . ") 
                    VALUES (" . implode(", ", $values) . ")";
            
            $stmt = $this->connection->prepare($sql);
            $stmt->execute(array_values($data));
            
            return $this->connection->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error en inserción: " . $e->getMessage());
            throw new Exception("Error al insertar datos");
        }
    }

    public function update($table, $data, $where, $whereParams = []) {
        try {
            $fields = array_keys($data);
            $set = implode(" = ?, ", $fields) . " = ?";
            
            $sql = "UPDATE " . $table . " SET " . $set . " WHERE " . $where;
            
            $params = array_merge(array_values($data), $whereParams);
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log("Error en actualización: " . $e->getMessage());
            throw new Exception("Error al actualizar datos");
        }
    }

    public function delete($table, $where, $params = []) {
        try {
            $sql = "DELETE FROM " . $table . " WHERE " . $where;
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log("Error en eliminación: " . $e->getMessage());
            throw new Exception("Error al eliminar datos");
        }
    }

    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }

    public function commit() {
        return $this->connection->commit();
    }

    public function rollBack() {
        return $this->connection->rollBack();
    }
}