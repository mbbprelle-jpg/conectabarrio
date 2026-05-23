<?php
class Database {
    private $host = DB_HOST;
    private $user = DB_USER;
    private $pass = DB_PASS;
    private $dbname = DB_NAME;

    private $dbh;
    private $stmt;
    private $error;

    public function __construct() {
        // Establecer DSN
        $dsn = 'mysql:host=' . $this->host . ';dbname=' . $this->dbname . ';charset=utf8mb4';
        
        $options = [
            PDO::ATTR_PERSISTENT => true,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ, // Retornar objetos por defecto
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        ];

        // Crear una instancia de PDO
        try {
            $this->dbh = new PDO($dsn, $this->user, $this->pass, $options);
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
            die("Error de conexión a la base de datos: " . htmlspecialchars($this->error));
        }
    }

    // Preparar consultas SQL
    public function query($sql) {
        $this->stmt = $this->dbh->prepare($sql);
    }

    // Vincular valores con tipos específicos
    public function bind($param, $value, $type = null) {
        if (is_null($type)) {
            switch (true) {
                case is_int($value):
                    $type = PDO::PARAM_INT;
                    break;
                case is_bool($value):
                    $type = PDO::PARAM_BOOL;
                    break;
                case is_null($value):
                    $type = PDO::PARAM_NULL;
                    break;
                default:
                    $type = PDO::PARAM_STR;
            }
        }
        $this->stmt->bindValue($param, $value, $type);
    }

    // Ejecutar la consulta preparada
    public function execute() {
        return $this->stmt->execute();
    }

    // Obtener un conjunto de registros (múltiples filas)
    public function resultSet() {
        $this->execute();
        return $this->stmt->fetchAll();
    }

    // Obtener un solo registro (una fila)
    public function single() {
        $this->execute();
        return $this->stmt->fetch();
    }

    // Obtener el número de filas afectadas/devueltas
    public function rowCount() {
        return $this->stmt->rowCount();
    }

    // Obtener el último ID insertado
    public function lastInsertId() {
        return $this->dbh->lastInsertId();
    }

    // Iniciar transacción
    public function beginTransaction() {
        return $this->dbh->beginTransaction();
    }

    // Confirmar transacción
    public function commit() {
        return $this->dbh->commit();
    }

    // Revertir transacción
    public function rollBack() {
        return $this->dbh->rollBack();
    }
}
