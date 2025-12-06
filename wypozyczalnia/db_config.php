<?php
// Włączenie sesji PHP (Niezbędne do śledzenia testowego ID użytkownika)
session_start();

// ==========================================================
// USTAWIENIA POŁĄCZENIA Z BAZĄ DANYCH
// ==========================================================
$servername = "localhost";
$username = "root";       
$password = "";           
$dbname = "wypozyczalnia"; 

// Tworzenie połączenia
$conn = new mysqli($servername, $username, $password, $dbname);

// Obsługa błędu połączenia
if ($conn->connect_error) {
    // W trybie produkcyjnym należy ukryć szczegóły błędu
    die("Błąd połączenia z bazą danych: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

// ==========================================================
// LOGIKA ZARZĄDZANIA AKTUALNYM ID UŻYTKOWNIKA (TRYB TESTOWY)
// ==========================================================

// Ustawienie domyślnego ID, jeśli sesja nie jest aktywna
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; 
}

// Obsługa przełączania ID użytkownika (z panelu testowego)
if (isset($_GET['zmien_user_id'])) {
    $new_id = (int)$_GET['zmien_user_id'];
    if ($new_id > 0) {
        $_SESSION['user_id'] = $new_id;
        // Przekierowanie (zapobiega ponownemu wysyłaniu GET po odświeżeniu)
        header("Location: index.php"); 
        exit();
    }
}

// Globalna zmienna ID używana w funkcjach
$current_user_id = $_SESSION['user_id'];
?>