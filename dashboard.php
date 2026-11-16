<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$name    = $_SESSION['name'];
$batch   = $_SESSION['batch'];
$role    = $_SESSION['role'];
$message = "";

// ১. Notification Mark as Read / Clear
if (isset($_GET['mark_read'])) {
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();
    header("Location: dashboard.php");
    exit();
}

// ২. Note Upload Handling (Only CR and Admin)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['upload_note'])) {
    if ($role === 'cr' || $role === 'admin') {
        $title       = trim($_POST['title']);
        $course_name = trim($_POST['course_name']);
        $semester    = trim($_POST['semester']);
        $drive_link  = trim($_POST['drive_link']);
        $file_path   = NULL;

        // Direct File Upload handling
        if (isset($_FILES['note_file']) && $_FILES['note_file']['error'] === UPLOAD_ERR_OK) {
            $file_tmp   = $_FILES['note_file']['tmp_name'];
            $file_name  = basename($_FILES['note_file']['name']);
            $file_ext   = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $allowed    = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'zip', 'txt', 'png', 'jpg'];

            if (in_array($file_ext, $allowed)) {
                $unique_name = time() . "_" . preg_replace("/[^a-zA-Z0-9._-]/", "", $file_name);
                $target_dir  = "uploads/";
                if (!is_dir($target_dir)) {
                    mkdir($target_dir, 0777, true);
                }
                $file_path = $target_dir . $unique_name;
                move_uploaded_file($file_tmp, $file_path);
            } else {
                $message = "<div class='alert error'>Invalid file type! Allowed: PDF, DOCX, PPT, ZIP, Images.</div>";
            }
        }

        if (empty($message)) {
            $stmt = $conn->prepare("INSERT INTO notes (title, course_name, semester, drive_link, file_path, uploaded_by) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssi", $title, $course_name, $semester, $drive_link, $file_path, $user_id);

            if ($stmt->execute()) {
                // ফাইল আপলোড সফল হলে সব রেজিস্টার্ড স্টুডেন্টদের জন্য নোটিফিকেশন তৈরি
                $notif_text = "New note uploaded: " . $title . " (" . $course_name . ", " . $semester . " Sem)";
                $students = $conn->query("SELECT id FROM users WHERE role = 'student'");
                
                if ($students && $students->num_rows > 0) {
                    $notif_stmt = $conn->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
                    while ($st = $students->fetch_assoc()) {
                        $st_id = $st['id'];
                        $notif_stmt->bind_param("is", $st_id, $notif_text);
                        $notif_stmt->execute();
                    }
                    $notif_stmt->close();
                }

                $message = "<div class='alert success'>Resource uploaded & notifications sent to students!</div>";
            } else {
                $message = "<div class='alert error'>Failed to upload resource!</div>";
            }
            $stmt->close();
        }
    }
}

// ৩. Note Delete Handling
if (isset($_GET['delete_note_id']) && ($role === 'cr' || $role === 'admin')) {
    $del_id = intval($_GET['delete_note_id']);
    
    $file_query = $conn->prepare("SELECT file_path FROM notes WHERE id = ?");
    $file_query->bind_param("i", $del_id);
    $file_query->execute();
    $res = $file_query->get_result();
    if ($f = $res->fetch_assoc()) {
        if (!empty($f['file_path']) && file_exists($f['file_path'])) {
            unlink($f['file_path']);
        }
    }
    $file_query->close();

    $del_stmt = $conn->prepare("DELETE FROM notes WHERE id = ?");
    $del_stmt->bind_param("i", $del_id);
    if ($del_stmt->execute()) {
        $message = "<div class='alert success'>Note deleted successfully!</div>";
    }
    $del_stmt->close();
}

// ৪. Note Request Handling
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_request'])) {
    $req_course  = trim($_POST['course_name']);
    $req_message = trim($_POST['message']);

    $stmt = $conn->prepare("INSERT INTO requests (user_id, batch, course_name, message) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $user_id, $batch, $req_course, $req_message);
    if ($stmt->execute()) {
        $message = "<div class='alert success'>Request submitted successfully!</div>";
    }
    $stmt->close();
}

// ৫. Request Status Update
if (isset($_GET['resolve_req_id']) && ($role === 'cr' || $role === 'admin')) {
    $req_id = intval($_GET['resolve_req_id']);
    $stmt = $conn->prepare("UPDATE requests SET status = 'resolved' WHERE id = ?");
    $stmt->bind_param("i", $req_id);
    $stmt->execute();
    $stmt->close();
    header("Location: dashboard.php");
    exit();
}

// ইউজারের জন্য আনরিড নোটিফিকেশন লোড করা
$notif_stmt = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC LIMIT 5");
$notif_stmt->bind_param("i", $user_id);
$notif_stmt->execute();
$notifications = $notif_stmt->get_result();

// Search & Filter
$search   = isset($_GET['search']) ? trim($_GET['search']) : '';
$semester = isset($_GET['semester']) ? trim($_GET['semester']) : '';

$sql = "SELECT notes.*, users.name as uploader_name FROM notes JOIN users ON notes.uploaded_by = users.id WHERE 1=1";
$params = [];
$types  = "";

if (!empty($search)) {
    $sql .= " AND (notes.title LIKE ? OR notes.course_name LIKE ?)";
    $search_param = "%" . $search . "%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}

if (!empty($semester)) {
    $sql .= " AND notes.semester = ?";
    $params[] = $semester;
    $types .= "s";
}

$sql .= " ORDER BY notes.created_at DESC";
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$notes_result = $stmt->get_result();

// Requests
$requests_result = $conn->query("SELECT requests.*, users.name as requester_name FROM requests JOIN users ON requests.user_id = users.id ORDER BY requests.created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - NoteHub</title>
    <style>
        * { box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background: #f0f2f5; margin: 0; padding: 20px; }
        .navbar { background: white; padding: 15px 30px; border-radius: 8px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .btn-logout { background: #dc3545; color: white; padding: 8px 15px; text-decoration: none; border-radius: 4px; font-weight: bold; }
        
        /* Personal Notification Box */
        .notif-box {
            background: #ffffff;
            border-left: 5px solid #1a73e8;
            padding: 15px 20px;
            border-radius: 8px;
            margin-top: 15px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.08);
        }
        .notif-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; }
        .notif-item { background: #f8f9fa; padding: 8px 12px; border-radius: 4px; font-size: 13px; margin-bottom: 6px; display: flex; justify-content: space-between; }
        .btn-clear-notif { font-size: 12px; color: #1a73e8; text-decoration: none; font-weight: 600; }

        .welcome-box { margin-top: 15px; background: white; padding: 20px 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .grid-container { display: grid; grid-template-columns: <?= ($role === 'cr' || $role === 'admin') ? '1fr 2fr' : '1fr'; ?>; gap: 20px; margin-top: 20px; }
        .card { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .form-group { margin-bottom: 12px; }
        label { display: block; margin-bottom: 5px; font-weight: 600; font-size: 14px; }
        input, select, textarea { width: 100%; padding: 8px 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px; }
        button { width: 100%; padding: 10px; background: #28a745; color: white; border: none; border-radius: 4px; font-size: 15px; cursor: pointer; font-weight: bold; }
        button:hover { background: #218838; }
        .btn-req { background: #ff9800; }
        .btn-req:hover { background: #e68a00; }
        
        .search-bar { display: flex; gap: 10px; margin-bottom: 20px; background: #f8f9fa; padding: 15px; border-radius: 6px; }
        .search-bar input { flex: 1; }
        .btn-search { width: auto; background: #1a73e8; padding: 8px 18px; }
        .btn-reset { background: #6c757d; color: white; padding: 8px 15px; text-decoration: none; border-radius: 4px; font-size: 14px; display: inline-flex; align-items: center; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table, th, td { border: 1px solid #dee2e6; }
        th, td { padding: 10px; text-align: left; font-size: 14px; }
        th { background: #f8f9fa; }
        .btn-link { background: #1a73e8; color: white; padding: 5px 10px; text-decoration: none; border-radius: 3px; font-size: 13px; display: inline-block; }
        .btn-delete { background: #dc3545; color: white; padding: 5px 10px; text-decoration: none; border-radius: 3px; font-size: 13px; display: inline-block; margin-left: 5px; }
        .btn-resolve { background: #28a745; color: white; padding: 4px 8px; text-decoration: none; border-radius: 3px; font-size: 12px; }
        
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; text-transform: uppercase; }
        .badge-pending { background: #fff3cd; color: #856404; }
        .badge-resolved { background: #d4edda; color: #155724; }
        .view-pill { background: #e8f0fe; color: #1a73e8; padding: 3px 8px; border-radius: 12px; font-size: 12px; font-weight: 600; display: inline-block; margin-top: 4px; }
        
        .alert { padding: 10px; border-radius: 4px; margin-top: 15px; font-size: 14px; }
        .alert.success { background: #d4edda; color: #155724; }
        .alert.error { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>

<div class="navbar">
    <h2>NoteHub Portal</h2>
    <a href="logout.php" class="btn-logout">Logout</a>
</div>

<!-- Personal Notifications for Logged-in User (Student) -->
<?php if ($notifications->num_rows > 0): ?>
    <div class="notif-box">
        <div class="notif-header">
            <strong>🔔 Unread Notifications (<?= $notifications->num_rows; ?>)</strong>
            <a href="dashboard.php?mark_read=1" class="btn-clear-notif">✓ Mark all as read</a>
        </div>
        <?php while ($notif = $notifications->fetch_assoc()): ?>
            <div class="notif-item">
                <span><?= htmlspecialchars($notif['message']); ?></span>
                <span style="color: #888; font-size: 11px;"><?= date("h:i A, d M", strtotime($notif['created_at'])); ?></span>
            </div>
        <?php endwhile; ?>
    </div>
<?php endif; ?>

<div class="welcome-box">
    <h3 style="margin: 0 0 8px 0;">Welcome, <?= htmlspecialchars($name); ?>! 👋</h3>
    <span style="margin-right: 15px;"><strong>Batch:</strong> <?= htmlspecialchars($batch); ?></span>
    <span><strong>Role:</strong> <?= strtoupper(htmlspecialchars($role)); ?></span>
</div>

<?= $message; ?>

<div class="grid-container">
    <div>
        <?php if ($role === 'cr' || $role === 'admin'): ?>
        <!-- CR / Admin Upload Form -->
        <div class="card">
            <h3>Upload New Note</h3>
            <form action="dashboard.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="upload_note" value="1">
                <div class="form-group">
                    <label>Note Title</label>
                    <input type="text" name="title" required placeholder="e.g. Chapter 3 - Algorithm">
                </div>
                <div class="form-group">
                    <label>Course Name</label>
                    <input type="text" name="course_name" required placeholder="e.g. Data Structures">
                </div>
                <div class="form-group">
                    <label>Semester</label>
                    <input type="text" name="semester" required placeholder="e.g. 5th">
                </div>
                <div class="form-group">
                    <label>Direct File Upload (PDF, Word, Images)</label>
                    <input type="file" name="note_file">
                </div>
                <div class="form-group">
                    <label>Or Google Drive Link</label>
                    <input type="url" name="drive_link" placeholder="https://drive.google.com/...">
                </div>
                <button type="submit">Upload & Notify Students</button>
            </form>
        </div>
        <?php endif; ?>

        <div class="card">
            <h3>Request Missing Notes</h3>
            <form action="dashboard.php" method="POST">
                <input type="hidden" name="submit_request" value="1">
                <div class="form-group">
                    <label>Course Name</label>
                    <input type="text" name="course_name" required placeholder="e.g. Discrete Math">
                </div>
                <div class="form-group">
                    <label>What note do you need?</label>
                    <textarea name="message" rows="3" required placeholder="e.g. Need lecture notes from week 4"></textarea>
                </div>
                <button type="submit" class="btn-req">Send Request</button>
            </form>
        </div>
    </div>

    <div>
        <div class="card">
            <h3>Available Resources</h3>
            <form method="GET" action="dashboard.php" class="search-bar">
                <input type="text" name="search" placeholder="Search by title or course..." value="<?= htmlspecialchars($search); ?>">
                <input type="text" name="semester" placeholder="Filter by semester (e.g. 5th)" value="<?= htmlspecialchars($semester); ?>">
                <button type="submit" class="btn-search">Search</button>
                <?php if (!empty($search) || !empty($semester)): ?>
                    <a href="dashboard.php" class="btn-reset">Reset</a>
                <?php endif; ?>
            </form>

            <?php if ($notes_result->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Title & Views</th>
                            <th>Course</th>
                            <th>Semester</th>
                            <th>Uploaded By</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($note = $notes_result->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($note['title']); ?></strong><br>
                                    <span class="view-pill">👁 <?= $note['view_count']; ?> downloads/views</span>
                                </td>
                                <td><?= htmlspecialchars($note['course_name']); ?></td>
                                <td><?= htmlspecialchars($note['semester']); ?></td>
                                <td><?= htmlspecialchars($note['uploader_name']); ?></td>
                                <td>
                                    <a href="view_note.php?id=<?= $note['id']; ?>" target="_blank" class="btn-link">View / Download</a>
                                    <?php if ($role === 'cr' || $role === 'admin'): ?>
                                        <a href="dashboard.php?delete_note_id=<?= $note['id']; ?>" class="btn-delete" onclick="return confirm('Are you sure you want to delete this note?');">Delete</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="color: #666; text-align: center; padding: 15px;">No notes found matching your criteria.</p>
            <?php endif; ?>
        </div>

        <div class="card">
            <h3>Student Requests & Feedback</h3>
            <?php if ($requests_result->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Batch</th>
                            <th>Course</th>
                            <th>Message</th>
                            <th>Status</th>
                            <?php if ($role === 'cr' || $role === 'admin'): ?>
                                <th>Action</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($req = $requests_result->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($req['requester_name']); ?></strong></td>
                                <td><?= htmlspecialchars($req['batch']); ?></td>
                                <td><?= htmlspecialchars($req['course_name']); ?></td>
                                <td><?= htmlspecialchars($req['message']); ?></td>
                                <td>
                                    <span class="badge badge-<?= htmlspecialchars($req['status']); ?>">
                                        <?= htmlspecialchars($req['status']); ?>
                                    </span>
                                </td>
                                <?php if ($role === 'cr' || $role === 'admin'): ?>
                                    <td>
                                        <?php if ($req['status'] === 'pending'): ?>
                                            <a href="dashboard.php?resolve_req_id=<?= $req['id']; ?>" class="btn-resolve">Mark Resolved</a>
                                        <?php else: ?>
                                            <span style="color: green; font-size: 13px;">✓ Done</span>
                                        <?php endif; ?>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="color: #666; text-align: center; padding: 15px;">No requests found.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

</body>
</html>