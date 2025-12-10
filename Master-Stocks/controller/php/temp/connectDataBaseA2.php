<?php
function connectODBC() {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
    header('Content-Type: text/html; charset=utf-8');
    mb_internal_encoding('UTF-8');
    
    try {
        // Conexión con configuración de codificación forzada
        $dsn = "odbc:DSN=data_a2;DBQ=\\\\Srv-01\\d_srv\\a2apps\\HAC\\Empre001\\Data";
        $username = "";
        $password = "";

        $connect = new PDO($dsn, $username, $password);
        $connect->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $connect->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $connect->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        
        // Decorador para convertir resultados automáticamente
        $connect->setAttribute(PDO::ATTR_STATEMENT_CLASS, [
            'DBISAM_UTF8_Statement',
            [$connect]
        ]);
        
        return $connect;
        
    } catch (PDOException $e) {
        $errorMsg = "Error de conexión ODBC:\n";
        $errorMsg .= "Código: " . $e->getCode() . "\n";
        $errorMsg .= "Mensaje: " . $e->getMessage() . "\n";
        
        if (isset($e->errorInfo[2])) {
            $errorMsg .= "Detalle ODBC: " . $e->errorInfo[2] . "\n";
        }
        
        error_log($errorMsg);
        die($errorMsg);
    }
}

// Clase personalizada para conversión automática (versión actualizada)
class DBISAM_UTF8_Statement extends PDOStatement {
    protected $pdo;
    
    protected function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    #[\ReturnTypeWillChange]
    public function fetch($mode = PDO::FETCH_DEFAULT, $cursorOrientation = PDO::FETCH_ORI_NEXT, $cursorOffset = 0) {
        $row = parent::fetch($mode, $cursorOrientation, $cursorOffset);
        return $this->convertEncoding($row);
    }
    
    #[\ReturnTypeWillChange]
    public function fetchAll($mode = PDO::FETCH_DEFAULT, ...$args) {
        $data = parent::fetchAll($mode, ...$args);
        return $this->convertEncoding($data);
    }
    
    private function convertEncoding($data) {
        if (is_array($data)) {
            array_walk_recursive($data, function(&$value) {
                if (is_string($value)) {
                    $value = mb_convert_encoding($value, 'UTF-8', 'Windows-1252');
                }
            });
        }
        return $data;
    }
}
?>