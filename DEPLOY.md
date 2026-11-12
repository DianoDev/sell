# Deploy — sell.areio.com.br

Mesmo VPS Hostinger, Traefik e Let's Encrypt do `web-ftv` (`areio.com.br` / `dev.areio.com.br`).

```
push master ──► GitHub Actions (build) ──► ghcr.io/dianodev/sell/{php-fpm,nginx}:prod-latest
                                   └──► (deploy) scp compose.yml + ssh: pull / up -d / migrate / optimize

Internet ─► Traefik :443 ──rede "proxy"──► sell-nginx:8080 ──rede "sell-internal"──► sell-fpm:9000
                                                                     sell-fpm ──rede "shared-prod"──► banco
```

| Item | Valor |
|---|---|
| Domínio | `sell.areio.com.br` |
| Branch | `master` |
| Pasta no VPS | `/dockerlabs/sell` |
| Containers | `sell-fpm`, `sell-nginx` |
| Router Traefik | `sell-prod` |

## Arquivos

| Arquivo | Função |
|---|---|
| `.docker/php-fpm/Dockerfile` | PHP 8.4-FPM com código, vendor e `public/build` |
| `.docker/php-fpm/php.ini`, `www.conf` | config do PHP/FPM (logs vão para `docker logs sell-fpm`) |
| `.docker/nginx/Dockerfile` | nginx unprivileged servindo `public/` |
| `.docker/nginx/nginx.conf`, `site.conf` | `fastcgi_pass sell-fpm:9000`; `/storage/` servido por alias |
| `compose.yml` | stack de produção + labels do Traefik |
| `.github/workflows/deploy.yml` | build, push, deploy e limpeza do GHCR |
| `.env.production.example` | modelo do `.env` do VPS |
| `.dockerignore` | mantém `.env`, `node_modules` e `tests` fora da imagem |

---

## 1. DNS (hPanel → Domínios → areio.com.br → DNS)

| Tipo | Nome | Valor |
|---|---|---|
| A | `sell` | IP do VPS (o mesmo de `areio.com.br`) |

Verifique com `dig +short sell.areio.com.br`.

## 2. VPS (uma vez só)

```bash
# 2.1 Rede compartilhada entre o Traefik e os sites
docker ps --format '{{.Names}}' | grep -i traefik          # descubra o nome do container
docker network create proxy
docker network connect proxy <nome-do-traefik>
```

> Coloque `proxy` como rede `external: true` também no compose do Traefik. Sem isso, a ligação se perde quando o Traefik for recriado.

```bash
# 2.2 Pasta, storage e .env
mkdir -p /dockerlabs/sell/storage/{app/public,logs} \
         /dockerlabs/sell/storage/framework/{cache/data,sessions,views}
chown -R 1000:33 /dockerlabs/sell/storage      # 1000 = www, 33 = www-data
chmod -R 775 /dockerlabs/sell/storage

nano /dockerlabs/sell/.env                       # conteúdo de .env.production.example
```

Gere o `APP_KEY` localmente com `php artisan key:generate --show` e cole no `.env`.

```bash
# 2.3 Banco (opção A: MySQL na rede shared-prod)
docker network inspect shared-prod --format '{{range .Containers}}{{.Name}} {{end}}'   # nome do container do banco → DB_HOST
docker exec -it <container-mysql> mysql -uroot -p -e "
  CREATE DATABASE sell CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
  CREATE USER 'sell'@'%' IDENTIFIED BY '<senha-forte>';
  GRANT ALL PRIVILEGES ON sell.* TO 'sell'@'%';
  FLUSH PRIVILEGES;"
```

Se a opção for SQLite: `touch /dockerlabs/sell/storage/database.sqlite && chown 1000:33 /dockerlabs/sell/storage/database.sqlite`.

## 3. GitHub (repo DianoDev/sell → Settings → Secrets and variables → Actions)

| Secret | Valor |
|---|---|
| `VPS_HOST` | IP do VPS |
| `VPS_USER` | usuário SSH (o mesmo do web-ftv) |
| `VPS_SSH_KEY` | chave privada SSH (a mesma do web-ftv) |

O VPS **não** precisa clonar o repositório: o workflow envia o `compose.yml` por scp.

## 4. Primeiro deploy

1. Push em `master` (ou Actions → Deploy → *Run workflow*).
2. `curl -I https://sell.areio.com.br` → `HTTP/2 200` com certificado válido.

## Diagnóstico

```bash
docker compose -f /dockerlabs/sell/compose.yml ps
docker logs -f sell-nginx
docker logs -f sell-fpm
tail -f /dockerlabs/sell/storage/logs/laravel.log
docker network inspect proxy --format '{{range .Containers}}{{.Name}} {{end}}'   # sell-nginx e o Traefik devem aparecer
docker logs <nome-do-traefik> 2>&1 | grep -i sell | tail
```

| Sintoma | Causa provável |
|---|---|
| `network proxy declared as external, but could not be found` | passo 2.1 não foi feito |
| 404 do Traefik | router não registrado: o Traefik não está na rede `proxy` |
| 502 / 504 | o nginx não alcança `sell-fpm`, ou o Traefik usa a rede errada |
| "TRAEFIK DEFAULT CERT" | DNS ainda não propagado |
| 500 "Permission denied" no storage | `chown -R 1000:33 /dockerlabs/sell/storage` |
| `SQLSTATE ... Connection refused` | `DB_HOST` errado ou o `sell-fpm` fora da rede `shared-prod` |

## Fila (opcional)

`QUEUE_CONNECTION=database` precisa de um worker. Quando for necessário, adicione ao `compose.yml`:

```yaml
  queue:
    container_name: sell-queue
    image: ${REGISTRY_IMAGE:-ghcr.io/dianodev/sell}/php-fpm:prod-${IMAGE_TAG:-latest}
    command: php artisan queue:work --sleep=3 --tries=3 --max-time=3600
    restart: unless-stopped
    env_file: [/dockerlabs/sell/.env]
    volumes: [/dockerlabs/sell/storage:/var/www/html/storage:rw]
    networks: [internal, shared-prod]
```
