<?php
echo "<h2>Version PHP utilisée par MAMP :</h2>";
echo "<strong>" . phpversion() . "</strong><br><br>";

echo "<h3>Extensions importantes :</h3>";
echo "PDO : " . (extension_loaded('pdo') ? '✅ Activé' : '❌ Désactivé') . "<br>";
echo "JSON : " . (extension_loaded('json') ? '✅ Activé' : '❌ Désactivé') . "<br>";
echo "MySQLi : " . (extension_loaded('mysqli') ? '✅ Activé' : '❌ Désactivé') . "<br>";

echo "<h3>Test de base :</h3>";
$test = ['message' => 'Test JSON OK'];
echo "JSON encode : " . json_encode($test) . "<br>";

echo "<h3>Sessions :</h3>";
session_start();
echo "Session ID : " . session_id() . "<br>";
?>