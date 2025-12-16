<?php

// === SSE HEADERS CONFIGURATION ===
// Set content type for Server-Sent Events
header('Content-Type: text/event-stream');
// Disable caching to ensure real-time delivery
header('Cache-Control: no-cache');
// Keep the connection open
header('Connection: keep-alive');
// Disable buffering for Nginx
header('X-Accel-Buffering: no');

// Disable buffering for Real-time logs
// Check if running under Apache and disable gzip
if (function_exists('apache_setenv')) {
    @apache_setenv('no-gzip', 1);
}

// Disable zlib output compression
@ini_set('zlib.output_compression', 0);
// Enable implicit flushing
@ini_set('implicit_flush', 1);
// Flush all existing output buffers
for ($i = 0; $i < ob_get_level(); $i++) {
    ob_end_flush();
}
// turn on implicit flush
ob_implicit_flush(1);

// Suppress error output to the stream to avoid breaking JSON format
// We want to handle errors manually via the logger
ini_set('display_errors', 0);
// Report all errors internally
error_reporting(E_ALL);
// Set script execution time limit to 5 minutes
set_time_limit(300);


// Load custom functions
require_once __DIR__ . '/functions/_loader.php';
// Load configuration files
require_once __DIR__ . '/config/config.php';


// Get search query from GET request
$queryTopic = trim($_GET['query'] ?? '');
$selectedCountry = $_GET['country'] ?? 'de';
$selectedPeriod = $_GET['period'] ?? '1d';
$selectedLimit = (int)($_GET['limit'] ?? 5);
$selectedOutputLang = $_GET['output_lang'] ?? 'de';

// Get configuration for the selected country
$geoConfig = $countries[$selectedCountry] ?? $countries['de'];

// 1. AUTO-TRANSLATE QUERY
// Extract language code (first 2 chars of 'hl', e.g., 'de' from 'de-DE')
$targetLang = substr($geoConfig['hl'], 0, 2);
$countryName = $geoConfig['name'];

$resultHtml = "";

if (!empty($queryTopic)) {

    logger("🚀 Start processing query: '$queryTopic'", 'system');

    // Ask Gemini for the correct translation/adaptation of the query
    $searchQuery = translateQuery($queryTopic, $targetLang, $countryName, $GEMINI_API_KEY);

    logger("🌍 Query translated as: '$searchQuery', for region $countryName", 'info');

    // 2. SEARCH FOR LINKS
    // Search for news links using the new parameters
    $links = getNewsLinks($searchQuery, $selectedPeriod, $geoConfig);

    $foundCount = count($links);

    if ($foundCount === 0) {

        logger("❌ Nothing found for the query.", 'error');

        sendResult("<p>Unfortunately, no news was found for this query.</p>");

        exit();
    }

    logger("✅ $foundCount links found. Processing the first $selectedLimit...", 'info');

    // 3. PYTHON (DOWNLOADING)
    $fullContext = "";
    $processedCount = 0;

    // Slice array to process only the selected limit
    $linksToProcess = array_slice($links, 0, $selectedLimit);

    foreach ($linksToProcess as $link) {

        $processedCount++;
        logger("⏳ [$processedCount/$selectedLimit] Downloading article: <a href='{$link}' target='_blank'> {$link}", 'info');

        // Protects a string before passing it to the command line (shell)
        $cmd = "python3 news_fetcher.py " . escapeshellarg($link);
        $output = shell_exec($cmd);
        $data = json_decode($output, true);


        if ($data && isset($data['status']) && $data['status'] === 'success') {

            logger("📄 Article successfully downloaded (" . mb_strlen($data['text']) . " chars). Link: <a href='{$data['url']}' target='_blank'>{$data['url']}</a>", 'success');

            // Append text to full context
            $fullContext .= "\n\n=== ARTICLE $processedCount: {$data['url']} ===\n";
            // Limit text length per article to 15000 chars to save tokens
            $fullContext .= mb_substr($data['text'], 0, 15000);
        } else {
            // Log error if download failed
            logger("⚠️ Download error: " . ($data['error'] ?? 'Unknown error'), 'error');
        }
    }

    // 4. ANALYSIS (GEMINI)
    if ($fullContext) {

        logger("🧠 Sending data to Gemini for analysis...", 'system');

        // Get target language name for the prompt
        $targetLangName = $outputLanguages[$selectedOutputLang]['name'] ?? 'German';

        // Construct the prompt using HEREDOC
        $prompt = <<<EOT
        You are a strict, automated intelligence briefing system.
        Your task is to analyze news articles and generate a structured report.

        Context:
        - Source Region: $countryName
        - Search Query: "$queryTopic"
        - Target Language: $targetLangName

        STRICT OUTPUT RULES (CRITICAL):
        1.  **NO conversational fillers.** Do NOT say "Here is the report", "Based on the text", or "In summary".
        2.  **NO meta-descriptions.** Do not describe what you are doing. Just do it.
        3.  **Start IMMEDIATELY** with the main headline (Format: # Headline).
        4.  **Language:** The ENTIRE report must be in $targetLangName.
        5.  **Localize Headers:** You MUST translate the section headers ("Key Takeaways", "In-Depth Analysis") into $targetLangName.
      

        Report Structure:
        # 🌍 [Main Analytical Headline of the Event in $targetLangName]
        ## ⚡ [Translate "Key Takeaways" to $targetLangName]
        [Bullet points of the most important facts]
        ## 🔍 [Translate "In-Depth Analysis" to $targetLangName]
        [Detailed summary of the situation based on the articles]

        ---
        Input Articles:
        $fullContext
        EOT;

        // Clean up encoding just in case
        $prompt = mb_convert_encoding($prompt, 'UTF-8', 'UTF-8');

        // Prepare API payload
        $apiData = [
            "contents" => [["parts" => [["text" => $prompt]]]]
        ];

        // JSON PROTECTION
        // Encode payload, ignoring invalid UTF-8 sequences
        $jsonPayload = json_encode($apiData, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_IGNORE);

        // Check for JSON encoding errors
        if ($jsonPayload === false) {
            $errorMsg = "JSON encoding error: " . json_last_error_msg();
        } else {

            // Initialize cURL session
            $ch = curl_init("https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-lite:generateContent?key=" . $GEMINI_API_KEY);
            // Return response as string
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            // Set method to POST
            curl_setopt($ch, CURLOPT_POST, true);
            // Set headers
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            // Set payload
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);

            // Execute request
            $response = curl_exec($ch);

            if (curl_errno($ch)) {
                logger("Curl error: " . curl_error($ch), 'error');
            }

            curl_close($ch);

            // Decode API response
            $jsonResp = json_decode($response, true);

            // Check if valid content exists
            if (isset($jsonResp['candidates'][0]['content']['parts'][0]['text'])) {
                $md = htmlspecialchars($jsonResp['candidates'][0]['content']['parts'][0]['text']);

                // MARKDOWN PARSING (Basic Regex)
                // Convert bold text
                $md = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $md);
                // Convert H1
                $md = preg_replace('/^# (.*)$/m', '<h2>$1</h2>', $md);
                // Convert H2
                $md = preg_replace('/^## (.*)$/m', '<h3>$1</h3>', $md);

                //4. FIXING INDENTS: Remove line breaks IMMEDIATELY AFTER headings
                $md = preg_replace('/(<\/h[23]>)\s+/s', '$1', $md);

                // Convert newlines to breaks
                $resultHtml = nl2br($md);

                logger("✨ Analysis complete!", 'success');

                // SEND FINAL RESULT
                sendResult($resultHtml);
            } elseif (isset($jsonResp['error'])) {

                $err = $jsonResp['error']['message'] ?? 'Unknown';

                // Log API error
                logger("Gemini API Error: $err", 'error');

                // Send error to user
                sendResult("<p style='color:red'>API Error: $err</p>");
            } else {

                // Handle empty or unexpected response
                logger("Empty response received", 'error');

                sendResult("<p>Empty response received from the neural network.</p>");
            }
        }
    } else {

        // Handle case where context is empty
        logger("Failed to retrieve article text.", 'error');

        sendResult("<p>Failed to read content of found articles.</p>");
    }
}
