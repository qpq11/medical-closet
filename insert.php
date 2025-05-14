<?php
session_start(); // Включаем работу с сессиями

$servername = "localhost";
$username = "ESP32";
$password = "1";
$dbname = "my_database";
$correct_password = 1234;

// Если пользователь заблокирован, перенаправляем на страницу блокировки
if (isset($_SESSION['blocked_until'])) {
    if (time() < $_SESSION['blocked_until']) {
        header("Location: blocked.html");
        exit();
    } else {
        // Разблокируем, если время вышло
        unset($_SESSION['blocked_until']);
        unset($_SESSION['failed_attempts']);
    }
}

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$password = (int) $_POST['password'];
$login = $_POST['login'];
$medicine = $_POST['medicine'];
$date = $_POST['date'];
$cell = $_POST['cell'];

if ($password === $correct_password) {
    // Если пароль верный, сбрасываем счетчик ошибок
    unset($_SESSION['failed_attempts']);
    $conn->query("DELETE FROM block_status WHERE id = 1");
    $sql = "INSERT INTO project_base (login, medicine, date, cell) VALUES ('$login', '$medicine', '$date', '$cell')";

    if ($conn->query($sql) === TRUE) {
        header("Location: index.html?success=1");
    } else {
        header("Location: index.html?error=db_error");
    }
} else {
    // Увеличиваем счетчик неверных попыток
    $_SESSION['failed_attempts'] = ($_SESSION['failed_attempts'] ?? 0) + 1;

    // Если 3 или более ошибок, блокируем на 1 минуту
    if ($_SESSION['failed_attempts'] >= 3) {
        $block_until = time() + 60;
        $_SESSION['blocked_until'] = $block_until;
        $conn->query("REPLACE INTO block_status (id, blocked_until) VALUES (1, $block_until)");
        header("Location: blocked.html");
    } else {
        header("Location: index.html?error=wrong_password&attempts=" . $_SESSION['failed_attempts']);
    }
}

$conn->close();
exit();
?>

