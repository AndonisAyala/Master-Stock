<?php
/**
 * Clase para conexión flexible con BDISAM que permite ejecutar queries completas
 * con soporte para caracteres especiales
 */

class FlexDBBdisam {
    private static $instancia = null;
    private $conexion;
    private $dsn;
    
    // Configuración de codificación
    private $bd_encoding = 'ISO-8859-1';
    private $app_encoding = 'UTF-8';

    private function __construct($dsn) {
        $this->dsn = $dsn;
        $this->conectar();
    }

    public static function getInstance($dsn = 'test') {
        if (self::$instancia === null) {
            self::$instancia = new self($dsn);
        }
        return self::$instancia;
    }

    public function conectar() {
        $this->conexion = odbc_connect($this->dsn, "", "");
        if (!$this->conexion) {
            throw new Exception("Error de conexión BDISAM: " . odbc_errormsg());
        }
    }

    /**
     * Ejecuta una consulta SQL completa
     * @param string $sql Consulta SQL completa
     * @return resource Resultado de la consulta
     */
    public function executeQuery($sql) {
        // Convertir encoding si hay caracteres especiales
        $sql = mb_convert_encoding($sql, $this->bd_encoding, $this->app_encoding);
        
        $resultado = odbc_exec($this->conexion, $sql);
        
        if (!$resultado) {
            $error = odbc_errormsg();
            throw new Exception("Error en consulta BDISAM: $error\nConsulta: $sql");
        }
        
        return $resultado;
    }

    /*
    *
    * Obtiene todos los resultados como array asociativo
    *
    */
    public function fetchAll($resultado) {
        $rows = [];
        while ($row = odbc_fetch_array($resultado)) {
            $rows[] = $this->convertRowEncoding($row);
        }
        return $rows;
    }

    /*
    *
    * Obtiene un solo registro
    *
    */
    public function fetchOne($resultado) {
        $row = odbc_fetch_array($resultado);
        return $row ? $this->convertRowEncoding($row) : null;
    }

    /**
     * Convierte encoding de una fila
     */
    private function convertRowEncoding($row) {
        foreach ($row as $key => $value) {
            if (is_string($value)) {
                $row[$key] = mb_convert_encoding($value, $this->app_encoding, $this->bd_encoding);
            }
        }
        return $row;
    }

    /**
     * Para consultas que no devuelven resultados (INSERT, UPDATE, DELETE)
     * @return int Número de filas afectadas
     */
    public function executeNonQuery($sql) {
        //$sql = mb_convert_encoding($sql, $this->bd_encoding, $this->app_encoding);
        $resultado = $this->executeQuery($sql);
        return odbc_num_rows($resultado);
    }

    public function close() {
        if ($this->conexion) {
            odbc_close($this->conexion);
            self::$instancia = null;
        }
    }

    public function __destruct() {
        $this->close();
    }
}

// Función helper global para facilitar el acceso
function flex_db_bdisam($dsn = 'test') {
    return FlexDBBdisam::getInstance($dsn);
}
?>