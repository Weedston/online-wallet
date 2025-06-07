<?php
$lang = $_GET['lang'] ?? 'en';

$locale = [
    'ru' => [
        'title' => 'Анонимный BTC Кошелёк',
        'description' => 'Простой и анонимный Bitcoin-кошелёк без регистрации и KYC. Требует интернет-соединения для отправки и получения транзакций через наш API-сервер.',
        'download' => 'Скачать APK',
        'screenshots' => 'Скриншоты',
        'features' => 'Возможности',
		'compatibility' => 'Совместимо с Android 10 и выше',
        'feature_list' => [
            'Без регистрации и логинов',
            'Анонимное подключение к API-серверу',
            'Хранение сид-фразы локально на устройстве',
            'PIN-код для защиты доступа',
            'Push-уведомления о входящих переводах',
            'Поддержка сканирования QR-кодов адресов',
            'Простой и быстрый интерфейс',
        ],
    ],
    'en' => [
        'title' => 'Anonymous BTC Wallet',
        'description' => 'A simple and anonymous Bitcoin wallet with no registration or KYC. Requires internet connection to interact with our API server for transactions.',
        'download' => 'Download APK',
        'screenshots' => 'Screenshots',
        'features' => 'Features',
		'compatibility' => 'Compatible with Android 10 and higher',
        'feature_list' => [
            'No registration or login',
            'Anonymous connection to API server',
            'Seed phrase stored locally on device',
            'PIN code protection',
            'Push notifications for incoming funds',
            'QR code scanning for BTC addresses',
            'Clean and fast interface',
        ],
    ]
];
$t = $locale[$lang];

$apkFileName = 'anonbtcapk.apk';
$stmt = $CONNECT->prepare("SELECT COUNT(*) as total FROM downloads");
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$downloadCount = $row['total'] ?? 0;

$t = $locale[$lang];
?>

<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $t['title'] ?></title>
    <style>
        body {
            font-family: "Segoe UI", sans-serif;
            margin: 0;
            background-color: #121212;
            color: #f5f5f5;
        }
        header {
            background-color: #ff6f00;
            padding: 1rem;
            text-align: center;
        }
        h1 {
            margin: 0;
            font-size: 2rem;
        }
        .container {
            max-width: 900px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        .description {
            font-size: 1.2rem;
            margin-bottom: 2rem;
        }
        .download-btn {
            background-color: #ff9800;
            color: #000;
            padding: 0.75rem 1.5rem;
            font-size: 1rem;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            display: inline-block;
            margin-bottom: 2rem;
        }
        .features {
            background-color: #1e1e1e;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 2rem;
        }
        .features ul {
            padding-left: 1.5rem;
        }
        .screenshots {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            justify-content: center;
        }
        .screenshots img {
            max-width: 250px;
            border-radius: 10px;
            border: 1px solid #444;
        }
        footer {
            text-align: center;
            margin: 2rem 0;
            font-size: 0.9rem;
            color: #777;
        }
        .lang-switch {
            text-align: right;
            margin-top: -2rem;
            margin-right: 1rem;
        }
        .lang-switch a {
            color: #f5f5f5;
            text-decoration: underline;
            margin-left: 1rem;
        }
		.compatibility {
			font-size: 1rem;
			color: #ccc;
			margin-top: -1rem;
			margin-bottom: 1.5rem;
			display: flex;
			align-items: center;
			gap: 0.5rem;
		}

		.android-icon {
			width: 24px;
			height: 24px;
		}

		.download-button {
			display: inline-block;
			padding: 12px 24px;
			font-size: 16px;
			font-weight: bold;
			color: #fff;
			background-color: #cc5500; /* тёмно-оранжевый */
			border: none;
			border-radius: 8px;
			text-decoration: none;
			transition: background-color 0.3s ease, box-shadow 0.3s ease;
			margin-top: 20px;
		}

		.download-button:hover {
			background-color: #ff6600; /* ярче при наведении */
			box-shadow: 0 0 10px rgba(255, 102, 0, 0.6);
		}

    </style>
</head>
<body>
    <header>
        <h1><?= $t['title'] ?></h1>
    </header>

    <div class="lang-switch">
        <a href="?lang=ru">🇷🇺 Рус</a> | <a href="?lang=en">🇺🇸 Eng</a>
    </div>

    <div class="container">
        <p class="description"><?= $t['description'] ?></p>
		<p class="compatibility">
			<img src="/images/android.png" alt="Android" class="android-icon">
			<?= $t['compatibility'] ?>
		</p>


        <a href="#" class="download-button" onclick="startDownload()">
			<?= $t['download'] ?>
		</a><br>
		<p style="font-size: 0.85rem; color: #aaa; margin-top: 1rem;">
			SHA-256: <code style="word-break: break-all; background-color: #1e1e1e; padding: 4px 8px; border-radius: 6px; display: inline-block;">
			d1ef768e518e6c1f176d1a1d3896fa3714bc4852656df6cb37039c9742fead70
    </code>
</p>
<p style="font-size: 0.85rem; color: #aaa;">
    <?= ($lang === 'ru') ? 'Скачиваний:' : 'Downloads:' ?> <strong><?= number_format($downloadCount) + 500?></strong>
</p>

				<script>
			function startDownload() {
				window.location.href = "/download";
				setTimeout(function() {
					window.location.href = "/anonbtcapk";
				}, 2000);
			}
		</script>

        <div class="features">
            <h2><?= $t['features'] ?></h2>
            <ul>
                <?php foreach ($t['feature_list'] as $feature): ?>
                    <li><?= $feature ?></li>
                <?php endforeach; ?>
            </ul>
        </div>

        <h2><?= $t['screenshots'] ?></h2>
        <div class="screenshots">
            <img src="/images/1.jpg" alt="Screenshot 1">
            <img src="/images/2.jpg" alt="Screenshot 2">
            <img src="/images/3.jpg" alt="Screenshot 3">
        </div>

        <footer>
            &copy; <?= date("Y") ?> Anonymous BTC Wallet | <a href="/privacy" style="color: #999;">Privacy Policy</a>
        </footer>
    </div>
</body>
</html>
