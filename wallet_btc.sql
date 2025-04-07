-- phpMyAdmin SQL Dump
-- version 5.1.1deb5ubuntu1
-- https://www.phpmyadmin.net/
--
-- Хост: localhost:3306
-- Время создания: Апр 07 2025 г., 13:02
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
(6, 187, '0.00015000', '7006541.00', NULL, 'RUB', 'buy', 'pending', '2025-04-06 15:02:20', '2025-04-06 22:30:13', NULL, 182, 0.0001, 0.0002),
(7, 184, NULL, '7001866.00', NULL, 'RUB', 'buy', 'active', '2025-04-06 15:22:11', '2025-04-06 15:22:11', '', NULL, 0.0002, 0.001),
(8, 184, '0.00100000', '6998992.00', NULL, 'RUB', 'buy', 'active', '2025-04-06 15:34:52', '2025-04-06 16:32:50', '', NULL, 0.001, 0.002);

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
(6, 'СБП'),
(7, 'СБП'),
(8, 'СБП'),
(6, 'Совкомбанк'),
(7, 'Совкомбанк'),
(6, 'Т-Банк'),
(7, 'Т-Банк'),
(8, 'Т-Банк');

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
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `buyer_confirmed` tinyint(1) DEFAULT '0',
  `seller_confirmed` tinyint(1) DEFAULT '0',
  `arbiter_confirmed` tinyint(1) DEFAULT '0',
  `buyer_cancelled` tinyint(1) DEFAULT '0',
  `seller_cancelled` tinyint(1) DEFAULT '0',
  `arbiter_cancelled` tinyint(1) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `escrow_deposits`
--

INSERT INTO `escrow_deposits` (`id`, `ad_id`, `escrow_address`, `buyer_pubkey`, `seller_pubkey`, `arbiter_pubkey`, `txid`, `btc_amount`, `status`, `created_at`, `updated_at`, `buyer_confirmed`, `seller_confirmed`, `arbiter_confirmed`, `buyer_cancelled`, `seller_cancelled`, `arbiter_cancelled`) VALUES
(1, 6, '2MyK5oy7a2uNDdemoADVMCMg6V4phnfWu7s', '02260ff95657db6c3c1eeecf1231e8c1fe7e5c30a48530856aca16e19eda21c439', '0250e6bc9fe036fa94e88ce58f645b0cbc1920dd1b961040c72a53e4e8d8d836bc', '02260ff95657db6c3c1eeecf1231e8c1fe7e5c30a48530856aca16e19eda21c439', 'ac7bcfba0a790758875013551ad0d85226c3856cb754cb6ddcea564d856acf9a', '0.00015000', 'btc_deposited', '2025-04-06 22:30:13', '2025-04-06 22:30:13', 0, 0, 0, 0, 0, 0);

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
  `username` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `role` enum('admin','user') DEFAULT 'user',
  `pubkey` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `members`
--

INSERT INTO `members` (`id`, `passw`, `wallet`, `balance`, `username`, `role`, `pubkey`) VALUES
(182, 'motors whimpering titanate trumpet redeclares lobsters spouses combinator different magnificent recoil airfoils stammers Buchwald tentacled rarety-s rose-s murmured', 'tb1qtdxq5dzdv29tkw7t3d07qqeuz80y9k80ynu5tn', '0.00980495', '', 'user', '02260ff95657db6c3c1eeecf1231e8c1fe7e5c30a48530856aca16e19eda21c439'),
(184, 'chinquapin absentia missionaries milky pirate-s midband audiovisual continuities tableaux nowadays tamed protestant falsified Fredericksburg watchword directory-s uproots thermistor', 'tb1qfzxhvj6a6tf0cujun67wyr4m98q0danqftcl7x', '0.00000000', '', 'user', '0387b9b086b1b947609b7fa03cd0da1db2fcef89f844756dc921f111a7b574451a'),
(187, 'adjutants adjudged fondle lime condemner subinterval meadowland haltingly columnizes mild demonstrators upperclassman unnecessary stabile temerity grudge-s MD slanderer', 'tb1q075lflht5yztm87tj7kldkysfxw0y84jdhgjf7', '0.00425463', NULL, 'user', '0250e6bc9fe036fa94e88ce58f645b0cbc1920dd1b961040c72a53e4e8d8d836bc');

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
(14, 184, 'Your ad #8 has been accepted and is in the pending status. Go to the \"Trade history\" section and continue the transaction.', 1, '2025-04-06 16:07:07'),
(15, 187, 'Your ad #6 has been accepted and is in the pending status. Go to the \"Trade history\" section and continue the transaction.', 1, '2025-04-06 22:30:14');

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
('total', 5807);

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
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT для таблицы `escrow_deposits`
--
ALTER TABLE `escrow_deposits`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

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
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=188;

--
-- AUTO_INCREMENT для таблицы `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=93;

--
-- AUTO_INCREMENT для таблицы `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

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
