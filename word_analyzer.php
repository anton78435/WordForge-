<?php
// word_analyzer.php - Анализатор частоты слов на PHP (CLI + веб)
// CLI: php word_analyzer.php --file sample.txt
// Веб: откройте как HTML

$stopWords = [
    'ru' => [
        'и','в','во','не','что','он','на','я','с','со','как','а','то','все','она','так','его','но','да','ты','к','у','же','вы','за','бы','по','только','ее','мне','было','вот','от','меня','еще','нет','о','из','ему','теперь','когда','даже','ну','вдруг','ли','если','уже','или','ни','быть','был','него','до','вас','нибудь','опять','уж','вам','ведь','там','потом','себя','ничего','ей','может','они','тут','где','есть','надо','ней','для','мы','тебя','их','чем','была','сам','чтоб','без','будто','чего','раз','тоже','себе','под','будет','ж','тогда','кто','этот','того','потому','этого','какой','совсем','ним','здесь','этом','один','почти','мой','тем','чтобы','нее','сейчас','были','куда','зачем','всех','можно','при','наконец','два','об','другой','хоть','после','над','больше','тот','через','эти','нас','про','всего','них','какая','много','разве','три','эту','моя','впрочем','хорошо','свою','этой','перед','иногда','лучше','чуть','том','нельзя','такой','им','более','всегда','конечно','всю','между','также','куда-то'
    ],
    'en' => [
        'the','be','to','of','and','a','in','that','have','i','it','for','not','on','with','he','as','you','do','at','this','but','his','by','from','they','we','say','her','she','or','an','will','my','one','all','would','there','their','what','so','up','out','if','about','who','get','which','go','me','when','make','can','like','time','no','just','him','know','take','people','into','year','your','good','some','could','them','see','other','than','then','now','look','only','come','its','over','think','also','back','after','use','two','how','our','work','first','well','way','even','new','want','because','any','these','give','day','most','us'
    ]
];

function cleanText($text) {
    $text = mb_strtolower($text);
    $text = preg_replace('/[^\w\s]/u', ' ', $text);
    $text = preg_replace('/\d+/', '', $text);
    $text = preg_replace('/\s+/', ' ', $text);
    return trim($text);
}

function analyzeText($text, $lang = 'ru', $removeStopwords = true) {
    global $stopWords;
    $cleaned = cleanText($text);
    $words = preg_split('/\s+/', $cleaned, -1, PREG_SPLIT_NO_EMPTY);
    $totalWords = count($words);
    if ($removeStopwords) {
        $stop = $stopWords[$lang] ?? $stopWords['ru'];
        $words = array_filter($words, function($w) use ($stop) {
            return !in_array($w, $stop) && mb_strlen($w) > 1;
        });
    }
    $counter = array_count_values($words);
    arsort($counter);
    $uniqueWords = count($counter);
    return [$counter, $totalWords, $uniqueWords];
}

function printTable($counter, $topN = 10) {
    if (empty($counter)) {
        echo "Нет слов для отображения.\n";
        return;
    }
    $pairs = array_slice($counter, 0, $topN, true);
    $maxCount = reset($counter);
    echo "\n" . str_pad("Топ-$topN самых частотных слов", 50, " ", STR_PAD_BOTH) . "\n";
    echo str_repeat("-", 50) . "\n";
    $i = 1;
    foreach ($pairs as $word => $count) {
        $barLen = (int)(($count / $maxCount) * 30);
        $bar = str_repeat("█", $barLen) . str_repeat("░", 30 - $barLen);
        printf("%2d. %-15s %4d  %s\n", $i, $word, $count, $bar);
        $i++;
    }
    echo str_repeat("-", 50) . "\n";
}

function exportCSV($counter, $filename) {
    $f = fopen($filename, 'w');
    fputcsv($f, ['Слово', 'Частота']);
    foreach ($counter as $word => $count) {
        fputcsv($f, [$word, $count]);
    }
    fclose($f);
}

function exportJSON($counter, $filename) {
    file_put_contents($filename, json_encode($counter, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function getInput($prompt) {
    echo $prompt;
    return trim(fgets(STDIN));
}

if (php_sapi_name() === 'cli') {
    $options = getopt("", ["file:", "text:", "lang:", "no-stop", "top:", "export-csv:", "export-json:", "all"]);
    if (isset($options['file']) || isset($options['text'])) {
        $content = isset($options['file']) ? file_get_contents($options['file']) : $options['text'];
        $lang = $options['lang'] ?? 'ru';
        $removeStop = !isset($options['no-stop']);
        $top = isset($options['top']) ? (int)$options['top'] : 10;
        list($counter, $total, $unique) = analyzeText($content, $lang, $removeStop);
        echo "Всего слов: $total\n";
        echo "Уникальных слов: $unique\n";
        printTable($counter, $top);
        if (isset($options['all'])) {
            foreach ($counter as $word => $count) {
                echo "$word: $count\n";
            }
        }
        if (isset($options['export-csv'])) {
            exportCSV($counter, $options['export-csv']);
            echo "Экспортировано в {$options['export-csv']}\n";
        }
        if (isset($options['export-json'])) {
            exportJSON($counter, $options['export-json']);
            echo "Экспортировано в {$options['export-json']}\n";
        }
    } else {
        // Интерактивный режим
        echo "📊 АНАЛИЗАТОР ЧАСТОТЫ СЛОВ\n";
        $lang = getInput("Язык (ru/en, по умолчанию ru): ") ?: 'ru';
        $removeStop = getInput("Игнорировать стоп-слова? (y/n, по умолчанию y): ") !== 'n';
        while (true) {
            echo "\nВведите текст (или 'file: путь' для файла, 'exit' для выхода):\n";
            $input = getInput("> ");
            if ($input === 'exit') break;
            if (strpos($input, 'file:') === 0) {
                $path = trim(substr($input, 5));
                $content = @file_get_contents($path);
                if ($content === false) {
                    echo "Ошибка чтения файла.\n";
                    continue;
                }
                echo "Файл '$path' загружен.\n";
            } else {
                $content = $input;
            }
            list($counter, $total, $unique) = analyzeText($content, $lang, $removeStop);
            echo "\nВсего слов: $total\n";
            echo "Уникальных слов: $unique\n";
            printTable($counter, 10);
            echo "\nДополнительные действия:\n";
            echo "1. Показать все слова\n";
            echo "2. Экспорт в CSV\n";
            echo "3. Экспорт в JSON\n";
            echo "4. Анализ другого текста\n";
            echo "0. Выход\n";
            $action = getInput("Ваш выбор: ");
            if ($action === '0') break;
            elseif ($action === '1') {
                foreach ($counter as $word => $count) echo "$word: $count\n";
            } elseif ($action === '2') {
                $filename = getInput("Имя CSV файла (по умолчанию freq.csv): ") ?: 'freq.csv';
                exportCSV($counter, $filename);
                echo "Экспортировано в $filename\n";
            } elseif ($action === '3') {
                $filename = getInput("Имя JSON файла (по умолчанию freq.json): ") ?: 'freq.json';
                exportJSON($counter, $filename);
                echo "Экспортировано в $filename\n";
            }
        }
    }
    exit;
}

// ========== ВЕБ-ИНТЕРФЕЙС ==========
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>📊 Анализатор частоты слов (PHP)</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f4f7fb; margin: 20px; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 16px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { text-align: center; }
        .form-group { margin-bottom: 15px; }
        label { display: inline-block; width: 120px; }
        textarea, input, select, button { padding: 6px; border-radius: 4px; border: 1px solid #ccc; }
        textarea { width: 100%; height: 150px; }
        button { background: #3498db; color: white; border: none; cursor: pointer; padding: 6px 20px; }
        button:hover { background: #2980b9; }
        .result { background: #ecf0f1; padding: 15px; border-radius: 8px; margin-top: 20px; }
        .table { font-family: monospace; white-space: pre; }
    </style>
</head>
<body>
<div class="container">
    <h1>📊 Анализатор частоты слов (PHP)</h1>
    <form method="GET">
        <div class="form-group">
            <label>Текст или файл:</label>
            <textarea name="text" rows="8"><?= isset($_GET['text']) ? htmlspecialchars($_GET['text']) : '' ?></textarea>
        </div>
        <div class="form-group">
            <label>Или файл:</label>
            <input type="text" name="file" placeholder="путь к файлу" value="<?= isset($_GET['file']) ? htmlspecialchars($_GET['file']) : '' ?>">
        </div>
        <div class="form-group">
            <label>Язык:</label>
            <select name="lang">
                <option value="ru" <?= isset($_GET['lang']) && $_GET['lang'] == 'ru' ? 'selected' : '' ?>>Русский</option>
                <option value="en" <?= isset($_GET['lang']) && $_GET['lang'] == 'en' ? 'selected' : '' ?>>English</option>
            </select>
        </div>
        <div class="form-group">
            <label>Стоп-слова:</label>
            <input type="checkbox" name="remove_stop" <?= !isset($_GET['remove_stop']) || $_GET['remove_stop'] == 'on' ? 'checked' : '' ?>> Игнорировать
        </div>
        <div class="form-group">
            <label>Топ-N:</label>
            <input type="number" name="top" value="<?= isset($_GET['top']) ? $_GET['top'] : 10 ?>" min="1" max="100">
        </div>
        <button type="submit">Анализировать</button>
        <a href="?export_csv=1&<?= http_build_query($_GET) ?>">📥 CSV</a>
        <a href="?export_json=1&<?= http_build_query($_GET) ?>">📥 JSON</a>
    </form>

    <?php if (isset($_GET['export_csv']) || isset($_GET['export_json'])): 
        $content = isset($_GET['file']) ? @file_get_contents($_GET['file']) : $_GET['text'];
        if ($content) {
            $lang = $_GET['lang'] ?? 'ru';
            $removeStop = !isset($_GET['remove_stop']) || $_GET['remove_stop'] == 'on';
            list($counter, $total, $unique) = analyzeText($content, $lang, $removeStop);
            if (isset($_GET['export_csv'])) {
                header('Content-Type: text/csv');
                header('Content-Disposition: attachment; filename="freq.csv"');
                $f = fopen('php://output', 'w');
                fputcsv($f, ['Слово', 'Частота']);
                foreach ($counter as $word => $count) fputcsv($f, [$word, $count]);
                fclose($f);
                exit;
            } else {
                header('Content-Type: application/json');
                echo json_encode($counter, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                exit;
            }
        }
    endif; ?>

    <?php if (isset($_GET['text']) || isset($_GET['file'])): 
        $content = isset($_GET['file']) ? @file_get_contents($_GET['file']) : $_GET['text'];
        if ($content):
            $lang = $_GET['lang'] ?? 'ru';
            $removeStop = !isset($_GET['remove_stop']) || $_GET['remove_stop'] == 'on';
            $top = isset($_GET['top']) ? (int)$_GET['top'] : 10;
            list($counter, $total, $unique) = analyzeText($content, $lang, $removeStop);
    ?>
        <div class="result">
            <p><strong>Всего слов:</strong> <?= $total ?></p>
            <p><strong>Уникальных слов:</strong> <?= $unique ?></p>
            <div class="table">
                <?php
                $pairs = array_slice($counter, 0, $top, true);
                $maxCount = reset($counter) ?: 1;
                echo str_pad("Топ-$top самых частотных слов", 50, " ", STR_PAD_BOTH) . "\n";
                echo str_repeat("-", 50) . "\n";
                $i = 1;
                foreach ($pairs as $word => $count) {
                    $barLen = (int)(($count / $maxCount) * 30);
                    $bar = str_repeat("█", $barLen) . str_repeat("░", 30 - $barLen);
                    printf("%2d. %-15s %4d  %s\n", $i, $word, $count, $bar);
                    $i++;
                }
                echo str_repeat("-", 50) . "\n";
                ?>
            </div>
            <p><a href="?all=1&<?= http_build_query($_GET) ?>">Показать все слова</a></p>
            <?php if (isset($_GET['all'])): ?>
                <div style="max-height:200px; overflow-y:auto; border:1px solid #ccc; padding:10px;">
                    <?php foreach ($counter as $word => $count): ?>
                        <?= "$word: $count\n" ?><br>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; endif; ?>
</div>
</body>
</html>
