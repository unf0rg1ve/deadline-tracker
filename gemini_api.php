<?php
class GeminiAPI {
    private $apiKey;

    public function __construct($apiKey) {
        $this->apiKey = $apiKey;
    }

    public function analyzeTask($title, $description, $deadline) {
        $prompt = "Ты — опытный преподаватель и тайм-менеджер. Проанализируй учебное задание и дай **чёткий, полезный** ответ студенту.

Задание:
Название: {$title}
Описание: {$description}
Дедлайн: {$deadline}

Ответь **точно** в таком формате (не больше 6-7 строк всего):

Сложность: [Легкая / Средняя / Высокая]
Примерное время: [X часов / X дней]
Срочность: [Низкая / Средняя / Высокая]

Рекомендации:
- Коротко 2-3 главных совета, что делать в первую очередь и как эффективнее выполнить.";

        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . $this->apiKey;

        $data = [
            "contents" => [
                ["parts" => [["text" => $prompt]]]
            ],
            "generationConfig" => [
                "temperature" => 0.75,
                "maxOutputTokens" => 2000,
                "topP" => 0.9
            ]
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            return "Ошибка подключения к Gemini (код: $httpCode).";
        }

        $result = json_decode($response, true);

        if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
            $text = $result['candidates'][0]['content']['parts'][0]['text'];
            return trim($text);
        }

        return "Не удалось получить нормальный ответ от ИИ.";
    }
}