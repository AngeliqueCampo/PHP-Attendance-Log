<?php
// student CRUD 
require_once __DIR__ . '/class/student_class.php';
require_once __DIR__ . '/class/course_class.php';
require_once __DIR__ . '/auth/session.php';
require_once __DIR__ . '/class/admin_class.php';

// admin access
Session::requireAdmin();

$admin = new Admin();
$currentUser = Session::getCurrentUser();

$student = new Student();
$course = new Course();
$message = '';
$editStudent = null;

// get all courses for dropdown
$courses = $course->readAll();

// handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'];

    if ($action == 'create') {
        $data = [
            'student_id' => $_POST['student_id'],
            'first_name' => $_POST['first_name'],
            'last_name' => $_POST['last_name'],
            'email' => $_POST['email'],
            'phone' => $_POST['phone'],
            'address' => $_POST['address'],
            'course_id' => $_POST['course_id'],
            'year_level' => $_POST['year_level']
        ];

        $errors = $student->validateStudentData($data);
        if (empty($errors)) {
            $message = $student->create($data)
                ? '<div class="bg-green-100 text-green-800 border border-green-300 px-4 py-3 rounded-lg mb-6">✅ Student created successfully!</div>'
                : '<div class="bg-red-100 text-red-800 border border-red-300 px-4 py-3 rounded-lg mb-6">❌ Error creating student!</div>';
        } else {
            $message = '<div class="bg-red-100 text-red-800 border border-red-300 px-4 py-3 rounded-lg mb-6">❌ ' . implode('<br>', $errors) . '</div>';
        }
    }

    if ($action == 'update') {
        $id = $_POST['id'];
        $currentStudentData = $student->readById($id);
        
        $data = [
            'student_id' => $_POST['student_id'],
            'first_name' => $_POST['first_name'],
            'last_name' => $_POST['last_name'],
            'email' => $_POST['email'],
            'phone' => $_POST['phone'],
            'address' => $_POST['address'],
            'course_id' => $_POST['course_id'],
            'year_level' => $_POST['year_level']
        ];

        $errors = $student->validateStudentData($data, true, $currentStudentData['student_id']);
        if (empty($errors)) {
            $message = $student->update($id, $data)
                ? '<div class="bg-green-100 text-green-800 border border-green-300 px-4 py-3 rounded-lg mb-6">✅ Student updated successfully!</div>'
                : '<div class="bg-red-100 text-red-800 border border-red-300 px-4 py-3 rounded-lg mb-6">❌ Error updating student!</div>';
        } else {
            $message = '<div class="bg-red-100 text-red-800 border border-red-300 px-4 py-3 rounded-lg mb-6">❌ ' . implode('<br>', $errors) . '</div>';
        }
    }
}

// handle GET requests
if (isset($_GET['action'])) {
    if ($_GET['action'] == 'delete' && isset($_GET['id'])) {
        $message = $student->delete($_GET['id'])
            ? '<div class="bg-green-100 text-green-800 border border-green-300 px-4 py-3 rounded-lg mb-6">✅ Student deleted successfully!</div>'
            : '<div class="bg-red-100 text-red-800 border border-red-300 px-4 py-3 rounded-lg mb-6">❌ Error deleting student!</div>';
    }

    if ($_GET['action'] == 'edit' && isset($_GET['id'])) {
        $editStudent = $student->readById($_GET['id']);
    }
}

// get all students
$students = $student->readAll();

// generate next student ID
$nextStudentId = $student->generateStudentID();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Student Management - CRUD System</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
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
        <a href="auth/logout.php" class="bg-white text-primary px-4 py-2 rounded-lg hover:bg-gray-100 font-medium text-sm">
          🚪 Logout
        </a>
      </div>
    </div>
  </header>

  <!-- navigation -->
  <nav class="bg-white border-b border-gray-200 shadow-sm">
    <div class="container mx-auto px-6 py-3 flex justify-center gap-6">
      <a href="admin/dashboard.php" class="text-gray-700 hover:text-primary font-medium">🏠 Dashboard</a>
      <a href="students.php" class="text-primary font-semibold">👨‍🎓 Students</a>
      <a href="attendance.php" class="text-gray-700 hover:text-primary font-medium">📋 Attendance</a>
      <a href="admin/add_course.php" class="text-gray-700 hover:text-primary font-medium">📚 Courses</a>
      <a href="admin/view_attendance.php" class="text-gray-700 hover:text-primary font-medium">📊 Reports</a>
    </div>
  </nav>

  <main class="container mx-auto px-4 py-10 flex-grow">
    <?php echo $message; ?>

    <!-- card -->
    <div class="bg-white rounded-xl shadow-md p-6 mb-10">
      <h3 class="text-xl font-semibold text-primary mb-6">
        <?php echo $editStudent ? 'Edit Student' : '➕ Add New Student'; ?>
      </h3>

      <form method="POST" class="space-y-6">
        <input type="hidden" name="action" value="<?php echo $editStudent ? 'update' : 'create'; ?>">
        <?php if ($editStudent): ?>
          <input type="hidden" name="id" value="<?php echo $editStudent['id']; ?>">
        <?php endif; ?>

        <div class="grid md:grid-cols-2 gap-6">
          <div>
            <label for="student_id" class="block font-medium text-gray-700 mb-1">Student ID</label>
            <?php if ($editStudent): ?>
              <input type="text" id="student_id" name="student_id" required
                value="<?php echo htmlspecialchars($editStudent['student_id']); ?>"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
            <?php else: ?>
              <input type="text" id="student_id" name="student_id" required
                value="<?php echo $nextStudentId; ?>"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg bg-gray-100 text-gray-600" readonly>
              <p class="text-sm text-gray-500 mt-1">Auto-generated student ID</p>
            <?php endif; ?>
          </div>
          <div>
            <label for="email" class="block font-medium text-gray-700 mb-1">Email *</label>
            <input type="email" id="email" name="email" required
              value="<?php echo $editStudent ? htmlspecialchars($editStudent['email']) : ''; ?>"
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
          </div>
        </div>

        <div class="grid md:grid-cols-2 gap-6">
          <div>
            <label for="first_name" class="block font-medium text-gray-700 mb-1">First Name *</label>
            <input type="text" id="first_name" name="first_name" required
              value="<?php echo $editStudent ? htmlspecialchars($editStudent['first_name']) : ''; ?>"
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
          </div>
          <div>
            <label for="last_name" class="block font-medium text-gray-700 mb-1">Last Name *</label>
            <input type="text" id="last_name" name="last_name" required
              value="<?php echo $editStudent ? htmlspecialchars($editStudent['last_name']) : ''; ?>"
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
          </div>
        </div>

        <div class="grid md:grid-cols-2 gap-6">
          <div>
            <label for="phone" class="block font-medium text-gray-700 mb-1">Phone</label>
            <input type="text" id="phone" name="phone"
              value="<?php echo $editStudent ? htmlspecialchars($editStudent['phone']) : ''; ?>"
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
          </div>
          <div>
            <label for="course_id" class="block font-medium text-gray-700 mb-1">Course/Program *</label>
            <select id="course_id" name="course_id" required
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
              <option value="">Select Course</option>
              <?php foreach ($courses as $courseItem): ?>
                <option value="<?php echo $courseItem['id']; ?>" 
                        <?php echo ($editStudent && $courseItem['id'] == $editStudent['course_id']) ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($courseItem['course_code'] . ' - ' . $courseItem['course_name']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="grid md:grid-cols-2 gap-6">
          <div>
            <label for="year_level" class="block font-medium text-gray-700 mb-1">Year Level *</label>
            <select id="year_level" name="year_level" required
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
              <option value="">Select Year</option>
              <?php for ($i = 1; $i <= 5; $i++): ?>
                <option value="<?php echo $i; ?>" 
                        <?php echo ($editStudent && $i == $editStudent['year_level']) ? 'selected' : ''; ?>>
                  <?php echo $i . getOrdinalSuffix($i) . ' Year'; ?>
                </option>
              <?php endfor; ?>
            </select>
          </div>
          <div></div>
        </div>

        <div>
          <label for="address" class="block font-medium text-gray-700 mb-1">Address</label>
          <textarea id="address" name="address" rows="3"
            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary"
            placeholder="Enter complete address (optional)"><?php echo $editStudent ? htmlspecialchars($editStudent['address']) : ''; ?></textarea>
        </div>

        <div class="flex items-center gap-4">
          <button type="submit" class="bg-primary text-white px-5 py-2 rounded-lg hover:bg-opacity-90">
            <?php echo $editStudent ? '💾 Update Student' : '➕ Create Student'; ?>
          </button>
          <?php if ($editStudent): ?>
            <a href="students.php" class="px-5 py-2 rounded-lg border border-gray-300 hover:bg-gray-100">❌ Cancel</a>
          <?php endif; ?>
        </div>
      </form>
    </div>

    <!-- student table -->
    <div class="bg-white rounded-xl shadow-md overflow-hidden">
      <div class="bg-accent text-white px-5 py-3 flex justify-between items-center">
        <h3 class="font-semibold">📊 All Students (<?php echo count($students); ?> records)</h3>
      </div>

      <?php if (!empty($students)): ?>
        <div class="overflow-x-auto">
          <table class="min-w-full border border-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Student ID</th>
                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Name</th>
                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Email</th>
                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Course</th>
                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Year</th>
                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Phone</th>
                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 text-sm">
              <?php foreach ($students as $s): ?>
                <tr>
                  <td class="px-4 py-2 font-medium"><?php echo htmlspecialchars($s['student_id']); ?></td>
                  <td class="px-4 py-2"><?php echo htmlspecialchars($s['first_name'] . ' ' . $s['last_name']); ?></td>
                  <td class="px-4 py-2"><?php echo htmlspecialchars($s['email']); ?></td>
                  <td class="px-4 py-2">
                    <?php if ($s['course_code'] && $s['course_name']): ?>
                      <span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded-full">
                        <?php echo htmlspecialchars($s['course_code']); ?>
                      </span>
                    <?php else: ?>
                      <span class="text-gray-400">No course assigned</span>
                    <?php endif; ?>
                  </td>
                  <td class="px-4 py-2">
                    <?php if ($s['year_level']): ?>
                      <span class="text-xs bg-green-100 text-green-800 px-2 py-1 rounded-full">
                        Year <?php echo $s['year_level']; ?>
                      </span>
                    <?php else: ?>
                      <span class="text-gray-400">-</span>
                    <?php endif; ?>
                  </td>
                  <td class="px-4 py-2"><?php echo htmlspecialchars($s['phone'] ?? '-'); ?></td>
                  <td class="px-4 py-2 space-x-2">
                    <a href="?action=edit&id=<?php echo $s['id']; ?>" 
                       class="px-3 py-1 bg-yellow-400 text-black rounded hover:bg-yellow-500 text-xs">Edit</a>
                    <a href="?action=delete&id=<?php echo $s['id']; ?>" 
                       onclick="return confirm('Are you sure you want to delete this student?')" 
                       class="px-3 py-1 bg-red-500 text-white rounded hover:bg-red-600 text-xs">Delete</a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <div class="px-6 py-10 text-center text-gray-500">
          <h4 class="text-lg font-medium mb-2">No Students Found</h4>
          <p>Start by adding your first student using the form above.</p>
        </div>
      <?php endif; ?>
    </div>
  </main>

  <!-- footer -->
  <footer class="bg-primary text-white text-center py-4">
    <p>&copy; 2025 School Management System</p>
  </footer>
</body>
</html>

<?php
// helper function only
function getOrdinalSuffix($number) {
    $ends = array('th','st','nd','rd','th','th','th','th','th','th');
    if ((($number % 100) >= 11) && (($number % 100) <= 13))
        return 'th';
    else
        return $ends[$number % 10];
}
?>