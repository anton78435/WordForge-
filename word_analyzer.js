#!/usr/bin/env node
/**
 * word_analyzer.js - Анализатор частоты слов на JavaScript (Node.js CLI + веб)
 * CLI: node word_analyzer.js --file sample.txt
 * Веб: откройте как HTML
 */
const fs = require('fs');
const readline = require('readline');
const rl = readline.createInterface({
    input: process.stdin,
    output: process.stdout
});

const STOP_WORDS = {
    ru: new Set(['и','в','во','не','что','он','на','я','с','со','как','а','то','все','она','так','его','но','да','ты','к','у','же','вы','за','бы','по','только','ее','мне','было','вот','от','меня','еще','нет','о','из','ему','теперь','когда','даже','ну','вдруг','ли','если','уже','или','ни','быть','был','него','до','вас','нибудь','опять','уж','вам','ведь','там','потом','себя','ничего','ей','может','они','тут','где','есть','надо','ней','для','мы','тебя','их','чем','была','сам','чтоб','без','будто','чего','раз','тоже','себе','под','будет','ж','тогда','кто','этот','того','потому','этого','какой','совсем','ним','здесь','этом','один','почти','мой','тем','чтобы','нее','сейчас','были','куда','зачем','всех','можно','при','наконец','два','об','другой','хоть','после','над','больше','тот','через','эти','нас','про','всего','них','какая','много','разве','три','эту','моя','впрочем','хорошо','свою','этой','перед','иногда','лучше','чуть','том','нельзя','такой','им','более','всегда','конечно','всю','между','также','куда-то']),
    en: new Set(['the','be','to','of','and','a','in','that','have','i','it','for','not','on','with','he','as','you','do','at','this','but','his','by','from','they','we','say','her','she','or','an','will','my','one','all','would','there','their','what','so','up','out','if','about','who','get','which','go','me','when','make','can','like','time','no','just','him','know','take','people','into','year','your','good','some','could','them','see','other','than','then','now','look','only','come','its','over','think','also','back','after','use','two','how','our','work','first','well','way','even','new','want','because','any','these','give','day','most','us'])
};

function cleanText(text) {
    text = text.toLowerCase();
    text = text.replace(/[^\w\s]/g, ' ');
    text = text.replace(/\d+/g, '');
    text = text.replace(/\s+/g, ' ').trim();
    return text;
}

function analyzeText(text, lang = 'ru', removeStopwords = true) {
    const cleaned = cleanText(text);
    const words = cleaned.split(/\s+/).filter(w => w.length > 0);
    const totalWords = words.length;
    let filtered = words;
    if (removeStopwords) {
        const stop = STOP_WORDS[lang] || STOP_WORDS['ru'];
        filtered = words.filter(w => !stop.has(w) && w.length > 1);
    }
    const counter = {};
    filtered.forEach(w => { counter[w] = (counter[w] || 0) + 1; });
    const sorted = Object.entries(counter).sort((a, b) => b[1] - a[1]);
    const uniqueWords = sorted.length;
    return { counter, sorted, totalWords, uniqueWords };
}

function printTable(sorted, topN = 10) {
    if (!sorted.length) {
        console.log('Нет слов для отображения.');
        return;
    }
    const maxCount = sorted[0][1];
    console.log(`\n${'Топ-' + topN + ' самых частотных слов'.padStart(25)}`);
    console.log('-'.repeat(50));
    sorted.slice(0, topN).forEach(([word, count], i) => {
        const barLen = Math.floor((count / maxCount) * 30);
        const bar = '█'.repeat(barLen) + '░'.repeat(30 - barLen);
        console.log(`${(i+1).toString().padStart(2)}. ${word.padEnd(15)} ${count.toString().padStart(4)}  ${bar}`);
    });
    console.log('-'.repeat(50));
}

function exportCSV(sorted, filename) {
    const lines = ['Слово,Частота'];
    sorted.forEach(([word, count]) => lines.push(`${word},${count}`));
    fs.writeFileSync(filename, lines.join('\n'), 'utf8');
}

function exportJSON(sorted, filename) {
    const obj = Object.fromEntries(sorted);
    fs.writeFileSync(filename, JSON.stringify(obj, null, 2), 'utf8');
}

function prompt(query) {
    return new Promise(resolve => rl.question(query, resolve));
}

async function interactive() {
    console.log('📊 АНАЛИЗАТОР ЧАСТОТЫ СЛОВ');
    let lang = await prompt('Язык (ru/en, по умолчанию ru): ') || 'ru';
    let removeStop = (await prompt('Игнорировать стоп-слова? (y/n, по умолчанию y): ')).toLowerCase() !== 'n';
    while (true) {
        console.log('\nВведите текст (или "file: путь" для файла, "exit" для выхода):');
        const input = await prompt('> ');
        if (input.toLowerCase() === 'exit') break;
        let text;
        if (input.startsWith('file:')) {
            const path = input.slice(5).trim();
            try {
                text = fs.readFileSync(path, 'utf8');
                console.log(`Файл '${path}' загружен.`);
            } catch (e) {
                console.log(`Ошибка чтения файла: ${e.message}`);
                continue;
            }
        } else {
            text = input;
        }
        const { sorted, totalWords, uniqueWords } = analyzeText(text, lang, removeStop);
        console.log(`\nВсего слов: ${totalWords}`);
        console.log(`Уникальных слов: ${uniqueWords}`);
        printTable(sorted, Math.min(10, sorted.length));
        console.log('\nДополнительные действия:');
        console.log('1. Показать все слова');
        console.log('2. Экспорт в CSV');
        console.log('3. Экспорт в JSON');
        console.log('4. Анализ другого текста');
        console.log('0. Выход');
        const action = await prompt('Ваш выбор: ');
        if (action === '0') break;
        else if (action === '1') {
            sorted.forEach(([word, count]) => console.log(`${word}: ${count}`));
        } else if (action === '2') {
            const filename = await prompt('Имя CSV файла (по умолчанию freq.csv): ') || 'freq.csv';
            exportCSV(sorted, filename);
            console.log(`Экспортировано в ${filename}`);
        } else if (action === '3') {
            const filename = await prompt('Имя JSON файла (по умолчанию freq.json): ') || 'freq.json';
            exportJSON(sorted, filename);
            console.log(`Экспортировано в ${filename}`);
        }
    }
    rl.close();
}

function main() {
    const args = process.argv.slice(2);
    if (args.length > 0) {
        const parsed = {};
        for (let i = 0; i < args.length; i++) {
            if (args[i] === '--file') parsed.file = args[++i];
            else if (args[i] === '--text') parsed.text = args[++i];
            else if (args[i] === '--lang') parsed.lang = args[++i];
            else if (args[i] === '--no-stop') parsed.noStop = true;
            else if (args[i] === '--top') parsed.top = parseInt(args[++i]);
            else if (args[i] === '--export-csv') parsed.exportCsv = args[++i];
            else if (args[i] === '--export-json') parsed.exportJson = args[++i];
            else if (args[i] === '--all') parsed.all = true;
        }
        let text = '';
        if (parsed.file) {
            try { text = fs.readFileSync(parsed.file, 'utf8'); }
            catch (e) { console.error(`Ошибка: ${e.message}`); process.exit(1); }
        } else if (parsed.text) {
            text = parsed.text;
        } else {
            console.log('Укажите --file или --text');
            process.exit(1);
        }
        const lang = parsed.lang || 'ru';
        const { sorted, totalWords, uniqueWords } = analyzeText(text, lang, !parsed.noStop);
        console.log(`Всего слов: ${totalWords}`);
        console.log(`Уникальных слов: ${uniqueWords}`);
        printTable(sorted, parsed.top || 10);
        if (parsed.all) sorted.forEach(([word, count]) => console.log(`${word}: ${count}`));
        if (parsed.exportCsv) { exportCSV(sorted, parsed.exportCsv); console.log(`Экспортировано в ${parsed.exportCsv}`); }
        if (parsed.exportJson) { exportJSON(sorted, parsed.exportJson); console.log(`Экспортировано в ${parsed.exportJson}`); }
    } else {
        interactive().catch(console.error);
    }
}

if (require.main === module) {
    main();
}

// ========== Браузерная версия ==========
if (typeof window !== 'undefined') {
    window.STOP_WORDS = STOP_WORDS;
    window.analyzeText = analyzeText;
    window.exportCSV = exportCSV;
    window.exportJSON = exportJSON;
}
