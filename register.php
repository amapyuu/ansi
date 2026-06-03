<?php
// Redirect to detail/register.php to unify registration
$program = $_GET['program'] ?? '';
header("Location: detail/register.php" . ($program ? "?program=" . urlencode($program) : ""));
exit;
?>

