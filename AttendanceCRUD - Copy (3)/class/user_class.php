<?php
require_once(__DIR__ . '/../database.php');
require_once(__DIR__ . '/student_class.php');

class User extends Database {
    
    public function __construct() {
        parent::__construct('users');
    }
    
    // create new user 
    public function register($data) {
        try {
            $this->pdo->beginTransaction();

            // if registering student, create student record
            if ($data['role'] === 'student') {
                $student = new Student();

                // create complete student record
                $studentData = [
                    'student_id' => $data['student_id'],
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'email' => $data['email'],
                    'phone' => isset($data['phone']) ? $data['phone'] : null,
                    'address' => isset($data['address']) ? $data['address'] : null,
                    'course_id' => $data['course_id'],
                    'year_level' => $data['year_level']
                ];
                
                if (!$student->create($studentData)) {
                    throw new Exception("Failed to create student record");
                }
            }

            // create user record
            $userData = [
                'username' => $data['username'],
                'password' => password_hash($data['password'], PASSWORD_DEFAULT),
                'role' => $data['role'],
                'student_id' => $data['role'] === 'student' ? $data['student_id'] : null
            ];

            if (!$this->create($userData)) {
                throw new Exception("Failed to create user record");
            }

            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            echo "Error creating user: " . $e->getMessage();
            return false;
        }
    }

    // authenticate user login
    public function login($username, $password) {
        try {
            $sql = "SELECT * FROM {$this->table} WHERE username = :username OR student_id = :username";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':username', $username);
            $stmt->execute();
            
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password'])) {
                return $user;
            }
            
            return false;
        } catch (PDOException $e) {
            echo "Error during login: " . $e->getMessage();
            return false;
        }
    }
    
    // get user by student_id (for student users)
    public function getByStudentId($student_id) {
        try {
            $sql = "SELECT u.*, s.first_name, s.last_name, s.email, c.course_name, s.year_level 
                    FROM {$this->table} u 
                    LEFT JOIN students s ON u.student_id = s.student_id 
                    LEFT JOIN courses c ON s.course_id = c.id 
                    WHERE u.student_id = :student_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':student_id', $student_id);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            echo "Error finding user by student ID: " . $e->getMessage();
            return null;
        }
    }
    
    // check if username exists
    public function usernameExists($username) {
        try {
            $sql = "SELECT id FROM {$this->table} WHERE username = :username";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':username', $username);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
        } catch (PDOException $e) {
            echo "Error checking username: " . $e->getMessage();
            return true; // return true to be safe
        }
    }
    
    // validate user registration data
    public function validateUserData($data) {
        $errors = [];
        
        if (empty($data['username'])) {
            $errors[] = "Username is required";
        } elseif ($this->usernameExists($data['username'])) {
            $errors[] = "Username already exists";
        }
        
        if (empty($data['password'])) {
            $errors[] = "Password is required";
        } elseif (strlen($data['password']) < 6) {
            $errors[] = "Password must be at least 6 characters long";
        }
        
        if (empty($data['role']) || !in_array($data['role'], ['admin', 'student'])) {
            $errors[] = "Valid role is required (admin or student)";
        }
        
        // additional validation for student accounts
        if ($data['role'] == 'student') {
            if (empty($data['student_id'])) {
                $errors[] = "Student ID is required for student accounts";
            }
            
            if (empty($data['first_name'])) {
                $errors[] = "First name is required for student accounts";
            }
            
            if (empty($data['last_name'])) {
                $errors[] = "Last name is required for student accounts";
            }
            
            if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Valid email address is required for student accounts";
            }
            
            if (empty($data['course_id'])) {
                $errors[] = "Course selection is required for student accounts";
            }
            
            if (empty($data['year_level']) || !in_array($data['year_level'], ['1', '2', '3', '4', '5'])) {
                $errors[] = "Valid year level is required for student accounts";
            }
            
            // Check if student ID already exists
            if (!empty($data['student_id'])) {
                $student = new Student();
                if ($student->studentIdExists($data['student_id'])) {
                    $errors[] = "Student ID already exists";
                }
            }
            
            // Check if email already exists
            if (!empty($data['email'])) {
                if ($this->emailExists($data['email'])) {
                    $errors[] = "Email address already exists";
                }
            }
        }
        
        return $errors;
    }
    
    // check if email exists
    public function emailExists($email) {
        try {
            $sql = "SELECT id FROM students WHERE email = :email";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
        } catch (PDOException $e) {
            echo "Error checking email: " . $e->getMessage();
            return true; // return true to be safe
        }
    }
    
    // change password
    public function changePassword($user_id, $old_password, $new_password) {
        try {
            // first verify old password
            $user = $this->readById($user_id);
            if (!$user || !password_verify($old_password, $user['password'])) {
                return false;
            }
            
            // update with new password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            return $this->update($user_id, ['password' => $hashed_password]);
        } catch (PDOException $e) {
            echo "Error changing password: " . $e->getMessage();
            return false;
        }
    }
}
?>