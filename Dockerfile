# Usamos a imagem oficial e levíssima do FrankenPHP baseada no Alpine Linux
FROM dunglas/frankenphp:php8.3-alpine

# 1. Configura a porta que o Franken irá responder dentro do container
ENV SERVER_NAME=":8000"

# 2. Ativa o cobiçado "Worker Mode", dizendo pro Franken qual é o arquivo principal que ficará em memória
ENV FRANKENPHP_CONFIG="worker ./public/index.php"

# 3. Instala extensões de banco e extras que o PHP costuma precisar num framework (Opcional, mas útil)
RUN install-php-extensions \
    pdo_mysql \
    pdo_sqlite \
    gd \
    intl \
    zip \
    bcmath \
    opcache

# Define a pasta padrão de trabalho dentro do sistema virtual
WORKDIR /app

# Copia tudo que está nesta pasta do Windows para o disco do Container
COPY . /app

# (Opcional) Libera escrita na pasta 'storage' ou de logs caso o seu framework comece a salvar arquivos de fato
# RUN chmod -R 777 /app/storage
