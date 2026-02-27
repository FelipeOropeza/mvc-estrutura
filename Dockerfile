# Usamos a imagem oficial do FrankenPHP baseada no Debian (Maior compatibilidade com Cloud/Render)
FROM dunglas/frankenphp:php8.3-bookworm

# 1. Permite que serviços em nuvem (como Render/Railway) injetem a Porta dinamicamente usando a variavel $PORT
# Caso rode local no seu PC, ele assume a 8000
ENV SERVER_NAME=":${PORT:-8000}"

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
    opcache \
    @composer

# 4. TRUQUE MÁGICO PARA A RENDER:
# A Render bloqueia binários que exigem "Linux Capabilities" avançados (como abrir portas 80/443).
# Como o Franken vem com isso de fábrica, a nuvem tomava susto e dava "Operation not permitted (126)".
# Copiar e colar o arquivo remove os atributos de permissões extras e libera a execução 100% normal lá!
RUN cp /usr/local/bin/frankenphp /usr/local/bin/frankenphp.tmp && \
    mv /usr/local/bin/frankenphp.tmp /usr/local/bin/frankenphp

# Define a pasta padrão de trabalho dentro do sistema virtual
WORKDIR /app

# Copia tudo que está nesta pasta do Windows para o disco do Container
COPY . /app

# 5. Instala os pacotes do Composer dentro do container (já que ignoramos a pasta /vendor no .dockerignore)
RUN composer install --no-dev --optimize-autoloader

# (Opcional) Libera escrita na pasta 'storage' ou de logs caso o seu framework comece a salvar arquivos de fato
# RUN chmod -R 777 /app/storage
