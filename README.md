# 🌍 GAIN (Global AI News)

**GAIN** is a powerful tool for analyzing the global information field. It allows users to search for news topics across different regions (USA, Europe, Asia, etc.), automatically translating the search query into the local language, fetching relevant articles using advanced scraping techniques, and generating a structured AI summary using Google Gemini.

![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=flat&logo=php&logoColor=white)
![Python](https://img.shields.io/badge/Python-3.10%2B-3776AB?style=flat&logo=python&logoColor=white)
![Docker](https://img.shields.io/badge/Docker-Enabled-2496ED?style=flat&logo=docker&logoColor=white)
![Gemini AI](https://img.shields.io/badge/AI-Google%20Gemini-8E75B2?style=flat&logo=google&logoColor=white)
![License](https://img.shields.io/badge/License-MIT-green.svg)

## ✨ Features

-   **Multi-Region Search:** Supports searching in US, UK, Germany, France, Italy, Spain, Russia, China, Japan, India, Brazil, and Ukraine.
-   **Smart Translation:** Automatically translates your query (e.g., "Elections") into the target region's language to find authentic local sources.
-   **Advanced Scraping:** Uses `Playwright` to bypass cookie walls (GDPR consent) and `Trafilatura` to extract clean text from complex HTML.
-   **AI Analysis:** Uses Google Gemini API to read, analyze, and summarize news content in your preferred language.
-   **Real-time Logging:** Displays the backend process (searching, downloading, analyzing) in a real-time console via Server-Sent Events (SSE).
-   **Zero Database:** Runs entirely on-the-fly, requiring no database setup.

## 📂 Project Structure

```text
GAIN/
├── config/              # Configuration & Environment loader
├── css/                 # Frontend styles
├── functions/           # PHP Helper functions
├── js/                  # Frontend scripts
├── vendor/              # Composer dependencies (auto-generated)
├── .dockerignore        # Docker exclusion rules
├── .env.example         # Template for API keys
├── docker-compose.yml   # Container orchestration
├── Dockerfile           # Build instructions
├── index.php            # Main UI Entry Point
├── news_fetcher.py      # Python Scraper (Playwright + Trafilatura)
├── process.php          # Backend Handler (SSE Stream)
└── requirements.txt     # Python Dependencies
```


## 🚀 Installation & Setup

### Option 1: Docker (Recommended)

The easiest way to run GAIN is using Docker. It handles all dependencies (PHP, Python, Browsers) automatically.

1.  **Clone the repository:**
    
    Bash
    
        git clone https://github.com/igarok88/Global-AI-News.git
        cd Global-AI-News
    
2.  **Configure Environment:** Create the `.env` file from the example.
    
    Bash
    
        cp .env.example .env
    
    Open `.env` and paste your Google Gemini API Key:
         
        GEMINI_API_KEY=YourKeyHere
    
3.  **Build and Run:**
    
    Bash
    
        docker-compose up -d --build
    
4.  **Access the App:** Open your browser and go to: [http://localhost:8000](http://localhost:8000)
    

* * *

### Option 2: Manual Installation

If you prefer running without Docker, ensure you have **PHP 8.0+**, **Python 3.8+**, and **Composer** installed.

1.  **Install PHP Dependencies:**
    
    Bash
    
        composer install
    
2.  **Install Python Dependencies:**
    
    Bash
    
        pip install -r requirements.txt
    
3.  **Install Playwright Browsers:** Required for the scraper to work correctly.
    
    Bash
    
        playwright install chromium
        playwright install-deps
    
4.  **Configure Environment:** (Same as Docker step 2) - create `.env` file with your key.
    
5.  **Start Local Server:**
    
    Bash
    
        php -S localhost:8000
    
    Visit [http://localhost:8000](http://localhost:8000).

    

## ⚙️ Configuration

### API Key Management

GAIN offers a flexible way to manage your API Key:

1.  **Browser (Cookie):** You can enter your key directly in the UI (Settings ⚙️). This is stored locally in your browser and has the highest priority.
    
2.  **Server (.env):** Defined in the `.env` file. Used if no cookie key is found. Recommended for personal hosting.
    

### Python Scraper Logic

The `news_fetcher.py` script is designed to be robust:

-   It mimics a real user agent to avoid bot detection.
    
-   It automatically handles "Accept Cookies" popups using `Playwright`.
    
-   It uses `googlenewsdecoder` to resolve encrypted Google News links.
    

## 🛠 Tech Stack

-   **Frontend:** HTML5, CSS3, Vanilla JS, EventSource (SSE).
    
-   **Backend:** PHP (Logic & Streaming), Python (Scraping).
    
-   **AI Engine:** Google Gemini 2.0 flash via REST API (cURL).
    
-   **Python Libs:** `playwright`, `trafilatura`, `googlenewsdecoder`, `requests`.
    

## 📄 License

This project is licensed under the **MIT License**.




