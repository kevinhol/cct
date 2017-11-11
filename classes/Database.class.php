<?php

class Database {

    private static $connectionSettings = array();
    private static $connection = null;

    public static function initialize() {
        if (self::$connection !== null) {
            return self::$connection;
        }
        try {
            self::$connectionSettings = parse_ini_file("./configs/database.ini");
        } catch (Exception $e) {
            // TODO log error and mitigate..  redirect(?)
            echo "Error reading config file";
            die();
        }

        switch (strtoupper(self::$connectionSettings['connectionType'])) {
            case 'PDO': return self::createPDOconn();
                break;
            default: return self::createPDOconn();
        }
    }

    private static function createPDOconn() {
        $dbname = self::$connectionSettings['dbname'];
        $host = self::$connectionSettings['host'];
        $username = self::$connectionSettings['username'];
        $password = self::$connectionSettings['password'];

        try {
            self::$connection = new PDO("mysql:host={$host};dbname={$dbname}", $username, $password);
            return self::$connection;
        } catch (PDOException $e) {
            // TODO log error and mitigate
            echo "Error creating PDO DB connection";
            die();
        }
    }

    public static function getColumnNames($table, $fromCache = false) {
        self::initialize();

        $cache_key = "getColumnNames_{$table}";
        $data = Cacher::getData($cache_key);

        if ($fromCache && !empty($data)) {
//            echo "<p>Getting table fields from cache";
            return $data;
        }

        $sql = "SHOW COLUMNS FROM {$table}";
       // echo $sql;
        $stmt = self::$connection->prepare($sql);
        $stmt->execute();
        $columns = array();

        while ($result = $stmt->fetch(PDO::FETCH_COLUMN)) {
            $columns[] = $result;

        }

        Cacher::setData($cache_key, $columns);
        return $columns;
    }

    public static function loadByID($table, $id, $primaryKey = "id") {
        self::initialize();

        $sql = "SELECT * FROM {$table} WHERE {$primaryKey} = :id";
        $stmt = self::$connection->prepare($sql);
        $stmt->bindParam("id", $id, PDO::PARAM_INT);

        $vals = array();
        if ($stmt->execute() && $stmt->rowCount() == 1) {
            $vals[] = (array) $stmt->fetch(PDO::FETCH_ASSOC);
            $vals = $vals[0];
            return $vals;
        }
        return false;
    }

    public static function writeOrUpdateDB($object) {

        self::initialize();
        // get the table name
        $table = strtolower(get_class($object));

        // get the tuple data
        $object_vars = get_object_vars($object);
        // columns
        $cols = array_keys($object_vars);

        // format the duplicate string sql
        $dup_str = '';
        foreach ($object_vars as $field => $value) {
            $dup_str .= " {$field}=:{$field} , ";
        }
        $dup_str = str_lreplace(",", "", $dup_str);

        // perpare stmt string
        $cols_str = implode(', ', $cols);
        $vals_str = ":" . implode(', :', $cols);
        $sql = "
            INSERT INTO {$table} 
                ( {$cols_str} ) 
            VALUES 
                ( {$vals_str} ) 
            ON DUPLICATE KEY UPDATE {$dup_str}  ";

        $stmt = self::$connection->prepare($sql);

        foreach ($object_vars as $param => $value) {
            if (!in_array($param, self::$connectionSettings['treatAsStrings']) && (is_int($value) || ctype_digit($value))) {
                $value = intval($value);
                $type = PDO::PARAM_INT;
            } elseif ($value === NULL) {
                $type = PDO::PARAM_NULL;
            } else {
                $type = PDO::PARAM_STR;
            }

            // insert bindings
            $stmt->bindValue($param, $value, $type);
            //update bindings
            $stmt->bindValue("':" . $param . "'", $value, $type);
        }
        try {
            self::$connection->beginTransaction();
            $stmt->execute();
            $insertID = self::$connection->lastInsertId();
            self::$connection->commit();
            //echo "insert = " ; $insertID; print_r(self::$connection->errorInfo()); exit;
            
            if ($insertID !== false) {
                return $insertID;
            }
            else{
                print_r(self::$connection->errorInfo()); exit;
            }
        } catch (PDOExecption $e) {
            $stmt->rollback();
            print "Error!: " . $e->getMessage() . "</br>";
            exit;
        }
    }

    public static function delete($object) {
        self::initialize();
        // get the taable name
        try {
            $table = get_class($object);
            $fields = self::getColumnNames($table);
            $pk = $fields[0];

            $stmt = self::$connection->prepare("DELETE FROM {$table} WHERE {$pk} = :id");
            $stmt->bindValue(":id", $object->$pk, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (Exception $e) {
            return false;
        }
    }

}

?>
