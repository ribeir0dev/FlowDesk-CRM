<?php
// db.php — conexão segura
$db_host = "localhost";
$db_name = "u710601266_flowdesk";
$db_user = "u710601266_contatoreinald";
$db_pass = "@001TIscml$";
$dsn = "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4";

//try {
//    $pdo = new PDO($dsn, $db_user, $db_pass, [
//        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
//        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
//    ]);
//} catch (PDOException $e) {
    // exit('Erro de conexão com o banco de dados');
//    die('Erro de conexão: ' . $e->getMessage());
//}

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die('Erro de conexão: ' . $e->getMessage());
}
?>
