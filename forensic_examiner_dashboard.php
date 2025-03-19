<?php  
// Start session to get the logged-in user
session_start();

// Database connection
$host = "localhost";
$username = "root";
$password = "";
$dbname = "chain_of_custody_db";

$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}

// Set Kenyan timezone
date_default_timezone_set('Africa/Nairobi');

// Function to log actions into the system_logs table
function logAction($conn, $user_id, $action, $evidence_id, $performed_by, $from_role, $to_role) {
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    $log_date = date("Y-m-d H:i:s");

    // Fetch role names from the database
    $from_role_name = getRoleName($conn, $from_role);
    $to_role_name = getRoleName($conn, $to_role);

    // Format the action message dynamically
    $action_message = "Evidence ID $evidence_id transferred to $to_role_name (User ID: $to_role) by $from_role_name (User ID: $performed_by)";

    $sql = "INSERT INTO system_logs (user_id, action, ip_address, user_agent, log_date, evidence_id, performed_by, from_role, to_role)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issssiiii", $user_id, $action_message, $ip_address, $user_agent, $log_date, $evidence_id, $performed_by, $from_role, $to_role);
    $stmt->execute();
    $stmt->close();
}

// Function to get role name based on role ID from the database
function getRoleName($conn, $role_id) {
    $sql = "SELECT role_name FROM roles WHERE role_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $role_id);
    $stmt->execute();
    $stmt->bind_result($role_name);
    $stmt->fetch();
    $stmt->close();
    return $role_name ?? "Unknown Role";
}

// Fetch received evidence
$evidence_sql = "SELECT e.evidence_id, e.evidence_name, e.description, e.collection_date, c.case_name, u.username AS investigator_name, e.status
                 FROM evidence e
                 JOIN cases c ON e.case_id = c.case_id
                 JOIN users u ON e.investigator_id = u.user_id
                 WHERE e.status = 'Transferred' OR e.status = 'Analysis Complete'";
$evidence_result = $conn->query($evidence_sql);
$evidence_data = [];
while ($row = $evidence_result->fetch_assoc()) {
    $evidence_data[] = $row;
}

// Handle evidence analysis
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['analyze_evidence'])) {
    $evidence_id = $_POST['evidence_id'];
    $analysis_notes = trim($_POST['analysis_notes']);
    $examiner_id = $_SESSION['user_id'];
    $analysis_date = date("Y-m-d H:i:s");

    // File upload handling
    if (!empty($_FILES['report']['name'])) {
        $target_dir = "uploads/reports/";
        $target_file = $target_dir . basename($_FILES['report']['name']);
        move_uploaded_file($_FILES['report']['tmp_name'], $target_file);
    } else {
        $target_file = NULL;
    }

    $sql_update = "UPDATE evidence SET status='Analysis Complete', analysis_notes=?, analysis_date=?, report_path=?, examiner_id=? WHERE evidence_id=?";
    $stmt = $conn->prepare($sql_update);
    $stmt->bind_param("ssssi", $analysis_notes, $analysis_date, $target_file, $examiner_id, $evidence_id);
    $stmt->execute();
    $stmt->close();

    echo "<script>alert('Evidence analyzed successfully!'); window.location='forensic_examiner_dashboard.php';</script>";
}

// Handle evidence status update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_status'])) {
    $evidence_id = $_POST['evidence_id'];
    $status = $_POST['status'];

    $sql_status_update = "UPDATE evidence SET status=? WHERE evidence_id=?";
    $stmt = $conn->prepare($sql_status_update);
    $stmt->bind_param("si", $status, $evidence_id);
    $stmt->execute();
    $stmt->close();

    echo "<script>alert('Evidence status updated successfully!'); window.location='forensic_examiner_dashboard.php';</script>";
}

// Handle evidence transfer to lab personnel
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['transfer_evidence'])) {
    $evidence_id = $_POST['evidence_id'];
    $transfer_date = date("Y-m-d H:i:s");
    $examiner_id = $_SESSION['user_id'];
    
    // Assuming lab personnel ID is 2 (you can fetch it dynamically if needed)
    $lab_personnel_id = 2;

    // Fetch the role IDs for Forensic Examiner and Lab Personnel
    $from_role = 3; // Forensic Examiner role ID
    $to_role = 4;   // Lab Personnel role ID

    // Update evidence status to 'Transferred'
    $sql_transfer = "UPDATE evidence SET status='Transferred', transfer_date=?, lab_personnel_id=? WHERE evidence_id=?";
    $stmt = $conn->prepare($sql_transfer);
    $stmt->bind_param("sii", $transfer_date, $lab_personnel_id, $evidence_id);
    $stmt->execute();
    $stmt->close();

    // Log the transfer action
    logAction($conn, $examiner_id, "Evidence Transfer", $evidence_id, $examiner_id, $from_role, $to_role);

    echo "<script>alert('Evidence transferred to Lab Personnel!'); window.location='forensic_examiner_dashboard.php';</script>";
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forensic Examiner Dashboard</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #1a1a2e; color: white; display: flex; }
        .sidebar { width: 250px; background: #16213e; padding: 20px; position: fixed; height: 100vh; }
        .sidebar button { width: 100%; padding: 10px; background: #0f3460; border: none; color: white; margin-bottom: 10px; cursor: pointer; }
        .sidebar button:hover { background: #1b4b91; }
        .content { margin-left: 270px; padding: 20px; width: calc(100% - 270px); }
        .form-container { background: #0f3460; padding: 20px; border-radius: 8px; margin-top: 20px; }
        input, textarea { width: 100%; padding: 8px; margin: 10px 0; }
        table { width: 100%; margin-top: 10px; border-collapse: collapse; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2>Forensic Examiner</h2>
        <button onclick="window.location.href='dashboard.php'">Main Dashboard</button>
        <button onclick="toggleSection('received_evidence')">Received Evidence</button>
        <button onclick="toggleSection('update_evidence')">Update Evidence</button>
        <button onclick="toggleSection('transfer_evidence')">Transfer to Lab Personnel</button>
        <button onclick="confirmLogout()">Logout</button>
    </div>
    
    <div class="content">
        <h3>Welcome, Forensic Examiner!</h3>

        <!-- Received Evidence Section -->
        <div id="received_evidence" class="form-container" style="display:none;">
            <h4>Received Evidence</h4>
            <table>
                <tr>
                    <th>ID</th>
                    <th>Evidence Name</th>
                    <th>Description</th>
                    <th>Case</th>
                    <th>Investigator</th>
                    <th>Actions</th>
                </tr>
                <?php foreach ($evidence_data as $evidence): ?>
                <tr>
                    <td><?php echo $evidence['evidence_id']; ?></td>
                    <td><?php echo htmlspecialchars($evidence['evidence_name']); ?></td>
                    <td><?php echo htmlspecialchars($evidence['description']); ?></td>
                    <td><?php echo htmlspecialchars($evidence['case_name']); ?></td>
                    <td><?php echo htmlspecialchars($evidence['investigator_name']); ?></td>
                    <td>
                        <button onclick="loadUpdateForm(<?php echo $evidence['evidence_id']; ?>)">Update</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <!-- Update Evidence Section -->
        <div id="update_evidence" class="form-container" style="display:none;">
            <h4>Update Evidence</h4>
            <form id="updateForm" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="evidence_id" id="evidence_id">
                <textarea name="analysis_notes" placeholder="Enter analysis notes" required></textarea>
                <label>Status:</label>
                <select name="status" required>
                    <option value="Pending">Pending</option>
                    <option value="Under Review">Under Review</option>
                    <option value="Analysis Complete">Analysis Complete</option>
                </select>
                <button type="submit" name="analyze_evidence">Analyze</button>
                <button type="submit" name="update_status">Update Status</button>
            </form>
        </div>

        <!-- Transfer Evidence Section -->
        <div id="transfer_evidence" class="form-container" style="display:none;">
            <h4>Transfer Evidence to Lab Personnel</h4>
            <table>
                <tr>
                    <th>ID</th>
                    <th>Evidence Name</th>
                    <th>Description</th>
                    <th>Case</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
                <?php foreach ($evidence_data as $evidence): ?>
                <tr>
                    <td><?php echo $evidence['evidence_id']; ?></td>
                    <td><?php echo htmlspecialchars($evidence['evidence_name']); ?></td>
                    <td><?php echo htmlspecialchars($evidence['description']); ?></td>
                    <td><?php echo htmlspecialchars($evidence['case_name']); ?></td>
                    <td><?php echo htmlspecialchars($evidence['status']); ?></td>
                    <td>
                        <form method="POST" action="">
                            <input type="hidden" name="evidence_id" value="<?php echo $evidence['evidence_id']; ?>">
                            <button type="submit" name="transfer_evidence">Transfer</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>

    </div>

    <script>
        function toggleSection(sectionId) {
            // Get all section elements
            const sections = document.querySelectorAll('.form-container');
            sections.forEach(function(section) {
                if (section.id === sectionId) {
                    section.style.display = section.style.display === 'none' ? 'block' : 'none';
                } else {
                    section.style.display = 'none';
                }
            });
        }

        function loadUpdateForm(evidence_id) {
            // Load the form with the specific evidence details
            const form = document.getElementById("updateForm");
            document.getElementById("evidence_id").value = evidence_id;
            form.style.display = "block"; // Show the update form
            toggleSection('update_evidence'); // Switch to the Update Evidence section
        }

        function confirmLogout() {
            if (confirm("Are you sure you want to log out?")) {
                window.location.href = "logout.php";
            }
        }
    </script>
</body>
</html>