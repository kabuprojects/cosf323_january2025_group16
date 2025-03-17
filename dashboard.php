<?php
// Include database connection and start session
require_once 'db.php';
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get user details from session
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$role_id = $_SESSION['role_id'];

// Fetch role from database
$sql = "SELECT * FROM roles WHERE role_id='$role_id' LIMIT 1";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    $role = $result->fetch_assoc();
} else {
    die("Role not found.");
}

// Role-based dashboard content with user's name
switch ($role['role_name']) {
    case 'Investigator':
        $role_content = "Welcome $username , you can view case details and manage investigations.";
        break;
    case 'Digital Forensic Examiner':
        $role_content = "Welcome $username, you can analyze evidence and view forensic reports.";
        break;
    case 'Lab Personnel':
        $role_content = "Welcome $username, you can manage lab results and oversee testing.";
        break;
    case 'System Administrator':
        $role_content = "Welcome $username, you can manage users and system settings.";
        break;
    default:
        $role_content = "Welcome $username,";
        break;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Secure Chain of Custody Dashboard</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      margin: 0;
      display: flex;
      flex-direction: column;
      height: 100vh;
      background-image: url('images/background.jpg'); /* Update with your image path */
      background-size: cover;
      background-position: center;
      background-repeat: no-repeat;
    }

    /* Navigation bar */
    .navbar {
      background-color: #16213e;
      padding: 1rem;
      display: flex;
      flex-direction: column;
      align-items: center;
      color: white;
      width: 180px; /* Expanded sidebar */
      position: fixed;
      height: 100%;
      transition: width 0.3s ease-in-out;
      overflow: hidden;
    }

    /* Hamburger menu and Home text */
    .hamburger-container {
      display: flex;
      align-items: center;
      cursor: pointer;
      margin-bottom: 20px;
    }

    .hamburger {
      width: 30px;
      height: 25px;
      position: relative;
    }

    .hamburger div {
      width: 100%;
      height: 5px;
      background-color: white;
      margin: 5px 0;
      transition: transform 0.3s, opacity 0.3s;
    }

    .hamburger-container span {
      margin-left: 10px;
      font-size: 1.2rem;
      color: white;
    }

    /* Transform hamburger to X */
    .navbar.open .hamburger div:nth-child(1) {
      transform: rotate(45deg) translate(5px, 5px);
    }

    .navbar.open .hamburger div:nth-child(2) {
      opacity: 0;
    }

    .navbar.open .hamburger div:nth-child(3) {
      transform: rotate(-45deg) translate(5px, -5px);
    }

    /* Sidebar expansion */
    .navbar.open {
      width: 200px;
    }

    /* Menu items */
    .menu {
      visibility: hidden;
      opacity: 0;
      transition: opacity 0.3s ease-in-out;
      text-align: center;
    }

    .navbar.open .menu {
      visibility: visible;
      opacity: 1;
    }

    .navbar a {
      text-decoration: none;
      color: white;
      margin-bottom: 20px;
      font-size: 1.1rem;
      transition: color 0.3s;
      cursor: pointer;
    }

    .navbar a:hover {
      color: #68d391;
    }

    /* Roles dropdown */
    .roles-btn {
      margin-top: 10px;
      background-color: #10b981; /* Green for evidence storage button */
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 12px;
      border-radius: 8px;
      transition: background-color 0.3s;
      font-size: 1.1rem;
      color: white;
      text-decoration: none;
    }

    .roles-list {
      display: none;
      flex-direction: column;
      margin-top: 20px;
    }

    .roles-list .icon {
      width: 70px;
      height: 50px;
      background-color: #374151;
      border-radius: 8px;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      margin-bottom: 1.5rem;
      cursor: pointer;
      transition: background-color 0.3s;
      text-align: center;
      padding: 0.5rem;
    }

    .roles-list .icon:hover {
      background-color: #4b5563;
    }

    .roles-list .icon span {
      font-size: 0.75rem;
      color: #fff;
    }

    /* Main Content */
    .main-content {
      flex: 1;
      padding: 2rem;
      overflow-y: auto;
      margin-left: 280px; /* Space for sidebar */
      color: white;
    }

    .main-content h1 {
      margin-bottom: 1rem;
    }

    /* Logout button */
    .logout-btn {
      margin-top: auto; /* Push to the bottom */
      margin-bottom: 20px;
      text-align: center;
    }

    .logout-btn a {
      text-decoration: none;
      color: white;
      font-size: 1.1rem;
      transition: color 0.3s;
    }

    .logout-btn a:hover {
      color: #ff6b6b; /* Red color for emphasis */
    }

    /* Evidence Storage Button */
    .evidence-storage-btn {
      background-color: #10b981; /* Green for evidence storage button */
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 12px;
      margin: 5px 0;
      border-radius: 8px;
      transition: background-color 0.3s;
      font-size: 1.1rem;
      color: white;
      text-decoration: none;
    }

    .evidence-storage-btn:hover {
      background-color: #0d8f63;
    }

    .evidence-storage-btn:before {
      content: '📦'; /* Add a package box icon */
      margin-right: 10px;
    }

    footer {
      background-color: #00193186;
      color: white;
      text-align: center;
      padding: 1rem 0;
      position: relative; /* Needed for sticky footer */
      bottom: 0; /* Stick to the bottom */
      width: 83%;
      left: 17%;
      top: 51%;
    }
  </style>
</head>
<body>
  <!-- Navigation Bar -->
  <div class="navbar" id="navbar">
    <div class="hamburger-container" onclick="toggleMenu()">
      <div class="hamburger">
        <div></div>
        <div></div>
        <div></div>
      </div>
      <span>HOME</span>
    </div>
    <div class="menu">
      <a href="#" class="roles-btn" onclick="toggleRoles()">Roles</a>
      <div class="roles-list" id="roles-list">
        <div class="icon" onclick="navigateTo('investigator')" title="Investigator">
          🔍
          <span>Investigator</span>
        </div>
        <div class="icon" onclick="navigateTo('forensic_examiner')" title="Digital Forensic Examiner">
          🧪
          <span>Forensic Examiner</span>
        </div>
        <div class="icon" onclick="navigateTo('lab_personnel')" title="Lab Personnel">
          🧫
          <span>Lab Personnel</span>
        </div>
        <div class="icon" onclick="navigateTo('system_admin')" title="System Administrator">
          ⚙️
          <span>System Admin</span>
        </div>
      </div>
      <!-- Evidence Storage Button -->
      <a href="evidence_storage.php" class="evidence-storage-btn">Evidence Storage</a>
      <!-- Logout Button -->
      <div class="logout-btn">
        <a href="logout.php">Logout</a>
      </div>
    </div>
  </div>

  <div class="overlay">
    <!-- Main Content -->
    <div class="main-content">
      <h1><?php echo $role_content; ?></h1>
      <p>You're logged in as a <?php echo htmlspecialchars($role['role_name']); ?></p>
    </div>
  </div>
  <footer>
    <p>&copy; 2025 team16(linet,anette,naftal)</p>
  </footer>

  <script>
    function toggleMenu() {
      const navbar = document.getElementById('navbar');
      const menu = document.querySelector('.menu');
      navbar.classList.toggle('open');

      // Toggle menu visibility
      if (navbar.classList.contains('open')) {
        menu.style.visibility = 'visible';
        menu.style.opacity = '1';
      } else {
        menu.style.visibility = 'hidden';
        menu.style.opacity = '0';
      }
    }

    function navigateTo(role) {
      switch (role) {
        case 'investigator':
          window.location.href = 'investigator.php';
          break;
        case 'forensic_examiner':
          window.location.href = 'forensic examiner.php';
          break;
        case 'lab_personnel':
          window.location.href = 'lab personnel.php';
          break;
        case 'system_admin':
          window.location.href = 'system admin.php';
          break;
        default:
          alert('Invalid role selected.');
          break;
      }
    }

    function toggleRoles() {
      const rolesList = document.getElementById('roles-list');
      rolesList.style.display = rolesList.style.display === 'flex' ? 'none' : 'flex';
    }
  </script>
</body>
</html>