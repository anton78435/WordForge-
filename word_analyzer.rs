// word_analyzer.rs - Анализатор частоты слов на Rust (CLI)
use serde::{Serialize, Deserialize};
use std::collections::HashMap;
use std::fs;
use std::io::{self, Write, BufRead};
use std::path::Path;
use regex::Regex;

#[derive(Serialize, Deserialize)]
struct WordCount {
    word: String,
    count: usize,
}

lazy_static::lazy_static! {
    static ref STOP_WORDS_RU: Vec<String> = vec![
        "и","в","во","не","что","он","на","я","с","со","как","а","то","все","она","так","его","но","да","ты","к","у","же","вы","за","бы","по","только","ее","мне","было","вот","от","меня","еще","нет","о","из","ему","теперь","когда","даже","ну","вдруг","ли","если","уже","или","ни","быть","был","него","до","вас","нибудь","опять","уж","вам","ведь","там","потом","себя","ничего","ей","может","они","тут","где","есть","надо","ней","для","мы","тебя","их","чем","была","сам","чтоб","без","будто","чего","раз","тоже","себе","под","будет","ж","тогда","кто","этот","того","потому","этого","какой","совсем","ним","здесь","этом","один","почти","мой","тем","чтобы","нее","сейчас","были","куда","зачем","всех","можно","при","наконец","два","об","другой","хоть","после","над","больше","тот","через","эти","нас","про","всего","них","какая","много","разве","три","эту","моя","впрочем","хорошо","свою","этой","перед","иногда","лучше","чуть","том","нельзя","такой","им","более","всегда","конечно","всю","между","также","куда-то"
    ].into_iter().map(|s| s.to_string()).collect();
    static ref STOP_WORDS_EN: Vec<String> = vec![
        "the","be","to","of","and","a","in","that","have","i","it","for","not","on","with","he","as","you","do","at","this","but","his","by","from","they","we","say","her","she","or","an","will","my","one","all","would","there","their","what","so","up","out","if","about","who","get","which","go","me","when","make","can","like","time","no","just","him","know","take","people","into","year","your","good","some","could","them","see","other","than","then","now","look","only","come","its","over","think","also","back","after","use","two","how","our","work","first","well","way","even","new","want","because","any","these","give","day","most","us"
    ].into_iter().map(|s| s.to_string()).collect();
    static ref STOP_WORDS: HashMap<String, Vec<String>> = {
        let mut m = HashMap::new();
        m.insert("ru".to_string(), STOP_WORDS_RU.clone());
        m.insert("en".to_string(), STOP_WORDS_EN.clone());
        m
    };
}

fn clean_text(text: &str) -> String {
    let re = Regex::new(r"[^\w\s]").unwrap();
    let re_num = Regex::new(r"\d+").unwrap();
    let re_space = Regex::new(r"\s+").unwrap();
    let text = text.to_lowercase();
    let text = re.replace_all(&text, " ").to_string();
    let text = re_num.replace_all(&text, "").to_string();
    let text = re_space.replace_all(&text, " ").to_string();
    text.trim().to_string()
}

fn analyze_text(text: &str, lang: &str, remove_stopwords: bool) -> (Vec<WordCount>, usize, usize) {
    let cleaned = clean_text(text);
    let words: Vec<&str> = cleaned.split_whitespace().collect();
    let total_words = words.len();
    let stop = if remove_stopwords {
        STOP_WORDS.get(lang).unwrap_or(&STOP_WORDS["ru"]).iter().map(|s| s.as_str()).collect::<Vec<_>>()
    } else {
        vec![]
    };
    let filtered: Vec<&str> = words.iter()
        .filter(|w| !stop.contains(w) && w.len() > 1)
        .map(|w| *w)
        .collect();
    let mut counter = HashMap::new();
    for w in &filtered {
        *counter.entry(w.to_string()).or_insert(0) += 1;
    }
    let mut pairs: Vec<WordCount> = counter.iter()
        .map(|(w, c)| WordCount { word: w.clone(), count: *c })
        .collect();
    pairs.sort_by(|a, b| b.count.cmp(&a.count).then(a.word.cmp(&b.word)));
    let unique = pairs.len();
    (pairs, total_words, unique)
}

fn print_table(pairs: &[WordCount], top_n: usize) {
    if pairs.is_empty() {
        println!("Нет слов для отображения.");
        return;
    }
    let top = if top_n > pairs.len() { pairs.len() } else { top_n };
    let max_count = pairs[0].count;
    println!("\n{:-^50}", format!(" Топ-{} самых частотных слов ", top));
    println!("{}", "-".repeat(50));
    for i in 0..top {
        let p = &pairs[i];
        let bar_len = (p.count as f64 / max_count as f64 * 30.0) as usize;
        let bar = "█".repeat(bar_len) + &"░".repeat(30 - bar_len);
        println!("{:2}. {:<15} {:4}  {}", i+1, p.word, p.count, bar);
    }
    println!("{}", "-".repeat(50));
}

fn export_csv(pairs: &[WordCount], filename: &str) -> Result<(), Box<dyn std::error::Error>> {
    let mut writer = csv::Writer::from_path(filename)?;
    writer.write_record(&["Слово", "Частота"])?;
    for p in pairs {
        writer.serialize((&p.word, p.count))?;
    }
    writer.flush()?;
    Ok(())
}

fn export_json(pairs: &[WordCount], filename: &str) -> Result<(), Box<dyn std::error::Error>> {
    let obj: HashMap<String, usize> = pairs.iter().map(|p| (p.word.clone(), p.count)).collect();
    let data = serde_json::to_string_pretty(&obj)?;
    fs::write(filename, data)?;
    Ok(())
}

fn read_line(prompt: &str) -> String {
    print!("{}", prompt);
    io::stdout().flush().unwrap();
    let mut input = String::new();
    io::stdin().read_line(&mut input).unwrap();
    input.trim().to_string()
}

fn interactive() {
    println!("📊 АНАЛИЗАТОР ЧАСТОТЫ СЛОВ");
    let lang = read_line("Язык (ru/en, по умолчанию ru): ");
    let lang = if lang.is_empty() { "ru" } else { &lang };
    let remove_stop = read_line("Игнорировать стоп-слова? (y/n, по умолчанию y): ") != "n";
    loop {
        println!("\nВведите текст (или 'file: путь' для файла, 'exit' для выхода):");
        let input = read_line("> ");
        if input == "exit" { break; }
        let text = if input.starts_with("file:") {
            let path = input[5..].trim();
            match fs::read_to_string(path) {
                Ok(content) => {
                    println!("Файл '{}' загружен.", path);
                    content
                }
                Err(e) => {
                    println!("Ошибка чтения файла: {}", e);
                    continue;
                }
            }
        } else {
            input
        };
        let (pairs, total, unique) = analyze_text(&text, lang, remove_stop);
        println!("\nВсего слов: {}", total);
        println!("Уникальных слов: {}", unique);
        print_table(&pairs, 10);
        println!("\nДополнительные действия:");
        println!("1. Показать все слова");
        println!("2. Экспорт в CSV");
        println!("3. Экспорт в JSON");
        println!("4. Анализ другого текста");
        println!("0. Выход");
        let action = read_line("Ваш выбор: ");
        match action.as_str() {
            "0" => break,
            "1" => for p in &pairs { println!("{}: {}", p.word, p.count); },
            "2" => {
                let filename = read_line("Имя CSV файла (по умолчанию freq.csv): ");
                let filename = if filename.is_empty() { "freq.csv".to_string() } else { filename };
                if let Err(e) = export_csv(&pairs, &filename) {
                    println!("Ошибка: {}", e);
                } else {
                    println!("Экспортировано в {}", filename);
                }
            }
            "3" => {
                let filename = read_line("Имя JSON файла (по умолчанию freq.json): ");
                let filename = if filename.is_empty() { "freq.json".to_string() } else { filename };
                if let Err(e) = export_json(&pairs, &filename) {
                    println!("Ошибка: {}", e);
                } else {
                    println!("Экспортировано в {}", filename);
                }
            }
            _ => {}
        }
    }
}

fn main() {
    let args: Vec<String> = std::env::args().collect();
    if args.len() > 1 {
        let mut file_path = String::new();
        let mut text = String::new();
        let mut lang = "ru".to_string();
        let mut no_stop = false;
        let mut top = 10;
        let mut export_csv = String::new();
        let mut export_json = String::new();
        let mut all = false;
        let mut i = 1;
        while i < args.len() {
            match args[i].as_str() {
                "--file" => { file_path = args[i+1].clone(); i += 1; }
                "--text" => { text = args[i+1].clone(); i += 1; }
                "--lang" => { lang = args[i+1].clone(); i += 1; }
                "--no-stop" => { no_stop = true; }
                "--top" => { top = args[i+1].parse().unwrap_or(10); i += 1; }
                "--export-csv" => { export_csv = args[i+1].clone(); i += 1; }
                "--export-json" => { export_json = args[i+1].clone(); i += 1; }
                "--all" => { all = true; }
                _ => {}
            }
            i += 1;
        }
        let content = if !file_path.is_empty() {
            match fs::read_to_string(&file_path) {
                Ok(c) => c,
                Err(e) => { println!("Ошибка чтения файла: {}", e); return; }
            }
        } else if !text.is_empty() {
            text
        } else {
            println!("Укажите --file или --text");
            return;
        };
        let (pairs, total, unique) = analyze_text(&content, &lang, !no_stop);
        println!("Всего слов: {}", total);
        println!("Уникальных слов: {}", unique);
        print_table(&pairs, top);
        if all { for p in &pairs { println!("{}: {}", p.word, p.count); } }
        if !export_csv.is_empty() {
            if let Err(e) = export_csv(&pairs, &export_csv) {
                println!("Ошибка: {}", e);
            } else {
                println!("Экспортировано в {}", export_csv);
            }
        }
        if !export_json.is_empty() {
            if let Err(e) = export_json(&pairs, &export_json) {
                println!("Ошибка: {}", e);
            } else {
                println!("Экспортировано в {}", export_json);
            }
        }
    } else {
        interactive();
    }
}
