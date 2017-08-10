-- phpMyAdmin SQL Dump
-- version 4.7.0
-- https://www.phpmyadmin.net/
--
-- Počítač: 127.0.0.1
-- Vytvořeno: Stř 09. srp 2017, 10:30
-- Verze PHP: 7.1.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Databáze: `bakalarka`
--

--
-- Vypisuji data pro tabulku `guests`
--

INSERT INTO `guests` (`id`, `name`, `email`, `phone`, `street`, `city`, `state`, `birthday`, `birthplace`, `document`) VALUES
(1, 'Zbyněk Mlčák', 'Zbynek.Mlcak@seznam.cz', '778472082', 'Stritez nad Ludinou 236', 'Stritez nad Ludinou', 'Czech Republic', '0000-00-00', 'Hranice', NULL),
(3, 'Eric Cartman', 'wow.zelda@seznam.cz', '123456789', '', '', '', '0000-00-00', '', NULL),
(4, 'Zeldour', 'zel@seznam.cz', '778472082', 'Stritez nad Ludinou 236', '', '', '0000-00-00', 'Stritez nad Ludinou', NULL),
(5, 'asd', 'asdh@asd.cz', '776631521', '', '', '', '0000-00-00', '', NULL);

--
-- Vypisuji data pro tabulku `image`
--

INSERT INTO `image` (`id`, `path`, `description`) VALUES
(1, 'loga/nette.png', 'Logo webového frameworku Nette'),
(2, 'room/2/051.jpg', 'Pokoj 1 - 1'),
(3, 'room/2/052.jpg', 'Pokoj 1-2'),
(4, 'room/1/057.jpg', ''),
(6, 'room/1/room_1498763573_056.jpg', ''),
(7, 'accommodation/acc1498764027_001.jpg', ''),
(8, 'accommodation/acc1498765451_062.jpg', ''),
(9, 'room/3/room_1498765784_053.jpg', '');

--
-- Vypisuji data pro tabulku `photogallery`
--

INSERT INTO `photogallery` (`id`, `name`) VALUES
(2, 'Pokoj č.2'),
(3, 'Pokoj č.1'),
(4, 'Pokoj č.3');

--
-- Vypisuji data pro tabulku `photogallery_images`
--

INSERT INTO `photogallery_images` (`image_id`, `photogallery_id`) VALUES
(2, 1),
(2, 2),
(3, 1),
(3, 2),
(4, 3),
(6, 3),
(7, 1),
(8, 1),
(9, 4);

--
-- Vypisuji data pro tabulku `reservation`
--

INSERT INTO `reservation` (`id`, `session_id`, `guests_id`, `date_from`, `date_to`, `last_change`, `step`, `paid`, `note`, `confirm`) VALUES
(33, 'mql9q8ah1l6eccofkt9ecqku7f', 1, '2017-07-26', '2017-09-05', '2017-08-09 02:07:30', 3, 1, 'Jou jou, test', 0),
(34, 'mql9q8ah1l6eccofkt9ecqku7f', 1, '2017-08-01', '2017-08-02', '2017-08-01 00:50:56', 3, 0, '', 0),
(41, '5dkoi21ufr6stlqgrsi9uu7fk8', 1, '2017-08-07', '2017-08-07', '2017-08-06 13:32:37', 3, 0, '', 0),
(42, 'diarsa96t0hsd1rnh7khivv88v', 4, '2017-08-07', '2017-08-08', '2017-08-09 02:44:26', 3, 1, '', 1),
(48, '3c0o4nd4t0nja8j81pj841s1d4', 1, '2017-08-08', '2017-08-09', '2017-08-09 02:43:29', 3, 1, '', 1),
(49, '3c0o4nd4t0nja8j81pj841s1d4', 1, '2017-08-08', '2017-08-09', '2017-08-09 14:04:19', 3, 1, '', 1),
(50, '3c0o4nd4t0nja8j81pj841s1d4', 1, '2017-08-09', '2017-08-11', '2017-08-08 14:44:33', 3, 0, '', 0),
(52, 'dia11ihi7lo4760febgpmuvcqg', 1, '2017-08-11', '2017-08-12', '2017-08-09 18:10:48', 3, 0, '', 0);

--
-- Vypisuji data pro tabulku `reviews`
--

INSERT INTO `reviews` (`reservation_id`, `stars`, `text`) VALUES
(33, 2, 'Ahoj tohle to je testovací oznámení, super věc. Tohle to je komentář k recenzi.');

--
-- Vypisuji data pro tabulku `room_type`
--

INSERT INTO `room_type` (`id`, `name`, `single_bed`, `double_bed`, `used`) VALUES
(1, 'Jednolůžko', 1, 0, 1),
(2, 'Dvojlůžko s manželskou postelí', 0, 1, 1),
(3, 'Dvojlůžko se samostatnými postelemi', 2, 0, 1);

--
-- Vypisuji data pro tabulku `user`
--

INSERT INTO `user` (`id`, `username`, `password`, `guests_id`, `password_token`, `role`) VALUES
(2, 'test', '$2y$10$Re6SSHFjyr25eaddRBQHP.tvQ0nUr0EqUK05y12bGhgM.MzeHa5c6', NULL, NULL, 'member'),
(3, 'root', '$2y$10$6lu7Ap5S4AZloC2IldFTwOO939gIjqrmD56KwkZJj42StpZI7SEJO', NULL, NULL, 'member'),
(4, 'Zbynek.Mlcak@seznam.cz', '$2y$10$tn/7E0A9qSy0hUpUiDvZtuZkO8AeDjS7NWiBYxfOaVviKBnWsexHm', 1, NULL, 'member'),
(7, 'wow.zelda@seznam.cz', '$2y$10$tn/7E0A9qSy0hUpUiDvZtuZkO8AeDjS7NWiBYxfOaVviKBnWsexHm', 3, NULL, 'member');

--
-- Vypisuji data pro tabulku `room`
--

INSERT INTO `room` (`id`, `name`, `type_id`, `image_id`, `description`, `price`, `extra_beds`, `photogallery_id`) VALUES
  (1, 'Pokoj č.1', 1, 4, 'Tento pokoj je zařízen do starého stylu. Zároveň je však vybaven nejmodernějším vybavením v podobě minibaru či sprchového koutu.', '1200.00', 0, 3),
  (2, 'Pokoj č.2', 2, 2, 'Tady toto je popis pokoje č.2', '1400.00', 0, 2),
  (3, 'Pokoj č.3', 3, 9, 'Toto je popis pokoje č.3', '3100.00', 2, 4);

--
-- Vypisuji data pro tabulku `reservation_rooms`
--

INSERT INTO `reservation_rooms` (`reservation_id`, `room_id`, `people`, `price`, `dph`) VALUES
  (33, 2, 1, 16500, 21),
  (34, 1, 1, 1200, 21),
  (34, 3, 2, 5000, 21),
  (41, 1, 0, 0, 21),
  (42, 1, 1, 1200, 21),
  (48, 1, 1, 1200, 21),
  (50, 1, 1, 1200, 21),
  (52, 1, 1, 1200, 21);

--
-- Vypisuji data pro tabulku `room_services`
--

INSERT INTO `room_services` (`id`, `room_id`, `service_id`) VALUES
  (1, 1, 1),
  (2, 1, 9),
  (3, 2, 2),
  (4, 2, 8),
  (6, 3, 1),
  (7, 3, 5),
  (8, 3, 8);

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
