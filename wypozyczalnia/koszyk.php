<?php
require_once 'db_config.php';
require_once 'functions.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
$current_user_id = $_SESSION['user_id'] ?? 1;

$message = ""; 
$error_message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'usun' && isset($_POST['konto_id'])) {
        $message = usunZKoszka($conn, $current_user_id, (int)$_POST['konto_id']);
    }
    
    // STRAŻNIK CZASU (Błąd 02)
    if ($_POST['action'] === 'platnosc') {
        $wygasle = weryfikujRezerwacjeKoszyka($conn, $current_user_id);
        if (empty($wygasle)) {
            echo "<script>alert('Przekierowanie do płatności...');</script>";
            // header("Location: finalizacja.php"); exit;
        } else {
            $error_message = "Rezerwacja wygasła dla: " . implode(", ", $wygasle) . ". Odśwież koszyk.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Koszyk - Wypożyczalnia</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header><h1><a href="index.php">Wypożyczalnia Kont</a></h1></header>
    <main class="container">
        <h2>Twój Koszyk (ID: <?php echo $current_user_id; ?>)</h2>
        
        <?php if ($message): ?><div style="background:green;color:white;padding:10px;"><?php echo $message; ?></div><?php endif; ?>
        <?php if ($error_message): ?><div style="background:red;color:white;padding:10px;"><?php echo $error_message; ?></div><?php endif; ?>

        <?php
        $sql = "
            SELECT k.id AS konto_id, k.tytul_gry, k.cena_podstawowa, p.wartosc_znizki, p.aktywna, p.data_zakonczenia
            FROM elementy_koszyka ek
            JOIN konta_gier k ON ek.konto_gier_id = k.id
            JOIN koszyki kosz ON ek.koszyk_id = kosz.id
            LEFT JOIN promocje p ON k.promocja_id = p.id
            WHERE kosz.uzytkownik_id = $current_user_id
        ";
        $result = $conn->query($sql);
        $total = 0;

        if ($result && $result->num_rows > 0) {
            echo '<table><tr><th>Tytuł</th><th>Cena</th><th>Akcja</th></tr>';
            while($row = $result->fetch_assoc()) {
                $cena_finalna = obliczCeneKoncowa($row['cena_podstawowa'], $row['wartosc_znizki'], $row['aktywna'], $row['data_zakonczenia']);
                $total += $cena_finalna;
                echo '<tr>';
                echo '<td>' . htmlspecialchars($row['tytul_gry']) . '</td>';
                echo '<td>' . number_format($cena_finalna, 2) . ' zł</td>';
                echo '<td><form method="POST"><input type="hidden" name="konto_id" value="'.$row['konto_id'].'"><button type="submit" name="action" value="usun" class="btn-danger">Usuń</button></form></td>';
                echo '</tr>';
            }
            echo '<tr><td colspan="2" style="text-align:right"><b>SUMA:</b></td><td><b>' . number_format($total, 2) . ' zł</b></td></tr>';
            echo '<tr><td colspan="3" style="text-align:right">';
            echo '<form method="POST"><button type="submit" name="action" value="platnosc" class="btn-success">PRZEJDŹ DO PŁATNOŚCI</button></form>';
            echo '</td></tr></table>';
        } else {
            echo "<p>Koszyk jest pusty.</p>";
        }
        $conn->close();
        ?>
        <a href="index.php" style="margin-top:20px;display:inline-block;"><-- Powrót</a>
    </main>
</body>
</html>
