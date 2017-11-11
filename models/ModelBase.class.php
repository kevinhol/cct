<?php

class ModelBase {

    protected $fields = array();
    protected $class = null;

    function __construct($class_name) {
        $this->class = $class_name;
        $this->fields = Database::getColumnNames($class_name);
    }

    public function loadFromDB($id, $fromCache = false) {

        $cache_key = "loadFromDB_{$this->class}_{$id}";
        $data = Cacher::getData($cache_key);

        if ($fromCache && !empty($data)) {
            // rebuild the model values;
            $modelData = unserialize($data);

            foreach ($this->fields as $field) {
                $this->$field = $modelData->$field;
            }
            return true;
        } else {
        
            $db_data = Database::loadByID($this->class, $id, $this->fields[0]);
            if($db_data !== false)
            {
                foreach ($db_data as $key => $val) {
                    $this->$key = $val;
                }
                Cacher::setData($cache_key, serialize($this));
                return true;
            }
            // Request for object not in DB
            return false;
        }
    }

    public function writeOrUpdateDB($reCache = false) {

       // $this->deleteCache();
        // To the insert/update. If the return is > 0 it was an insert
        $insertUpdateID = Database::writeOrUpdateDB($this);

        if ($insertUpdateID > 0) {
            $this->fields[0] = $insertUpdateID;
            //echo "FIELDS ID = " .$this->fields[0];
        }
        return (boolean)$insertUpdateID;
    }

    public function delete() {
        //delete the cache
        $this->deleteCache();
        
        //return the opertion outcome for the deletion
        return Database::delete($this);
    }
    
    protected function deleteCache() {
        if (!empty($this->fields[0])) {
            $pk = $this->fields[0];
            if($this->$pk)
            {    
                $cache_key = "loadFromDB_{$this->class}_{$this->$pk}";
                return Cacher::deleteData($cache_key);
            }
            return false;
        }
        return false;
    }
}
?>
    