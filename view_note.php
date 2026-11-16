<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (isset($_GET['id'])) {
    $note_id = intval($_GET['id']);

    // View / Download count বাড়ানো
    $stmt = $conn->prepare("UPDATE notes SET view_count = view_count + 1 WHERE id = ?");
    $stmt->bind_param("i", $note_id);
    $stmt->execute();
    $stmt->close();

    // নোটের লিংক বা ফাইল পাথ বের করা
    $stmt = $conn->prepare("SELECT drive_link, file_path FROM notes WHERE id = ?");
    $stmt->bind_param("i", $note_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if (!empty($row['file_path']) && file_exists($row['file_path'])) {
            header("Location: " . $row['file_path']);
            exit();
        } elseif (!empty($row['drive_link'])) {
            header("Location: " . $row['drive_link']);
            exit();
        }
    }
}

header("Location: dashboard.php");
exit();
?>