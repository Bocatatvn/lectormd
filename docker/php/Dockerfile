FROM php:8.2-apache

# Instalar extensiones necesarias y dependencias para compilar extensiones
# Instalar extensiones de PHP necesarias

# Dependencias del sistema

RUN apt-get update && apt-get install -y \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libzip-dev \
    libicu-dev \
    zip \
    unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        gd \
        intl \
        zip \
        pdo \
        pdo_mysql \
        mysqli \
    && a2enmod rewrite \
    && a2enmod headers \
    && a2dissite 000-default.conf \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*



# Instalar Composer
#COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configurar PHP
#COPY conf/php.ini /usr/local/etc/php/conf.d/custom.ini

# Configurar Apache DocumentRoot
ENV APACHE_DOCUMENT_ROOT /var/www/html
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Configurar ServerName para Apache
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf
RUN chown -R www-data:www-data /var/www/html
WORKDIR /var/www/html






