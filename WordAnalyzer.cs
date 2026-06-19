// WordAnalyzer.cs - Анализатор частоты слов на C# (CLI)
using System;
using System.Collections.Generic;
using System.IO;
using System.Linq;
using System.Text;
using System.Text.RegularExpressions;
using System.Threading.Tasks;

namespace WordAnalyzer
{
    class WordCount : IComparable<WordCount>
    {
        public string Word { get; set; }
        public int Count { get; set; }
        public int CompareTo(WordCount other)
        {
            if (Count != other.Count) return other.Count - Count;
            return Word.CompareTo(other.Word);
        }
    }

    class Program
    {
        private static readonly Dictionary<string, HashSet<string>> StopWords = new Dictionary<string, HashSet<string>>();

        static Program()
        {
            StopWords["ru"] = new HashSet<string>
            {
                "и","в","во","не","что","он","на","я","с","со","как","а","то","все","она","так","его","но","да","ты","к","у","же","вы","за","бы","по","только","ее","мне","было","вот","от","меня","еще","нет","о","из","ему","теперь","когда","даже","ну","вдруг","ли","если","уже","или","ни","быть","был","него","до","вас","нибудь","опять","уж","вам","ведь","там","потом","себя","ничего","ей","может","они","тут","где","есть","надо","ней","для","мы","тебя","их","чем","была","сам","чтоб","без","будто","чего","раз","тоже","себе","под","будет","ж","тогда","кто","этот","того","потому","этого","какой","совсем","ним","здесь","этом","один","почти","мой","тем","чтобы","нее","сейчас","были","куда","зачем","всех","можно","при","наконец","два","об","другой","хоть","после","над","больше","тот","через","эти","нас","про","всего","них","какая","много","разве","три","эту","моя","впрочем","хорошо","свою","этой","перед","иногда","лучше","чуть","том","нельзя","такой","им","более","всегда","конечно","всю","между","также","куда-то"
            };
            StopWords["en"] = new HashSet<string>
            {
                "the","be","to","of","and","a","in","that","have","i","it","for","not","on","with","he","as","you","do","at","this","but","his","by","from","they","we","say","her","she","or","an","will","my","one","all","would","there","their","what","so","up","out","if","about","who","get","which","go","me","when","make","can","like","time","no","just","him","know","take","people","into","year","your","good","some","could","them","see","other","than","then","now","look","only","come","its","over","think","also","back","after","use","two","how","our","work","first","well","way","even","new","want","because","any","these","give","day","most","us"
            };
        }

        static string CleanText(string text)
        {
            text = text.ToLower();
            text = Regex.Replace(text, @"[^\w\s]", " ");
            text = Regex.Replace(text, @"\d+", "");
            text = Regex.Replace(text, @"\s+", " ").Trim();
            return text;
        }

        static List<WordCount> AnalyzeText(string text, string lang, bool removeStopwords)
        {
            var cleaned = CleanText(text);
            var words = cleaned.Split(new[] { ' ' }, StringSplitOptions.RemoveEmptyEntries).ToList();
            int totalWords = words.Count;
            if (removeStopwords)
            {
                var stop = StopWords.ContainsKey(lang) ? StopWords[lang] : StopWords["ru"];
                words = words.Where(w => !stop.Contains(w) && w.Length > 1).ToList();
            }
            var counter = new Dictionary<string, int>();
            foreach (var w in words)
            {
                if (counter.ContainsKey(w)) counter[w]++;
                else counter[w] = 1;
            }
            var result = counter.Select(kv => new WordCount { Word = kv.Key, Count = kv.Value }).ToList();
            result.Sort();
            return result;
        }

        static void PrintTable(List<WordCount> pairs, int topN)
        {
            if (pairs.Count == 0)
            {
                Console.WriteLine("Нет слов для отображения.");
                return;
            }
            int top = Math.Min(topN, pairs.Count);
            int maxCount = pairs[0].Count;
            Console.WriteLine($"\n{"Топ-" + top + " самых частотных слов",50}");
            Console.WriteLine(new string('-', 50));
            for (int i = 0; i < top; i++)
            {
                var p = pairs[i];
                int barLen = (int)((double)p.Count / maxCount * 30);
                string bar = new string('█', barLen) + new string('░', 30 - barLen);
                Console.WriteLine($"{i+1,2}. {p.Word,-15} {p.Count,4}  {bar}");
            }
            Console.WriteLine(new string('-', 50));
        }

        static void ExportCSV(List<WordCount> pairs, string filename)
        {
            using (var sw = new StreamWriter(filename, false, Encoding.UTF8))
            {
                sw.WriteLine("Слово,Частота");
                foreach (var p in pairs)
                    sw.WriteLine($"{p.Word},{p.Count}");
            }
        }

        static void ExportJSON(List<WordCount> pairs, string filename)
        {
            using (var sw = new StreamWriter(filename, false, Encoding.UTF8))
            {
                sw.WriteLine("{");
                for (int i = 0; i < pairs.Count; i++)
                {
                    var p = pairs[i];
                    sw.Write($"  \"{p.Word}\": {p.Count}");
                    if (i < pairs.Count - 1) sw.Write(",");
                    sw.WriteLine();
                }
                sw.WriteLine("}");
            }
        }

        static async Task Interactive()
        {
            Console.WriteLine("📊 АНАЛИЗАТОР ЧАСТОТЫ СЛОВ");
            Console.Write("Язык (ru/en, по умолчанию ru): ");
            string lang = Console.ReadLine();
            if (string.IsNullOrEmpty(lang)) lang = "ru";
            Console.Write("Игнорировать стоп-слова? (y/n, по умолчанию y): ");
            string stopInput = Console.ReadLine();
            bool removeStop = stopInput != "n";
            while (true)
            {
                Console.WriteLine("\nВведите текст (или 'file: путь' для файла, 'exit' для выхода):");
                Console.Write("> ");
                string input = Console.ReadLine();
                if (input == "exit") break;
                string text;
                if (input.StartsWith("file:"))
                {
                    string path = input.Substring(5).Trim();
                    try
                    {
                        text = await File.ReadAllTextAsync(path);
                        Console.WriteLine($"Файл '{path}' загружен.");
                    }
                    catch (Exception e)
                    {
                        Console.WriteLine($"Ошибка чтения файла: {e.Message}");
                        continue;
                    }
                }
                else
                {
                    text = input;
                }
                var pairs = AnalyzeText(text, lang, removeStop);
                int total = text.Split(new[] { ' ' }, StringSplitOptions.RemoveEmptyEntries).Length;
                Console.WriteLine($"\nВсего слов: {total}");
                Console.WriteLine($"Уникальных слов: {pairs.Count}");
                PrintTable(pairs, 10);
                Console.WriteLine("\nДополнительные действия:");
                Console.WriteLine("1. Показать все слова");
                Console.WriteLine("2. Экспорт в CSV");
                Console.WriteLine("3. Экспорт в JSON");
                Console.WriteLine("4. Анализ другого текста");
                Console.WriteLine("0. Выход");
                Console.Write("Ваш выбор: ");
                string action = Console.ReadLine();
                if (action == "0") break;
                else if (action == "1")
                {
                    foreach (var p in pairs) Console.WriteLine($"{p.Word}: {p.Count}");
                }
                else if (action == "2")
                {
                    Console.Write("Имя CSV файла (по умолчанию freq.csv): ");
                    string filename = Console.ReadLine();
                    if (string.IsNullOrEmpty(filename)) filename = "freq.csv";
                    ExportCSV(pairs, filename);
                    Console.WriteLine($"Экспортировано в {filename}");
                }
                else if (action == "3")
                {
                    Console.Write("Имя JSON файла (по умолчанию freq.json): ");
                    string filename = Console.ReadLine();
                    if (string.IsNullOrEmpty(filename)) filename = "freq.json";
                    ExportJSON(pairs, filename);
                    Console.WriteLine($"Экспортировано в {filename}");
                }
            }
        }

        static async Task Main(string[] args)
        {
            if (args.Length > 0)
            {
                string filePath = null, text = null, lang = "ru", exportCsv = null, exportJson = null;
                bool noStop = false, all = false;
                int top = 10;
                for (int i = 0; i < args.Length; i++)
                {
                    if (args[i] == "--file") filePath = args[++i];
                    else if (args[i] == "--text") text = args[++i];
                    else if (args[i] == "--lang") lang = args[++i];
                    else if (args[i] == "--no-stop") noStop = true;
                    else if (args[i] == "--top") top = int.Parse(args[++i]);
                    else if (args[i] == "--export-csv") exportCsv = args[++i];
                    else if (args[i] == "--export-json") exportJson = args[++i];
                    else if (args[i] == "--all") all = true;
                }
                string content;
                if (filePath != null)
                {
                    content = await File.ReadAllTextAsync(filePath);
                }
                else if (text != null)
                {
                    content = text;
                }
                else
                {
                    Console.WriteLine("Укажите --file или --text");
                    return;
                }
                var pairs = AnalyzeText(content, lang, !noStop);
                int total = content.Split(new[] { ' ' }, StringSplitOptions.RemoveEmptyEntries).Length;
                Console.WriteLine($"Всего слов: {total}");
                Console.WriteLine($"Уникальных слов: {pairs.Count}");
                PrintTable(pairs, top);
                if (all) foreach (var p in pairs) Console.WriteLine($"{p.Word}: {p.Count}");
                if (exportCsv != null) { ExportCSV(pairs, exportCsv); Console.WriteLine($"Экспортировано в {exportCsv}"); }
                if (exportJson != null) { ExportJSON(pairs, exportJson); Console.WriteLine($"Экспортировано в {exportJson}"); }
            }
            else
            {
                await Interactive();
            }
        }
    }
}
