# Estágio de construção do PHP
FROM php:8.2-fpm

# Instalar extensões do sistema e dependências do PHP
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip \
    unzip \
    git \
    curl \
    nginx

RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo pdo_mysql bcmath

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Definir diretório de trabalho
WORKDIR /var/www

# Copiar arquivos do projeto
COPY . .

# Instalar dependências do Laravel
RUN composer install --no-dev --optimize-autoloader

# Ajustar permissões para o Laravel
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

# Copiar configuração do Nginx (criaremos abaixo)
COPY ./docker/nginx.conf /etc/nginx/sites-available/default

# Expor a porta que o Render usa
EXPOSE 80

# Script para iniciar Nginx e PHP-FPM
CMD service nginx start && php-fpm
