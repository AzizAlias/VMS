<?php
session_start();
session_unset();
session_destroy();

// Redirect to the login page after logging out
header("Location: log_in_organizer.php?logout=success");
exit();
?>
