<?php
// ==========================================================
// KONFIGURACJA I DOŁĄCZENIE PLIKÓW
// ==========================================================
require_once 'db_config.php';
require_once 'functions.php';

// Ustawienie ID bieżącego użytkownika z sesji
$current_user_id = $_SESSION['user_id'];

// ==========================================================
// OBSŁUGA AKCJI USUWANIA Z KOSZYKA (POST)
// ==========================================================
$message = ""; 

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'usun' && isset($_POST['konto_id'])) {
    $konto_do_usuniecia_id = (int)$_POST['konto_id'];
    $message = usunZKoszka($conn, $current_user_id, $konto_do_usuniecia_id);
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Twój Koszyk - Wypożyczalnia Kont</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <h1>
            <a href="index.php" style="text-decoration: none; color: var(--primary-color);"> 
                Wypożyczalnia Kont z Grami
            </a>
        </h1>
        <nav>
            <a href="#">Logowanie</a>
            <a href="#">Zmień Motyw</a>
        </nav>
    </header>

    <main class="container">
        <h2>Zawartość Twojego Koszyka (ID Użytkownika: <?php echo $current_user_id; ?>)</h2>
        
        <?php if (!empty($message)): ?>
            <div style='padding: 15px; margin-bottom: 20px; background-color: #008000; color: white; border-radius: 5px; text-align: center;'>
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <?php
        // ==========================================================
        // POBIERANIE I WYŚWIETLANIE ZAWATOŚCI KOSZYKA
        // ==========================================================
        $sql = "
            SELECT 
                k.id AS konto_id,
                k.tytul_gry, 
                k.cena_podstawowa
            FROM elementy_koszyka ek
            JOIN konta_gier k ON ek.konto_gier_id = k.id
            JOIN koszyki kosz ON ek.koszyk_id = kosz.id
            WHERE kosz.uzytkownik_id = $current_user_id
        ";
        $result = $conn->query($sql);
        $total = 0;

        if ($result->num_rows > 0) {
            // TABLE TAG: Został przeniesiony do zewnętrznego CSS
            echo '<table>';
            echo '<tr><th>Nazwa Konta</th><th>Cena</th><th>Akcja</th></tr>';
            while($row = $result->fetch_assoc()) {
                $total += $row['cena_podstawowa'];
                echo '<tr>';
                echo '<td>' . htmlspecialchars($row['tytul_gry']) . '</td>';
                echo '<td>' . number_format($row['cena_podstawowa'], 2) . ' zł</td>';
                
                // Formularz usuwania dla każdego elementu
                echo '<td>';
                echo '<form method="POST" action="koszyk.php" style="margin: 0;">';
                echo '<input type="hidden" name="konto_id" value="' . $row['konto_id'] . '">'; 
                // Użyto klasy btn-danger z style.css
                echo '<button type="submit" name="action" value="usun" class="btn-danger">Usuń</button>';
                echo '</form>';
                echo '</td>';

                echo '</tr>';
            }
            // Podsumowanie: SUMA
            echo '<tr><td colspan="2" style="text-align: right; font-weight: bold;">SUMA:</td><td>' . number_format($total, 2) . ' zł</td></tr>';
            
            // Przycisk finalizacji zakupu 
            echo '<tr><td colspan="3" style="text-align: right; border: none;">';
            // Użyto klasy btn-success z style.css
            echo '<button class="btn-success" style="border-radius: 5px; padding: 10px 20px;">PRZEJDŹ DO PŁATNOŚCI</button>';
            echo '</td></tr>';
            
            echo '</table>';
        } else {
            echo "<p>Twój koszyk jest pusty.</p>";
        }

        $conn->close();
        ?>
        
        <a href="index.php" style="display: block; margin-top: 30px; font-weight: bold; color: var(--primary-color);">
            <-- Kontynuuj zakupy
        </a>
    </main>

    <footer>
        <p>&copy; 2025 Wypożyczalnia Kont z Grami</p>
    </footer>
</body>
</html>