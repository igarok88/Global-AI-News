<?php

/**
 * Получает новости за последние 24 часа.
 * * @param string $searchQuery Поисковый запрос
 * @param object $client Объект клиента SerpApi
 * @return array Массив ссылок
 */
function getNewsLinks($searchQuery, $apiKey)
{
    // Конфигурация SerpApi
    $client = new GoogleSearchResults($apiKey);
    // Параметры запроса
    $query = [
        "engine"   => "google_news", // Лучше использовать стандартный движок для гибкости
        "q"        => $searchQuery,
        "gl"       => "ru",          // Геолокация: Россия (или de для Германии)
        "hl"       => "ru",          // Язык интерфейса: Русский
        "tbs"      => "qdr:d",       // <--- МАГИЯ ЗДЕСЬ: qdr:d означает "за последние 24 часа"
        "num"      => 10             // Сколько новостей брать
    ];

    try {
        // Получаем результат (обычно это массив, а не объект, в PHP библиотеке SerpApi)
        $response = $client->get_json($query);

        $links = [];

        if (isset($response->news_results)) {
            foreach ($response->news_results as $item) {
                if (isset($item->link)) {
                    $links[] = $item->link;
                }
            }
        }

        return $links;
    } catch (Exception $e) {
        // Логируем ошибку, если API упал
        error_log("Ошибка SerpApi: " . $e->getMessage());
        return [];
    }
}
