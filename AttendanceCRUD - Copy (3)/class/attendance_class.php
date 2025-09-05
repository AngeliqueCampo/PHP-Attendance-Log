<?php
require_once(__DIR__ . '/../database.php');

class Attendance extends Database {
    
    public function __construct() {
        parent::__construct('attendance');
    }
    
    // process attendance form submission
    public function processAttendanceSubmission($studentId, $formData) {
        $result = [
            'success' => false,
            'message' => '',
            'messageType' => ''
        ];
        
        $date = $formData['attendance_date'] ?? date('Y-m-d');
        $status = $formData['status'] ?? '';
        $remarks = $formData['remarks'] ?? '';
        $current_time = date('H:i:s');
        
        // validate time restrictions for daily attendance
        if ($date == date('Y-m-d')) {
            $current_hour = (int)date('H');
            if ($current_hour < 8 || $current_hour > 21) {
                $result['message'] = "You can only log attendance between 8:00 AM and 9:00 PM";
                $result['messageType'] = 'error';
                return $result;
            }
        }
        
        try {
            if ($date == date('Y-m-d') && !$status) {
                // auto-log for today based on time
                $success = $this->logStudentAttendance($studentId, $date, $current_time, $remarks);
            } else {
                $validationErrors = $this->validateStudentAttendanceData([
                    'student_id' => $studentId,
                    'date' => $date,
                    'status' => $status
                ]);
                
                if (!empty($validationErrors)) {
                    $result['message'] = implode(', ', $validationErrors);
                    $result['messageType'] = 'error';
                    return $result;
                }
                
                $success = $this->updateStudentAttendance($studentId, $date, $status, $remarks);
            }
            
            if ($success) {
                $result['success'] = true;
                $result['message'] = "Attendance logged successfully!";
                $result['messageType'] = 'success';
            } else {
                $result['message'] = "Error logging attendance. Please try again.";
                $result['messageType'] = 'error';
            }
            
        } catch (Exception $e) {
            $result['message'] = "An unexpected error occurred: " . $e->getMessage();
            $result['messageType'] = 'error';
        }
        
        return $result;
    }
    
    // get attendance statistics for filtered data
    public function calculateAttendanceStats($attendanceRecords) {
        $totalFiltered = count($attendanceRecords);
        $presentCount = 0;
        $lateCount = 0;
        $absentCount = 0;
        
        foreach ($attendanceRecords as $record) {
            switch ($record['status']) {
                case 'Present':
                    $presentCount++;
                    break;
                case 'Late':
                    $lateCount++;
                    break;
                case 'Absent':
                    $absentCount++;
                    break;
            }
        }
        
        $attendancePercentage = $totalFiltered > 0 ? round(($presentCount / $totalFiltered) * 100, 1) : 0;
        
        return [
            'total' => $totalFiltered,
            'present' => $presentCount,
            'late' => $lateCount,
            'absent' => $absentCount,
            'percentage' => $attendancePercentage
        ];
    }
    
    // filter attendance records
    public function filterAttendanceRecords($allAttendance, $monthFilter = '', $yearFilter = '', $statusFilter = '') {
        if (!$monthFilter && !$statusFilter) {
            return $allAttendance;
        }
        
        return array_filter($allAttendance, function($record) use ($monthFilter, $yearFilter, $statusFilter) {
            $record_date = new DateTime($record['date']);
            
            $month_match = !$monthFilter || $record_date->format('m') == $monthFilter;
            $year_match = !$yearFilter || $record_date->format('Y') == $yearFilter;
            $status_match = !$statusFilter || $record['status'] == $statusFilter;
            
            return $month_match && $year_match && $status_match;
        });
    }
    
    // get available months and years from attendance data
    public function getAvailableMonthsAndYears($attendanceRecords) {
        $availableMonths = [];
        $availableYears = [];
        
        foreach ($attendanceRecords as $record) {
            $date = new DateTime($record['date']);
            $month = $date->format('m');
            $year = $date->format('Y');
            
            if (!in_array($month, $availableMonths)) {
                $availableMonths[] = $month;
            }
            if (!in_array($year, $availableYears)) {
                $availableYears[] = $year;
            }
        }
        
        sort($availableMonths);
        rsort($availableYears);
        
        return [
            'months' => $availableMonths,
            'years' => $availableYears
        ];
    }
    
    // check if student can log attendance today
    public function canLogAttendanceToday() {
        $currentHour = (int)date('H');
        return ($currentHour >= 8 && $currentHour <= 21);
    }
    
    // check if current time is after cutoff (8:15 AM)
    public function isAfterCutoffTime() {
        return (date('H:i') >= '08:15');
    }
    
    // get attendance by student ID
    public function getByStudentId($student_id) {
        try {
            $sql = "SELECT a.*, s.first_name, s.last_name 
                    FROM {$this->table} a 
                    LEFT JOIN students s ON a.student_id = s.student_id 
                    WHERE a.student_id = :student_id 
                    ORDER BY a.date DESC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':student_id', $student_id);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            echo "Error getting attendance: " . $e->getMessage();
            return [];
        }
    }
    
    // get attendance by date
    public function getByDate($date) {
        try {
            $sql = "SELECT a.*, s.first_name, s.last_name 
                    FROM {$this->table} a 
                    LEFT JOIN students s ON a.student_id = s.student_id 
                    WHERE a.date = :date";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':date', $date);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            echo "Error getting attendance by date: " . $e->getMessage();
            return [];
        }
    }
    
    // get attendance + student details
    public function getAttendanceWithStudents() {
        try {
            $sql = "SELECT a.*, s.first_name, s.last_name 
                    FROM {$this->table} a 
                    LEFT JOIN students s ON a.student_id = s.student_id 
                    ORDER BY a.date DESC, s.last_name ASC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            echo "Error getting attendance with students: " . $e->getMessage();
            return [];
        }
    }
    
    // validate attendance data
    public function validateAttendanceData($data) {
        $errors = [];
        
        if (empty($data['student_id'])) {
            $errors[] = "Student ID is required";
        }
        
        if (empty($data['date'])) {
            $errors[] = "Date is required";
        }
        
        if (empty($data['status']) || !in_array($data['status'], ['Present', 'Absent', 'Late'])) {
            $errors[] = "Valid status is required (Present, Absent, Late)";
        }
        
        return $errors;
    }
    
    // check if student already has attendance record for a specific date
    public function hasAttendanceForDate($student_id, $date) {
        try {
            $sql = "SELECT id FROM {$this->table} WHERE student_id = :student_id AND date = :date";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':student_id', $student_id);
            $stmt->bindParam(':date', $date);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
        } catch (PDOException $e) {
            echo "Error checking attendance for date: " . $e->getMessage();
            return true; // return true to be safe
        }
    }
    
    // get student's attendance record for a specific date
    public function getStudentAttendanceForDate($student_id, $date) {
        try {
            $sql = "SELECT * FROM {$this->table} WHERE student_id = :student_id AND date = :date";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':student_id', $student_id);
            $stmt->bindParam(':date', $date);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            echo "Error getting student attendance for date: " . $e->getMessage();
            return null;
        }
    }
    
    // student self-log attendance
    public function logStudentAttendance($student_id, $date, $current_time, $remarks = '') {
        try {
            // determine status based on time 
            $cutoff_time = '08:15:00';
            $status = ($current_time <= $cutoff_time) ? 'Present' : 'Late';
            
            $data = [
                'student_id' => $student_id,
                'date' => $date,
                'status' => $status,
                'remarks' => $remarks
            ];
            
            // check if record already exists
            if ($this->hasAttendanceForDate($student_id, $date)) {
                // update existing record
                $sql = "UPDATE {$this->table} 
                        SET status = :status, remarks = :remarks, updated_at = CURRENT_TIMESTAMP 
                        WHERE student_id = :student_id AND date = :date";
                $stmt = $this->pdo->prepare($sql);
                return $stmt->execute($data);
            } else {
                // create new
                return $this->create($data);
            }
        } catch (PDOException $e) {
            echo "Error logging student attendance: " . $e->getMessage();
            return false;
        }
    }
    
    // student update their own attendance 
    public function updateStudentAttendance($student_id, $date, $status, $remarks = '') {
        try {
            $data = [
                'student_id' => $student_id,
                'date' => $date,
                'status' => $status,
                'remarks' => $remarks
            ];
            
            // validate status
            if (!in_array($status, ['Present', 'Absent', 'Late'])) {
                return false;
            }
            
            // check if record exists
            if ($this->hasAttendanceForDate($student_id, $date)) {
                // update existing record
                $sql = "UPDATE {$this->table} 
                        SET status = :status, remarks = :remarks, updated_at = CURRENT_TIMESTAMP 
                        WHERE student_id = :student_id AND date = :date";
                $stmt = $this->pdo->prepare($sql);
                return $stmt->execute($data);
            } else {
                // create new
                return $this->create($data);
            }
        } catch (PDOException $e) {
            echo "Error updating student attendance: " . $e->getMessage();
            return false;
        }
    }
    
    // validate student attendance data
    public function validateStudentAttendanceData($data) {
        $errors = [];
        
        if (empty($data['student_id'])) {
            $errors[] = "Student ID is required";
        }
        
        if (empty($data['date'])) {
            $errors[] = "Date is required";
        }
        
        if (empty($data['status']) || !in_array($data['status'], ['Present', 'Absent', 'Late'])) {
            $errors[] = "Valid status is required";
        }
        
        // Check if date is not in the future
        if (!empty($data['date']) && strtotime($data['date']) > strtotime(date('Y-m-d'))) {
            $errors[] = "Cannot log attendance for future dates";
        }
        
        return $errors;
    }
}