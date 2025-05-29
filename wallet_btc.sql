-- phpMyAdmin SQL Dump
-- version 5.1.1deb5ubuntu1
-- https://www.phpmyadmin.net/
--
-- Хост: localhost:3306
-- Время создания: Май 29 2025 г., 17:46
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
  `status` enum('active','inactive','pending','completed','deleted') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `comment` text,
  `buyer_id` int DEFAULT NULL,
  `seller_id` int DEFAULT NULL,
  `min_amount_btc` double NOT NULL,
  `max_amount_btc` double NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `ads`
--

INSERT INTO `ads` (`id`, `user_id`, `amount_btc`, `rate`, `payment_method`, `fiat_currency`, `trade_type`, `status`, `created_at`, `updated_at`, `comment`, `buyer_id`, `seller_id`, `min_amount_btc`, `max_amount_btc`) VALUES
(5, 187, NULL, '8678987.00', NULL, 'RUB', 'buy', 'active', '2025-05-28 14:29:28', '2025-05-28 14:31:19', 'В среднем сделка проходит от 10 до 30 минут. Отправляю с чистых аккаунтов несколькими платежами.. ', NULL, NULL, 0.01, 0.5);

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
(4, 'PayPal'),
(5, 'Альфа Банк'),
(5, 'МИР'),
(5, 'Сбербанк'),
(5, 'СБП'),
(5, 'Совкомбанк'),
(5, 'Т-Банк');

-- --------------------------------------------------------

--
-- Структура таблицы `btc_notifications`
--

CREATE TABLE `btc_notifications` (
  `id` int NOT NULL,
  `txid` varchar(100) NOT NULL,
  `address` varchar(100) DEFAULT NULL,
  `amount` decimal(16,8) DEFAULT NULL,
  `confirmations` int DEFAULT NULL,
  `notified` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `btc_notifications`
--

INSERT INTO `btc_notifications` (`id`, `txid`, `address`, `amount`, `confirmations`, `notified`, `created_at`) VALUES
(1, '0305b74c0577304d3e4cf17195077d6daf04e82c19706913905e8a94e6668a7d', 'bc1qcf4xqfs0lrmy6yktd8s3lfedhmx586le4hxkje', '0.00023681', 9377, 0, '2025-05-18 09:31:12'),
(2, '60d5e7295af6a68bad1b3d6a2434558a51f2cb64aa8afeeaf56fc7e8d7c6e73a', 'bc1qqs3egyd4m60v0t8a3xyz87txpkvulp4e8ucv8d', '0.00060017', 4622, 0, '2025-05-18 09:31:13'),
(3, '4912b80de6f6ee1ba4ca80d9a7fcc8514dadf9fbade634ef4d86d6dff55a0a77', 'bc1qq00pgwy3mleht2ts3yz99k5u7zr76fylek9sad', '0.00003110', 1702, 0, '2025-05-18 09:31:13'),
(4, '974cec446025c8dbb3e73521227d5f323c4910381bed1378a8a2768b710b07f7', 'bc1qw90nd8yakjd49ww6rplwkn5m6uj6jk9mjrvpcq', '0.00100000', 1234, 0, '2025-05-18 09:31:13'),
(5, '6961d10a127426dd711bf75e127426aa047e97c19b0bfb6ec08b032988be4ed9', 'bc1qw90nd8yakjd49ww6rplwkn5m6uj6jk9mjrvpcq', '0.00550000', 1229, 0, '2025-05-18 09:31:14'),
(6, 'f7aeee23a0f8ca92498c37c89505ef39bfc3567b6a47157e0b19fea5c3bcabec', 'bc1qq00pgwy3mleht2ts3yz99k5u7zr76fylek9sad', '0.00652298', 1227, 0, '2025-05-18 09:31:14'),
(7, '03aae458e005526074f00b98159be69561590f633fe6477c61a0ff4dfa2c476d', 'bc1qq00pgwy3mleht2ts3yz99k5u7zr76fylek9sad', '0.00651933', 1226, 0, '2025-05-18 09:31:14'),
(8, '4a51c7bf351fd090bcc03426a3fc1db8bee9901b1372fbc7120816954040d4a1', 'bc1qjf7896kf83a6x32ta3dfv5p983sm8snepjvx4v', '0.00097034', 369, 0, '2025-05-18 09:31:15'),
(9, 'fdf203bbabeadb5eb3beddcd4d45edd890fffdc2465a1afee0ee3c990686da57', 'bc1qp2w0lca33q0x7n57qv8sechtjkp3yp9t9uppwc', '-0.00023000', 8033, 0, '2025-05-18 11:52:01'),
(10, 'fede3cb0b342a3a0b3ac2bd59d2ae65ca1824abe517c88438c17f155f5c4caab', 'bc1qp2w0lca33q0x7n57qv8sechtjkp3yp9t9uppwc', '-0.00060188', 1790, 0, '2025-05-18 11:52:01'),
(11, '396f3e71c72eb9e3cc04cd7f2753ae79cf73bf99f57ef320c8cdb9fc5466d936', 'bc1qp2w0lca33q0x7n57qv8sechtjkp3yp9t9uppwc', '-0.00651602', 1229, 0, '2025-05-18 11:52:01'),
(12, '9c3af7ea45d3475420c7ae50d9c75812ed3241078d08731d43783b66bde93535', 'bc1qp2w0lca33q0x7n57qv8sechtjkp3yp9t9uppwc', '-0.00096688', 0, 0, '2025-05-18 11:52:02');

-- --------------------------------------------------------

--
-- Структура таблицы `escrow_deposits`
--

CREATE TABLE `escrow_deposits` (
  `id` int NOT NULL,
  `ad_id` int NOT NULL,
  `escrow_address` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `buyer_pubkey` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `seller_pubkey` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `arbiter_pubkey` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `txid` varchar(100) DEFAULT NULL,
  `btc_amount` decimal(16,8) NOT NULL,
  `status` enum('waiting_deposit','btc_deposited','fiat_paid','btc_released','disputed','refunded','completed') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'waiting_deposit',
  `transaction_id` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `buyer_confirmed` tinyint(1) DEFAULT '0',
  `seller_confirmed` tinyint(1) DEFAULT '0',
  `arbiter_confirmed` tinyint(1) DEFAULT '0',
  `buyer_cancelled` tinyint(1) DEFAULT '0',
  `seller_cancelled` tinyint(1) DEFAULT '0',
  `arbiter_cancelled` tinyint(1) DEFAULT '0',
  `deposited` tinyint(1) DEFAULT '0',
  `service_comments` json DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `escrow_signatures`
--

CREATE TABLE `escrow_signatures` (
  `id` int NOT NULL,
  `escrow_id` int NOT NULL,
  `role` enum('buyer','seller','arbiter') NOT NULL,
  `signed_hex` text NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
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
  `username` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `role` enum('admin','user') DEFAULT 'user',
  `pubkey` varchar(255) NOT NULL DEFAULT '0',
  `privkey` varchar(256) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `notified` tinyint(1) DEFAULT '0',
  `session_token` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `members`
--

INSERT INTO `members` (`id`, `passw`, `wallet`, `balance`, `username`, `role`, `pubkey`, `privkey`, `created_at`, `notified`, `session_token`) VALUES
(7, 'codeword-s typeface hexagonally floured disallowed thermostat-s indefinable customizing totals critically transporting palladia bicameral snugly splurge besmirch rhythm-s tourist-s', 'bc1qq00pgwy3mleht2ts3yz99k5u7zr76fylek9sad', '0.00000000', '', 'user', '02d9a3fe81b149e03fb8603ddb807ca237993df6e323a06c3c71a998be084c2bf4', 'KxzmdHUvkgobYjKdJP8fFQq6MQ4oBBFY49AVxiNsDV4jk8XozMr1', '2025-05-18 09:40:36', 1, '15d63ab0d738b3354d9dcb2581deab54bf50d0f6f598dc1289eb0ee17cb59a4d'),
(8, 'convergent fleetest Basel autumn airline fawned organic gent suds geophysics latent Balinese chanting adipic Abo refinement-s adjourned croaks', 'bc1qvy6srmp48mmma55k7tc6ahhva7f43vskep5ll0', '0.00000000', '', 'user', '0', '0', '2025-05-18 09:40:36', 1, NULL),
(9, 'rooting wineskin dissents duplications turrets they-d Estella Schulz polluted sweets death bartender ballerinas dull piston-s cylinder suppose spaceship-s', 'bc1qlme5graf9vztz738pa5yw57w478vrej5r3ervm', '0', '', 'user', '0', '0', '2025-05-18 09:40:36', 1, NULL),
(14, 'suffers cholinesterase donor assaulting imperatively bandied renews pirate-s quarry-s dipped mutated synchrotron phrases binder spectacles labors eastern spits', 'bc1qvh6yjclar0vsup9tkzuvd3m4l5cy3ewj4g4sh6', '0', '', 'user', '0', '0', '2025-05-18 09:40:36', 1, NULL),
(20, 'cheered quietness proviso quitting cuddly consigns atolls provisional expandable impugn refused printout devils countermeasures evidence motif-s blackmailers kill', 'bc1qef2ys24dnxjfpxm04cc29gs0c6y95d8rudsrsv', '0', '', 'user', '0', '0', '2025-05-18 09:40:36', 1, NULL),
(24, 'clip anticipates abreaction fielders insistence Schuster keenest automatically eunuchs rilly sandal naivete symposia sprayer bastion-s residue Prof coring', 'bc1qgr97sw5mjseytzc3g68xn8ldumhs6smcmhwp6n', '0', '', 'user', '0', '0', '2025-05-18 09:40:36', 1, NULL),
(34, 'removing jubilate Cheshire renew imprints convincers characteristically cursory cartilage survivor abstracted mammas Fizeau besotter Dostoevsky grata Wahl briefcases', 'bc1q60303vspahuewd94jsu694m70g2fecrdeathes', '0', '', 'user', '0', '0', '2025-05-18 09:40:36', 1, NULL),
(37, 'peddler-s besieged larval biomass lees profitably globularity conservation-s blunts notarizes gracefully Johns steadiest superegos Sequoia sufficiently interconnection-s treasonous', 'bc1qc56zpedrueat3j2jpxrq525pqmrctw79u27jt8', '0', '', 'user', '0', '0', '2025-05-18 09:40:36', 1, NULL),
(43, 'wart anodes disembowel actively distortion homers armpit ineffectual guerrillas diffuse transcended mull tunics dipole hexagonal maxim help pedagogic', 'bc1qw9878fja38zvwv888h9u45umt2fq4zvkx87p2f', '0', '', 'user', '0', '0', '2025-05-18 09:40:36', 1, NULL),
(44, 'deployed leper sloop bloodroot connotative Rhine tango basted apologies defective electrocuting outlandish purports venturi Claus bandwagon-s Bart Gabriel', 'bc1qkthusugn7a0gvm32qqwq4t7d5l2lnhv7flxufq', '0', '', 'user', '0', '0', '2025-05-18 09:40:36', 1, NULL),
(49, 'raincoat Dominique potentials enigma bean safeguards Monash mow tributary overflowing requiring subtly checkmate proselytizes gentlemanly demijohn mischievously bilks', 'bc1q5pd6nzk94adnyn4uzy9xred7wglvx0uxunw4z5', '0', '', 'user', '0', '0', '2025-05-18 09:40:36', 1, NULL),
(50, 'fantasist fitter finishing haberdashery doghouse blindness lymphoma step guardhouse Moslem grids culvert deplete cathedrals brutal starfish reaches highway', 'bc1qg4zfzedgss9h0ln2nyvpcrzmudv2nfurpjn6cv', '0', '', 'user', '0', '0', '2025-05-18 09:40:36', 1, NULL),
(54, 'applicability scuffles term ammo breastplate humorers Presbyterian gratifying cereal-s boyfriends republic-s translational alpha Borneo Winnetka encounter blackens alignment', 'bc1qc2hlefhr8c6l7qaqxcs7msrqlz93477gg3uz6c', '0', '', 'user', '0', '0', '2025-05-18 09:40:36', 1, NULL),
(57, 'grindstone muffs anomalous overlaps unkindness basses heroically natty handbag leeches illuminates hitchhikes Swarthmore moron inspires malcontent alkali matters', 'bc1q74tqxag0er5zvxh3q9g4q57r34xghy0205nrcx', '0', '', 'user', '0', '0', '2025-05-18 09:40:36', 1, NULL),
(69, 'zagging station internescine wearisome inhaling passageway layer redesign waves scanners subsidizing patronizing won improvements colander settlers cumulatively helmsman', 'bc1qjpvcwrn7kekew8mtk6nevz3h5e7xr7362g4dvc', '0', '', 'user', '0', '0', '2025-05-18 09:40:36', 1, NULL),
(72, 'craft fatality modules politicking maidenhair throwaway buyer-s contends cuts mired mantel popularity emblem Buchanan interfering distraction bendable unavoidable', 'bc1qm83gru2aay3nzhle07sulssglr37an2h6tz8hz', '0', '', 'user', '0', '0', '2025-05-18 09:40:36', 1, NULL),
(78, 'kenning displacement lashing briefness travesties deems Doge machine-s motorist-s stimulating fans stapler professionally infect defocus thrashes beading boners', 'bc1q3qhwzdt9gwps8z0vt0vmrw8h45e77hnemfdpsd', '0', '', 'user', '0', '0', '2025-05-18 09:40:36', 1, NULL),
(137, 'this byway handkerchiefs appreciable bolshevik-s starters builder manufacturer octile clashes doll-s valent invoice extensible Waldron immaculate trombone orphan', 'bc1qt8ukjj284jarx9awhdvt9p48t9u3vxl082ck5l', '0', '', 'user', '0', '0', '2025-05-18 09:40:36', 1, NULL),
(145, 'persecutes bicentennial Yorktown bookseller-s hostages Moslem publicized mezzanine pitfall-s garb adoptions jobs expeditiously acquiescent deliberates pyramids abductions tidied', 'bc1qr2c0ycrg2j4lytttw5greev668kcpf648w7xl0', '0', '', 'user', '0', '0', '2025-05-18 09:40:36', 1, NULL),
(177, 'discrimination zounds warmth dispersive manicures eradicating widening flatiron dequeues disposals aspheric doubleton basting Haines highfalutin dissuade immigrant carbon-s', 'bc1qxw0a595r7tkdtjc7jm0tren9j8qs4tfqswqzu7', '0', '', 'user', '0', '0', '2025-05-18 09:40:36', 1, NULL),
(183, 'seas bloats enumerators preassigns Berniece Dairylea deliberator-s disconnected twos laid striking Herr tamale facts stenches dedicated expose charmingly', 'bc1q2gwvl0fjxnp4qsw3227z66j8hf3gq6dvfau57f', '0', '', 'user', '0', '0', '2025-05-18 09:40:36', 1, NULL),
(185, 'wadi wherewithal acceptability stem greed Madeleine marginal e-s frames benefit adverbial Gaul climactic crazier torment cable pelting founded', 'bc1qe0s6gy2y5lt4rend46y2vnnxv6qfhapsgg3c9t', '0', '', 'user', '0', '0', '2025-05-18 09:40:36', 1, NULL),
(187, 'whistling polynomials whatever bibliography-s sawfly Dwyer strangle arises electron pleas cerium supermarkets dawn suppression tawdry host money anisotropy', 'bc1qaf26zcx9sa78gkxg8n5n2e8yxdzwhcctq7mmxd', '0', '', 'user', '0', '0', '2025-05-18 09:40:36', 1, NULL),
(189, 'carriages incompetent-s consign manipulability financing networks pushbutton tumbler mink molybdate cascading agricultural abiding dismaying conditioning realigns devotee Lionel', 'bc1qrw9hkptzvhgljraz5pql77em2wfn3vd5csewpt', '0', '', 'user', '0', '0', '2025-05-18 09:40:36', 1, NULL),
(199, 'orangeroot worming victimizer divine sensitiveness allegiance-s chandelier-s infantryman mangling revolutionary shibboleth despairing Portugal distribute suicidally tell Maggie pioneer', 'bc1qtkhaslxmuhhghfx9uz4e06mjhg4h9rxr6wfpf9', '0.00000000', '', 'user', '0', '0', '2025-05-18 09:40:36', 1, NULL),
(203, 'heterogeneity concealing wombs Yellowknife spud donates catlike olivine happens eddy-s artful sharper hang samplings Donahue ignorance anchorage-s offended', 'bc1q3wct85jjvkynqnctfkkkuwkfw37ead4kewjgr7', '0.00000000', '', 'user', '0', '0', '2025-05-18 09:40:36', 1, NULL),
(220, 'Bernard romancer perceptual rhodium protactinium actinometer liquifies Waring adjectival Pravda cabs intentness reactionary digressive atoll-s discerned atomics splitters', 'bc1qehr4e70lhefwk5ffhk3l5enufx5tdlg3zsfp80', '0.00000000', '', 'user', '0', '0', '2025-05-18 09:40:36', 1, NULL),
(228, 'lined freshly blackmailer robin-s emigrate Fedders medallion-s peeked carbons leathers Byzantine shrewd glycol cranberry-s aptitude same newcomers bate', 'bc1qc3aeypx299cs2596emdl2v8gvrqxwxdle3xtf9', '0.00000000', '', 'user', '0', '0', '2025-05-18 09:40:36', 1, NULL),
(229, 'Julio calligraphy shallot travail seduce broadened Nubia climate graveness monastic represent buffaloes guzzle trier reliableness spill translated boldly', 'bc1qspy78vv2fc98dll8v5ysjfcmp8627nsjvzqjpm', '0.00000000', '', 'user', '0', '0', '2025-05-18 09:40:36', 1, NULL),
(242, 'balked giant captives unavailable disabling relocation altruist mother-s elderly joints condition campaigner arraign tamp reincarnated beckoned chronic sphere', 'bc1q3h3vpccpna86hvyy997vex5qlcmnnmja4ala5q', '0.00000000', '', 'user', '0', '0', '2025-05-18 09:40:36', 1, NULL),
(249, 'biological similarity bum-s reviler canaries kingdoms appellate vagaries doctor allure amazon detent devours corrects rudely flashlight-s headlined Martinique', 'bc1qhkrjgk3e2hagwydvxtfhzc6nehzwy62ecgu7u3', '0.00000000', NULL, 'user', '0014bd87245a3955fa8711ac32d3716353cdc4e26959', '0', '2025-05-18 09:40:36', 1, NULL),
(250, 'inhomogeneity Iran disperse engendering extoller pluton erecting bloodhound amphetamines improvisation-s delirium ash shuffled monopoly-s bordering movable blowback Mellon', 'bc1qcmkl0f4y27ujxvdmfnesd53m4ahe2vdzq0sagy', '0.00000000', NULL, 'user', '0014c6edf7a6a457b92331bb4cf306d23baf6f9531a2', 'L4mGY9v9NWiaWZT7fjg3jxizTyeS6zMDP2nCgQk5HU5sNAZFaLGD', '2025-05-18 09:40:36', 1, NULL),
(266, 'gateway-s reassessment syndic overwrite caches Ophiuchus mangle climates dysentery anticipates Dudley commodores once unlabeled destined sourest farms disclosure', 'bc1qqtkcnlntecmhpqk7nftzjzz8jjv7upsvw4dyp3', '0.00000000', NULL, 'user', '', 'L5ZyiEUzhGKKcAGkbaTuoFcK8nWGyxke5CzowKQxLpztRkzLDC6x', '2025-05-18 09:40:36', 1, NULL),
(276, 'breading Drummond Netherlands puzzling PepsiCo diabetic searching cowman finances proceed buried saves chafe acceptably cargo answerer oligopoly foundry-s', 'bc1qx67zzwy7nc6zl3fqes9m9vfkwgendq2rctg5r7', '0.00000000', NULL, 'user', '', 'L1swdNt5dnxQU8yK5Tr2YJaTbwz8uN5q5UBY3YkieNmo9CANcTis', '2025-05-18 09:40:36', 1, NULL),
(282, 'traffics conception nobody-d flees sample attendees shapelessness enhancement partner helicopter pressurized indictment-s federal strolled unrest software-s advisement hedge', 'bc1qethx6xhvy9mtg982st4pfdkjml3k97pf9fjrtm', '0.00000000', NULL, 'user', '', 'KxfPRRE9M2baasLANniuY3kTs5By6tPHVBxdx2TcuHo1uGEF93iw', '2025-05-18 09:40:36', 1, NULL),
(284, 'chortle recur washing fortran boldest peep transplantation imp conflicted untouchables merging colons ashamedly carbonize selector-s faster auctioneers deluding', 'bc1q0x9fjk6qt3xalg8c8e08lmgk3vepdxzrh9arex', '0.00000000', NULL, 'user', '', 'KydvZ9xHL949tQ7FzV52hjNtnHmfA1G7wmpfaTrJnrWUfAt3Kkw1', '2025-05-18 09:40:36', 1, NULL),
(293, 'cranberry-s floodgate RCA represents oilseed outlast hothouse powders Iroquois gorge Garvey adulterated disproves molecule-s postcard calculator maritime inanimate', 'bc1qqs3egyd4m60v0t8a3xyz87txpkvulp4e8ucv8d', '0.00000000', NULL, 'user', '', 'L3gUU9NSckK8g8LbLsZTAY5HZ9ZkH8qQmww9gz2L2huKnV62PB2R', '2025-05-18 09:40:36', 1, NULL),
(303, 'escapades failures butyric preciously imprisonment electrical representationally chieftain perpetuation apiary where irritate sanely downdraft eighth Brahmsian trampler stroker', 'bc1q3qv5r5rlurhz5pxddy8a7wwav7ecmnpfc59qzr', '0.00000000', NULL, 'user', '', 'L5LQvjPbmMTaNrvdwSfqgAg25rpja2rDGohstuCVuCxCD8xCkk9o', '2025-05-18 09:40:36', 1, NULL),
(304, 'open aimless economized gained Huxtable seize lovelies vigor Laplace unit chestnuts rook characterizing parliamentarian jests dilating Durham volunteered', 'bc1qsp0wt2y3fjrvgue72eusmlq0frg8m2td7s39z6', '0.00000000', NULL, 'user', '', 'L43gHyozTYWkFrGZRXziBGZUx91v6VUGSKvvdd45epKDSoqRKKVz', '2025-05-18 09:40:36', 1, NULL),
(321, 'clangs Bantu aromas Castro edicts owners icings refrigerator bacillus contingencies thirds in advent phonic hydrophilic disdaining Abidjan Formosa', 'bc1qdpl75z4mx7f92sz7gkj7daavwd4aekuhe9ma9h', '0.00000000', NULL, 'user', '', 'L1747L2PqJhuZ9rQe4o3dxQtsQnijmrEb1U9e7Ypa2uV4FQqXNoZ', '2025-05-18 09:40:36', 1, NULL),
(342, 'permissible definitive Ernest kitchen administrable crests dynamo precise spate-s woodwind pulsing riot ash lacewing confessors oracular Creole parboil', 'bc1qxc6ez37y2fsg6wm49q5xwzx620vhkg2we789jk', '0.00000000', NULL, 'user', '', 'L32rpY945AzqdwUFduK5PuVcEk3b8BzBUG4TMmTW468nKJNoaxdc', '2025-05-18 09:40:36', 1, NULL),
(344, 'presupposes retire revenues disqualification centering saturnine stratospheric tumults extracellular snappily niece Sparkman fireboat initialization gun-s diagrammers microcomputer governing', 'bc1qt7lce0anv22anzx2sm980rxqa4axnzsalrse57', '0.00000000', NULL, 'user', '', 'L2eQQLoCSw8jh7yFgxaS319yaPhQ3HGvJNWTNTCXX9abZfbWaUjE', '2025-05-18 09:40:36', 1, NULL),
(360, 'commoner-s Maine avariciously remark odor-s motionlessness applicator birthplace mineralogy cottonmouth calamitous modularization detect pessimist Greg stereotypes ration entrust', 'bc1qjccut6zh8vg4lwk4dmsaht53uyxw9hmhu8cl8q', '0.00000000', NULL, 'user', '', 'L3bz5ybwxWoypgEvBkCdyowH5H9aqW1u8th8mLEZKdTJSATCjBTg', '2025-05-18 09:40:36', 1, NULL),
(361, 'bettered collapsible consonantal terrorizes infringements Waterhouse palmetto Clausen antiquarian-s conducive irreverent seasons roper decisionmake barbarously greases results arcana', 'bc1qxl7tqlcm3ju9l902f0zkffw4x03j73gwpkqrav', '0.00000000', NULL, 'user', '', 'L1WM6CTi8kMGZbf9zxp5p63q7pBXuxqgHX6T8RoZnXLkgPnRTVkJ', '2025-05-18 09:40:36', 1, NULL),
(403, 'Inman spices crepe cannister Diego housework busboy Otto edition-s kneels l-oeil ferry electrolysis doer horsewoman cybernetics DuPont topocentric', 'bc1qhvh8q4d49u8g94ngq280qjd2sdr4p64ygzdg3q', '0.00000000', NULL, 'user', '', 'L1SovaTMNmKuFvuzTnMwNh8dq3ec7iafNMSqoWvhJALqyrSNuWF9', '2025-05-18 09:40:36', 1, NULL),
(415, 'unfortunates tragedian saturater herrings surroundings Courtney apparition-s shadily chorale superposed choices overwork Cessna purporting overnight meditative sisterly maturity', 'bc1qkmqydznhdfz3zw7sv4gtx74e0lxtyhvw8xegcc', '0.00000000', NULL, 'user', '', 'L4jBA5fQNoTgZjaucUg3k3zUYz4Dc7WtPk789QqVCQQDSQSVd17e', '2025-05-18 09:40:36', 1, NULL),
(423, 'establishments blond-s gravestone neighborhood-s humped suite studio-s primitivism starting needle rostrum confess widowed ironings Stacy sensation mate-s intentions', 'bc1qu4dgujpr3g99nm970zs5ys0r880hwhu9m8vx6j', '0.00000000', NULL, 'user', '', 'KzBBmTcHxhMk262C9dtSi73L2x3XZFQU2Hyxsx3KWjt3Sv884bqg', '2025-05-18 09:40:36', 1, NULL),
(430, 'meditate resume apartment wrote serviceberry boston Vanderbilt commended epitomizing liquifying insidiously wrinkled subscribing cocking southerners baffle lifters lava', 'bc1qympgxhzm98ks66q84jxas0wuwy5j624u4cuesx', '0.00000000', NULL, 'user', '', 'KxoaTEC5K4mECDyn1wmWk5Z1qSdc23m8GaytaW393cmzxYGkz3LS', '2025-05-18 09:40:36', 1, NULL),
(431, 'Casey irregularities tar twitched Zorn Purcell clamming unblocks seasoned promoting concealers flops pages mentalities actuarial transplants precursor-s looker', 'bc1q20cstul34ecnrrwzuazgph8ca0rd9kcdrmahvr', '0.00000000', NULL, 'user', '', 'KxiaSg4PCMq4JHnUGs8oMAyyKYRFCRREQMPn5eNcrsLH8UZ6cBea', '2025-05-18 15:55:40', 1, NULL),
(434, 'barest inheritor-s Vreeland lowness reactor Bender midweek urinary ships department-s distiller emasculate magazine potentate-s gladdy Eloise polarography oilseed', 'bc1qyc2maygk8hvmavfwysdgst5hjzs7khznnfhkgz', '0.00000000', NULL, 'user', '', 'L3wbLW3zUHj2dLiNQY4FmgRvBat6DXd5jZGJh6ZA2YjDn73QEpkj', '2025-05-18 21:11:13', 1, NULL),
(438, 'equilibriums multiplying leeches convocate suitor ballrooms renditions irrepressible figaro tirelessness newcomer encyclopedias bachelors Debra majesties undetermined germinating epoch', 'bc1qdsr89cfsk0w7zy38aueczwuhasqtcfmh3t58la', '0.00000000', NULL, 'user', '', 'Kx7h1Q43UFVDFdgJbTWiRLCxYixjyC6WGh3guyGew693gFBZnwgD', '2025-05-19 14:04:22', 1, NULL),
(439, 'January ludicrous hotly Cunningham journalist ballplayer-s Olga interleaved reeds kraft martyrdom admonishment logics abbreviating vex coffin anaerobic coquina', 'bc1qrlwpx7l40gn3xynxc58t03t27jmcchuqmm5pck', '0.00000000', NULL, 'user', '', 'L2xyrfpwcQfGvc26njsHB7tXhGZLjvEdd718dH42Bn4YEHh5AQY6', '2025-05-19 14:10:55', 1, NULL),
(440, 'forbidding tensile bristle reentering humanely deemed dualities objectivity involve baritones scalings raiser otherworld Bradford suited Pusan dwelt lashed', 'bc1qjy3r6mf3e7489zjkve35pza07nj9nadjzgt0s3', '0.00000000', NULL, 'user', '', 'KwdW9cQ7CodcHk8PuiVEFVJreXuSM94wK6VSKP4PRRXGwxLidES4', '2025-05-19 15:24:56', 1, NULL),
(442, 'satisfaction hysteria hesitation Mindanao deserted signer sibyl blamer trackage entrusted polynomial detracts outcry Nobel fostered merged meningitis dabbling', 'bc1qyd7pw2cf3d90n6ms9k0xq8wedzxkfgp8xap7lu', '0.00000000', NULL, 'user', '', 'KzWJir4dkH336dwQU33DWemBNPYTLWy2XD6jXSNEqKrZ9uDhzgaB', '2025-05-20 08:08:28', 1, NULL),
(451, 'Israel infest prorate backing turnabout accessible imitate nomograph mulct pathetic conservatism bankrupted rhombus minim Marsha expressing Collins disallows', 'bc1q65wgk9f6gr3a0lewukkavy89wzmv2ax03gnxwz', '0.00000000', NULL, 'user', '', 'KwFqUFUDRVZ97HDyfcwYHjkd7KmmkzSpvXLoMP6wt1EPaEYGhhqi', '2025-05-22 01:09:46', 1, NULL),
(454, 'Sagittarius progression warm tree requirement anthropomorphism hate maggots beaned descent-s danced insensitivity lists gothic hoopla Ku corrupt unanticipated', 'bc1q7zc4ds6yakamf2z20jg37ylrg4pv3wuwc2d2ks', '0.00000000', NULL, 'user', '', 'KyNQyW8ZANSSr3q2smJEVLy151xw2mj91Qcy4BdknhFMbGf2Ffrt', '2025-05-22 17:20:49', 1, NULL),
(455, 'dope lean strangulation-s cornfield-s heritages byproduct nightmare collect collapses shoal-s execrable irrelevances TNT allegro wintered lariat tyrannic throbbing', 'bc1qraeku5jy5nfd8l2zj864t3lgpgfgvkspyvqmtd', '0.00000000', NULL, 'user', '', 'L51AuALQY8WyrvpfEJqSuZb2rMRvF8o9ScTy2Sd2DWcvobn2amAd', '2025-05-22 17:21:13', 1, NULL),
(456, 'caiman legion-s crusading statistic diffuser grandson anchovies radioing chutney befit childlike followed twitter burglarizing coastal gyroscope channelled unlocks', 'bc1qylh0n4x7lkyz7vpv8utfww5glm29g62g5jn3m8', '0.00000000', NULL, 'user', '', 'L3UPXtq2rAMUQmFwpffsWtrTook1GthxCeypmpjBd95axL4ixZ2J', '2025-05-22 18:39:07', 1, NULL),
(471, 'sickly dickey lovebird Amerada symphonies emitting bestowal Bennington vellum spiraled erects cramp-s resinlike Cologne researched Bantu coughs hertz', 'bc1qz6pmjpp8464cmpknx4dv7vvxqfyqlnmhg9wcl5', '0.00000000', NULL, 'user', '', 'L2V5PqSnH9rXBiiVxhFrv3Ro4AmYtP1QnNgdUkA3Tqg6GfGVXT9x', '2025-05-25 17:15:20', 1, NULL),
(474, 'tensing divides encourages snorkel disclaim attenuator-s Nair chloride narrowest bristle funeral-s dissemination hoc evading mounter referent caches chit', 'bc1q8epxzmta6l9pv75hltnqz9ffxu8x5e4c6nsez2', '0.00000000', NULL, 'user', '', 'L2WLEGrrzidCsjyey3xuydvAVAGv8Wbf3jcMPKmeuW6o5fMN1vAt', '2025-05-26 20:34:33', 1, '20eaa367bd194f893ffb87e4cb5ffe5a9650a127504fbdf9725bc3548cf8e051'),
(477, 'bleak cavern abuilding captivity lisps resents featuring searchers demur sharper protein faker punting stems acclimatization catnip abandonment hire', 'bc1qvp8eft2gh9h8nhgla498d3qsnrzyc8k57rsmq6', '0.00000000', NULL, 'user', '0', 'L3vA7kvaXJhuHzKHn2WBeaFK3fdWuUvDw8h6XeZvZynamWXhYQKc', '2025-05-27 05:34:34', 1, '7a93b5cf3f55cb5037a01efda8274b6f2882b10f21a1e6f2782894cbadb9ba65'),
(478, 'theological confusions bakes drier IR affianced tree-s conjure singularity-s diaries silicone thiocyanate adventitious compilation deeper caskets emigrant-s grins', 'bc1q3v0jp0nl3lkvpxmzrml0daxpc5w9zahe9pwauc', '0.00000000', NULL, 'user', '0', 'Kz6zvuRLZpUY27bjsGNhcbeP7v948p5F5H2hBc9jHXewKEHTGmjE', '2025-05-27 08:34:46', 1, '74542846f09074f3f323c3c14ea9ee325569f5749a414efeff31f959fb74d62a'),
(479, 'heating research flexibility reactive receives rout inconclusive comfort babel-s gooseberry vanquishes hemlock consulted weakens rawboned fastest humming introit', 'bc1q53eqrkqg4az9e2qfdsjdwk5rh7qp2swrfrrxam', '0.00000000', NULL, 'user', '0', 'L2MKZWsPahVA6mjAtJQ9mxi4AHeJ5qeudYEDyyYozPa1H3Z4enbX', '2025-05-27 08:37:38', 1, '266ab0438f0fe636c81d568f35aad85171b828c9947a2c42c27af71ec8e33c1e'),
(480, 'antiquity whopping corporacy subtitle smuggles afferent Payne pa cowerers campaigned bulk ejecting ranker colloidal evocable Eccles yeast allegedly', 'bc1qmx0zueu7nw97zx0le7d8j66gvvpay99ehfkr6p', '0.00000000', NULL, 'user', '0', 'L3vTFzUbrZeze6ghBjkJAEyEtTbWNtot2Rorr7US3MrJhHr4K3De', '2025-05-27 08:39:40', 1, '7f300127163a759943db2c6370d81bba092e86615dd4553623fb7d3e3469d31b'),
(863, 'manse evoking inconsistency till schools disputes yardage aisle churchgoing peregrine jeopard purgative snowshoe-s cobbler-s Cornelia muddle exclaim Osborne', 'bc1qdcta29eu4c4wnhd7r6r373ys9j2wace234e6z8', NULL, NULL, 'user', '0', 'L4atKda6QFRqFKh72oGrJbYAxLz1WHhdPrDARMUiE7h7Qs41uoZM', '2025-05-28 07:56:46', 1, 'dd71f96235db803558eb1cfabdf0a7a74101978a415ceb3d041b71687a6ef77d'),
(864, 'pinnate separator-s reliquary waistcoat threats fonts anchovies discernible neoconservative confiscatory catabolic cremations Ephesian arabian subversive dazzle reiterate typhoon', 'bc1qqagnajgr33uktt0ezr9u7zcv9amsfwpwwktzve', NULL, NULL, 'user', '0', 'L2GQqEGnfisoAHDgC82e6HhGNwAty2TNSHuoP3QPMsi2T6xQFfBa', '2025-05-28 09:29:45', 1, 'bd93ad7fc606e7ded74839f664eb5ae3c2095ead64a12295624233b4a077fe37'),
(865, 'dearly whiten bolt stung pointers alkalis allotted stomaches embank riboflavin assertions Hubert Werther leaked Frazier amuser spheric ionic', 'bc1qrjy0z6tljs2yng77ncklzgep6k3esk9njcyphl', NULL, NULL, 'user', '0', 'L4Cvjq4yHPapPgSQdpmoU15FsU6axTtXvazTuMLwhyW8MDXBGG81', '2025-05-28 10:16:03', 1, '6424cba366f24e319bff7e1035537f2859c04867ee71069ca768dc4b68f6d236'),
(866, 'ballparks Gibraltar jilt con associate electrons compiler does Volkswagen rusticated probate asset-s combinators absurdly strip-s purporter resoluteness gimmicks', 'bc1qn7h0rpqx9cxc0cdev5qd8tcdla6ahml47tvg4m', '0.00000000', NULL, 'user', '0', 'KxpBhsXQrpzXH9qXj5M2CtNy3QWqVQxdn4ZYnjzGjHjmAEhNY1xD', '2025-05-28 18:40:28', 1, '3fe25af961736965238f5747038db749bb41a7b9a84b8f4a4b4a1c467449b2bd'),
(867, 'substrate shred-s go consecrate Kong autocorrelation indians urinating rises hexachloride Pasteur uproar result birthplace Connors ancients hacker southeastern', 'bc1qqt598u8dzrzljg6g42kg06hx5dn9u6xf8pg8fl', '0.00000000', NULL, 'user', '0', 'L4r1A9V7h9esXoLevEq4kmQ3n3S3sH8cBkAJ3BGNowBYGRYhtLwV', '2025-05-28 19:10:19', 1, '53f0304e3a167b4c6ae69e228c5f09121566592b87ae95ef5dd08dfa55c6b752'),
(868, 'critic stingy recently negatives habitant planers acquainted sated clockings microinstruction illustration sloppiness Saturday wholesaler howsomever corroborate demodulate croquet', 'bc1qmsfsndmwm42htvl54rs9rt4jdxvqu8kj0hvx26', NULL, NULL, 'user', '0', 'L1YjL5q5JfWdAwNiZZQ2NoCHSFzQ6ERLCrmC4i39ncFvS7ARWuje', '2025-05-28 20:18:22', 1, 'b2576d518aac90e00234657fbeefeb915ede34089396c91796685303a6923462'),
(869, 'quart coil abominate wheelchair tackle hauls pallid racers Palatine gear cabinets branched frieze-s quaternary cube meridional reasonableness rend', 'bc1qgc4valfx9ec3f2qa3jd2wm4uv5cdaux3n63d9d', NULL, NULL, 'user', '0', 'KwmdcuDVyQCiAVH3bmMZ7Wcfq2xo6t28hXtC5nADrb72QvkcJg8F', '2025-05-28 21:32:55', 1, 'a43e54dc3a64f0b230126d9225368f56432be5f414cd4fea38881f0e3f3b92bc'),
(870, 'septate chairs loudspeakers mender patriotism bandage era-s drinkable quint minimax hell-s impenetrably automatic totality-s include hasty conceiving wretchedness', 'bc1q0j4ujel6uzr4kaww3yst7rkd0tru5z876r0vm7', '0.00000000', NULL, 'user', '0', 'KxggmwFXMUXivbZahDDYL9krJH1kfcDL1vdKkUgs7Wc5NJ1RyFn7', '2025-05-29 00:56:46', 1, 'bf4013e2949a2fc9b65dd2fd19940f30b5107f8b77fcb08ccba226800c696eed'),
(871, 'eradication anvils conception cell descendant windsurf remotely sourer satiety feigning artichoke-s accompany maxwell candid trailed Fletcher retrogress solid', 'bc1q6v6srh9vkx923zmkyp9uq42r9xncteulgc62wn', '0.00000000', NULL, 'user', '0', 'L3v5tX3UbDVHzCPbC7HLB9H7GxDvkFF6Rm8zsbghAPKmNotdHXow', '2025-05-29 00:58:04', 1, '5c57985d672188b9548641ca1c2b24e15c34b918747d3f1874064e11b20dd6e6'),
(872, 'minutiae seaside distributive heaver egotist domino transistors rooted locally committees idealization merriment contentment resumptions cropped Jacobi implementations congressman', 'bc1qd0wn6jv86xepxg4ayckthru5xja7z4hh3560nl', NULL, NULL, 'user', '0', 'KwGgreSYhzGWV3ssyGcsAWqDmXfxGN73MpPuiVqQJFBgtyp7K3dT', '2025-05-29 09:24:20', 1, 'f4a240f70138eb8b48458fe9663e4b7041d137e1c85df33e0087579adcecd510'),
(873, 'eminent indoctrinated diagonally equalizers re suffixed chocolate rhetorician immune fatter drifting oughtn-t impinges thinkably assaying unofficially preliminary Blackwell', 'bc1q58u48csq0lma2daj79nx8z6ev20fp07j0ksdhu', NULL, NULL, 'user', '0', 'KwyBxkXreJoznfmqirXRBrX8F3rtuz7U3KSXqkG9Ao2jCGD3tAog', '2025-05-29 09:24:21', 1, '87f3c0279af830496a7dc0cbe13a7bfaeba00730c25594fe2c9d16a88819a227');

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
-- Структура таблицы `settings`
--

CREATE TABLE `settings` (
  `id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `value` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `settings`
--

INSERT INTO `settings` (`id`, `name`, `value`) VALUES
(1, 'escrow_wallet_address', 'bc1qvy6srmp48mmma55k7tc6ahhva7f43vskep5ll0'),
(2, 'service_fee_address', 'bc1qq00pgwy3mleht2ts3yz99k5u7zr76fylek9sad'),
(3, 'message', 'Dear users! We are pleased to inform you that the Bitcoin turnover threshold of more than 500 BTC has been overcome. In this regard, we are announcing a competition: any user who makes the maximum transaction through our service will receive 0.1 BTC. The promotion period is until 30.06.2025. We wish you all good luck!\r\nAt the moment, the user who has made a transaction in the amount of 0.01312500 btc is in the lead.'),
(4, 'message_display', '1'),
(5, 'maintenance_mode', 'off'),
(6, 'message_ru', 'Уважаемые пользователи! Мы рады сообщить вам, что порог оборота биткоинов в размере более 500 BTC преодолен. В связи с этим мы объявляем конкурс: любой пользователь, совершивший максимальную транзакцию через наш сервис, получит 0,1 BTC. Срок действия акции - до 30.06.2025. Желаем всем вам удачи!\r\nНа данный момент лидирует пользователь, совершивший транзакцию на сумму 0,01312500 btc.');

-- --------------------------------------------------------

--
-- Структура таблицы `support_requests`
--

CREATE TABLE `support_requests` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `response` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `notified` tinyint(1) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `support_requests`
--

INSERT INTO `support_requests` (`id`, `user_id`, `message`, `response`, `created_at`, `notified`) VALUES
(14, 24, 'How does it work, how much do I need to deposit?', 'Hello. There are no minimum restrictions, as well as maximum ones. For any outgoing transaction, the system charges a 1% commission.', '2025-02-17 10:42:55', 1),
(30, 303, 'how can i add btc on my wallet', 'Hello dear user. In order to replenish your wallet, you need to make a transfer to your address, which can be seen in the dashboard section. You can also use the QR code for the transfer.', '2025-04-17 01:35:34', 1),
(32, 7, 'Привет.', 'Привет. не очкуй', '2025-05-25 19:11:29', 1);

-- --------------------------------------------------------

--
-- Структура таблицы `visit_counter`
--

CREATE TABLE `visit_counter` (
  `page` varchar(50) NOT NULL,
  `visit_date` date NOT NULL,
  `count` int NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `visit_counter`
--

INSERT INTO `visit_counter` (`page`, `visit_date`, `count`) VALUES
('total', '2025-05-12', 6316),
('total', '2025-05-17', 169),
('total', '2025-05-18', 2185),
('total', '2025-05-19', 2169),
('total', '2025-05-20', 2937),
('total', '2025-05-21', 3038),
('total', '2025-05-22', 2903),
('total', '2025-05-23', 1777),
('total', '2025-05-24', 1319),
('total', '2025-05-25', 2083),
('total', '2025-05-26', 1436),
('total', '2025-05-27', 2457),
('total', '2025-05-28', 3636),
('total', '2025-05-29', 1942);

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
-- Индексы таблицы `btc_notifications`
--
ALTER TABLE `btc_notifications`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `escrow_deposits`
--
ALTER TABLE `escrow_deposits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ad_id` (`ad_id`);

--
-- Индексы таблицы `escrow_signatures`
--
ALTER TABLE `escrow_signatures`
  ADD PRIMARY KEY (`id`),
  ADD KEY `escrow_id` (`escrow_id`);

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
  ADD PRIMARY KEY (`id`),
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
-- Индексы таблицы `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name_unique` (`name`);

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
  ADD PRIMARY KEY (`page`,`visit_date`),
  ADD UNIQUE KEY `unique_page_date` (`page`,`visit_date`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `ads`
--
ALTER TABLE `ads`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT для таблицы `btc_notifications`
--
ALTER TABLE `btc_notifications`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT для таблицы `escrow_deposits`
--
ALTER TABLE `escrow_deposits`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `escrow_signatures`
--
ALTER TABLE `escrow_signatures`
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
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=874;

--
-- AUTO_INCREMENT для таблицы `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=146;

--
-- AUTO_INCREMENT для таблицы `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=81;

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
-- AUTO_INCREMENT для таблицы `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT для таблицы `support_requests`
--
ALTER TABLE `support_requests`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `escrow_deposits`
--
ALTER TABLE `escrow_deposits`
  ADD CONSTRAINT `escrow_deposits_ibfk_1` FOREIGN KEY (`ad_id`) REFERENCES `ads` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `support_requests`
--
ALTER TABLE `support_requests`
  ADD CONSTRAINT `support_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `members` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
