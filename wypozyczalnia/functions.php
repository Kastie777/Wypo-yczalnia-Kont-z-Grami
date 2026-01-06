<?php

/**
 * FUNKCJA POMOCNICZA: obliczCeneKoncowa
 * Oblicza cenę po zniżce, jeśli promocja jest aktywna.
 */
function obliczCeneKoncowa($cena_podstawowa, $wartosc_znizki, $czy_aktywna, $data_zakonczenia) {
    if ($wartosc_znizki && $czy_aktywna && strtotime($data_zakonczenia) > time()) {
        return $cena_podstawowa * (1 - $wartosc_znizki);
    }
    return $cena_podstawowa;
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

    if (!$koszyk_id) {
        return "Błąd krytyczny: Nie udało się ustalić ID koszyka.";
    }

    $reserved_query = $conn->query("
        SELECT uzytkownik_id FROM rezerwacje 
        WHERE konto_gier_id = $konto_id AND status = 'aktywna' AND czas_wygasniecia > NOW()
    ");
    
    if ($reserved_query->num_rows > 0) {
        $reservation = $reserved_query->fetch_assoc();
        if ($reservation['uzytkownik_id'] != $uzytkownik_id) {
            return "Błąd: To konto jest obecnie zarezerwowane przez innego użytkownika.";
        }
    }

    $item_query = $conn->query("SELECT id FROM elementy_koszyka WHERE koszyk_id = $koszyk_id AND konto_gier_id = $konto_id");

    if ($item_query->num_rows > 0) {
        return "To konto jest już w koszyku."; 
    }

    $insert_item = $conn->query("INSERT INTO elementy_koszyka (koszyk_id, konto_gier_id) VALUES ($koszyk_id, $konto_id)");

    if ($insert_item) {
        return "Pomyślnie dodano konto do koszyka!";
    } else {
        return "Błąd podczas dodawania do koszyka.";
    }
}

/**
 * FUNKCJA 2: zarezerwujKonto
 */
function zarezerwujKonto($conn, $uzytkownik_id, $konto_id) {
    $active_reservation = $conn->query("
        SELECT id FROM rezerwacje 
        WHERE uzytkownik_id = $uzytkownik_id AND status = 'aktywna'
    ");
    if ($active_reservation->num_rows > 0) {
        return "Masz już aktywną rezerwację, dokończ transakcję.";
    }

    $reserved_query = $conn->query("
        SELECT id FROM rezerwacje 
        WHERE konto_gier_id = $konto_id AND status = 'aktywna' AND czas_wygasniecia > NOW()
    ");
    if ($reserved_query->num_rows > 0) {
        return "To konto jest już niedostępne (zarezerwowane)."; 
    }
    
    $expiration_time = date('Y-m-d H:i:s', strtotime('+15 minutes'));

    $insert_res = $conn->query("
        INSERT INTO rezerwacje (uzytkownik_id, konto_gier_id, czas_wygasniecia, status) 
        VALUES ($uzytkownik_id, $konto_id, '$expiration_time', 'aktywna')
    ");

    if ($insert_res) {
        $conn->query("
            DELETE ek 
            FROM elementy_koszyka ek
            JOIN koszyki k ON ek.koszyk_id = k.id
            WHERE k.uzytkownik_id != $uzytkownik_id
            AND ek.konto_gier_id = $konto_id
        ");
        
        $koszyk_message = dodajDoKoszyka($conn, $uzytkownik_id, $konto_id);
        return "Konto zarezerwowane pomyślnie na 15 minut! {$koszyk_message} Przejdź do finalizacji w koszyku.";
    } else {
        return "Wystąpił błąd podczas rezerwacji, spróbuj ponownie."; 
    }
}

/**
 * FUNKCJA 3: anulujRezerwacje
 */
function anulujRezerwacje($conn, $uzytkownik_id, $konto_id) {
    $conn->query("
        DELETE ek 
        FROM elementy_koszyka ek
        JOIN koszyki k ON ek.koszyk_id = k.id
        WHERE k.uzytkownik_id = $uzytkownik_id
        AND ek.konto_gier_id = $konto_id
    ");
    
    $delete_res = $conn->query("
        DELETE FROM rezerwacje 
        WHERE uzytkownik_id = $uzytkownik_id 
        AND konto_gier_id = $konto_id 
        AND status = 'aktywna'
        LIMIT 1
    ");

    if ($delete_res && $conn->affected_rows > 0) {
        return "Rezerwacja została anulowana. Konto zostało usunięte z koszyka.";
    } else {
        return "Błąd: Brak aktywnej rezerwacji do anulowania.";
    }
}

/**
 * FUNKCJA 4: pobierzKontaZGryPromocjami
 */
function pobierzKontaZGryPromocjami($conn) {
    $sql = "
        SELECT 
            k.id, k.tytul_gry, k.cena_podstawowa, 
            p.wartosc_znizki, p.data_zakonczenia, p.aktywna,
            r.status AS rezerwacja_status,
            r.czas_wygasniecia AS rezerwacja_do
        FROM konta_gier k
        LEFT JOIN promocje p ON k.promocja_id = p.id
        LEFT JOIN rezerwacje r ON k.id = r.konto_gier_id AND r.status = 'aktywna' AND r.czas_wygasniecia > NOW()
        WHERE k.dostepne = TRUE 
        AND (p.id IS NULL OR (p.aktywna = TRUE AND p.data_zakonczenia > NOW())) 
    ";
    
    $result = $conn->query($sql);
    $konta = [];

    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $cena_finalna = obliczCeneKoncowa($row['cena_podstawowa'], $row['wartosc_znizki'], $row['aktywna'], $row['data_zakonczenia']);
            
            $znizka_opis = "";
            if ($cena_finalna < $row['cena_podstawowa']) {
                $znizka_procent = $row['wartosc_znizki'] * 100;
                $znizka_opis = " (Promocja: -$znizka_procent%!)";
            }

            $konta[] = [
                'id' => $row['id'],
                'tytul' => $row['tytul_gry'],
                'cena_podstawowa' => number_format($row['cena_podstawowa'], 2),
                'cena_promocyjna' => number_format($cena_finalna, 2),
                'znizka_opis' => $znizka_opis,
                'rezerwacja_status' => $row['rezerwacja_status'],
                'rezerwacja_do' => $row['rezerwacja_do']
            ];
        }
    }
    return $konta;
}

/**
 * FUNKCJA 5: usunZKoszka
 */
function usunZKoszka($conn, $uzytkownik_id, $konto_id) {
    $koszyk_query = $conn->query("SELECT id FROM koszyki WHERE uzytkownik_id = $uzytkownik_id");
    if ($koszyk_query->num_rows == 0) return "Błąd: Brak koszyka.";

    $koszyk_id = $koszyk_query->fetch_assoc()['id'];
    $delete_item = $conn->query("DELETE FROM elementy_koszyka WHERE koszyk_id = $koszyk_id AND konto_gier_id = $konto_id LIMIT 1");

    return ($delete_item && $conn->affected_rows > 0) ? "Pomyślnie usunięto z koszyka." : "Błąd podczas usuwania.";
}
?>
