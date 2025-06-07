<?php
$locale = $_GET['lang'] ?? 'en';

$translations = [
    'ru' => [
        'title' => 'Политика конфиденциальности',
        'last_updated' => 'Последнее обновление: 5 июня 2025 г.',
        'intro' => 'Мы уважаем вашу конфиденциальность и обязуемся защищать личные данные пользователей.',
        'info_title' => '1. Собираемая информация',
        'info' => 'Приложение не требует регистрации и не запрашивает доступ к личным данным. Мы можем собирать технические данные об устройстве для улучшения работы приложения.',
        'usage_title' => '2. Использование данных',
        'usage' => 'Собранные данные используются только для анализа работы приложения и не передаются третьим лицам.',
        'security_title' => '3. Безопасность',
        'security' => 'Сид-фразы и ключи не покидают ваше устройство. Мы не храним и не контролируем ваши BTC-активы.',
        'logs_title' => '4. Файлы журналов',
        'logs' => 'Анонимные отчёты о сбоях могут быть отправлены нам только при согласии пользователя.',
        'third_title' => '5. Сторонние сервисы',
        'third' => 'Приложение не использует рекламные SDK или трекеры.',
        'changes_title' => '6. Изменения в политике',
        'changes' => 'Мы можем обновлять политику, изменения публикуются на этой странице.',
        'contact_title' => '7. Контакты',
        'contact' => 'Если у вас есть вопросы, свяжитесь с нами в личном кабинете пользователя.',
        'consent_title' => '8. Согласие',
        'consent' => 'Устанавливая приложение, вы соглашаетесь с этой политикой конфиденциальности.',
    ],
    'en' => [
        'title' => 'Privacy Policy',
        'last_updated' => 'Last updated: June 5, 2025',
        'intro' => 'We respect your privacy and are committed to protecting user data.',
        'info_title' => '1. Collected Information',
        'info' => 'The app does not require registration or access to personal data. We may collect technical data for improving app performance.',
        'usage_title' => '2. Use of Data',
        'usage' => 'Collected data is used only for analytics and is not shared with third parties.',
        'security_title' => '3. Security',
        'security' => 'Seed phrases and keys stay on your device. We do not store or control your BTC assets.',
        'logs_title' => '4. Log Files',
        'logs' => 'Crash reports may be sent anonymously only with user consent.',
        'third_title' => '5. Third-party Services',
        'third' => 'The app does not use advertising SDKs or trackers.',
        'changes_title' => '6. Policy Changes',
        'changes' => 'We may update this policy. Changes will be posted here.',
        'contact_title' => '7. Contact',
        'contact' => "If you have any questions, please contact us through your personal account.",
        'consent_title' => '8. Consent',
        'consent' => 'By installing the app, you agree to this privacy policy.',
    ],
];
$t = $translations[$locale] ?? $translations['ru'];
?>
<!DOCTYPE html>
<html lang="<?= $locale ?>">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($t['title']) ?></title>
    <style>
        body {
            background-color: #1e1e1e;
            color: #ffa500;
            font-family: Arial, sans-serif;
            padding: 20px;
            line-height: 1.6;
        }
        .container {
            max-width: 900px;
            margin: auto;
            background-color: #2b2b2b;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px #000;
        }
        h1, h2 {
            color: #ff8c00;
        }
        a.lang {
            color: #ffffff;
            margin-right: 15px;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <div style="text-align: right;">
            <a class="lang" href="?lang=ru">RU</a>
            <a class="lang" href="?lang=en">EN</a>
        </div>

        <h1><?= htmlspecialchars($t['title']) ?></h1>
        <p><em><?= htmlspecialchars($t['last_updated']) ?></em></p>
        <p><?= htmlspecialchars($t['intro']) ?></p>

        <h2><?= htmlspecialchars($t['info_title']) ?></h2>
        <p><?= htmlspecialchars($t['info']) ?></p>

        <h2><?= htmlspecialchars($t['usage_title']) ?></h2>
        <p><?= htmlspecialchars($t['usage']) ?></p>

        <h2><?= htmlspecialchars($t['security_title']) ?></h2>
        <p><?= htmlspecialchars($t['security']) ?></p>

        <h2><?= htmlspecialchars($t['logs_title']) ?></h2>
        <p><?= htmlspecialchars($t['logs']) ?></p>

        <h2><?= htmlspecialchars($t['third_title']) ?></h2>
        <p><?= htmlspecialchars($t['third']) ?></p>

        <h2><?= htmlspecialchars($t['changes_title']) ?></h2>
        <p><?= htmlspecialchars($t['changes']) ?></p>

        <h2><?= htmlspecialchars($t['contact_title']) ?></h2>
        <p><?= htmlspecialchars($t['contact']) ?></p>

        <h2><?= htmlspecialchars($t['consent_title']) ?></h2>
        <p><?= htmlspecialchars($t['consent']) ?></p>
    </div>
</body>
</html>
