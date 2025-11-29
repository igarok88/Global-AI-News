# Используем официальный образ Playwright
FROM mcr.microsoft.com/playwright/python:v1.44.0-jammy

# ВАЖНО: Отключаем вопросы установщика (Fix зависания)
ENV DEBIAN_FRONTEND=noninteractive

# Рабочая папка
WORKDIR /app

# Шаг 1: Обновляем списки пакетов (отдельно, для кэширования)
RUN apt-get update

# Шаг 2: Устанавливаем PHP и утилиты
# Добавили --no-install-recommends для уменьшения размера и времени
RUN apt-get install -y --no-install-recommends \
    php \
    php-cli \
    php-curl \
    php-json \
    php-mbstring \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# Шаг 3: Python зависимости
COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt --break-system-packages

# Шаг 4: Код проекта
COPY . .

EXPOSE 8000
CMD ["php", "-S", "0.0.0.0:8000", "index.php"]