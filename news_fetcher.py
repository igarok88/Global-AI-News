import sys
import json
import asyncio
from googlenewsdecoder import decoderv1
from trafilatura import extract
from playwright.async_api import async_playwright

"""
sys — для чтения аргумента от PHP;

json — чтобы распечатать результат;

asyncio — асинхронный запуск браузера;

googlenewsdecoder — расшифровка Google News redirect URL;

trafilatura — «умное извлечение» текста статьи;

playwright — запуск Chromium, загрузка DOM, выполнение JS.

"""

# Включаем поддержку UTF-8 для консоли Windows
sys.stdout.reconfigure(encoding='utf-8')

async def fetch_article(google_url):
    result = {"status": "error", "url": google_url, "text": ""} # Создаём шаблон ответа.
    
    # 1. Попытка декодировать ссылку алгоритмически 
    try:
        target_url = decoderv1(google_url)
        if not target_url:
            target_url = google_url
    except:
        target_url = google_url

    # 2. Запуск браузера для рендеринга (Playwright) 
    async with async_playwright() as p:
        try:
            browser = await p.chromium.launch(headless=True)
            # Маскируемся под обычный Chrome (Stealth mode) 
            context = await browser.new_context(
                user_agent='Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
            )
            page = await context.new_page() # Открываем страницу
            
            # Таймаут 20 сек, ждем загрузки DOM
            await page.goto(target_url, wait_until="domcontentloaded", timeout=20000)
            
            # Забираем HTML после отработки JS
            html_content = await page.content()
            
            # 3. Чистим мусор через Trafilatura 
            text = extract(html_content, include_comments=False, include_tables=False)
            
            if text:
                result = {
                    "status": "success",
                    "url": target_url,
                    "text": text
                }
            else:
                result["message"] = "Trafilatura returned empty text"
                
            await browser.close()
            
        except Exception as e:
            result["message"] = str(e)
            
    return result

if __name__ == "__main__":
    if len(sys.argv) > 1:
        # Получаем ссылку от PHP аргументом
        url = sys.argv[1]
        # Запускаем асинхронную функцию
        data = asyncio.run(fetch_article(url))
        # Важно: Выводим ТОЛЬКО JSON, чтобы PHP мог его прочитать
        print(json.dumps(data, ensure_ascii=False))
    else:
        print(json.dumps({"status": "error", "message": "No URL provided"}))