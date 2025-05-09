<?php
session_start();
session_unset();
session_destroy();
header("Location: /MedStudy-Space-System/index.php");
exit();
?>