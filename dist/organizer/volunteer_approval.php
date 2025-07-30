<?php
session_start();
// Include the config file
require_once '../volunteer/config.php';

// Check if the user is logged in as an organizer
if (!isset($_SESSION['organizer_id'])) {
    header("Location: log_in_organizer.php");
    exit();
}


// Fetch all pending registrations for the event
$sql = "SELECT ev.volunteer_id, v.volunteer_name FROM event_volunteers ev 
        JOIN volunteer v ON ev.volunteer_id = v.volunteer_id 
        WHERE ev.event_id = ? AND ev.status = 'pending'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $event_id);
$stmt->execute();
$result = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Approve Volunteers</title>
</head>
<body>
    <h1>Pending Registrations for Event</h1>
    <table>
        <tr>
            <th>Volunteer Name</th>
            <th>Action</th>
        </tr>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['volunteer_name']); ?></td>
                <td>
                    <a href="approve_registration.php?volunteer_id=<?php echo $row['volunteer_id']; ?>&event_id=<?php echo $event_id; ?>&action=approve">Approve</a> |
                    <a href="approve_registration.php?volunteer_id=<?php echo $row['volunteer_id']; ?>&event_id=<?php echo $event_id; ?>&action=reject">Reject</a>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>
</body>
</html>

<?php
$conn->close();
?>
