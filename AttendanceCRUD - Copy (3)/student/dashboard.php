<?php
require_once __DIR__ . '/../auth/session.php';
require_once __DIR__ . '/../class/user_class.php';
require_once __DIR__ . '/../class/student_class.php';
require_once __DIR__ . '/../class/attendance_class.php';
require_once __DIR__ . '/../class/course_class.php';

// student access
Session::requireStudent();

$currentUser = Session::getCurrentUser();
$studentId = Session::getCurrentStudentId();

// get student details with course information
$userObj = new User();
$studentObj = new Student();
$attendanceObj = new Attendance();

$studentData = $userObj->getByStudentId($studentId);
$attendanceRecords = $attendanceObj->getByStudentId($studentId);

// calculate attendance statistics
$totalRecords = count($attendanceRecords);
$presentCount = 0;
$lateCount = 0;
$absentCount = 0;
$recentRecords = array_slice($attendanceRecords, 0, 5); // last 5 records

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

$attendancePercentage = $totalRecords > 0 ? round(($presentCount / $totalRecords) * 100, 1) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Student Dashboard - School Management System</title>

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
      <h1 class="text-3xl font-bold">Student Dashboard</h1>
      <div class="flex items-center gap-4">
        <span class="text-sm">Welcome, <?php echo htmlspecialchars($currentUser['username']); ?></span>
        <a href="../auth/logout.php" class="bg-white text-primary px-4 py-2 rounded-lg hover:bg-gray-100 font-medium text-sm">
          🚪 Logout
        </a>
      </div>
    </div>
  </header>

  <!-- navigation -->
  <nav class="bg-white border-b border-gray-200 shadow-sm">
    <div class="container mx-auto px-6 py-3 flex justify-center gap-6">
      <a href="dashboard.php" class="text-primary font-semibold">🏠 Dashboard</a>
      <a href="attendance_history.php" class="text-gray-700 hover:text-primary font-medium">📋 My Attendance</a>
    </div>
  </nav>

  <!-- main content -->
  <main class="container mx-auto px-6 py-12 flex-grow">
    
    <!-- student info card -->
    <div class="bg-white rounded-xl shadow-md p-6 mb-10">
      <h2 class="text-2xl font-semibold text-primary mb-6">Student Information</h2>
      
      <div class="grid md:grid-cols-2 gap-8">
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-600 mb-1">Student ID</label>
            <p class="text-lg font-semibold text-gray-800"><?php echo htmlspecialchars($studentData['student_id'] ?? 'N/A'); ?></p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-600 mb-1">Full Name</label>
            <p class="text-lg font-semibold text-gray-800">
              <?php echo htmlspecialchars(($studentData['first_name'] ?? 'N/A') . ' ' . ($studentData['last_name'] ?? '')); ?>
            </p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-600 mb-1">Email Address</label>
            <p class="text-lg text-gray-800"><?php echo htmlspecialchars($studentData['email'] ?? 'N/A'); ?></p>
          </div>
        </div>
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-600 mb-1">Course/Program</label>
            <p class="text-lg font-semibold text-gray-800"><?php echo htmlspecialchars($studentData['course_name'] ?? 'Not Assigned'); ?></p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-600 mb-1">Year Level</label>
            <p class="text-lg font-semibold text-gray-800">
              <?php echo $studentData['year_level'] ? 'Year ' . $studentData['year_level'] : 'Not Set'; ?>
            </p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-600 mb-1">Username</label>
            <p class="text-lg text-gray-800"><?php echo htmlspecialchars($studentData['username'] ?? 'N/A'); ?></p>
          </div>
        </div>
      </div>
    </div>

    <!-- attendance statistics -->
    <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
      
      <!-- total records -->
      <div class="bg-white rounded-xl shadow-md p-6">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-600">Total Records</p>
            <p class="text-3xl font-bold text-primary"><?php echo $totalRecords; ?></p>
          </div>
          <div class="bg-primary bg-opacity-10 p-3 rounded-full">
            <span class="text-2xl">📊</span>
          </div>
        </div>
      </div>

      <!-- present -->
      <div class="bg-white rounded-xl shadow-md p-6">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-600">Present</p>
            <p class="text-3xl font-bold text-green-600"><?php echo $presentCount; ?></p>
          </div>
          <div class="bg-green-100 p-3 rounded-full">
            <span class="text-2xl">✅</span>
          </div>
        </div>
      </div>

      <!-- late -->
      <div class="bg-white rounded-xl shadow-md p-6">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-600">Late</p>
            <p class="text-3xl font-bold text-yellow-600"><?php echo $lateCount; ?></p>
          </div>
          <div class="bg-yellow-100 p-3 rounded-full">
            <span class="text-2xl">⏰</span>
          </div>
        </div>
      </div>

      <!-- absent -->
      <div class="bg-white rounded-xl shadow-md p-6">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-600">Absent</p>
            <p class="text-3xl font-bold text-red-600"><?php echo $absentCount; ?></p>
          </div>
          <div class="bg-red-100 p-3 rounded-full">
            <span class="text-2xl">❌</span>
          </div>
        </div>
      </div>

    </div>


    <!-- recent attendance -->
    <?php if (!empty($recentRecords)): ?>
    <div class="bg-white rounded-xl shadow-md overflow-hidden">
      <div class="bg-accent text-white px-5 py-3">
        <h3 class="font-semibold">📅 Recent Attendance Records (Last 5)</h3>
      </div>
      <div class="overflow-x-auto">
        <table class="min-w-full">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Date</th>
              <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Status</th>
              <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Remarks</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100 text-sm">
            <?php foreach ($recentRecords as $record): ?>
              <tr>
                <td class="px-4 py-2 font-medium">
                  <?php echo date('M d, Y', strtotime($record['date'])); ?>
                </td>
                <td class="px-4 py-2">
                  <?php if ($record['status'] == 'Present'): ?>
                    <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs font-medium">Present</span>
                  <?php elseif ($record['status'] == 'Absent'): ?>
                    <span class="px-2 py-1 bg-red-100 text-red-800 rounded-full text-xs font-medium">Absent</span>
                  <?php else: ?>
                    <span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs font-medium">Late</span>
                  <?php endif; ?>
                </td>
                <td class="px-4 py-2 text-gray-600">
                  <?php echo htmlspecialchars($record['remarks'] ?: 'No remarks'); ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <div class="bg-gray-50 px-5 py-3 text-center">
        <a href="attendance_history.php" class="text-primary hover:text-primary font-medium">
          View Full Attendance History →
        </a>
      </div>
    </div>



    <?php else: ?>
    <!-- no records message -->
    <div class="bg-white rounded-xl shadow-md p-10 text-center">
      <h4 class="text-lg font-medium text-gray-800 mb-2">📭 No Attendance Records</h4>
      <p class="text-gray-600">Your attendance records will appear here once they are recorded by your administrator.</p>
    </div>
    <?php endif; ?>

  </main>

  <!-- footer -->
  <footer class="bg-primary text-white text-center py-4">
  </footer>

</body>
</html>