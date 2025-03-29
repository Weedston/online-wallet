<?php
function add_notification($user_id, $message) {
    global $CONNECT; // Declare global variable

    if (!$CONNECT) {
        error_log("Error: Database connection is missing.");
        return false;
    }

    $stmt = $CONNECT->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
    
    if (!$stmt) {
        error_log("Error preparing statement: " . $CONNECT->error);
        return false;
    }

    $stmt->bind_param("is", $user_id, $message);
    
    if (!$stmt->execute()) {
        error_log("Error executing statement: " . $stmt->error);
        $stmt->close();
        return false;
    }

    $stmt->close();
    return true; // Successful execution
}
?>