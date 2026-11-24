FROM mcr.microsoft.com/devcontainers/php:8.3

# pdo_mysql — драйвер MySQL для PHP; tcpdump/tshark — перехоплення трафіку; mysql-клієнт — консоль
RUN docker-php-ext-install pdo_mysql \
 && apt-get update \
 && DEBIAN_FRONTEND=noninteractive apt-get install -y --no-install-recommends \
      tcpdump tshark default-mysql-client \
 && rm -rf /var/lib/apt/lists/*
