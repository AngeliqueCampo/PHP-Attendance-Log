<?php
// attendance CRUD 
require_once __DIR__ . '/class/attendance_class.php';
require_once __DIR__ . '/class/student_class.php';
require_once __DIR__ . '/auth/session.php';
require_once __DIR__ . '/class/admin_class.php';

// ensure admin access
Session::requireAdmin();

$admin = new Admin();
$currentUser = Session::getCurrentUser();


$attendance = new Attendance();
$student = new Student();
$message = '';
$editAttendance = null;

// handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'];

    $data = [
        'student_id' => $_POST['student_id'],
        'date' => $_POST['date'],
        'status' => $_POST['status'],
        'remarks' => $_POST['remarks']
    ];

    if ($action == 'create') {
        $errors = $attendance->validateAttendanceData($data);
        if (empty($errors)) {
            $message = $attendance->create($data)
                ? '<div class="bg-green-100 text-green-800 border border-green-300 px-4 py-3 rounded-lg mb-6">✅ Attendance record created successfully!</div>'
                : '<div class="bg-red-100 text-red-800 border border-red-300 px-4 py-3 rounded-lg mb-6">❌ Error creating attendance record!</div>';
        } else {
            $message = '<div class="bg-red-100 text-red-800 border border-red-300 px-4 py-3 rounded-lg mb-6">❌ ' . implode('<br>', $errors) . '</div>';
        }
    }

    if ($action == 'update') {
        $id = $_POST['id'];
        $errors = $attendance->validateAttendanceData($data);
        if (empty($errors)) {
            $message = $attendance->update($id, $data)
                ? '<div class="bg-green-100 text-green-800 border border-green-300 px-4 py-3 rounded-lg mb-6">✅ Attendance record updated successfully!</div>'
                : '<div class="bg-red-100 text-red-800 border border-red-300 px-4 py-3 rounded-lg mb-6">❌ Error updating attendance record!</div>';
        } else {
            $message = '<div class="bg-red-100 text-red-800 border border-red-300 px-4 py-3 rounded-lg mb-6">❌ ' . implode('<br>', $errors) . '</div>';
        }
    }
}

// handle GET requests
if (isset($_GET['action'])) {
    if ($_GET['action'] == 'delete' && isset($_GET['id'])) {
        $message = $attendance->delete($_GET['id'])
            ? '<div class="bg-green-100 text-green-800 border border-green-300 px-4 py-3 rounded-lg mb-6">✅ Attendance record deleted successfully!</div>'
            : '<div class="bg-red-100 text-red-800 border border-red-300 px-4 py-3 rounded-lg mb-6">❌ Error deleting attendance record!</div>';
    }

    if ($_GET['action'] == 'edit' && isset($_GET['id'])) {
        $editAttendance = $attendance->readById($_GET['id']);
    }
}

// get all attendance records and student details
$attendanceRecords = $attendance->getAttendanceWithStudents();
$students = $student->readAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Attendance Management - CRUD System</title>
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
      <a href="admin/dashboard.php" class="text-gray-700 hover:text-primary font-medium">🏠 Dashboard</a>
      <a href="students.php" class="text-gray-700 hover:text-primary font-medium">👨‍🎓 Students</a>
      <a href="attendance.php" class="text-primary font-semibold">📋 Attendance</a>
      <a href="admin/add_course.php" class="text-gray-700 hover:text-primary font-medium">📚 Courses</a>
      <a href="admin/view_attendance.php" class="text-gray-700 hover:text-primary font-medium">📊 Reports</a>
    </div>
  </nav>
  
  <main class="container mx-auto px-4 py-10 flex-grow">
    <?php echo $message; ?>

    <!-- card -->
    <div class="bg-white rounded-xl shadow-md p-6 mb-10">
      <h3 class="text-xl font-semibold text-primary mb-6">
        <?php echo $editAttendance ? '✏️ Edit Attendance Record' : '➕ Add New Attendance Record'; ?>
      </h3>

      <form method="POST" class="space-y-6">
        <input type="hidden" name="action" value="<?php echo $editAttendance ? 'update' : 'create'; ?>">
        <?php if ($editAttendance): ?>
          <input type="hidden" name="id" value="<?php echo $editAttendance['id']; ?>">
        <?php endif; ?>

        <div class="grid md:grid-cols-2 gap-6">
          <div>
            <label for="student_id" class="block font-medium text-gray-700 mb-1">Student</label>
            <select id="student_id" name="student_id" required
              class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
              <option value="">Select a Student</option>
              <?php foreach ($students as $s): ?>
                <option value="<?php echo $s['student_id']; ?>" 
                  <?php echo ($editAttendance && $editAttendance['student_id'] == $s['student_id']) ? 'selected' : ''; ?>>
                  <?php echo $s['student_id'] . ' - ' . $s['first_name'] . ' ' . $s['last_name']; ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label for="date" class="block font-medium text-gray-700 mb-1">Date</label>
            <input type="date" id="date" name="date" required
              value="<?php echo $editAttendance ? $editAttendance['date'] : date('Y-m-d'); ?>"
              class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
          </div>
        </div>

        <div class="grid md:grid-cols-2 gap-6">
          <div>
            <label for="status" class="block font-medium text-gray-700 mb-1">Status</label>
            <select id="status" name="status" required
              class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
              <option value="">Select Status</option>
              <option value="Present" <?php echo ($editAttendance && $editAttendance['status'] == 'Present') ? 'selected' : ''; ?>>Present</option>
              <option value="Absent" <?php echo ($editAttendance && $editAttendance['status'] == 'Absent') ? 'selected' : ''; ?>>Absent</option>
              <option value="Late" <?php echo ($editAttendance && $editAttendance['status'] == 'Late') ? 'selected' : ''; ?>>Late</option>
            </select>
          </div>
          <div>
            <label for="remarks" class="block font-medium text-gray-700 mb-1">Remarks</label>
            <textarea id="remarks" name="remarks" rows="3"
              class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary"><?php echo $editAttendance ? $editAttendance['remarks'] : ''; ?></textarea>
          </div>
        </div>

        <div class="flex items-center gap-4">
          <button type="submit" class="bg-primary text-white px-5 py-2 rounded-lg hover:bg-opacity-90">
            <?php echo $editAttendance ? '💾 Update Record' : '➕ Create Record'; ?>
          </button>
          <?php if ($editAttendance): ?>
            <a href="attendance.php" class="px-5 py-2 rounded-lg border border-gray-300 hover:bg-gray-100">❌ Cancel</a>
          <?php endif; ?>
        </div>
      </form>
    </div>

    <!-- attendance table -->
    <div class="bg-white rounded-xl shadow-md overflow-hidden">
      <div class="bg-accent text-white px-5 py-3 flex justify-between items-center">
        <h3 class="font-semibold">📊 All Attendance Records (<?php echo count($attendanceRecords); ?> records)</h3>
      </div>

      <?php if (!empty($attendanceRecords)): ?>
        <div class="overflow-x-auto">
          <table class="min-w-full border border-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">ID</th>
                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Student ID</th>
                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Name</th>
                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Date</th>
                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Status</th>
                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Remarks</th>
                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 text-sm">
              <?php foreach ($attendanceRecords as $record): ?>
                <tr>
                  <td class="px-4 py-2"><?php echo $record['id']; ?></td>
                  <td class="px-4 py-2 font-medium"><?php echo htmlspecialchars($record['student_id']); ?></td>
                  <td class="px-4 py-2"><?php echo htmlspecialchars($record['first_name'] . ' ' . $record['last_name']); ?></td>
                  <td class="px-4 py-2"><?php echo date('M d, Y', strtotime($record['date'])); ?></td>
                  <td class="px-4 py-2">
                    <?php if ($record['status'] == 'Present'): ?>
                      <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs font-medium">Present</span>
                    <?php elseif ($record['status'] == 'Absent'): ?>
                      <span class="px-2 py-1 bg-red-100 text-red-800 rounded-full text-xs font-medium">Absent</span>
                    <?php else: ?>
                      <span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs font-medium">Late</span>
                    <?php endif; ?>
                  </td>
                  <td class="px-4 py-2"><?php echo htmlspecialchars(substr($record['remarks'], 0, 30)) . (strlen($record['remarks']) > 30 ? '...' : ''); ?></td>
                  <td class="px-4 py-2 space-x-2">
                    <a href="?action=edit&id=<?php echo $record['id']; ?>" class="px-3 py-1 bg-yellow-400 text-black rounded hover:bg-yellow-500">Edit</a>
                    <a href="?action=delete&id=<?php echo $record['id']; ?>" onclick="return confirm('Are you sure you want to delete this attendance record?')" class="px-3 py-1 bg-red-500 text-white rounded hover:bg-red-600">Delete</a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <div class="px-6 py-10 text-center text-gray-500">
          <h4 class="text-lg font-medium mb-2">No Attendance Records Found</h4>
          <p>Start by adding your first attendance record using the form above.</p>
        </div>
      <?php endif; ?>
    </div>
  </main>

  <!-- footer -->
  <footer class="bg-primary text-white text-center py-4">
  </footer>
</body>
</html>
