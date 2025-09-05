<?php
require_once __DIR__ . '/class/user_class.php';
require_once __DIR__ . '/class/student_class.php';
require_once __DIR__ . '/class/course_class.php';
require_once __DIR__ . '/auth/session.php';

// redirect if already logged in
Session::redirectIfLoggedIn();

$user = new User();
$student = new Student();
$course = new Course();
$message = '';
$showRegistration = false;

// get all courses for dropdown
$courses = $course->readAll();

// handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'];

    if ($action == 'login') {
        $username = $_POST['username'];
        $password = $_POST['password'];
        
        if (empty($username) || empty($password)) {
            $message = '<div class="bg-red-100 text-red-800 border border-red-300 px-4 py-3 rounded-lg mb-6">⚠️ Username and password are required!</div>';
        } else {
            $loginUser = $user->login($username, $password);
            
            if ($loginUser) {
                Session::login($loginUser);
                
                // redirect based on role
                if ($loginUser['role'] === 'admin') {
                    header('Location: admin/dashboard.php');
                } else {
                    header('Location: student/dashboard.php');
                }
                exit();
            } else {
                $message = '<div class="bg-red-100 text-red-800 border border-red-300 px-4 py-3 rounded-lg mb-6">❌ Invalid username or password!</div>';
            }
        }
    }

    if ($action == 'register') {
        $data = [
            'username' => $_POST['username'],
            'password' => $_POST['password'],
            'role' => $_POST['role']
        ];

        // add if registering as student
        if ($_POST['role'] === 'student') {
            $data['student_id'] = $_POST['student_id'];
            $data['first_name'] = $_POST['first_name'];
            $data['last_name'] = $_POST['last_name'];
            $data['email'] = $_POST['email'];
            $data['phone'] = $_POST['phone'];
            $data['address'] = $_POST['address'];
            $data['course_id'] = $_POST['course_id'];
            $data['year_level'] = $_POST['year_level'];
        }

        $errors = $user->validateUserData($data);
        if (empty($errors)) {
            if ($user->register($data)) {
                $message = '<div class="bg-green-100 text-green-800 border border-green-300 px-4 py-3 rounded-lg mb-6">✅ Account created successfully! You can now login.</div>';
                $showRegistration = false;
            } else {
                $message = '<div class="bg-red-100 text-red-800 border border-red-300 px-4 py-3 rounded-lg mb-6">❌ Error creating account!</div>';
            }
        } else {
            $message = '<div class="bg-red-100 text-red-800 border border-red-300 px-4 py-3 rounded-lg mb-6">❌ ' . implode('<br>', $errors) . '</div>';
        }
    }
}

// toggle registration form
if (isset($_GET['register'])) {
    $showRegistration = true;
}

// generate next student ID for the form
$nextStudentId = $student->generateStudentID();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>School Management System - Login</title>

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
<body class="bg-light min-h-screen flex items-center justify-center py-8">

  <div class="w-full max-w-lg">
    
    <!-- header -->
    <div class="text-center mb-8">
      <h1 class="text-4xl font-bold text-primary mb-2">School Management System</h1>
      <p class="text-gray-600">Please login to continue</p>
    </div>

    <!-- message -->
    <?php echo $message; ?>

    <!-- login/registration card -->
    <div class="bg-white rounded-xl shadow-md p-8">
      
      <?php if (!$showRegistration): ?>
        <!-- LOGIN FORM -->
        <h2 class="text-2xl font-semibold text-primary mb-6 text-center">Login</h2>
        
        <form method="POST" class="space-y-6">
          <input type="hidden" name="action" value="login">
          
          <div>
            <label for="username" class="block font-medium text-gray-700 mb-1">Username / Student ID</label>
            <input type="text" id="username" name="username" required
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary"
              placeholder="Enter your username or student ID">
          </div>

          <div>
            <label for="password" class="block font-medium text-gray-700 mb-1">Password</label>
            <input type="password" id="password" name="password" required
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary"
              placeholder="Enter your password">
          </div>

          <button type="submit" class="w-full bg-primary text-white py-3 rounded-lg hover:bg-opacity-90 font-semibold">
            Login
          </button>
        </form>

        <div class="mt-6 text-center">
          <p class="text-gray-600">Don't have an account?</p>
          <a href="?register=1" class="text-primary hover:underline font-medium">Create Account</a>
        </div>

      <?php else: ?>
        <!-- REGISTRATION FORM -->
        <h2 class="text-2xl font-semibold text-primary mb-6 text-center">Create Account</h2>
        
        <form method="POST" class="space-y-6" id="registerForm">
          <input type="hidden" name="action" value="register">
          
          <div>
            <label for="role" class="block font-medium text-gray-700 mb-1">Account Type</label>
            <select id="role" name="role" required onchange="toggleStudentFields()"
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
              <option value="">Select Account Type</option>
              <option value="admin">Admin</option>
              <option value="student">Student</option>
            </select>
          </div>

          <!-- student fields -->
          <div id="studentFields" style="display: none;" class="space-y-6">
            <div>
              <label for="student_id" class="block font-medium text-gray-700 mb-1">Student ID</label>
              <input type="text" id="student_id_display" value="<?php echo $nextStudentId; ?>" 
                     class="w-full px-4 py-3 border border-gray-300 rounded-lg bg-gray-100 text-gray-600" 
                     readonly disabled>
              <input type="hidden" id="student_id" name="student_id" value="<?php echo $nextStudentId; ?>">
              <p class="text-sm text-gray-500 mt-1">This student ID has been automatically assigned to you</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label for="first_name" class="block font-medium text-gray-700 mb-1">First Name *</label>
                <input type="text" id="first_name" name="first_name" required
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary"
                  placeholder="Enter your first name">
              </div>
              <div>
                <label for="last_name" class="block font-medium text-gray-700 mb-1">Last Name *</label>
                <input type="text" id="last_name" name="last_name" required
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary"
                  placeholder="Enter your last name">
              </div>
            </div>

            <div>
              <label for="email" class="block font-medium text-gray-700 mb-1">Email Address *</label>
              <input type="email" id="email" name="email" required
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary"
                placeholder="Enter your email address">
            </div>

            <div>
              <label for="phone" class="block font-medium text-gray-700 mb-1">Phone Number</label>
              <input type="tel" id="phone" name="phone"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary"
                placeholder="Enter your phone number (optional)">
            </div>

            <div>
              <label for="address" class="block font-medium text-gray-700 mb-1">Address</label>
              <textarea id="address" name="address" rows="3"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary"
                placeholder="Enter your address (optional)"></textarea>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label for="course_id" class="block font-medium text-gray-700 mb-1">Course/Program *</label>
                <select id="course_id" name="course_id" required
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                  <option value="">Select Course</option>
                  <?php foreach ($courses as $courseItem): ?>
                    <option value="<?php echo $courseItem['id']; ?>">
                      <?php echo htmlspecialchars($courseItem['course_code'] . ' - ' . $courseItem['course_name']); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div>
                <label for="year_level" class="block font-medium text-gray-700 mb-1">Year Level *</label>
                <select id="year_level" name="year_level" required
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                  <option value="">Select Year</option>
                  <option value="1">1st Year</option>
                  <option value="2">2nd Year</option>
                  <option value="3">3rd Year</option>
                  <option value="4">4th Year</option>
                  <option value="5">5th Year</option>
                </select>
              </div>
            </div>
          </div>
          
          <div>
            <label for="reg_username" class="block font-medium text-gray-700 mb-1">Username *</label>
            <input type="text" id="reg_username" name="username" required
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary"
              placeholder="Choose a username">
          </div>

          <div>
            <label for="reg_password" class="block font-medium text-gray-700 mb-1">Password *</label>
            <input type="password" id="reg_password" name="password" required
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary"
              placeholder="Choose a password (min 6 characters)">
          </div>

          <button type="submit" class="w-full bg-accent text-white py-3 rounded-lg hover:bg-opacity-90 font-semibold">
            ➕ Create Account
          </button>
        </form>

        <div class="mt-6 text-center">
          <p class="text-gray-600">Already have an account?</p>
          <a href="index.php" class="text-primary hover:underline font-medium">Login</a>
        </div>
      <?php endif; ?>

    </div>

    <!-- footer note -->
    <div class="text-center mt-8 text-sm text-gray-500">
      <p>🎓 School Management System</p>
    </div>

  </div>

  <script>
    function toggleStudentFields() {
      const role = document.getElementById('role').value;
      const studentFields = document.getElementById('studentFields');
      const requiredFields = ['first_name', 'last_name', 'email', 'course_id', 'year_level'];
      
      if (role === 'student') {
        studentFields.style.display = 'block';
        // make fields required
        requiredFields.forEach(fieldName => {
          const field = document.getElementById(fieldName);
          if (field) {
            field.required = true;
          }
        });
        // generate new student ID when student role is selected
        updateStudentId();
      } else {
        studentFields.style.display = 'none';
        // remove required attribute
        requiredFields.forEach(fieldName => {
          const field = document.getElementById(fieldName);
          if (field) {
            field.required = false;
            field.value = '';
          }
        });
      }
    }

    function updateStudentId() {
      const currentId = document.getElementById('student_id_display').value;
      document.getElementById('student_id').value = currentId;
    }
  </script>

</body>
</html>