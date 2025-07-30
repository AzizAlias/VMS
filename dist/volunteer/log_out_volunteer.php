<?php
session_start();
session_unset();
session_destroy();

// Redirect to the login page after logging out
header("Location: log_in_volunteer.php?logout=success");


exit();
?>
   <script>
        // Check if the logout parameter is in the URL
        window.onload = function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('logout') && urlParams.get('logout') === 'success') {
                alert("You have successfully logged out.");
            }
        }
    </script>
