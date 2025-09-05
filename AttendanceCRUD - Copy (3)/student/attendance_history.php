<?php
require_once __DIR__ . '/../auth/session.php';
require_once __DIR__ . '/../class/user_class.php';
require_once __DIR__ . '/../class/student_class.php';
require_once __DIR__ . '/../class/attendance_class.php';

// student access
Session::requireStudent();

$currentUser = Session::getCurrentUser();
$studentId = Session::getCurrentStudentId();

// handle attendance logging form submission
$message = '';
$messageType = '';

if ($_POST && isset($_POST['log_attendance'])) {
    $attendanceObj = new Attendance();
    $result = $attendanceObj->processAttendanceSubmission($studentId, $_POST);
    
    $message = $result['message'];
    $messageType = $result['messageType'];
}

// get filter parameters
$month_filter = $_GET['month'] ?? '';
$year_filter = $_GET['year'] ?? date('Y');
$status_filter = $_GET['status'] ?? '';

// get student details and attendance data
$userObj = new User();
$attendanceObj = new Attendance();

$studentData = $userObj->getByStudentId($studentId);
$allAttendance = $attendanceObj->getByStudentId($studentId);

// check if student already logged attendance today
$todayDate = date('Y-m-d');
$todayAttendance = $attendanceObj->getStudentAttendanceForDate($studentId, $todayDate);

// apply filters using the new method
$filteredAttendance = $attendanceObj->filterAttendanceRecords(
    $allAttendance, 
    $month_filter, 
    $year_filter, 
    $status_filter
);

// calculate statistics using the new method
$stats = $attendanceObj->calculateAttendanceStats($filteredAttendance);
$attendancePercentage = $stats['percentage'];
$totalFiltered = $stats['total'];
$presentCount = $stats['present'];
$lateCount = $stats['late'];
$absentCount = $stats['absent'];

// get available months and years using the new method
$availableData = $attendanceObj->getAvailableMonthsAndYears($allAttendance);
$availableMonths = $availableData['months'];
$availableYears = $availableData['years'];

// month names
$monthNames = [
    '01' => 'January', '02' => 'February', '03' => 'March', '04' => 'April',
    '05' => 'May', '06' => 'June', '07' => 'July', '08' => 'August',
    '09' => 'September', '10' => 'October', '11' => 'November', '12' => 'December'
];

// check current time for attendance logging restrictions using new methods
$canLogToday = $attendanceObj->canLogAttendanceToday();
$isAfterCutoff = $attendanceObj->isAfterCutoffTime();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Attendance History - Student Portal</title>

  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;600&display=swap" rel="stylesheet">

  <style>
    :root {
      --primary: #217B71;
      --accent: #8ACBA9;
      --light: #F0FFE3;
    }
    body {
      font-family: 'Instrument Sans', sans-serif;
    }
    .bg-primary { background-color: var(--primary); }
    .bg-accent { background-color: var(--accent); }
    .bg-light { background-color: var(--light); }
    .text-primary { color: var(--primary); }
  </style>
</head>
<body class="bg-light min-h-screen flex flex-col">

  <!-- header -->
  <header class="bg-primary text-white py-6 shadow-md">
    <div class="container mx-auto px-6 flex justify-between items-center">
      <h1 class="text-3xl font-bold">My Attendance History</h1>
      <div class="flex items-center gap-4">
        <span class="text-sm">Welcome, <?php echo htmlspecialchars($currentUser['username']); ?></span>
        <a href="../auth/logout.php" class="bg-white text-primary px-4 py-2 rounded-lg hover:bg-gray-100 font-medium text-sm">
          Logout
        </a>
      </div>
    </div>
  </header>

  <!-- navigation -->
  <nav class="bg-white border-b border-gray-200 shadow-sm">
    <div class="container mx-auto px-6 py-3 flex justify-center gap-6">
      <a href="dashboard.php" class="text-gray-700 hover:text-primary font-medium">Dashboard</a>
      <a href="attendance_history.php" class="text-primary font-semibold">My Attendance</a>
    </div>
  </nav>

  <main class="container mx-auto px-6 py-10 flex-grow">

    <!-- display success/error messages -->
    <?php if ($message): ?>
    <div class="mb-6 p-4 rounded-lg <?php echo $messageType == 'success' ? 'bg-green-100 text-green-800 border border-green-300' : 'bg-red-100 text-red-800 border border-red-300'; ?>">
      <?php echo htmlspecialchars($message); ?>
    </div>
    <?php endif; ?>

    <!-- student info header -->
    <div class="bg-white rounded-xl shadow-md p-6 mb-6">
      <div class="flex items-center justify-between">
        <div>
          <h2 class="text-xl font-semibold text-primary">
            <?php echo htmlspecialchars(($studentData['first_name'] ?? '') . ' ' . ($studentData['last_name'] ?? '')); ?>
          </h2>
          <p class="text-gray-600">
            Student ID: <?php echo htmlspecialchars($studentData['student_id'] ?? 'N/A'); ?> | 
            <?php echo htmlspecialchars($studentData['course_name'] ?? 'No Course'); ?>
            <?php if ($studentData['year_level']): ?>
              | Year <?php echo $studentData['year_level']; ?>
            <?php endif; ?>
          </p>
        </div>
        <div class="text-right">
          <p class="text-2xl font-bold <?php echo $attendancePercentage >= 90 ? 'text-green-600' : ($attendancePercentage >= 75 ? 'text-yellow-600' : 'text-red-600'); ?>">
            <?php echo $attendancePercentage; ?>%
          </p>
          <p class="text-sm text-gray-600">Attendance Rate</p>
        </div>
      </div>
    </div>

    <!-- attendance Logging Section -->
    <div class="bg-white rounded-xl shadow-md p-6 mb-6">
      <h3 class="text-lg font-semibold text-primary mb-4">Log Your Attendance</h3>
      
      <?php if ($todayAttendance): ?>
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
          <p class="text-blue-800">
            <strong>Today's Status:</strong> 
            <span class="font-semibold <?php echo $todayAttendance['status'] == 'Present' ? 'text-green-600' : ($todayAttendance['status'] == 'Late' ? 'text-yellow-600' : 'text-red-600'); ?>">
              <?php echo $todayAttendance['status']; ?>
            </span>
            <?php if ($todayAttendance['remarks']): ?>
              | Remarks: <?php echo htmlspecialchars($todayAttendance['remarks']); ?>
            <?php endif; ?>
            <br><small class="text-blue-600">Logged at: <?php echo date('M d, Y g:i A', strtotime($todayAttendance['created_at'])); ?></small>
          </p>
        </div>
      <?php endif; ?>

      <?php if (!$canLogToday): ?>
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">
          <p class="text-yellow-800">
            <strong>Note:</strong> Attendance can only be logged between 8:00 AM and 9:00 PM.
            <br>Current time: <?php echo date('g:i A'); ?>
          </p>
        </div>
      <?php endif; ?>

      <form method="POST" class="space-y-4">
        <div class="grid md:grid-cols-3 gap-4">
          
          <div>
            <label for="attendance_date" class="block font-medium text-gray-700 mb-1">Date</label>
            <input type="date" id="attendance_date" name="attendance_date" 
                   value="<?php echo date('Y-m-d'); ?>" 
                   max="<?php echo date('Y-m-d'); ?>"
                   class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
          </div>

          <div>
            <label for="status" class="block font-medium text-gray-700 mb-1">Status</label>
            <select id="status" name="status" class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
              <option value="">Auto (for today only)</option>
              <option value="Present">Present</option>
              <option value="Late">Late</option>
              <option value="Absent">Absent</option>
            </select>
          </div>

          <div>
            <label for="remarks" class="block font-medium text-gray-700 mb-1">Remarks (Optional)</label>
            <input type="text" id="remarks" name="remarks" placeholder="Any additional notes..."
                   class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
          </div>

        </div>

        <?php if ($canLogToday): ?>
          <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
            <p class="text-sm text-gray-700">
              <strong>Time Status:</strong> 
              <?php if ($isAfterCutoff): ?>
                <span class="text-yellow-600">After 8:15 AM - Auto status will be "Late"</span>
              <?php else: ?>
                <span class="text-green-600">Before 8:15 AM - Auto status will be "Present"</span>
              <?php endif; ?>
            </p>
          </div>
        <?php endif; ?>

        <div>
          <button type="submit" name="log_attendance" 
                  class="bg-primary text-white px-6 py-2 rounded-lg hover:bg-opacity-90 <?php echo !$canLogToday ? 'opacity-50 cursor-not-allowed' : ''; ?>"
                  <?php echo !$canLogToday ? 'disabled' : ''; ?>>
            <?php echo $todayAttendance ? 'Update Attendance' : 'Log Attendance'; ?>
          </button>
        </div>

      </form>
    </div>

    <!-- filters -->
    <div class="bg-white rounded-xl shadow-md p-6 mb-6">
      <h3 class="text-lg font-semibold text-primary mb-4">Filter Attendance Records</h3>

      <form method="GET" class="space-y-4">
        <div class="grid md:grid-cols-4 gap-4">
          
          <div>
            <label for="year" class="block font-medium text-gray-700 mb-1">Year</label>
            <select id="year" name="year" class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
              <?php foreach ($availableYears as $year): ?>
                <option value="<?php echo $year; ?>" <?php echo $year_filter == $year ? 'selected' : ''; ?>>
                  <?php echo $year; ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div>
            <label for="month" class="block font-medium text-gray-700 mb-1">Month</label>
            <select id="month" name="month" class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
              <option value="">All Months</option>
              <?php foreach ($availableMonths as $month): ?>
                <option value="<?php echo $month; ?>" <?php echo $month_filter == $month ? 'selected' : ''; ?>>
                  <?php echo $monthNames[$month]; ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div>
            <label for="status" class="block font-medium text-gray-700 mb-1">Status</label>
            <select id="status" name="status" class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
              <option value="">All Status</option>
              <option value="Present" <?php echo $status_filter == 'Present' ? 'selected' : ''; ?>>Present</option>
              <option value="Late" <?php echo $status_filter == 'Late' ? 'selected' : ''; ?>>Late</option>
              <option value="Absent" <?php echo $status_filter == 'Absent' ? 'selected' : ''; ?>>Absent</option>
            </select>
          </div>

          <div class="flex items-end">
            <button type="submit" class="w-full bg-primary text-white px-4 py-2 rounded-lg hover:bg-opacity-90">
              Filter
            </button>
          </div>

        </div>

        <div class="flex items-center gap-4">
          <a href="attendance_history.php" class="px-4 py-2 rounded-lg border border-gray-300 hover:bg-gray-100 text-sm">
            Clear Filters
          </a>
        </div>
      </form>
    </div>

    <!-- attendance history table -->
    <?php if (!empty($filteredAttendance)): ?>
    <div class="bg-white rounded-xl shadow-md overflow-hidden">
      <div class="bg-accent text-white px-5 py-3 flex justify-between items-center">
        <h3 class="font-semibold">Attendance Records (<?php echo count($filteredAttendance); ?> records)</h3>
        <div class="text-sm">
          <?php if ($month_filter): ?>
            <?php echo $monthNames[$month_filter] . ' '; ?>
          <?php endif; ?>
          <?php echo $year_filter; ?>
          <?php if ($status_filter): ?>
            - <?php echo $status_filter; ?> only
          <?php endif; ?>
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="min-w-full">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Date</th>
              <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Day</th>
              <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Status</th>
              <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Remarks</th>
              <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Recorded</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100 text-sm">
            <?php foreach ($filteredAttendance as $record): ?>
              <?php
              $recordDate = new DateTime($record['date']);
              $isLate = $record['status'] == 'Late';
              ?>
              <tr class="<?php echo $isLate ? 'bg-yellow-50' : ''; ?>">
                <td class="px-4 py-3 font-medium">
                  <?php echo $recordDate->format('M d, Y'); ?>
                </td>
                <td class="px-4 py-3 text-gray-600">
                  <?php echo $recordDate->format('l'); ?>
                </td>
                <td class="px-4 py-3">
                  <?php if ($record['status'] == 'Present'): ?>
                    <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs font-medium">
                      Present
                    </span>
                  <?php elseif ($record['status'] == 'Absent'): ?>
                    <span class="px-2 py-1 bg-red-100 text-red-800 rounded-full text-xs font-medium">
                      Absent
                    </span>
                  <?php else: ?>
                    <span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs font-medium">
                      Late
                    </span>
                  <?php endif; ?>
                </td>
                <td class="px-4 py-3 text-gray-600">
                  <?php 
                  $remarks = htmlspecialchars($record['remarks'] ?: 'No remarks');
                  echo strlen($remarks) > 40 ? substr($remarks, 0, 40) . '...' : $remarks;
                  ?>
                </td>
                <td class="px-4 py-3 text-gray-500 text-xs">
                  <?php echo date('M d, Y g:i A', strtotime($record['created_at'])); ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <!-- summary -->
      <div class="bg-gray-50 px-5 py-3 border-t">
        <div class="flex justify-between items-center text-sm text-gray-600">
          <span>
            <strong>Filtered Results:</strong> <?php echo $totalFiltered; ?> records | 
            Attendance Rate: <strong class="<?php echo $attendancePercentage >= 90 ? 'text-green-600' : ($attendancePercentage >= 75 ? 'text-yellow-600' : 'text-red-600'); ?>">
              <?php echo $attendancePercentage; ?>%
            </strong>
          </span>
          <span>
            Present: <strong class="text-green-600"><?php echo $presentCount; ?></strong> | 
            Late: <strong class="text-yellow-600"><?php echo $lateCount; ?></strong> | 
            Absent: <strong class="text-red-600"><?php echo $absentCount; ?></strong>
          </span>
        </div>
      </div>
    </div>

    <?php else: ?>
    <!-- no records message -->
    <div class="bg-white rounded-xl shadow-md p-10 text-center">
      <h4 class="text-lg font-medium text-gray-800 mb-2">No Attendance Records Found</h4>
      <p class="text-gray-600">
        <?php if ($month_filter || $status_filter): ?>
          No records match your selected filters. Try adjusting your search criteria.
        <?php else: ?>
          Your attendance records will appear here once they are recorded.
        <?php endif; ?>
      </p>
      <?php if ($month_filter || $status_filter): ?>
        <a href="attendance_history.php" class="inline-block mt-4 bg-primary text-white px-4 py-2 rounded-lg hover:bg-opacity-90">
          View All Records
        </a>
      <?php endif; ?>
    </div>
    <?php endif; ?>

  </main>

  <!-- footer -->
  <footer class="bg-primary text-white text-center py-4">
  </footer>

</body>
</html>