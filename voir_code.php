<?php
session_start();
echo "<h1>Code de vérification (MODE TEST)</h1>";
if (isset($_SESSION['test_code'])) {
    echo "<h2 style='color: green;'>Code : " . $_SESSION['test_code'] . "</h2>";
} else {
    echo "<p>Aucun code disponible. Connectez-vous d'abord.</p>";
}
?>