-- phpMyAdmin SQL Dump
-- version 5.1.1deb5ubuntu1
-- https://www.phpmyadmin.net/
--
-- Хост: localhost:3306
-- Время создания: Апр 06 2025 г., 12:37
-- Версия сервера: 8.0.41-0ubuntu0.22.04.1
-- Версия PHP: 8.1.2-1ubuntu2.21

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
  `amount_btc` decimal(16,8) DEFAULT NULL,
  `rate` decimal(16,2) NOT NULL,
  `payment_method` varchar(255) DEFAULT NULL,
  `fiat_currency` varchar(255) NOT NULL,
  `trade_type` enum('buy','sell') NOT NULL,
  `status` enum('active','inactive','pending','completed') NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `comment` text,
  `buyer_id` int DEFAULT NULL,
  `min_amount_btc` double NOT NULL,
  `max_amount_btc` double NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `ads`
--

INSERT INTO `ads` (`id`, `user_id`, `amount_btc`, `rate`, `payment_method`, `fiat_currency`, `trade_type`, `status`, `created_at`, `updated_at`, `comment`, `buyer_id`, `min_amount_btc`, `max_amount_btc`) VALUES
(2, 184, '0.00030000', '7204926.00', 'Сбербанк', 'RUB', 'sell', 'pending', '2025-03-28 07:42:18', '2025-03-29 12:23:44', 'Готов', 182, 0, 0),
(3, 182, '0.00030000', '7012641.00', 'Сбербанк', 'EUR', 'sell', 'pending', '2025-03-28 09:11:33', '2025-04-06 12:36:24', 'Только Сбер по номеру телефона', 184, 0.0004, 0.002),
(4, 182, '0.00200000', '7150000.00', NULL, 'RUB', 'buy', 'active', '2025-03-28 15:05:04', '2025-04-06 12:36:46', '', 184, 0.00004, 0.003),
(5, 182, NULL, '70187000.00', NULL, 'RUB', 'sell', 'active', '2025-04-06 11:33:23', '2025-04-06 11:33:23', NULL, NULL, 0.00002, 0.004);

-- --------------------------------------------------------

--
-- Структура таблицы `ad_payment_methods`
--

CREATE TABLE `ad_payment_methods` (
  `ad_id` int NOT NULL,
  `payment_method` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `ad_payment_methods`
--

INSERT INTO `ad_payment_methods` (`ad_id`, `payment_method`) VALUES
(2, 'OZON Банк'),
(2, 'PayPal'),
(4, 'PayPal'),
(3, 'Wise '),
(4, 'МИР'),
(5, 'МИР'),
(3, 'Сбербанк'),
(5, 'Сбербанк'),
(2, 'СБП'),
(4, 'СБП'),
(5, 'СБП'),
(3, 'Совкомбанк'),
(3, 'Т-Банк'),
(5, 'Т-Банк');

-- --------------------------------------------------------

--
-- Структура таблицы `escrow_deposits`
--

CREATE TABLE `escrow_deposits` (
  `id` int NOT NULL,
  `ad_id` int NOT NULL,
  `escrow_address` varchar(100) NOT NULL,
  `buyer_pubkey` text NOT NULL,
  `seller_pubkey` text NOT NULL,
  `arbiter_pubkey` text NOT NULL,
  `txid` varchar(100) DEFAULT NULL,
  `btc_amount` decimal(16,8) NOT NULL,
  `status` enum('waiting_deposit','btc_deposited','fiat_paid','btc_released','disputed','refunded') NOT NULL DEFAULT 'waiting_deposit',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
(182, 'motors whimpering titanate trumpet redeclares lobsters spouses combinator different magnificent recoil airfoils stammers Buchwald tentacled rarety-s rose-s murmured', 'tb1qtdxq5dzdv29tkw7t3d07qqeuz80y9k80ynu5tn', '0.01425463', '', 'user'),
(184, 'chinquapin absentia missionaries milky pirate-s midband audiovisual continuities tableaux nowadays tamed protestant falsified Fredericksburg watchword directory-s uproots thermistor', 'tb1qfzxhvj6a6tf0cujun67wyr4m98q0danqftcl7x', '0.00000000', '', 'user');

-- --------------------------------------------------------

--
-- Структура таблицы `messages`
--

CREATE TABLE `messages` (
  `id` int NOT NULL,
  `ad_id` int NOT NULL,
  `user_id` int NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `recipient_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `messages`
--

INSERT INTO `messages` (`id`, `ad_id`, `user_id`, `message`, `created_at`, `recipient_id`) VALUES
(87, 3, 184, 'Привет, готовы?', '2025-03-29 19:09:28', 182),
(88, 3, 182, 'да, готов.', '2025-03-29 19:10:18', 184),
(89, 2, 182, 'Добрый день', '2025-03-30 06:33:29', 184),
(90, 2, 182, 'ага,', '2025-03-30 06:45:41', 184),
(91, 2, 182, '1', '2025-03-30 06:46:22', 184),
(92, 2, 182, '21', '2025-03-30 07:00:58', 184);

-- --------------------------------------------------------

--
-- Структура таблицы `notifications`
--

CREATE TABLE `notifications` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `message`, `is_read`, `created_at`) VALUES
(4, 182, 'Новое сообщение в чате по объявлению #2', 1, '2025-03-29 18:22:58'),
(5, 182, 'Новое сообщение в чате по объявлению #2', 1, '2025-03-29 18:25:04'),
(6, 184, 'Новое сообщение в чате по объявлению #2', 1, '2025-03-29 18:35:34'),
(7, 182, 'Новое сообщение в чате по объявлению #3', 1, '2025-03-29 19:09:29'),
(8, 184, 'Новое сообщение в чате по объявлению #3', 1, '2025-03-29 19:10:19'),
(9, 184, 'A new chat message based on the ad #2. Go to the <a href=\"p2p-trade_history\">Trade history</a> section and continue the transaction.', 1, '2025-03-30 06:33:29'),
(10, 184, 'A new chat message based on the ad #2. Go to the <a href=\"p2p-trade_history\">Trade history</a> section to continue the transaction.', 1, '2025-03-30 06:45:42'),
(11, 184, 'A new chat message based on the ad #2. Go to the <a href=\"p2p-trade_history\">Trade history</a> section to continue the transaction.', 1, '2025-03-30 06:46:22'),
(12, 184, 'A new chat message based on the ad #2. Go to the <a href=\"p2p-trade_history\">Trade history</a> section to continue the transaction.', 1, '2025-03-30 07:00:58');

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
('total', 5800);

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `ads`
--
ALTER TABLE `ads`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `ad_payment_methods`
--
ALTER TABLE `ad_payment_methods`
  ADD PRIMARY KEY (`ad_id`,`payment_method`),
  ADD KEY `payment_method` (`payment_method`);

--
-- Индексы таблицы `escrow_deposits`
--
ALTER TABLE `escrow_deposits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ad_id` (`ad_id`);

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
-- Индексы таблицы `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ad_id` (`ad_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Индексы таблицы `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`);

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
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT для таблицы `escrow_deposits`
--
ALTER TABLE `escrow_deposits`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

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
-- AUTO_INCREMENT для таблицы `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=93;

--
-- AUTO_INCREMENT для таблицы `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

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
-- Ограничения внешнего ключа таблицы `ad_payment_methods`
--
ALTER TABLE `ad_payment_methods`
  ADD CONSTRAINT `ad_payment_methods_ibfk_1` FOREIGN KEY (`ad_id`) REFERENCES `ads` (`id`),
  ADD CONSTRAINT `ad_payment_methods_ibfk_2` FOREIGN KEY (`payment_method`) REFERENCES `payment_methods` (`method_name`);

--
-- Ограничения внешнего ключа таблицы `escrow_deposits`
--
ALTER TABLE `escrow_deposits`
  ADD CONSTRAINT `escrow_deposits_ibfk_1` FOREIGN KEY (`ad_id`) REFERENCES `ads` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`ad_id`) REFERENCES `ads` (`id`),
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `members` (`id`);

--
-- Ограничения внешнего ключа таблицы `support_requests`
--
ALTER TABLE `support_requests`
  ADD CONSTRAINT `support_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `members` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
