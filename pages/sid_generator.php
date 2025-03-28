<?php
function generateSidPhrase($wordCount = 18) {
    $wordlist = file("wordlist.txt", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!$wordlist || count($wordlist) < $wordCount) {
        die("Error: Wordlist not found or insufficient words.");
    }
    shuffle($wordlist);
    return implode(" ", array_slice($wordlist, 0, $wordCount));
}

$sidPhrase = generateSidPhrase();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate SID Phrase</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            padding: 50px;
            background-color: #121212;
            color: white;
        }
        .phrase {
            background: #1e1e1e;
            padding: 15px;
            border-radius: 5px;
            display: inline-block;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <h1>Your Generated SID Phrase</h1>
    <div class="phrase"><?php echo htmlspecialchars($sidPhrase); ?></div>
</body>
</html>
