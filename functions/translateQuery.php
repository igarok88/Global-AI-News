<?php

/**
 * Agent Function: Translates/Localizes the search query for the target country.
 * This ensures we search for local news using local terms (e.g., "Germany" -> "Deutschland").
 *
 * @param string $text           The original search query (e.g., "Trump")
 * @param string $targetLangCode The 2-letter ISO code for the target language (e.g., 'de', 'fr')
 * @param string $countryName    The full name of the target country (e.g., 'Germany')
 * @param string $apiKey         Google Gemini API Key
 * @return string                The localized query string
 */

function translateQuery($text, $targetLangCode, $countryName, $apiKey)
{

    // Sanitize input: Replace double quotes with single quotes to prevent breaking the JSON payload or prompt structure
    $safeText = str_replace('"', "'", $text);

    // Optimization: If the target language is German or the input is empty, return the original text immediately.
    // (This saves an API call if the user is likely searching in their native language or input is invalid)
    if ($targetLangCode === 'de' || empty($safeText)) {
        return $safeText;
    }


    // Construct the prompt for the AI using HEREDOC syntax.
    // This prompt instructs the AI to act as a "Localization Specialist".
    $prompt = <<<EOT
    You are an expert international news editor and localization specialist. 
    Your task is to adapt a short search query (provided in ANY source language) into the specific local language and script used in {$countryName} for a Google News search.

    Context:
    - Target Country: {$countryName}
    - Target Language Code: {$targetLangCode}
    - Original Query: "{$safeText}"

    CRITICAL INSTRUCTIONS:

    1.  **Detect & Adapt:**
        - Automatically detect the source language of the "Original Query".
        - Translate the *intent* and *keywords* into the target language defined by {$targetLangCode}.

    2.  **Entity Recognition (Crucial):**
        - If the query contains Proper Names (Politicians, Cities, Companies), do NOT just transliterate. Identify the entity and use the **standard spelling used by major local media** in {$countryName}.
        - *Example (Target: Germany):* Input "Beijing" (En) -> Output "Peking". Input "Зеленский" (Ru) -> Output "Selenskyj".

    3.  **Script & Alphabet:**
        - **China (CN), Japan (JP), Korea (KR):** MUST use native script (Hanzi, Kanji, Hangul). No Latin characters unless it's a specific brand name.
        - **Arab Countries (EG, SA, AE) & Israel (IL):** MUST use Arabic/Hebrew script.
        - **Ukraine (UA):** Use Ukrainian spelling (e.g., "Єрмак" instead of "Yermak").
        - **Europe/Americas:** Use the standard Latin alphabet with local diacritics (e.g., "München", "François").

    4.  **Formatting:**
        - If the query is a common noun (e.g., "Elections", "War", "Inflation"), translate it to the local equivalent (e.g., "Wahlen", "Krieg", "Inflation").
        - If the query is a person, ensure the First Name is included if it helps ambiguity (e.g., "Donald Trump" instead of just "Trump").

    Output Format:
    Output ONLY the final translated/localized search string. No explanations. No quotes.
    EOT;

    // Prepare the payload structure required by the Gemini API
    $apiData = [
        "contents" => [["parts" => [["text" => $prompt]]]]
    ];

    // Initialize cURL session targeting the Gemini 2.0 Flash model endpoint
    $ch = curl_init("https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-lite:generateContent?key=" . $apiKey);
    // Set option to return the response as a string instead of printing it
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    // Set HTTP method to POST
    curl_setopt($ch, CURLOPT_POST, true);
    // Set headers (Content-Type is essential for JSON payloads)
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    // Attach the JSON encoded payload
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($apiData));

    // Execute the API request and store the raw response
    $response = curl_exec($ch);
    // Close the cURL resource to free up memory
    curl_close($ch);

    // Decode the JSON response into an associative array
    $json = json_decode($response, true);

    // Check if the response contains the expected generated text path
    if (isset($json['candidates'][0]['content']['parts'][0]['text'])) {
        return trim($json['candidates'][0]['content']['parts'][0]['text']);
    }

    // Fallback: If API fails, returns error, or unexpected structure, return the original sanitized text
    return $safeText;
}
