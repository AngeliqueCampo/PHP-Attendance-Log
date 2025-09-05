<?php
require_once __DIR__ . '/../auth/session.php';
require_once __DIR__ . '/../class/admin_class.php';
require_once __DIR__ . '/../class/course_class.php';

// admin access
Session::requireAdmin();

$admin = new Admin();
$courseObj = new Course();

// get filter parameters
$selected_course = $_GET['course_id'] ?? '';
$selected_year = $_GET['year_level'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// get attendance data based on filters
$attendanceData = [];
if ($selected_course || $selected_year || $date_from || $date_to) {
    $attendanceData = $admin->getAttendanceSummary($selected_course ?: null, $selected_year ?: null, $date_from ?: null, $date_to ?: null);
}

// get all courses for filter dropdown
$courses = $courseObj->readAll();

// get year levels for selected course
$yearLevels = [];
if ($selected_course) {
    $yearLevels = $courseObj->getYearLevelsByCourse($selected_course);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Attendance Reports - Admin Dashboard</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;600&display=swap" rel="stylesheet">

  <style>
    :root {
      --primary: #217B71;
      --accent: #8ACBA9;
      --light: #F0FFE3;
    }
    body { font-family: 'Instrument Sans', sans-serif; }
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
      <h1 class="text-3xl font-bold">Attendance Reports</h1>
      <a href="../auth/logout.php" class="bg-white text-primary px-4 py-2 rounded-lg hover:bg-gray-100 font-medium text-sm">
        🚪 Logout
      </a>
    </div>
  </header>

  <!-- navigation -->
  <nav class="bg-white border-b border-gray-200 shadow-sm">
    <div class="container mx-auto px-6 py-3 flex justify-center gap-6">
      <a href="dashboard.php" class="text-gray-700 hover:text-primary font-medium">🏠 Dashboard</a>
      <a href="../students.php" class="text-gray-700 hover:text-primary font-medium">👨‍🎓 Students</a>
      <a href="../attendance.php" class="text-gray-700 hover:text-primary font-medium">📋 Attendance</a>
      <a href="add_course.php" class="text-gray-700 hover:text-primary font-medium">📚 Courses</a>
      <a href="view_attendance.php" class="text-primary font-semibold">📊 Reports</a>
    </div>
  </nav>

  <main class="container mx-auto px-6 py-10 flex-grow">

    <!-- filters -->
    <div class="bg-white rounded-xl shadow-md p-6 mb-10">
      <h3 class="text-xl font-semibold text-primary mb-6">Attendance Report Filters</h3>

      <form method="GET" class="space-y-6">
        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
          
          <div>
            <label for="course_id" class="block font-medium text-gray-700 mb-1">Course</label>
            <select id="course_id" name="course_id" onchange="this.form.submit()"
              class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
              <option value="">All Courses</option>
              <?php foreach ($courses as $course): ?>
                <option value="<?php echo $course['id']; ?>" <?php echo $selected_course == $course['id'] ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div>
            <label for="year_level" class="block font-medium text-gray-700 mb-1">Year Level</label>
            <select id="year_level" name="year_level"
              class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
              <option value="">All Year Levels</option>
              <?php if ($selected_course): ?>
                <?php foreach ($yearLevels as $year): ?>
                  <option value="<?php echo $year; ?>" <?php echo $selected_year == $year ? 'selected' : ''; ?>>
                    Year <?php echo $year; ?>
                  </option>
                <?php endforeach; ?>
              <?php else: ?>
                <option value="1">Year 1</option>
                <option value="2">Year 2</option>
                <option value="3">Year 3</option>
                <option value="4">Year 4</option>
              <?php endif; ?>
            </select>
          </div>

          <div>
            <label for="date_from" class="block font-medium text-gray-700 mb-1">From Date</label>
            <input type="date" id="date_from" name="date_from" value="<?php echo $date_from; ?>"
              class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
          </div>

          <div>
            <label for="date_to" class="block font-medium text-gray-700 mb-1">To Date</label>
            <input type="date" id="date_to" name="date_to" value="<?php echo $date_to; ?>"
              class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
          </div>

        </div>

        <!-- preserve other filters when course changes -->
        <?php if ($selected_year): ?><input type="hidden" name="year_level" value="<?php echo $selected_year; ?>"><?php endif; ?>
        <?php if ($date_from): ?><input type="hidden" name="date_from" value="<?php echo $date_from; ?>"><?php endif; ?>
        <?php if ($date_to): ?><input type="hidden" name="date_to" value="<?php echo $date_to; ?>"><?php endif; ?>

        <div class="flex items-center gap-4">
          <button type="submit" class="bg-primary text-white px-5 py-2 rounded-lg hover:bg-opacity-90">
            Filter Reports
          </button>
          <a href="view_attendance.php" class="px-5 py-2 rounded-lg border border-gray-300 hover:bg-gray-100">
            Clear Filters
          </a>
        </div>
      </form>
    </div>

    <!-- attendance summary -->
    <?php if (!empty($attendanceData)): ?>
    <div class="bg-white rounded-xl shadow-md overflow-hidden">
      <div class="bg-accent text-white px-5 py-3 flex justify-between items-center">
        <h3 class="font-semibold">📊 Attendance Summary (<?php echo count($attendanceData); ?> students)</h3>
        <div class="text-sm">
          <?php if ($selected_course): ?>
            <?php
            $selectedCourseData = array_filter($courses, function($c) use ($selected_course) {
              return $c['id'] == $selected_course;
            });
            $selectedCourseData = reset($selectedCourseData);
            echo htmlspecialchars($selectedCourseData['course_code']);
            ?>
          <?php endif; ?>
          <?php if ($selected_year): echo " - Year $selected_year"; endif; ?>
          <?php if ($date_from && $date_to): echo " ($date_from to $date_to)"; endif; ?>
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="min-w-full border border-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Student ID</th>
              <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Name</th>
              <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Course</th>
              <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Year</th>
              <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Present</th>
              <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Late</th>
              <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Absent</th>
              <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Total</th>
              <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Attendance %</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100 text-sm">
            <?php foreach ($attendanceData as $record): ?>
              <tr>
                <td class="px-4 py-2 font-medium"><?php echo htmlspecialchars($record['student_id']); ?></td>
                <td class="px-4 py-2"><?php echo htmlspecialchars($record['first_name'] . ' ' . $record['last_name']); ?></td>
                <td class="px-4 py-2"><?php echo htmlspecialchars($record['course_code']); ?></td>
                <td class="px-4 py-2 text-center"><?php echo $record['year_level']; ?></td>
                <td class="px-4 py-2 text-center">
                  <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs font-medium">
                    <?php echo $record['present_count']; ?>
                  </span>
                </td>
                <td class="px-4 py-2 text-center">
                  <span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs font-medium">
                    <?php echo $record['late_count']; ?>
                  </span>
                </td>
                <td class="px-4 py-2 text-center">
                  <span class="px-2 py-1 bg-red-100 text-red-800 rounded-full text-xs font-medium">
                    <?php echo $record['absent_count']; ?>
                  </span>
                </td>
                <td class="px-4 py-2 text-center font-medium"><?php echo $record['total_records']; ?></td>
                <td class="px-4 py-2 text-center">
                  <?php
                  $percentage = $record['attendance_percentage'];
                  $colorClass = $percentage >= 90 ? 'text-green-600' : ($percentage >= 75 ? 'text-yellow-600' : 'text-red-600');
                  ?>
                  <span class="font-semibold <?php echo $colorClass; ?>">
                    <?php echo number_format($percentage, 1); ?>%
                  </span>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <!-- summary statistics -->
      <div class="bg-gray-50 px-5 py-3 border-t">
        <?php
        $totalStudents = count($attendanceData);
        $avgAttendance = $totalStudents > 0 ? array_sum(array_column($attendanceData, 'attendance_percentage')) / $totalStudents : 0;
        $totalPresent = array_sum(array_column($attendanceData, 'present_count'));
        $totalLate = array_sum(array_column($attendanceData, 'late_count'));
        $totalAbsent = array_sum(array_column($attendanceData, 'absent_count'));
        $totalRecords = array_sum(array_column($attendanceData, 'total_records'));
        ?>
        <div class="flex justify-between items-center text-sm text-gray-600">
          <span>
            <strong>Summary:</strong> <?php echo $totalStudents; ?> students | 
            Average Attendance: <strong><?php echo number_format($avgAttendance, 1); ?>%</strong>
          </span>
          <span>
            Total Records: <strong><?php echo $totalRecords; ?></strong> | 
            Present: <strong class="text-green-600"><?php echo $totalPresent; ?></strong> | 
            Late: <strong class="text-yellow-600"><?php echo $totalLate; ?></strong> | 
            Absent: <strong class="text-red-600"><?php echo $totalAbsent; ?></strong>
          </span>
        </div>
      </div>
    </div>

    <?php elseif ($_GET): ?>
    <!-- no results message -->
    <div class="bg-white rounded-xl shadow-md p-10 text-center">
      <h4 class="text-lg font-medium text-gray-800 mb-2">No Results Found</h4>
      <p class="text-gray-600">No attendance records match your selected filters. Try adjusting your search criteria.</p>
    </div>

    <?php else: ?>
    <!-- initial message -->
    <div class="bg-white rounded-xl shadow-md p-10 text-center">
      <h4 class="text-lg font-medium text-gray-800 mb-2">📊 Attendance Reports</h4>
      <p class="text-gray-600">Use the filters above to generate attendance reports by course, year level, and date range.</p>
    </div>
    <?php endif; ?>

  </main>

  <!-- footer -->
  <footer class="bg-primary text-white text-center py-4">
  </footer>

</body>
</html>