FROM nginxinc/nginx-unprivileged:1.29-alpine3.22

# Somente os arquivos estáticos — o PHP roda no container sell-fpm
COPY public /var/www/html/public

COPY .docker/nginx/site.conf /etc/nginx/conf.d/default.conf
COPY .docker/nginx/nginx.conf /etc/nginx/nginx.conf

WORKDIR /var/www/html

ENV TZ=America/Cuiaba

EXPOSE 8080
