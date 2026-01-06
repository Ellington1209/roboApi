FROM php:8.3-fpm

# Instalar dependências do sistema
RUN apt-get update && apt-get install -y \
    libpq-dev \
    git \
    unzip \
    && docker-php-ext-install pdo_pgsql \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Definir diretório de trabalho
WORKDIR /var/www

# Copiar apenas composer.json e composer.lock primeiro (para cache do Docker)
COPY composer.json composer.lock* ./

# Instalar dependências do Composer
RUN composer install --no-dev --optimize-autoloader --no-scripts --no-interaction || true

# Copiar o resto dos arquivos
COPY . .

# Executar scripts do composer e otimizar autoloader
RUN composer dump-autoload --optimize

# Definir permissões
RUN chown -R www-data:www-data /var/www \
    && chmod -R 755 /var/www/storage \
    && chmod -R 755 /var/www/bootstrap/cache

