<?php
require_once(__DIR__ . '/../database.php');

class Student extends Database {
    
    public function __construct() {
        parent::__construct('students');
    }
    
    // get student by student_id
    public function getByStudentId($student_id) {
        try {
            $sql = "SELECT s.*, c.course_name, c.course_code 
                    FROM {$this->table} s 
                    LEFT JOIN courses c ON s.course_id = c.id 
                    WHERE s.student_id = :student_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':student_id', $student_id);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            echo "Error finding student: " . $e->getMessage();
            return null;
        }
    }
    
    // search by name
    public function searchByName($name) {
        try {
            $sql = "SELECT s.*, c.course_name, c.course_code 
                    FROM {$this->table} s 
                    LEFT JOIN courses c ON s.course_id = c.id 
                    WHERE s.first_name LIKE :name OR s.last_name LIKE :name";
            $stmt = $this->pdo->prepare($sql);
            $searchTerm = "%$name%";
            $stmt->bindParam(':name', $searchTerm);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            echo "Error searching students: " . $e->getMessage();
            return [];
        }
    }
    
    // get all students with course information
    public function readAll() {
        try {
            $sql = "SELECT s.*, c.course_name, c.course_code 
                    FROM {$this->table} s 
                    LEFT JOIN courses c ON s.course_id = c.id 
                    ORDER BY s.year_level ASC, s.last_name ASC, s.first_name ASC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            echo "Error reading students: " . $e->getMessage();
            return [];
        }
    }
    
    // get students by course and year level
    public function getStudentsByCourseAndYear($course_id, $year_level = null) {
        try {
            $sql = "SELECT s.*, c.course_name, c.course_code 
                    FROM {$this->table} s 
                    LEFT JOIN courses c ON s.course_id = c.id 
                    WHERE s.course_id = :course_id";
            
            $params = ['course_id' => $course_id];
            
            if ($year_level !== null) {
                $sql .= " AND s.year_level = :year_level";
                $params['year_level'] = $year_level;
            }
            
            $sql .= " ORDER BY s.year_level ASC, s.last_name ASC, s.first_name ASC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            echo "Error getting students by course and year: " . $e->getMessage();
            return [];
        }
    }
    
    // validate student data before creating/updating
    public function validateStudentData($data, $isUpdate = false, $currentStudentId = null) {
        $errors = [];
        
        if (empty($data['student_id'])) {
            $errors[] = "Student ID is required";
        } elseif (!$isUpdate || $data['student_id'] != $currentStudentId) {
            // Only check for existing student ID if this is a new record or the ID is being changed
            if ($this->studentIdExists($data['student_id'])) {
                $errors[] = "Student ID already exists";
            }
        }
        
        if (empty($data['first_name'])) {
            $errors[] = "First name is required";
        }
        
        if (empty($data['last_name'])) {
            $errors[] = "Last name is required";
        }
        
        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Valid email address is required";
        } elseif (!$isUpdate || $data['email'] != $this->getCurrentEmail($currentStudentId)) {
            // Only check for existing email if this is a new record or the email is being changed
            if ($this->emailExists($data['email'])) {
                $errors[] = "Email address already exists";
            }
        }
        
        if (empty($data['course_id'])) {
            $errors[] = "Course selection is required";
        }
        
        if (empty($data['year_level']) || !in_array($data['year_level'], [1, 2, 3, 4, 5])) {
            $errors[] = "Valid year level (1-5) is required";
        }
        
        return $errors;
    }
    
    // generate next available student ID YYYY-XXX
    public function generateStudentID() {
        try {
            // get last student_id (sort year + sequence)
            $sql = "SELECT student_id FROM {$this->table} ORDER BY student_id DESC LIMIT 1";
            $stmt = $this->pdo->query($sql);
            $last = $stmt->fetch(PDO::FETCH_ASSOC);

            $currentYear = date("Y");

            if ($last && isset($last['student_id'])) {
                // "202X-XXX"
                [$year, $seq] = explode("-", $last['student_id']);

                if ($year == $currentYear) {
                    // increment
                    $nextSeq = str_pad(((int)$seq) + 1, 3, "0", STR_PAD_LEFT);
                    return $year . "-" . $nextSeq;
                } else {
                    // new year will reset to 001
                    return $currentYear . "-001";
                }
            } else {
                // first ID
                return $currentYear . "-001";
            }
        } catch (PDOException $e) {
            echo "Error generating student ID: " . $e->getMessage();
            // Fallback
            return date("Y") . "-001";
        }
    }

    // check if student ID already exists
    public function studentIdExists($student_id) {
        try {
            $sql = "SELECT id FROM {$this->table} WHERE student_id = :student_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':student_id', $student_id);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
        } catch (PDOException $e) {
            echo "Error checking student ID: " . $e->getMessage();
            return true; // return true to be safe
        }
    }
    
    // check if email already exists
    public function emailExists($email) {
        try {
            $sql = "SELECT id FROM {$this->table} WHERE email = :email";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
        } catch (PDOException $e) {
            echo "Error checking email: " . $e->getMessage();
            return true; // return true to be safe
        }
    }
    
    // get current email for a student (used during updates)
    private function getCurrentEmail($student_id) {
        if (!$student_id) return null;
        
        try {
            $sql = "SELECT email FROM {$this->table} WHERE student_id = :student_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':student_id', $student_id);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? $result['email'] : null;
        } catch (PDOException $e) {
            echo "Error getting current email: " . $e->getMessage();
            return null;
        }
    }
    
    // update student information (for admin editing)
    public function updateByStudentId($student_id, $data) {
        try {
            $setClause = '';
            foreach ($data as $key => $value) {
                $setClause .= "$key = :$key, ";
            }
            $setClause = rtrim($setClause, ', ');
            
            $sql = "UPDATE {$this->table} SET $setClause WHERE student_id = :student_id";
            $stmt = $this->pdo->prepare($sql);
            
            $data['student_id'] = $student_id;
            return $stmt->execute($data);
        } catch (PDOException $e) {
            echo "Error updating student: " . $e->getMessage();
            return false;
        }
    }
    
    // get student attendance history
    public function getAttendanceHistory($student_id, $limit = null) {
        try {
            $sql = "SELECT a.*, s.first_name, s.last_name 
                    FROM attendance a 
                    LEFT JOIN {$this->table} s ON a.student_id = s.student_id 
                    WHERE a.student_id = :student_id 
                    ORDER BY a.date DESC";
            
            if ($limit) {
                $sql .= " LIMIT :limit";
            }
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':student_id', $student_id);
            
            if ($limit) {
                $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            }
            
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            echo "Error getting attendance history: " . $e->getMessage();
            return [];
        }
    }
    
    // get student stats
    public function getStudentStats($student_id) {
        try {
            $sql = "SELECT 
                        COUNT(*) as total_days,
                        SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) as present_days,
                        SUM(CASE WHEN status = 'Absent' THEN 1 ELSE 0 END) as absent_days,
                        SUM(CASE WHEN status = 'Late' THEN 1 ELSE 0 END) as late_days
                    FROM attendance 
                    WHERE student_id = :student_id";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':student_id', $student_id);
            $stmt->execute();
            
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Calculate attendance percentage
            if ($stats['total_days'] > 0) {
                $stats['attendance_percentage'] = round(($stats['present_days'] / $stats['total_days']) * 100, 2);
            } else {
                $stats['attendance_percentage'] = 0;
            }
            
            return $stats;
        } catch (PDOException $e) {
            echo "Error getting student statistics: " . $e->getMessage();
            return null;
        }
    }
    
    // delete student record
    public function deleteByStudentId($student_id) {
        try {
            $sql = "DELETE FROM {$this->table} WHERE student_id = :student_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':student_id', $student_id);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            echo "Error deleting student: " . $e->getMessage();
            return false;
        }
    }
}
?>