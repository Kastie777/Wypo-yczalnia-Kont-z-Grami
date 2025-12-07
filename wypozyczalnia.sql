-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 07, 2025 at 09:34 PM
-- Wersja serwera: 10.4.32-MariaDB
-- Wersja PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `wypozyczalnia`
--

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `elementy_koszyka`
--

CREATE TABLE `elementy_koszyka` (
  `id` int(11) NOT NULL,
  `koszyk_id` int(11) NOT NULL,
  `konto_gier_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `konta_gier`
--

CREATE TABLE `konta_gier` (
  `id` int(11) NOT NULL,
  `tytul_gry` varchar(255) NOT NULL,
  `platforma` varchar(50) DEFAULT 'Steam',
  `cena_podstawowa` decimal(10,2) NOT NULL,
  `opis` text DEFAULT NULL,
  `zdjecie_url` varchar(255) DEFAULT NULL,
  `dostepne` tinyint(1) DEFAULT 1,
  `login_steam` varchar(100) DEFAULT NULL,
  `haslo_steam` varchar(100) DEFAULT NULL,
  `promocja_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `konta_gier`
--

INSERT INTO `konta_gier` (`id`, `tytul_gry`, `platforma`, `cena_podstawowa`, `opis`, `zdjecie_url`, `dostepne`, `login_steam`, `haslo_steam`, `promocja_id`) VALUES
(10, 'Wiedzmin 3: Dziki Gon', 'Steam', 99.99, NULL, NULL, 1, 'witcher_login', 'tajne123', NULL),
(11, 'Cyberpunk 2077', 'Steam', 149.99, NULL, NULL, 1, 'cpunk_login', 'secure456', 101),
(12, 'Red Dead Redemption 2', 'Rockstar', 179.99, NULL, NULL, 0, 'rdr_login', 'hidden789', NULL);

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `koszyki`
--

CREATE TABLE `koszyki` (
  `id` int(11) NOT NULL,
  `uzytkownik_id` int(11) NOT NULL,
  `data_utworzenia` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `koszyki`
--

INSERT INTO `koszyki` (`id`, `uzytkownik_id`, `data_utworzenia`) VALUES
(1, 1, '2025-12-06 13:50:41'),
(2, 2, '2025-12-06 14:13:28');

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `promocje`
--

CREATE TABLE `promocje` (
  `id` int(11) NOT NULL,
  `nazwa` varchar(100) NOT NULL,
  `wartosc_znizki` decimal(5,2) NOT NULL,
  `data_rozpoczecia` datetime NOT NULL,
  `data_zakonczenia` datetime NOT NULL,
  `aktywna` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `promocje`
--

INSERT INTO `promocje` (`id`, `nazwa`, `wartosc_znizki`, `data_rozpoczecia`, `data_zakonczenia`, `aktywna`) VALUES
(101, 'Jesienna Wyprzedaż', 0.20, '2025-11-01 00:00:00', '2026-01-31 23:59:59', 1),
(102, 'Stara Promocja', 0.10, '2024-05-01 00:00:00', '2024-05-31 23:59:59', 0);

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `rezerwacje`
--

CREATE TABLE `rezerwacje` (
  `id` int(11) NOT NULL,
  `uzytkownik_id` int(11) NOT NULL,
  `konto_gier_id` int(11) NOT NULL,
  `data_rezerwacji` datetime DEFAULT current_timestamp(),
  `czas_wygasniecia` datetime NOT NULL,
  `STATUS` enum('aktywna','wygasla','zrealizowana') DEFAULT 'aktywna'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rezerwacje`
--

INSERT INTO `rezerwacje` (`id`, `uzytkownik_id`, `konto_gier_id`, `data_rezerwacji`, `czas_wygasniecia`, `STATUS`) VALUES
(1, 2, 11, '2025-01-01 12:00:00', '2025-01-01 12:15:00', 'wygasla');

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `szczegoly_zamowienia`
--

CREATE TABLE `szczegoly_zamowienia` (
  `id` int(11) NOT NULL,
  `zamowienie_id` int(11) NOT NULL,
  `konto_gier_id` int(11) NOT NULL,
  `cena_w_momencie_zakupu` decimal(10,2) NOT NULL,
  `data_wygasniecia_dostepu` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `uzytkownicy`
--

CREATE TABLE `uzytkownicy` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `haslo` varchar(255) NOT NULL,
  `data_rejestracji` datetime DEFAULT current_timestamp(),
  `motyw` varchar(20) DEFAULT 'light',
  `rola` enum('user','admin') DEFAULT 'user'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `uzytkownicy`
--

INSERT INTO `uzytkownicy` (`id`, `email`, `haslo`, `data_rejestracji`, `motyw`, `rola`) VALUES
(1, 'test.user@project.pl', 'haslo_hash', '2025-12-06 13:49:12', 'light', 'user'),
(2, 'admin@project.pl', 'admin_hash', '2025-12-06 13:49:12', 'light', 'admin');

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `wiadomosci`
--

CREATE TABLE `wiadomosci` (
  `id` int(11) NOT NULL,
  `uzytkownik_id` int(11) DEFAULT NULL,
  `temat` varchar(255) DEFAULT NULL,
  `tresc` text NOT NULL,
  `data_wyslania` datetime DEFAULT current_timestamp(),
  `STATUS` enum('nowe','w_trakcie','rozwiazane') DEFAULT 'nowe'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `zamowienia`
--

CREATE TABLE `zamowienia` (
  `id` int(11) NOT NULL,
  `uzytkownik_id` int(11) NOT NULL,
  `data_zamowienia` datetime DEFAULT current_timestamp(),
  `STATUS` enum('oplacone','zrealizowane','anulowane','zwrot') DEFAULT 'oplacone',
  `calkowita_kwota` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indeksy dla zrzutów tabel
--

--
-- Indeksy dla tabeli `elementy_koszyka`
--
ALTER TABLE `elementy_koszyka`
  ADD PRIMARY KEY (`id`),
  ADD KEY `koszyk_id` (`koszyk_id`),
  ADD KEY `konto_gier_id` (`konto_gier_id`);

--
-- Indeksy dla tabeli `konta_gier`
--
ALTER TABLE `konta_gier`
  ADD PRIMARY KEY (`id`),
  ADD KEY `promocja_id` (`promocja_id`);

--
-- Indeksy dla tabeli `koszyki`
--
ALTER TABLE `koszyki`
  ADD PRIMARY KEY (`id`),
  ADD KEY `uzytkownik_id` (`uzytkownik_id`);

--
-- Indeksy dla tabeli `promocje`
--
ALTER TABLE `promocje`
  ADD PRIMARY KEY (`id`);

--
-- Indeksy dla tabeli `rezerwacje`
--
ALTER TABLE `rezerwacje`
  ADD PRIMARY KEY (`id`),
  ADD KEY `uzytkownik_id` (`uzytkownik_id`),
  ADD KEY `konto_gier_id` (`konto_gier_id`);

--
-- Indeksy dla tabeli `szczegoly_zamowienia`
--
ALTER TABLE `szczegoly_zamowienia`
  ADD PRIMARY KEY (`id`),
  ADD KEY `zamowienie_id` (`zamowienie_id`),
  ADD KEY `konto_gier_id` (`konto_gier_id`);

--
-- Indeksy dla tabeli `uzytkownicy`
--
ALTER TABLE `uzytkownicy`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indeksy dla tabeli `wiadomosci`
--
ALTER TABLE `wiadomosci`
  ADD PRIMARY KEY (`id`),
  ADD KEY `uzytkownik_id` (`uzytkownik_id`);

--
-- Indeksy dla tabeli `zamowienia`
--
ALTER TABLE `zamowienia`
  ADD PRIMARY KEY (`id`),
  ADD KEY `uzytkownik_id` (`uzytkownik_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `elementy_koszyka`
--
ALTER TABLE `elementy_koszyka`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `konta_gier`
--
ALTER TABLE `konta_gier`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `koszyki`
--
ALTER TABLE `koszyki`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `promocje`
--
ALTER TABLE `promocje`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=103;

--
-- AUTO_INCREMENT for table `rezerwacje`
--
ALTER TABLE `rezerwacje`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `szczegoly_zamowienia`
--
ALTER TABLE `szczegoly_zamowienia`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `uzytkownicy`
--
ALTER TABLE `uzytkownicy`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `wiadomosci`
--
ALTER TABLE `wiadomosci`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `zamowienia`
--
ALTER TABLE `zamowienia`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `elementy_koszyka`
--
ALTER TABLE `elementy_koszyka`
  ADD CONSTRAINT `elementy_koszyka_ibfk_1` FOREIGN KEY (`koszyk_id`) REFERENCES `koszyki` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `elementy_koszyka_ibfk_2` FOREIGN KEY (`konto_gier_id`) REFERENCES `konta_gier` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `konta_gier`
--
ALTER TABLE `konta_gier`
  ADD CONSTRAINT `konta_gier_ibfk_1` FOREIGN KEY (`promocja_id`) REFERENCES `promocje` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `koszyki`
--
ALTER TABLE `koszyki`
  ADD CONSTRAINT `koszyki_ibfk_1` FOREIGN KEY (`uzytkownik_id`) REFERENCES `uzytkownicy` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `rezerwacje`
--
ALTER TABLE `rezerwacje`
  ADD CONSTRAINT `rezerwacje_ibfk_1` FOREIGN KEY (`uzytkownik_id`) REFERENCES `uzytkownicy` (`id`),
  ADD CONSTRAINT `rezerwacje_ibfk_2` FOREIGN KEY (`konto_gier_id`) REFERENCES `konta_gier` (`id`);

--
-- Constraints for table `szczegoly_zamowienia`
--
ALTER TABLE `szczegoly_zamowienia`
  ADD CONSTRAINT `szczegoly_zamowienia_ibfk_1` FOREIGN KEY (`zamowienie_id`) REFERENCES `zamowienia` (`id`),
  ADD CONSTRAINT `szczegoly_zamowienia_ibfk_2` FOREIGN KEY (`konto_gier_id`) REFERENCES `konta_gier` (`id`);

--
-- Constraints for table `wiadomosci`
--
ALTER TABLE `wiadomosci`
  ADD CONSTRAINT `wiadomosci_ibfk_1` FOREIGN KEY (`uzytkownik_id`) REFERENCES `uzytkownicy` (`id`);

--
-- Constraints for table `zamowienia`
--
ALTER TABLE `zamowienia`
  ADD CONSTRAINT `zamowienia_ibfk_1` FOREIGN KEY (`uzytkownik_id`) REFERENCES `uzytkownicy` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
