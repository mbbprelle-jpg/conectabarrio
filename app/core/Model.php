<?php
/*
 * Base Model
 * Conecta a la base de datos
 */
class Model {
    public $db;

    public function __construct() {
        $this->db = new Database();
    }
}
