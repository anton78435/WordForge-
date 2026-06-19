#!/usr/bin/env python3
"""
word_analyzer.py - Анализатор частоты слов на Python
Поддерживает: чтение из файла, ввод текста, стоп-слова, визуализацию, экспорт.
"""
import sys
import re
import json
import csv
from collections import Counter
from typing import List, Dict, Tuple

# Стоп-слова (русские и английские)
STOP_WORDS = {
    'ru': {'и', 'в', 'во', 'не', 'что', 'он', 'на', 'я', 'с', 'со', 'как', 'а', 'то', 'все', 'она', 'так', 'его', 'но', 'да', 'ты', 'к', 'у', 'же', 'вы', 'за', 'бы', 'по', 'только', 'ее', 'мне', 'было', 'вот', 'от', 'меня', 'еще', 'нет', 'о', 'из', 'ему', 'теперь', 'когда', 'даже', 'ну', 'вдруг', 'ли', 'если', 'уже', 'или', 'ни', 'быть', 'был', 'него', 'до', 'вас', 'нибудь', 'опять', 'уж', 'вам', 'ведь', 'там', 'потом', 'себя', 'ничего', 'ей', 'может', 'они', 'тут', 'где', 'есть', 'надо', 'ней', 'для', 'мы', 'тебя', 'их', 'чем', 'была', 'сам', 'чтоб', 'без', 'будто', 'чего', 'раз', 'тоже', 'себе', 'под', 'будет', 'ж', 'тогда', 'кто', 'этот', 'того', 'потому', 'этого', 'какой', 'совсем', 'ним', 'здесь', 'этом', 'один', 'почти', 'мой', 'тем', 'чтобы', 'нее', 'сейчас', 'были', 'куда', 'зачем', 'всех', 'можно', 'при', 'наконец', 'два', 'об', 'другой', 'хоть', 'после', 'над', 'больше', 'тот', 'через', 'эти', 'нас', 'про', 'всего', 'них', 'какая', 'много', 'разве', 'три', 'эту', 'моя', 'впрочем', 'хорошо', 'свою', 'этой', 'перед', 'иногда', 'лучше', 'чуть', 'том', 'нельзя', 'такой', 'им', 'более', 'всегда', 'конечно', 'всю', 'между', 'также', 'куда-то'},  # упрощённый список
    'en': {'the', 'be', 'to', 'of', 'and', 'a', 'in', 'that', 'have', 'i', 'it', 'for', 'not', 'on', 'with', 'he', 'as', 'you', 'do', 'at', 'this', 'but', 'his', 'by', 'from', 'they', 'we', 'say', 'her', 'she', 'or', 'an', 'will', 'my', 'one', 'all', 'would', 'there', 'their', 'what', 'so', 'up', 'out', 'if', 'about', 'who', 'get', 'which', 'go', 'me', 'when', 'make', 'can', 'like', 'time', 'no', 'just', 'him', 'know', 'take', 'people', 'into', 'year', 'your', 'good', 'some', 'could', 'them', 'see', 'other', 'than', 'then', 'now', 'look', 'only', 'come', 'its', 'over', 'think', 'also', 'back', 'after', 'use', 'two', 'how', 'our', 'work', 'first', 'well', 'way', 'even', 'new', 'want', 'because', 'any', 'these', 'give', 'day', 'most', 'us'}
}

def clean_text(text: str) -> str:
    """Очистка текста: нижний регистр, удаление пунктуации, чисел, лишних пробелов."""
    text = text.lower()
    text = re.sub(r'[^\w\s]', ' ', text)  # удаляем пунктуацию
    text = re.sub(r'\d+', '', text)       # удаляем числа
    text = re.sub(r'\s+', ' ', text)      # схлопываем пробелы
    return text.strip()

def get_stop_words(lang: str = 'ru') -> set:
    return STOP_WORDS.get(lang, STOP_WORDS['ru'])

def analyze_text(text: str, lang: str = 'ru', remove_stopwords: bool = True, top_n: int = 10) -> Tuple[Counter, int, int]:
    """Анализирует текст, возвращает Counter слов, общее число слов, число уникальных слов."""
    cleaned = clean_text(text)
    words = cleaned.split()
    total_words = len(words)
    if remove_stopwords:
        stop = get_stop_words(lang)
        words = [w for w in words if w not in stop and len(w) > 1]
    counter = Counter(words)
    unique_words = len(counter)
    return counter, total_words, unique_words

def print_table(counter: Counter, top_n: int = 10) -> None:
    """Выводит таблицу топ-N слов с ASCII-гистограммой."""
    if not counter:
        print("Нет слов для отображения.")
        return
    print(f"\n{'Топ-' + str(top_n) + ' самых частотных слов':^50}")
    print("-" * 50)
    max_count = max(counter.values()) if counter else 1
    for i, (word, count) in enumerate(counter.most_common(top_n), 1):
        bar_len = int((count / max_count) * 30)
        bar = "█" * bar_len + "░" * (30 - bar_len)
        print(f"{i:2}. {word:<15} {count:>4}  {bar}")
    print("-" * 50)

def export_csv(counter: Counter, filename: str) -> None:
    with open(filename, 'w', newline='', encoding='utf-8') as f:
        writer = csv.writer(f)
        writer.writerow(['Слово', 'Частота'])
        for word, count in counter.most_common():
            writer.writerow([word, count])

def export_json(counter: Counter, filename: str) -> None:
    with open(filename, 'w', encoding='utf-8') as f:
        json.dump(dict(counter.most_common()), f, indent=2, ensure_ascii=False)

def interactive():
    print("📊 АНАЛИЗАТОР ЧАСТОТЫ СЛОВ")
    print("Введите текст или путь к файлу (для файла укажите 'file: путь')")
    lang = input("Язык (ru/en, по умолчанию ru): ").strip() or 'ru'
    remove_stop = input("Игнорировать стоп-слова? (y/n, по умолчанию y): ").strip().lower() != 'n'
    while True:
        print("\nВведите текст (или 'file: путь' для файла, 'exit' для выхода):")
        user_input = input("> ").strip()
        if user_input.lower() == 'exit':
            break
        if user_input.startswith('file:'):
            path = user_input[5:].strip()
            try:
                with open(path, 'r', encoding='utf-8') as f:
                    text = f.read()
                print(f"Файл '{path}' загружен.")
            except Exception as e:
                print(f"Ошибка чтения файла: {e}")
                continue
        else:
            text = user_input
        counter, total, unique = analyze_text(text, lang, remove_stop)
        print(f"\nВсего слов: {total}")
        print(f"Уникальных слов: {unique}")
        print_table(counter, min(10, len(counter)))
        print("\nДополнительные действия:")
        print("1. Показать все слова")
        print("2. Экспорт в CSV")
        print("3. Экспорт в JSON")
        print("4. Анализ другого текста")
        print("0. Выход")
        action = input("Ваш выбор: ").strip()
        if action == '0':
            break
        elif action == '1':
            for word, count in counter.most_common():
                print(f"{word}: {count}")
        elif action == '2':
            filename = input("Имя CSV файла (по умолчанию freq.csv): ").strip() or "freq.csv"
            export_csv(counter, filename)
            print(f"Экспортировано в {filename}")
        elif action == '3':
            filename = input("Имя JSON файла (по умолчанию freq.json): ").strip() or "freq.json"
            export_json(counter, filename)
            print(f"Экспортировано в {filename}")

def main():
    if len(sys.argv) > 1:
        # CLI с аргументами
        import argparse
        parser = argparse.ArgumentParser(description="Анализатор частоты слов")
        parser.add_argument("--file", help="Путь к файлу для анализа")
        parser.add_argument("--text", help="Текст для анализа")
        parser.add_argument("--lang", default="ru", help="Язык (ru/en)")
        parser.add_argument("--no-stop", action="store_true", help="Не удалять стоп-слова")
        parser.add_argument("--top", type=int, default=10, help="Количество слов в топе")
        parser.add_argument("--export-csv", help="Экспорт в CSV")
        parser.add_argument("--export-json", help="Экспорт в JSON")
        parser.add_argument("--all", action="store_true", help="Показать все слова")
        args = parser.parse_args()
        text = ""
        if args.file:
            with open(args.file, 'r', encoding='utf-8') as f:
                text = f.read()
        elif args.text:
            text = args.text
        else:
            print("Укажите --file или --text")
            sys.exit(1)
        counter, total, unique = analyze_text(text, args.lang, not args.no_stop)
        print(f"Всего слов: {total}")
        print(f"Уникальных слов: {unique}")
        print_table(counter, args.top)
        if args.all:
            for word, count in counter.most_common():
                print(f"{word}: {count}")
        if args.export_csv:
            export_csv(counter, args.export_csv)
            print(f"Экспортировано в {args.export_csv}")
        if args.export_json:
            export_json(counter, args.export_json)
            print(f"Экспортировано в {args.export_json}")
    else:
        interactive()

if __name__ == "__main__":
    main()
