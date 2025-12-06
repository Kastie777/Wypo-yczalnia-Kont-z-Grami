<?php
// ==========================================================
// KONFIGURACJA ŚRODOWISKA
// ==========================================================
require_once 'db_config.php';
require_once 'functions.php';

// Zmienna $current_user_id jest ustawiana przez session_start() w db_config.php

// ==========================================================
// OBSŁUGA AKCJI FORMULARZY (POST)
// ==========================================================
$message = ""; 

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['konto_id'])) {
    $action = $_POST['action'];
    $konto_id = (int)$_POST['konto_id']; 

    if ($action === 'rezerwuj') {
        $message = zarezerwujKonto($conn, $current_user_id, $konto_id);
    } elseif ($action === 'dodaj_koszyk') {
        $message = dodajDoKoszyka($conn, $current_user_id, $konto_id);
    } elseif ($action === 'anuluj') {
        $message = anulujRezerwacje($conn, $current_user_id, $konto_id);
    }
}

// ==========================================================
// POBIERANIE DANYCH KONT DLA WIDOKU (z promocjami i rezerwacjami)
// ==========================================================
$lista_kont = pobierzKontaZGryPromocjami($conn);

?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Wypożyczalnia Kont z Grami</title>
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
            <a href="index.php">Wyszukiwanie (Filtrowanie)</a>
            <a href="koszyk.php">Koszyk</a>
            <a href="#">Logowanie</a>
            <a href="#">Zmień Motyw</a> </nav>
    </header>

    <main class="container">
        
        <div class="panel-testowy">
            <p style="font-weight: bold; margin-top: 0;">Tryb testowy: Zmień użytkownika</p>
            <p>
                Aktualny ID Użytkownika: 
                <span class="user-id"><?php echo $current_user_id; ?></span>
            </p>
            
            <a href="?zmien_user_id=1" style="background-color: #5bc0de;" class="panel-testowy a">
                Przełącz na Użytkownika 1
            </a>
            
            <a href="?zmien_user_id=2" style="background-color: #f0ad4e;" class="panel-testowy a">
                Przełącz na Użytkownika 2
            </a>
        </div>
        
        <?php if (!empty($message)): ?>
            <div class="msg-alert">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <section class="account-list">
            <?php if (!empty($lista_kont)): ?>
                <?php foreach ($lista_kont as $konto): ?>
                    
                    <div class="account-card">
                        <div class="image-placeholder">
                            </div>
                        <div class="card-content">
                            <p class="category">Platforma: Steam</p>
                            
                            <h2><?php echo htmlspecialchars($konto['tytul']); ?></h2>
                            
                            <?php 
                            $reserved_by_current_user = false;
                            $reserved_check = $conn->query("
                                SELECT uzytkownik_id, czas_wygasniecia FROM rezerwacje 
                                WHERE konto_gier_id = {$konto['id']} AND status = 'aktywna' AND czas_wygasniecia > NOW()
                            ");

                            if ($reserved_check->num_rows > 0) {
                                $res_data = $reserved_check->fetch_assoc();
                                if ($res_data['uzytkownik_id'] == $current_user_id) {
                                    $reserved_by_current_user = true;
                                    $konto['rezerwacja_do'] = $res_data['czas_wygasniecia']; 
                                }
                            }
                            ?>

                            <?php if ($reserved_by_current_user): ?>
                                <div class="reservation-active" style="background-color: #333;">
                                    TWOJA REZERWACJA (do <?php echo date('H:i:s', strtotime($konto['rezerwacja_do'])); ?>)
                                </div>

                                <form method="POST" action="index.php" style="display: inline;">
                                    <input type="hidden" name="konto_id" value="<?php echo $konto['id']; ?>">
                                    <button type="submit" name="action" value="anuluj" class="btn-danger" style="margin-right: 5px;">
                                        Anuluj Rezerwację
                                    </button>
                                </form>

                                <a href="koszyk.php" class="btn-success">
                                    Przejdź do Koszyka
                                </a>

                            <?php elseif ($konto['rezerwacja_status'] == 'aktywna'): ?>
                                <div class="reservation-active">
                                    ZAREZERWOWANE (do <?php echo date('H:i:s', strtotime($konto['rezerwacja_do'])); ?>)
                                </div>
                                <button disabled class="btn-disabled">
                                    Niedostępne
                                </button>
                                
                            <?php else: ?>
                                <?php if ($konto['znizka_opis']): ?>
                                    <p class="price-line-through">Cena: <?php echo $konto['cena_podstawowa']; ?> zł</p>
                                    <p style='color: var(--primary-color); font-weight: bold; margin-bottom: 10px;'>
                                        PROMOCJA: <?php echo $konto['cena_promocyjna']; ?> zł 
                                    </p>
                                <?php else: ?>
                                    <p style="margin-bottom: 10px;">Cena: <?php echo $konto['cena_podstawowa']; ?> zł</p>
                                <?php endif; ?>
                                
                                <form method="POST" action="index.php">
                                    <input type="hidden" name="konto_id" value="<?php echo $konto['id']; ?>">
                                    
                                    <button type="submit" name="action" value="rezerwuj" class="btn-secondary" style="margin-right: 5px;">
                                        Zarezerwuj (15 min)
                                    </button>
                                    
                                    <button type="submit" name="action" value="dodaj_koszyk" class="btn-primary">
                                        Dodaj do Koszyka
                                    </button>
                                </form>
                            <?php endif; ?>
                            
                            <a href="#" class="read-more" style="margin-top: 10px;">Read more</a>
                        </div>
                    </div>

                <?php endforeach; ?>
            <?php else: ?>
                <p>Brak dostępnych kont do wyświetlenia. Dodaj rekordy do bazy danych.</p>
            <?php endif; ?>
        </section>
        
    </main>

    <footer>
        <p>&copy; 2025 Wypożyczalnia Kont z Grami</p>
    </footer>
</body>
</html>