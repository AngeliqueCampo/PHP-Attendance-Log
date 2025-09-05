<?php
require_once(__DIR__ . '/../database.php');

class Course extends Database {
    
    public function __construct() {
        parent::__construct('courses');
    }
    
    // get course by course code
    public function getByCourseCode($course_code) {
        try {
            $sql = "SELECT * FROM {$this->table} WHERE course_code = :course_code";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':course_code', $course_code);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            echo "Error finding course: " . $e->getMessage();
            return null;
        }
    }
    
    // get students by course and year level
    public function getStudentsByCourseAndYear($course_id, $year_level = null) {
        try {
            $sql = "SELECT s.*, c.course_name, c.course_code 
                    FROM students s 
                    LEFT JOIN courses c ON s.course_id = c.id 
                    WHERE s.course_id = :course_id";
            
            $params = ['course_id' => $course_id];
            
            if ($year_level !== null) {
                $sql .= " AND s.year_level = :year_level";
                $params['year_level'] = $year_level;
            }
            
            $sql .= " ORDER BY s.year_level ASC, s.last_name ASC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            echo "Error getting students by course: " . $e->getMessage();
            return [];
        }
    }
    
    // get attendance by course and year level
    public function getAttendanceByCourseAndYear($course_id, $year_level = null, $date_from = null, $date_to = null) {
        try {
            $sql = "SELECT a.*, s.first_name, s.last_name, s.student_id, s.year_level, c.course_name, c.course_code
                    FROM attendance a
                    LEFT JOIN students s ON a.student_id = s.student_id
                    LEFT JOIN courses c ON s.course_id = c.id
                    WHERE s.course_id = :course_id";
            
            $params = ['course_id' => $course_id];
            
            if ($year_level !== null) {
                $sql .= " AND s.year_level = :year_level";
                $params['year_level'] = $year_level;
            }
            
            if ($date_from) {
                $sql .= " AND a.date >= :date_from";
                $params['date_from'] = $date_from;
            }
            
            if ($date_to) {
                $sql .= " AND a.date <= :date_to";
                $params['date_to'] = $date_to;
            }
            
            $sql .= " ORDER BY a.date DESC, s.year_level ASC, s.last_name ASC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            echo "Error getting attendance by course: " . $e->getMessage();
            return [];
        }
    }
    
    // get all year levels for a course
    public function getYearLevelsByCourse($course_id) {
        try {
            $sql = "SELECT DISTINCT year_level FROM students WHERE course_id = :course_id AND year_level IS NOT NULL ORDER BY year_level ASC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':course_id', $course_id);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            echo "Error getting year levels: " . $e->getMessage();
            return [];
        }
    }
    
    // check if course code exists
    public function courseCodeExists($course_code) {
        try {
            $sql = "SELECT id FROM {$this->table} WHERE course_code = :course_code";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':course_code', $course_code);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
        } catch (PDOException $e) {
            echo "Error checking course code: " . $e->getMessage();
            return true; // return true to be safe
        }
    }
    
    // validate course data
    public function validateCourseData($data) {
        $errors = [];
        
        if (empty($data['course_code'])) {
            $errors[] = "Course code is required";
        } elseif ($this->courseCodeExists($data['course_code'])) {
            $errors[] = "Course code already exists";
        }
        
        if (empty($data['course_name'])) {
            $errors[] = "Course name is required";
        }
        
        return $errors;
    }
    
    // get course statistics
    public function getCourseStats($course_id) {
        try {
            $sql = "SELECT 
                        COUNT(DISTINCT s.id) as total_students,
                        COUNT(DISTINCT s.year_level) as year_levels,
                        COUNT(a.id) as total_attendance_records
                    FROM students s
                    LEFT JOIN attendance a ON s.student_id = a.student_id
                    WHERE s.course_id = :course_id";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':course_id', $course_id);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            echo "Error getting course stats: " . $e->getMessage();
            return null;
        }
    }
}
?>