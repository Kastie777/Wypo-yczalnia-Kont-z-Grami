<?php

/**
 * FUNKCJA POMOCNICZA: obliczCeneKoncowa
 * Oblicza cenę po zniżce, jeśli promocja jest aktywna i czasowo poprawna.
 */
function obliczCeneKoncowa($cena_podstawowa, $wartosc_znizki, $czy_aktywna, $data_zakonczenia) {
    if ($wartosc_znizki && $czy_aktywna == 1 && strtotime($data_zakonczenia) > time()) {
        return $cena_podstawowa * (1 - $wartosc_znizki);
    }
    return $cena_podstawowa;
}

/**
 * FUNKCJA: weryfikujRezerwacjeKoszyka
 * Sprawdza przed płatnością, czy rezerwacje w koszyku nie wygasły.
 */
function weryfikujRezerwacjeKoszyka($conn, $uzytkownik_id) {
    $wygasle = [];
    $sql = "
        SELECT k.tytul_gry, ek.konto_gier_id
        FROM elementy_koszyka ek
        JOIN koszyki kosz ON ek.koszyk_id = kosz.id
        JOIN konta_gier k ON ek.konto_gier_id = k.id
        LEFT JOIN rezerwacje r ON ek.konto_gier_id = r.konto_gier_id 
            AND r.uzytkownik_id = $uzytkownik_id 
            AND r.status = 'aktywna' 
            AND r.czas_wygasniecia > NOW()
        WHERE kosz.uzytkownik_id = $uzytkownik_id
    ";
    
    $result = $conn->query($sql);
    while ($row = $result->fetch_assoc()) {
        // Jeśli rezerwacja nie istnieje lub wygasła (r.id byłoby NULL w LEFT JOIN)
        $check = $conn->query("SELECT id FROM rezerwacje WHERE konto_gier_id = {$row['konto_gier_id']} AND uzytkownik_id = $uzytkownik_id AND status = 'aktywna' AND czas_wygasniecia > NOW()");
        if ($check->num_rows == 0) {
            $wygasle[] = $row['tytul_gry'];
        }
    }
    return $wygasle;
}

/**
 * FUNKCJA 1: dodajDoKoszyka
 */
function dodajDoKoszyka($conn, $uzytkownik_id, $konto_id) {
    $koszyk_id = null;
    $cart_query = $conn->query("SELECT id FROM koszyki WHERE uzytkownik_id = $uzytkownik_id");
    
    if ($cart_query->num_rows == 0) {
        $conn->query("INSERT INTO koszyki (uzytkownik_id) VALUES ($uzytkownik_id)");
        $koszyk_id = $conn->insert_id;
    } else {
        $koszyk_id = $cart_query->fetch_assoc()['id'];
    }

    if (!$koszyk_id) return "Błąd krytyczny: Nie udało się ustalić ID koszyka.";

    $reserved_query = $conn->query("SELECT uzytkownik_id FROM rezerwacje WHERE konto_gier_id = $konto_id AND status = 'aktywna' AND czas_wygasniecia > NOW()");
    if ($reserved_query->num_rows > 0) {
        if ($reserved_query->fetch_assoc()['uzytkownik_id'] != $uzytkownik_id) {
            return "Błąd: Konto zarezerwowane przez kogoś innego.";
        }
    }

    $item_query = $conn->query("SELECT id FROM elementy_koszyka WHERE koszyk_id = $koszyk_id AND konto_gier_id = $konto_id");
    if ($item_query->num_rows > 0) return "To konto jest już w koszyku.";

    $insert_item = $conn->query("INSERT INTO elementy_koszyka (koszyk_id, konto_gier_id) VALUES ($koszyk_id, $konto_id)");
    return $insert_item ? "Pomyślnie dodano do koszyka!" : "Błąd podczas dodawania.";
}

/**
 * FUNKCJA 2: zarezerwujKonto
 */
function zarezerwujKonto($conn, $uzytkownik_id, $konto_id) {
    $active_res = $conn->query("SELECT id FROM rezerwacje WHERE uzytkownik_id = $uzytkownik_id AND status = 'aktywna' AND czas_wygasniecia > NOW()");
    if ($active_res->num_rows > 0) return "Masz już aktywną rezerwację.";

    $reserved = $conn->query("SELECT id FROM rezerwacje WHERE konto_gier_id = $konto_id AND status = 'aktywna' AND czas_wygasniecia > NOW()");
    if ($reserved->num_rows > 0) return "To konto jest już niedostępne.";

    $expiration = date('Y-m-d H:i:s', strtotime('+15 minutes'));
    $insert = $conn->query("INSERT INTO rezerwacje (uzytkownik_id, konto_gier_id, czas_wygasniecia, status) VALUES ($uzytkownik_id, $konto_id, '$expiration', 'aktywna')");

    if ($insert) {
        $conn->query("DELETE ek FROM elementy_koszyka ek JOIN koszyki k ON ek.koszyk_id = k.id WHERE k.uzytkownik_id != $uzytkownik_id AND ek.konto_gier_id = $konto_id");
        return "Zarezerwowano na 15 minut! " . dodajDoKoszyka($conn, $uzytkownik_id, $konto_id);
    }
    return "Błąd rezerwacji.";
}

/**
 * FUNKCJA 4: pobierzKontaZGryPromocjami
 */
function pobierzKontaZGryPromocjami($conn) {
    $sql = "
        SELECT k.id, k.tytul_gry, k.cena_podstawowa, p.wartosc_znizki, p.data_zakonczenia, p.aktywna, r.status AS rezerwacja_status, r.czas_wygasniecia AS rezerwacja_do
        FROM konta_gier k
        LEFT JOIN promocje p ON k.promocja_id = p.id
        LEFT JOIN rezerwacje r ON k.id = r.konto_gier_id AND r.status = 'aktywna' AND r.czas_wygasniecia > NOW()
        WHERE k.dostepne = TRUE 
        AND (p.id IS NULL OR (p.aktywna = TRUE AND p.data_zakonczenia > NOW())) 
    ";
    
    $result = $conn->query($sql);
    $konta = [];
    while($row = $result->fetch_assoc()) {
        $cena_f = obliczCeneKoncowa($row['cena_podstawowa'], $row['wartosc_znizki'], $row['aktywna'], $row['data_zakonczenia']);
        $konta[] = [
            'id' => $row['id'],
            'tytul' => $row['tytul_gry'],
            'cena_podstawowa' => number_format($row['cena_podstawowa'], 2),
            'cena_promocyjna' => number_format($cena_f, 2),
            'znizka_opis' => ($cena_f < $row['cena_podstawowa']) ? " (Promocja: -".($row['wartosc_znizki']*100)."%!)" : "",
            'rezerwacja_status' => $row['rezerwacja_status'],
            'rezerwacja_do' => $row['rezerwacja_do']
        ];
    }
    return $konta;
}

function usunZKoszka($conn, $uzytkownik_id, $konto_id) {
    $koszyk_q = $conn->query("SELECT id FROM koszyki WHERE uzytkownik_id = $uzytkownik_id");
    if ($koszyk_q->num_rows == 0) return "Błąd: Brak koszyka.";
    $koszyk_id = $koszyk_q->fetch_assoc()['id'];
    $conn->query("DELETE FROM elementy_koszyka WHERE koszyk_id = $koszyk_id AND konto_gier_id = $konto_id LIMIT 1");
    return "Usunięto konto z koszyka.";
}
?>
