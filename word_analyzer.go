// word_analyzer.go - Анализатор частоты слов на Go (CLI)
package main

import (
	"bufio"
	"encoding/csv"
	"encoding/json"
	"flag"
	"fmt"
	"os"
	"regexp"
	"sort"
	"strings"
)

var stopWords = map[string]map[string]bool{
	"ru": {
		"и": true, "в": true, "во": true, "не": true, "что": true, "он": true, "на": true, "я": true, "с": true, "со": true,
		"как": true, "а": true, "то": true, "все": true, "она": true, "так": true, "его": true, "но": true, "да": true,
		"ты": true, "к": true, "у": true, "же": true, "вы": true, "за": true, "бы": true, "по": true, "только": true,
		"ее": true, "мне": true, "было": true, "вот": true, "от": true, "меня": true, "еще": true, "нет": true, "о": true,
		"из": true, "ему": true, "теперь": true, "когда": true, "даже": true, "ну": true, "вдруг": true, "ли": true,
		"если": true, "уже": true, "или": true, "ни": true, "быть": true, "был": true, "него": true, "до": true,
		"вас": true, "нибудь": true, "опять": true, "уж": true, "вам": true, "ведь": true, "там": true, "потом": true,
		"себя": true, "ничего": true, "ей": true, "может": true, "они": true, "тут": true, "где": true, "есть": true,
		"надо": true, "ней": true, "для": true, "мы": true, "тебя": true, "их": true, "чем": true, "была": true,
		"сам": true, "чтоб": true, "без": true, "будто": true, "чего": true, "раз": true, "тоже": true, "себе": true,
		"под": true, "будет": true, "ж": true, "тогда": true, "кто": true, "этот": true, "того": true, "потому": true,
		"этого": true, "какой": true, "совсем": true, "ним": true, "здесь": true, "этом": true, "один": true, "почти": true,
		"мой": true, "тем": true, "чтобы": true, "нее": true, "сейчас": true, "были": true, "куда": true, "зачем": true,
		"всех": true, "можно": true, "при": true, "наконец": true, "два": true, "об": true, "другой": true, "хоть": true,
		"после": true, "над": true, "больше": true, "тот": true, "через": true, "эти": true, "нас": true, "про": true,
		"всего": true, "них": true, "какая": true, "много": true, "разве": true, "три": true, "эту": true, "моя": true,
		"впрочем": true, "хорошо": true, "свою": true, "этой": true, "перед": true, "иногда": true, "лучше": true,
		"чуть": true, "том": true, "нельзя": true, "такой": true, "им": true, "более": true, "всегда": true,
		"конечно": true, "всю": true, "между": true, "также": true, "куда-то": true,
	},
	"en": {
		"the": true, "be": true, "to": true, "of": true, "and": true, "a": true, "in": true, "that": true, "have": true,
		"i": true, "it": true, "for": true, "not": true, "on": true, "with": true, "he": true, "as": true, "you": true,
		"do": true, "at": true, "this": true, "but": true, "his": true, "by": true, "from": true, "they": true, "we": true,
		"say": true, "her": true, "she": true, "or": true, "an": true, "will": true, "my": true, "one": true, "all": true,
		"would": true, "there": true, "their": true, "what": true, "so": true, "up": true, "out": true, "if": true,
		"about": true, "who": true, "get": true, "which": true, "go": true, "me": true, "when": true, "make": true,
		"can": true, "like": true, "time": true, "no": true, "just": true, "him": true, "know": true, "take": true,
		"people": true, "into": true, "year": true, "your": true, "good": true, "some": true, "could": true, "them": true,
		"see": true, "other": true, "than": true, "then": true, "now": true, "look": true, "only": true, "come": true,
		"its": true, "over": true, "think": true, "also": true, "back": true, "after": true, "use": true, "two": true,
		"how": true, "our": true, "work": true, "first": true, "well": true, "way": true, "even": true, "new": true,
		"want": true, "because": true, "any": true, "these": true, "give": true, "day": true, "most": true, "us": true,
	},
}

type Pair struct {
	Word  string
	Count int
}

func cleanText(text string) string {
	re := regexp.MustCompile(`[^\w\s]`)
	text = strings.ToLower(text)
	text = re.ReplaceAllString(text, " ")
	reNum := regexp.MustCompile(`\d+`)
	text = reNum.ReplaceAllString(text, "")
	reSpace := regexp.MustCompile(`\s+`)
	text = reSpace.ReplaceAllString(text, " ")
	return strings.TrimSpace(text)
}

func analyzeText(text, lang string, removeStopwords bool) ([]Pair, int, int) {
	cleaned := cleanText(text)
	words := strings.Fields(cleaned)
	totalWords := len(words)
	var filtered []string
	if removeStopwords {
		stop := stopWords[lang]
		if stop == nil {
			stop = stopWords["ru"]
		}
		for _, w := range words {
			if !stop[w] && len(w) > 1 {
				filtered = append(filtered, w)
			}
		}
	} else {
		filtered = words
	}
	counter := make(map[string]int)
	for _, w := range filtered {
		counter[w]++
	}
	var pairs []Pair
	for w, c := range counter {
		pairs = append(pairs, Pair{w, c})
	}
	sort.Slice(pairs, func(i, j int) bool {
		if pairs[i].Count == pairs[j].Count {
			return pairs[i].Word < pairs[j].Word
		}
		return pairs[i].Count > pairs[j].Count
	})
	return pairs, totalWords, len(counter)
}

func printTable(pairs []Pair, topN int) {
	if len(pairs) == 0 {
		fmt.Println("Нет слов для отображения.")
		return
	}
	if topN > len(pairs) {
		topN = len(pairs)
	}
	maxCount := pairs[0].Count
	fmt.Printf("\n%50s\n", "Топ-"+fmt.Sprint(topN)+" самых частотных слов")
	fmt.Println(strings.Repeat("-", 50))
	for i := 0; i < topN; i++ {
		p := pairs[i]
		barLen := int(float64(p.Count) / float64(maxCount) * 30)
		bar := strings.Repeat("█", barLen) + strings.Repeat("░", 30-barLen)
		fmt.Printf("%2d. %-15s %4d  %s\n", i+1, p.Word, p.Count, bar)
	}
	fmt.Println(strings.Repeat("-", 50))
}

func exportCSV(pairs []Pair, filename string) error {
	file, err := os.Create(filename)
	if err != nil {
		return err
	}
	defer file.Close()
	writer := csv.NewWriter(file)
	defer writer.Flush()
	writer.Write([]string{"Слово", "Частота"})
	for _, p := range pairs {
		writer.Write([]string{p.Word, fmt.Sprint(p.Count)})
	}
	return nil
}

func exportJSON(pairs []Pair, filename string) error {
	obj := make(map[string]int)
	for _, p := range pairs {
		obj[p.Word] = p.Count
	}
	data, err := json.MarshalIndent(obj, "", "  ")
	if err != nil {
		return err
	}
	return os.WriteFile(filename, data, 0644)
}

func interactive() {
	scanner := bufio.NewScanner(os.Stdin)
	fmt.Println("📊 АНАЛИЗАТОР ЧАСТОТЫ СЛОВ")
	fmt.Print("Язык (ru/en, по умолчанию ru): ")
	scanner.Scan()
	lang := scanner.Text()
	if lang == "" {
		lang = "ru"
	}
	fmt.Print("Игнорировать стоп-слова? (y/n, по умолчанию y): ")
	scanner.Scan()
	removeStop := scanner.Text() != "n"
	for {
		fmt.Println("\nВведите текст (или 'file: путь' для файла, 'exit' для выхода):")
		fmt.Print("> ")
		scanner.Scan()
		input := scanner.Text()
		if input == "exit" {
			break
		}
		var text string
		if strings.HasPrefix(input, "file:") {
			path := strings.TrimSpace(input[5:])
			data, err := os.ReadFile(path)
			if err != nil {
				fmt.Printf("Ошибка чтения файла: %v\n", err)
				continue
			}
			text = string(data)
			fmt.Printf("Файл '%s' загружен.\n", path)
		} else {
			text = input
		}
		pairs, total, unique := analyzeText(text, lang, removeStop)
		fmt.Printf("\nВсего слов: %d\n", total)
		fmt.Printf("Уникальных слов: %d\n", unique)
		printTable(pairs, 10)
		fmt.Println("\nДополнительные действия:")
		fmt.Println("1. Показать все слова")
		fmt.Println("2. Экспорт в CSV")
		fmt.Println("3. Экспорт в JSON")
		fmt.Println("4. Анализ другого текста")
		fmt.Println("0. Выход")
		fmt.Print("Ваш выбор: ")
		scanner.Scan()
		action := scanner.Text()
		switch action {
		case "0":
			return
		case "1":
			for _, p := range pairs {
				fmt.Printf("%s: %d\n", p.Word, p.Count)
			}
		case "2":
			fmt.Print("Имя CSV файла (по умолчанию freq.csv): ")
			scanner.Scan()
			filename := scanner.Text()
			if filename == "" {
				filename = "freq.csv"
			}
			if err := exportCSV(pairs, filename); err != nil {
				fmt.Printf("Ошибка: %v\n", err)
			} else {
				fmt.Printf("Экспортировано в %s\n", filename)
			}
		case "3":
			fmt.Print("Имя JSON файла (по умолчанию freq.json): ")
			scanner.Scan()
			filename := scanner.Text()
			if filename == "" {
				filename = "freq.json"
			}
			if err := exportJSON(pairs, filename); err != nil {
				fmt.Printf("Ошибка: %v\n", err)
			} else {
				fmt.Printf("Экспортировано в %s\n", filename)
			}
		}
	}
}

func main() {
	var filePath, text, lang, exportCsv, exportJson string
	var noStop, all bool
	var top int
	flag.StringVar(&filePath, "file", "", "Путь к файлу")
	flag.StringVar(&text, "text", "", "Текст для анализа")
	flag.StringVar(&lang, "lang", "ru", "Язык (ru/en)")
	flag.BoolVar(&noStop, "no-stop", false, "Не удалять стоп-слова")
	flag.IntVar(&top, "top", 10, "Количество слов в топе")
	flag.StringVar(&exportCsv, "export-csv", "", "Экспорт в CSV")
	flag.StringVar(&exportJson, "export-json", "", "Экспорт в JSON")
	flag.BoolVar(&all, "all", false, "Показать все слова")
	flag.Parse()

	if filePath != "" || text != "" {
		var content string
		if filePath != "" {
			data, err := os.ReadFile(filePath)
			if err != nil {
				fmt.Printf("Ошибка чтения файла: %v\n", err)
				return
			}
			content = string(data)
		} else {
			content = text
		}
		pairs, total, unique := analyzeText(content, lang, !noStop)
		fmt.Printf("Всего слов: %d\n", total)
		fmt.Printf("Уникальных слов: %d\n", unique)
		printTable(pairs, top)
		if all {
			for _, p := range pairs {
				fmt.Printf("%s: %d\n", p.Word, p.Count)
			}
		}
		if exportCsv != "" {
			if err := exportCSV(pairs, exportCsv); err != nil {
				fmt.Printf("Ошибка экспорта CSV: %v\n", err)
			} else {
				fmt.Printf("Экспортировано в %s\n", exportCsv)
			}
		}
		if exportJson != "" {
			if err := exportJSON(pairs, exportJson); err != nil {
				fmt.Printf("Ошибка экспорта JSON: %v\n", err)
			} else {
				fmt.Printf("Экспортировано в %s\n", exportJson)
			}
		}
	} else {
		interactive()
	}
}
