-- phpMyAdmin SQL Dump
-- version 5.1.1deb5ubuntu1
-- https://www.phpmyadmin.net/
--
-- Хост: localhost:3306
-- Время создания: Мар 28 2025 г., 10:16
-- Версия сервера: 8.0.41-0ubuntu0.22.04.1
-- Версия PHP: 8.1.2-1ubuntu2.20

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `wallet_btc`
--

-- --------------------------------------------------------

--
-- Структура таблицы `ads`
--

CREATE TABLE `ads` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `amount_btc` decimal(16,8) NOT NULL,
  `rate` decimal(16,2) NOT NULL,
  `payment_method` varchar(255) NOT NULL,
  `fiat_currency` varchar(255) NOT NULL,
  `trade_type` enum('buy','sell') NOT NULL,
  `status` enum('active','inactive') NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `ads`
--

INSERT INTO `ads` (`id`, `user_id`, `amount_btc`, `rate`, `payment_method`, `fiat_currency`, `trade_type`, `status`, `created_at`, `updated_at`) VALUES
(2, 184, '0.00030000', '7204926.00', 'Сбербанк', 'RUB', 'sell', 'active', '2025-03-28 07:42:18', '2025-03-28 07:42:18'),
(3, 182, '0.00030000', '7126455.00', 'Сбербанк', 'EUR', 'sell', 'active', '2025-03-28 09:11:33', '2025-03-28 09:29:16');

-- --------------------------------------------------------

--
-- Структура таблицы `fiat_currencies`
--

CREATE TABLE `fiat_currencies` (
  `id` int NOT NULL,
  `currency_code` varchar(10) NOT NULL,
  `currency_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `fiat_currencies`
--

INSERT INTO `fiat_currencies` (`id`, `currency_code`, `currency_name`) VALUES
(1, 'EUR', 'Euro'),
(2, 'USD', 'United States Dollar'),
(3, 'RUB', 'Russian Ruble');

-- --------------------------------------------------------

--
-- Структура таблицы `logs`
--

CREATE TABLE `logs` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `action` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `members`
--

CREATE TABLE `members` (
  `id` int NOT NULL,
  `passw` varchar(556) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `wallet` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `balance` varchar(256) DEFAULT NULL,
  `username` varchar(255) NOT NULL,
  `role` enum('admin','user') DEFAULT 'user'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `members`
--

INSERT INTO `members` (`id`, `passw`, `wallet`, `balance`, `username`, `role`) VALUES
(182, 'motors whimpering titanate trumpet redeclares lobsters spouses combinator different magnificent recoil airfoils stammers Buchwald tentacled rarety-s rose-s murmured', 'tb1qtdxq5dzdv29tkw7t3d07qqeuz80y9k80ynu5tn', '0.00000535', '', 'user'),
(184, 'chinquapin absentia missionaries milky pirate-s midband audiovisual continuities tableaux nowadays tamed protestant falsified Fredericksburg watchword directory-s uproots thermistor', 'tb1qfzxhvj6a6tf0cujun67wyr4m98q0danqftcl7x', '0.00020850', '', 'user');

-- --------------------------------------------------------

--
-- Структура таблицы `p2p_offers`
--

CREATE TABLE `p2p_offers` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `btc_amount` float NOT NULL,
  `fiat_amount` float NOT NULL,
  `fiat_currency` varchar(10) NOT NULL,
  `payment_method` varchar(255) NOT NULL,
  `status` varchar(20) NOT NULL,
  `accepted_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `payment_methods`
--

CREATE TABLE `payment_methods` (
  `id` int NOT NULL,
  `method_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `payment_methods`
--

INSERT INTO `payment_methods` (`id`, `method_name`) VALUES
(8, 'OZON Банк'),
(2, 'Payoneer'),
(1, 'PayPal'),
(3, 'Revolut'),
(4, 'Wise '),
(7, 'Альфа Банк'),
(10, 'МИР'),
(6, 'Сбербанк'),
(11, 'СБП'),
(9, 'Совкомбанк'),
(5, 'Т-Банк');

-- --------------------------------------------------------

--
-- Структура таблицы `support_requests`
--

CREATE TABLE `support_requests` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `response` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `support_requests`
--

INSERT INTO `support_requests` (`id`, `user_id`, `message`, `response`, `created_at`) VALUES
(30, 182, NULL, 'wefdsfsdf', '2025-03-27 18:22:24'),
(31, 184, NULL, 'sdfsdfvxcv234', '2025-03-27 18:22:50'),
(32, 184, NULL, 'sdfsdfvxcv234', '2025-03-27 18:22:53'),
(33, 182, NULL, 'sdfg5555555555555555555', '2025-03-27 18:23:02');

-- --------------------------------------------------------

--
-- Структура таблицы `visit_counter`
--

CREATE TABLE `visit_counter` (
  `page` varchar(50) NOT NULL,
  `count` int NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `visit_counter`
--

INSERT INTO `visit_counter` (`page`, `count`) VALUES
('total', 5783);

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `ads`
--
ALTER TABLE `ads`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `fiat_currencies`
--
ALTER TABLE `fiat_currencies`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `currency_code` (`currency_code`);

--
-- Индексы таблицы `logs`
--
ALTER TABLE `logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Индексы таблицы `members`
--
ALTER TABLE `members`
  ADD KEY `id` (`id`);

--
-- Индексы таблицы `p2p_offers`
--
ALTER TABLE `p2p_offers`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `payment_methods`
--
ALTER TABLE `payment_methods`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `method_name` (`method_name`);

--
-- Индексы таблицы `support_requests`
--
ALTER TABLE `support_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Индексы таблицы `visit_counter`
--
ALTER TABLE `visit_counter`
  ADD PRIMARY KEY (`page`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `ads`
--
ALTER TABLE `ads`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT для таблицы `fiat_currencies`
--
ALTER TABLE `fiat_currencies`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT для таблицы `logs`
--
ALTER TABLE `logs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `members`
--
ALTER TABLE `members`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=187;

--
-- AUTO_INCREMENT для таблицы `p2p_offers`
--
ALTER TABLE `p2p_offers`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `payment_methods`
--
ALTER TABLE `payment_methods`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT для таблицы `support_requests`
--
ALTER TABLE `support_requests`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `support_requests`
--
ALTER TABLE `support_requests`
  ADD CONSTRAINT `support_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `members` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
