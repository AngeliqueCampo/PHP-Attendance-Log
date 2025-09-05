<?php
require_once __DIR__ . '/../auth/session.php';
require_once __DIR__ . '/../class/admin_class.php';

// admin access
Session::requireAdmin();

$admin = new Admin();
$currentUser = Session::getCurrentUser();

// system stats
$stats = $admin->getSystemStats();
$courses = $admin->getCoursesWithStats();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard - School Management System</title>

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
      <h1 class="text-3xl font-bold">Admin Dashboard</h1>
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
      <a href="../students.php" class="text-gray-700 hover:text-primary font-medium">👨‍🎓 Students</a>
      <a href="../attendance.php" class="text-gray-700 hover:text-primary font-medium">📋 Attendance</a>
      <a href="add_course.php" class="text-gray-700 hover:text-primary font-medium">📚 Courses</a>
      <a href="view_attendance.php" class="text-gray-700 hover:text-primary font-medium">📊 Reports</a>
    </div>
  </nav>

  <!-- main content -->
  <main class="container mx-auto px-6 py-12 flex-grow">
    
    <h2 class="text-2xl font-semibold text-primary text-center mb-10">Management Modules</h2>

    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8 max-w-6xl mx-auto">
      
      <!-- student management -->
      <a href="../students.php" class="block bg-white border border-gray-200 rounded-2xl shadow-md hover:shadow-lg hover:scale-[1.02] transition transform duration-200">
        <div class="bg-accent text-white px-5 py-3 rounded-t-2xl">
          <h3 class="text-lg font-semibold">Student Management</h3>
        </div>
        <div class="p-6">
          <p class="text-gray-700 mb-4">
            Create, Read, Update, and Delete student records. Manage student information including personal details and course assignments.
          </p>
          <div class="inline-block bg-primary text-white px-5 py-2 rounded-full font-semibold text-sm">
            Manage Students
          </div>
        </div>
      </a>

      <!-- attendance management -->
      <a href="../attendance.php" class="block bg-white border border-gray-200 rounded-2xl shadow-md hover:shadow-lg hover:scale-[1.02] transition transform duration-200">
        <div class="bg-accent text-white px-5 py-3 rounded-t-2xl">
          <h3 class="text-lg font-semibold">Attendance Management</h3>
        </div>
        <div class="p-6">
          <p class="text-gray-700 mb-4">
            Track student attendance, mark present/absent/late status, and manage daily attendance records.
          </p>
          <div class="inline-block bg-primary text-white px-5 py-2 rounded-full font-semibold text-sm">
            Manage Attendance
          </div>
        </div>
      </a>

      <!-- course management -->
      <a href="add_course.php" class="block bg-white border border-gray-200 rounded-2xl shadow-md hover:shadow-lg hover:scale-[1.02] transition transform duration-200">
        <div class="bg-accent text-white px-5 py-3 rounded-t-2xl">
          <h3 class="text-lg font-semibold">Course Management</h3>
        </div>
        <div class="p-6">
          <p class="text-gray-700 mb-4">
            Add new courses and programs. Manage course information and assignments to students.
          </p>
          <div class="inline-block bg-primary text-white px-5 py-2 rounded-full font-semibold text-sm">
            Manage Courses
          </div>
        </div>
      </a>

    </div>

    <!-- courses overview -->
    <?php if (!empty($courses)): ?>
    <div class="mt-12 max-w-6xl mx-auto">
      <h3 class="text-xl font-semibold text-primary mb-6">Courses Overview</h3>
      <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <div class="bg-accent text-white px-5 py-3">
          <h4 class="font-semibold">All Courses (<?php echo count($courses); ?>)</h4>
        </div>
        <div class="overflow-x-auto">
          <table class="min-w-full">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Course Code</th>
                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Course Name</th>
                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Students</th>
                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Attendance Records</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 text-sm">
              <?php foreach ($courses as $course): ?>
                <tr>
                  <td class="px-4 py-2 font-medium"><?php echo htmlspecialchars($course['course_code']); ?></td>
                  <td class="px-4 py-2"><?php echo htmlspecialchars($course['course_name']); ?></td>
                  <td class="px-4 py-2 text-center"><?php echo $course['stats']['total_students']; ?></td>
                  <td class="px-4 py-2 text-center"><?php echo $course['stats']['total_attendance_records']; ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <?php endif; ?>

  </main>

  <!-- footer -->
  <footer class="bg-primary text-white text-center py-4">
  </footer>

</body>
</html>