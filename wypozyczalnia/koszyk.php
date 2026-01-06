<?php
require_once 'db_config.php';
require_once 'functions.php';

// Rozpocznij sesję jeśli nie została rozpoczęta
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$current_user_id = $_SESSION['user_id'] ?? 1; // Domyślnie 1 dla testów

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
    <title>Twój Koszyk</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <h1><a href="index.php" style="text-decoration: none; color: var(--primary-color);">Wypożyczalnia Kont z Grami</a></h1>
    </header>

    <main class="container">
        <h2>Zawartość Twojego Koszyka (ID: <?php echo $current_user_id; ?>)</h2>
        
        <?php if (!empty($message)): ?>
            <div style='padding: 15px; margin-bottom: 20px; background-color: #008000; color: white; border-radius: 5px; text-align: center;'>
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <?php
        // KLUCZOWA ZMIANA: Pobieramy dane o promocjach przypisanych do kont w koszyku
        $sql = "
            SELECT 
                k.id AS konto_id,
                k.tytul_gry, 
                k.cena_podstawowa,
                p.wartosc_znizki,
                p.aktywna,
                p.data_zakonczenia
            FROM elementy_koszyka ek
            JOIN konta_gier k ON ek.konto_gier_id = k.id
            JOIN koszyki kosz ON ek.koszyk_id = kosz.id
            LEFT JOIN promocje p ON k.promocja_id = p.id
            WHERE kosz.uzytkownik_id = $current_user_id
        ";
        
        $result = $conn->query($sql);
        $total = 0;

        if ($result && $result->num_rows > 0) {
            echo '<table>';
            echo '<tr><th>Nazwa Konta</th><th>Cena</th><th>Akcja</th></tr>';
            
            while($row = $result->fetch_assoc()) {
                // Obliczamy cenę uwzględniając promocję
                $cena_do_zaplaty = obliczCeneKoncowa(
                    $row['cena_podstawowa'], 
                    $row['wartosc_znizki'], 
                    $row['aktywna'], 
                    $row['data_zakonczenia']
                );

                $total += $cena_do_zaplaty;

                echo '<tr>';
                echo '<td>' . htmlspecialchars($row['tytul_gry']) . '</td>';
                echo '<td>';
                // Jeśli jest promocja, pokaż przekreśloną starą cenę
                if ($cena_do_zaplaty < $row['cena_podstawowa']) {
                    echo '<span style="text-decoration: line-through; color: #888; font-size: 0.9em;">' . number_format($row['cena_podstawowa'], 2) . ' zł</span> ';
                }
                echo '<strong>' . number_format($cena_do_zaplaty, 2) . ' zł</strong>';
                echo '</td>';
                
                echo '<td>';
                echo '<form method="POST" action="koszyk.php" style="margin: 0;">';
                echo '<input type="hidden" name="konto_id" value="' . $row['konto_id'] . '">'; 
                echo '<button type="submit" name="action" value="usun" class="btn-danger">Usuń</button>';
                echo '</form>';
                echo '</td>';
                echo '</tr>';
            }
            
            echo '<tr><td colspan="2" style="text-align: right; font-weight: bold;">SUMA:</td><td style="font-size: 1.2em; color: var(--primary-color); font-weight: bold;">' . number_format($total, 2) . ' zł</td></tr>';
            echo '<tr><td colspan="3" style="text-align: right; border: none;">';
            echo '<button class="btn-success" style="border-radius: 5px; padding: 10px 20px; cursor: pointer;">PRZEJDŹ DO PŁATNOŚCI</button>';
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
