<?php
require_once 'user_class.php';
require_once 'course_class.php';
require_once 'student_class.php';
require_once 'attendance_class.php';

class Admin extends User {
    
    private $courseObj;
    private $studentObj;
    private $attendanceObj;
    
    public function __construct() {
        parent::__construct();
        $this->courseObj = new Course();
        $this->studentObj = new Student();
        $this->attendanceObj = new Attendance();
    }
    
    // get all courses with stats
    public function getCoursesWithStats() {
        try {
            $courses = $this->courseObj->readAll();
            
            foreach ($courses as &$course) {
                $stats = $this->courseObj->getCourseStats($course['id']);
                $course['stats'] = $stats;
            }
            
            return $courses;
        } catch (Exception $e) {
            echo "Error getting courses with stats: " . $e->getMessage();
            return [];
        }
    }
    
    // get attendance summary by course and year
    public function getAttendanceSummary($course_id = null, $year_level = null, $date_from = null, $date_to = null) {
        try {
            $sql = "SELECT 
                        s.student_id,
                        s.first_name,
                        s.last_name,
                        s.year_level,
                        c.course_name,
                        c.course_code,
                        COUNT(a.id) as total_records,
                        SUM(CASE WHEN a.status = 'Present' THEN 1 ELSE 0 END) as present_count,
                        SUM(CASE WHEN a.status = 'Absent' THEN 1 ELSE 0 END) as absent_count,
                        SUM(CASE WHEN a.status = 'Late' THEN 1 ELSE 0 END) as late_count,
                        ROUND((SUM(CASE WHEN a.status = 'Present' THEN 1 ELSE 0 END) / COUNT(a.id)) * 100, 2) as attendance_percentage
                    FROM students s
                    LEFT JOIN courses c ON s.course_id = c.id
                    LEFT JOIN attendance a ON s.student_id = a.student_id";
            
            $params = [];
            $conditions = [];
            
            if ($course_id) {
                $conditions[] = "s.course_id = :course_id";
                $params['course_id'] = $course_id;
            }
            
            if ($year_level) {
                $conditions[] = "s.year_level = :year_level";
                $params['year_level'] = $year_level;
            }
            
            if ($date_from) {
                $conditions[] = "a.date >= :date_from";
                $params['date_from'] = $date_from;
            }
            
            if ($date_to) {
                $conditions[] = "a.date <= :date_to";
                $params['date_to'] = $date_to;
            }
            
            if (!empty($conditions)) {
                $sql .= " WHERE " . implode(" AND ", $conditions);
            }
            
            $sql .= " GROUP BY s.id, s.student_id, s.first_name, s.last_name, s.year_level, c.course_name, c.course_code
                      HAVING COUNT(a.id) > 0
                      ORDER BY c.course_name ASC, s.year_level ASC, s.last_name ASC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            echo "Error getting attendance summary: " . $e->getMessage();
            return [];
        }
    }
    
    // get overall system stats
    public function getSystemStats() {
        try {
            $sql = "SELECT 
                        (SELECT COUNT(*) FROM students) as total_students,
                        (SELECT COUNT(*) FROM courses) as total_courses,
                        (SELECT COUNT(*) FROM attendance) as total_attendance_records,
                        (SELECT COUNT(*) FROM users WHERE role = 'student') as total_student_users,
                        (SELECT COUNT(*) FROM users WHERE role = 'admin') as total_admin_users";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            echo "Error getting system stats: " . $e->getMessage();
            return null;
        }
    }
    
    // get students without user accounts
    public function getStudentsWithoutAccounts() {
        try {
            $sql = "SELECT s.* 
                    FROM students s 
                    LEFT JOIN users u ON s.student_id = u.student_id 
                    WHERE u.id IS NULL
                    ORDER BY s.last_name ASC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            echo "Error getting students without accounts: " . $e->getMessage();
            return [];
        }
    }
    
    // create student user account
    public function createStudentAccount($student_id, $username, $password) {
        try {
            $userData = [
                'username' => $username,
                'password' => $password,
                'role' => 'student',
                'student_id' => $student_id
            ];
            
            return $this->register($userData);
        } catch (Exception $e) {
            echo "Error creating student account: " . $e->getMessage();
            return false;
        }
    }
    
    // validate admin permissions (can be extended for more specific permissions)
    public function hasPermission($action) {
        // since this is admin class, all actions are allowed
        // can be extended for more granular permissions
        return true;
    }
}
?>