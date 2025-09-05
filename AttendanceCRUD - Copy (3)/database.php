<?php

class Database {
    protected $pdo;
    protected $table;
    
    public function __construct($table) {
        $this->table = $table;
        $this->connect();
    }
    
    // PDO db connection
    private function connect() {
        try {
            $host = 'localhost';
            $dbname = 'student';
            $username = 'root';
            $password = '';
            
            $this->pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die("Connection failed: " . $e->getMessage());
        }
    }
    
    // CREATE -- insert new record
    public function create($data) {
        try {
            $columns = implode(',', array_keys($data));
            $placeholders = ':' . implode(', :', array_keys($data));
            
            $sql = "INSERT INTO {$this->table} ($columns) VALUES ($placeholders)";
            $stmt = $this->pdo->prepare($sql);
            
            return $stmt->execute($data);
        } catch (PDOException $e) {
            echo "Error creating record: " . $e->getMessage();
            return false;
        }
    }
    
    // READ -- get all records
    public function readAll() {
        try {
            $sql = "SELECT * FROM {$this->table}";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            echo "Error reading records: " . $e->getMessage();
            return [];
        }
    }
    
    // READ -- get single record by ID
    public function readById($id) {
        try {
            $sql = "SELECT * FROM {$this->table} WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            echo "Error reading record: " . $e->getMessage();
            return null;
        }
    }
    
    // UPDATE -- update existing record
    public function update($id, $data) {
        try {
            $setClause = '';
            foreach ($data as $key => $value) {
                $setClause .= "$key = :$key, ";
            }
            $setClause = rtrim($setClause, ', ');
            
            $sql = "UPDATE {$this->table} SET $setClause WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            
            $data['id'] = $id;
            return $stmt->execute($data);
        } catch (PDOException $e) {
            echo "Error updating record: " . $e->getMessage();
            return false;
        }
    }
    
    // DELETE -- delete record by ID
    public function delete($id) {
        try {
            $sql = "DELETE FROM {$this->table} WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':id', $id);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            echo "Error deleting record: " . $e->getMessage();
            return false;
        }
    }
    
    // get PDO connection
    public function getPDO() {
        return $this->pdo;
    }
}
?>