<?php
require_once __DIR__ . '/../auth/session.php';
require_once __DIR__ . '/../class/course_class.php';

// ensure admin access
Session::requireAdmin();

$course = new Course();
$message = '';
$editCourse = null;

// handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'];

    if ($action == 'create') {
        $data = [
            'course_code' => strtoupper(trim($_POST['course_code'])),
            'course_name' => trim($_POST['course_name']),
            'description' => trim($_POST['description'])
        ];

        $errors = $course->validateCourseData($data);
        if (empty($errors)) {
            $message = $course->create($data)
                ? '<div class="bg-green-100 text-green-800 border border-green-300 px-4 py-3 rounded-lg mb-6">✅ Course created successfully!</div>'
                : '<div class="bg-red-100 text-red-800 border border-red-300 px-4 py-3 rounded-lg mb-6">❌ Error creating course!</div>';
        } else {
            $message = '<div class="bg-red-100 text-red-800 border border-red-300 px-4 py-3 rounded-lg mb-6">❌ ' . implode('<br>', $errors) . '</div>';
        }
    }

    if ($action == 'update') {
        $id = $_POST['id'];
        $data = [
            'course_code' => strtoupper(trim($_POST['course_code'])),
            'course_name' => trim($_POST['course_name']),
            'description' => trim($_POST['description'])
        ];

        $errors = $course->validateCourseData($data);
        if (empty($errors)) {
            $message = $course->update($id, $data)
                ? '<div class="bg-green-100 text-green-800 border border-green-300 px-4 py-3 rounded-lg mb-6">✅ Course updated successfully!</div>'
                : '<div class="bg-red-100 text-red-800 border border-red-300 px-4 py-3 rounded-lg mb-6">❌ Error updating course!</div>';
        } else {
            $message = '<div class="bg-red-100 text-red-800 border border-red-300 px-4 py-3 rounded-lg mb-6">❌ ' . implode('<br>', $errors) . '</div>';
        }
    }
}

// handle GET requests
if (isset($_GET['action'])) {
    if ($_GET['action'] == 'delete' && isset($_GET['id'])) {
        $message = $course->delete($_GET['id'])
            ? '<div class="bg-green-100 text-green-800 border border-green-300 px-4 py-3 rounded-lg mb-6">✅ Course deleted successfully!</div>'
            : '<div class="bg-red-100 text-red-800 border border-red-300 px-4 py-3 rounded-lg mb-6">❌ Error deleting course!</div>';
    }

    if ($_GET['action'] == 'edit' && isset($_GET['id'])) {
        $editCourse = $course->readById($_GET['id']);
    }
}

// get all courses
$courses = $course->readAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Course Management - Admin Dashboard</title>
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
      <h1 class="text-3xl font-bold">Course Management</h1>
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
      <a href="add_course.php" class="text-primary font-semibold">📚 Courses</a>
      <a href="view_attendance.php" class="text-gray-700 hover:text-primary font-medium">📊 Reports</a>
    </div>
  </nav>

  <main class="container mx-auto px-6 py-10 flex-grow">
    <?php echo $message; ?>

    <!-- add/edit course form -->
    <div class="bg-white rounded-xl shadow-md p-6 mb-10">
      <h3 class="text-xl font-semibold text-primary mb-6">
        <?php echo $editCourse ? '✏️ Edit Course' : '➕ Add New Course'; ?>
      </h3>

      <form method="POST" class="space-y-6">
        <input type="hidden" name="action" value="<?php echo $editCourse ? 'update' : 'create'; ?>">
        <?php if ($editCourse): ?>
          <input type="hidden" name="id" value="<?php echo $editCourse['id']; ?>">
        <?php endif; ?>

        <div class="grid md:grid-cols-2 gap-6">
          <div>
            <label for="course_code" class="block font-medium text-gray-700 mb-1">Course Code</label>
            <input type="text" id="course_code" name="course_code" required
              value="<?php echo $editCourse ? htmlspecialchars($editCourse['course_code']) : ''; ?>"
              class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary"
              placeholder="e.g., BSCS, BSIT, BSBA">
          </div>
          <div>
            <label for="course_name" class="block font-medium text-gray-700 mb-1">Course Name</label>
            <input type="text" id="course_name" name="course_name" required
              value="<?php echo $editCourse ? htmlspecialchars($editCourse['course_name']) : ''; ?>"
              class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary"
              placeholder="e.g., Bachelor of Science in Computer Science">
          </div>
        </div>

        <div>
          <label for="description" class="block font-medium text-gray-700 mb-1">Description</label>
          <textarea id="description" name="description" rows="4"
            class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary"
            placeholder="Brief description of the course/program"><?php echo $editCourse ? htmlspecialchars($editCourse['description']) : ''; ?></textarea>
        </div>

        <div class="flex items-center gap-4">
          <button type="submit" class="bg-primary text-white px-5 py-2 rounded-lg hover:bg-opacity-90">
            <?php echo $editCourse ? '💾 Update Course' : '➕ Create Course'; ?>
          </button>
          <?php if ($editCourse): ?>
            <a href="add_course.php" class="px-5 py-2 rounded-lg border border-gray-300 hover:bg-gray-100">❌ Cancel</a>
          <?php endif; ?>
        </div>
      </form>
    </div>

    <!-- courses table -->
    <div class="bg-white rounded-xl shadow-md overflow-hidden">
      <div class="bg-accent text-white px-5 py-3 flex justify-between items-center">
        <h3 class="font-semibold">📊 All Courses (<?php echo count($courses); ?> courses)</h3>
      </div>

      <?php if (!empty($courses)): ?>
        <div class="overflow-x-auto">
          <table class="min-w-full border border-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">ID</th>
                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Course Code</th>
                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Course Name</th>
                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Description</th>
                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Created</th>
                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 text-sm">
              <?php foreach ($courses as $c): ?>
                <tr>
                  <td class="px-4 py-2"><?php echo $c['id']; ?></td>
                  <td class="px-4 py-2 font-medium"><?php echo htmlspecialchars($c['course_code']); ?></td>
                  <td class="px-4 py-2"><?php echo htmlspecialchars($c['course_name']); ?></td>
                  <td class="px-4 py-2"><?php echo htmlspecialchars(substr($c['description'], 0, 50)) . (strlen($c['description']) > 50 ? '...' : ''); ?></td>
                  <td class="px-4 py-2"><?php echo date('M d, Y', strtotime($c['created_at'])); ?></td>
                  <td class="px-4 py-2 space-x-2">
                    <a href="?action=edit&id=<?php echo $c['id']; ?>" class="px-3 py-1 bg-yellow-400 text-black rounded hover:bg-yellow-500">Edit</a>
                    <a href="?action=delete&id=<?php echo $c['id']; ?>" onclick="return confirm('Are you sure you want to delete this course? This will affect all students enrolled in this course!')" class="px-3 py-1 bg-red-500 text-white rounded hover:bg-red-600">Delete</a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <div class="px-6 py-10 text-center text-gray-500">
          <h4 class="text-lg font-medium mb-2">No Courses Found</h4>
          <p>Start by adding your first course using the form above.</p>
        </div>
      <?php endif; ?>
    </div>
  </main>

  <!-- footer -->
  <footer class="bg-primary text-white text-center py-4">
  </footer>
</body>
</html>