<?php

/**
 * FUNKCJA 1: dodajDoKoszyka
 * Tworzy koszyk dla użytkownika (jeśli nie istnieje) i dodaje do niego konto.
 */
function dodajDoKoszyka($conn, $uzytkownik_id, $konto_id) {
    // Krok 1: Weryfikacja i pobranie ID koszyka (tworzenie, jeśli nie istnieje)
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

    // Krok 2: Sprawdzenie blokady (synchronizacja z rezerwacją)
    $reserved_query = $conn->query("
        SELECT uzytkownik_id FROM rezerwacje 
        WHERE konto_gier_id = $konto_id AND status = 'aktywna' AND czas_wygasniecia > NOW()
    ");
    
    if ($reserved_query->num_rows > 0) {
        $reservation = $reserved_query->fetch_assoc();
        
        // Blokada, jeśli zarezerwowane przez innego użytkownika
        if ($reservation['uzytkownik_id'] != $uzytkownik_id) {
            return "Błąd: To konto jest obecnie zarezerwowane przez innego użytkownika.";
        }
    }

    // Krok 3: Sprawdzenie duplikatu w koszyku
    $item_query = $conn->query("SELECT id FROM elementy_koszyka WHERE koszyk_id = $koszyk_id AND konto_gier_id = $konto_id");

    if ($item_query->num_rows > 0) {
        return "To konto jest już w koszyku."; 
    }

    // Krok 4: Dodanie rekordu
    $insert_item = $conn->query("INSERT INTO elementy_koszyka (koszyk_id, konto_gier_id) VALUES ($koszyk_id, $konto_id)");

    if ($insert_item) {
        return "Pomyślnie dodano konto do koszyka!";
    } else {
        return "Błąd podczas dodawania do koszyka.";
    }
}

/**
 * FUNKCJA 2: zarezerwujKonto
 * Rezerwuje konto na 15 minut, automatycznie dodaje do koszyka i usuwa konto z koszyków innych użytkowników.
 */
function zarezerwujKonto($conn, $uzytkownik_id, $konto_id) {
    // 1. Sprawdzenie istniejącej rezerwacji (dla bieżącego użytkownika)
    $active_reservation = $conn->query("
        SELECT id FROM rezerwacje 
        WHERE uzytkownik_id = $uzytkownik_id AND status = 'aktywna'
    ");
    if ($active_reservation->num_rows > 0) {
        return "Masz już aktywną rezerwację, dokończ transakcję.";
    }

    // 2. Sprawdzenie blokady (przez innego użytkownika)
    $reserved_query = $conn->query("
        SELECT id FROM rezerwacje 
        WHERE konto_gier_id = $konto_id AND status = 'aktywna' AND czas_wygasniecia > NOW()
    ");
    if ($reserved_query->num_rows > 0) {
        return "To konto jest już niedostępne (zarezerwowane)."; 
    }
    
    // 3. Ustawienie czasu wygaśnięcia
    $expiration_time = date('Y-m-d H:i:s', strtotime('+15 minutes'));

    // 4. Utworzenie rezerwacji
    $insert_res = $conn->query("
        INSERT INTO rezerwacje (uzytkownik_id, konto_gier_id, czas_wygasniecia, status) 
        VALUES ($uzytkownik_id, $konto_id, '$expiration_time', 'aktywna')
    ");

    if ($insert_res) {
        // 5. BLOKADA: Wymuszone usunięcie konta z koszyków WSZYSTKICH INNYCH użytkowników (synchronizacja)
        $conn->query("
            DELETE ek 
            FROM elementy_koszyka ek
            JOIN koszyki k ON ek.koszyk_id = k.id
            WHERE k.uzytkownik_id != $uzytkownik_id
            AND ek.konto_gier_id = $konto_id
        ");
        
        // 6. AUTOMATYCZNE DODANIE DO KOSZYKA rezerwującego (funkcja F1 zajmie się tworzeniem koszyka)
        $koszyk_message = dodajDoKoszyka($conn, $uzytkownik_id, $konto_id);
        
        return "Konto zarezerwowane pomyślnie na 15 minut! {$koszyk_message} Przejdź do finalizacji w koszyku.";
    } else {
        return "Wystąpił błąd podczas rezerwacji, spróbuj ponownie."; 
    }
}

/**
 * FUNKCJA 3: anulujRezerwacje
 * Anuluje aktywną rezerwację i usuwa konto z koszyka bieżącego użytkownika.
 */
function anulujRezerwacje($conn, $uzytkownik_id, $konto_id) {
    // 1. Usuń również konto z koszyka
    $conn->query("
        DELETE ek 
        FROM elementy_koszyka ek
        JOIN koszyki k ON ek.koszyk_id = k.id
        WHERE k.uzytkownik_id = $uzytkownik_id
        AND ek.konto_gier_id = $konto_id
    ");
    
    // 2. Usuń aktywną rezerwację
    $delete_res = $conn->query("
        DELETE FROM rezerwacje 
        WHERE uzytkownik_id = $uzytkownik_id 
        AND konto_gier_id = $konto_id 
        AND status = 'aktywna'
        LIMIT 1
    ");


    if ($delete_res && $conn->affected_rows > 0) {
        return "Rezerwacja została anulowana. Konto zostało usunięte z koszyka.";
    } elseif ($conn->affected_rows == 0) {
        return "Błąd: Brak aktywnej rezerwacji do anulowania dla tego konta.";
    } else {
        return "Wystąpił nieznany błąd podczas anulowania.";
    }
}


/**
 * FUNKCJA 4: pobierzKontaZGryPromocjami
 * Pobiera listę kont z bazy, oblicza ceny promocyjne i status rezerwacji.
 */
function pobierzKontaZGryPromocjami($conn) {
    // Zapytanie z JOINami pobierające promocje i status rezerwacji
    $sql = "
        SELECT 
            k.id, k.tytul_gry, k.cena_podstawowa, 
            p.nazwa, p.wartosc_znizki, p.data_zakonczenia,
            r.status AS rezerwacja_status,
            r.czas_wygasniecia AS rezerwacja_do
            
        FROM konta_gier k
        LEFT JOIN promocje p ON k.promocja_id = p.id
        LEFT JOIN rezerwacje r ON k.id = r.konto_gier_id AND r.status = 'aktywna' AND r.czas_wygasniecia > NOW()
        WHERE 
            k.dostepne = TRUE 
            AND (p.id IS NULL OR (p.aktywna = TRUE AND p.data_zakonczenia > NOW())) 
    ";
    
    $result = $conn->query($sql);
    $konta = [];

    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $cena = $row['cena_podstawowa'];
            $cena_promocyjna = $cena;
            $znizka_opis = "";

            if ($row['wartosc_znizki']) {
                $znizka_procent = $row['wartosc_znizki'] * 100;
                $cena_promocyjna = $cena * (1 - $row['wartosc_znizki']);
                $znizka_opis = " (Promocja: -$znizka_procent%!)";
            }

            $konta[] = [
                'id' => $row['id'],
                'tytul' => $row['tytul_gry'],
                'cena_podstawowa' => number_format($cena, 2),
                'cena_promocyjna' => number_format($cena_promocyjna, 2),
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
 * Usuwa dany produkt z koszyka bieżącego użytkownika, bazując na ID konta.
 */
function usunZKoszka($conn, $uzytkownik_id, $konto_id) {
    // 1. Najpierw znajdź ID koszyka BIEŻĄCEGO użytkownika
    $koszyk_query = $conn->query("SELECT id FROM koszyki WHERE uzytkownik_id = $uzytkownik_id");
    
    if ($koszyk_query->num_rows == 0) {
        return "Błąd: Nie masz aktywnego koszyka do usunięcia produktu.";
    }

    $koszyk_id = $koszyk_query->fetch_assoc()['id'];

    // 2. Usuń element, używając ZNALEZIONEGO ID KOSZYKA
    $delete_item = $conn->query("
        DELETE FROM elementy_koszyka 
        WHERE koszyk_id = $koszyk_id
        AND konto_gier_id = $konto_id
        LIMIT 1
    ");

    if ($delete_item && $conn->affected_rows > 0) {
        return "Pomyślnie usunięto konto z koszyka.";
    } elseif ($delete_item) {
        return "Błąd: To konto nie znajdowało się w Twoim koszyku.";
    } else {
        return "Wystąpił błąd podczas usuwania.";
    }
}
?>