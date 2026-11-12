# AdTrack Architecture & Automation System (USA Affiliate Market)

## 📌 Visão Geral do Projeto

Este projeto consiste na arquitetura de um **ecossistema proprietário de rastreamento de dados ponta a ponta, automação de métricas e blindagem de tráfego (contingência)**. O objetivo principal é atuar como afiliado de alta performance no mercado dos Estados Unidos (EUA), promovendo infoprodutos e encapsulados (cápsulas/suplementos) via tráfego pago nas redes Google Ads, Bing Ads, Meta (Instagram) e TikTok Ads.

Como o ecossistema de afiliados tradicional sofre com "rastreamento cego" e perda de dados, este sistema unifica dados de custo de APIs de anúncios com notificações de conversão em tempo real do Digistore24, calculando o **ROI real por palavra-chave, criativo e anúncio**.

---

## 🛠️ Componentes do Ecossistema e Justificativa Científica

### 1. Camada de Anonimização e Gerenciamento de Identidade (AdsPower)

- **O que faz:** Isola perfis de navegação controlando parâmetros de hardware e rede através de impressões digitais de navegador (_Browser Fingerprinting_).
- **Motivo de uso:** O TikTok e o Google monitoram Cookies, IPs, Canvas, WebGL e MAC Address. O AdsPower garante isolamento total das contas publicitárias. Em caso de bloqueio em uma campanha de cápsula, as demais contas estruturadas no ecossistema não sofrem efeito cascata.
- **Integração Dev:** Utilização da _Local REST API_ do AdsPower integrada com Puppeteer/Playwright para automação de login e extração de cookies operacionais de contingência.

### 2. Camada de Roteamento de Tráfego e Firewall (Cloaker)

- **O que faz:** Intercepta requisições HTTP na raiz do servidor e analisa cabeçalhos (IP, User-Agent, ISP) para segmentar o tráfego de entrada.
- **Motivo de uso:** Campanhas de suplementos e infoprodutos agressivos nos EUA violam termos estritos das redes de anúncios. O Cloaker atua como um roteador condicional:
    - `IF` (Robô de análise da plataforma, ISP governamental ou requisição fora dos EUA) $\rightarrow$ Renderiza a **Safe Page** (Blog institucional leve/HTML estático dentro das regras).
    - `ELSE` (Comprador real dos EUA) $\rightarrow$ Renderiza a **Money Page** (Quiz interativo focado em conversão).

### 3. Frontend de Engajamento e Coleta de Parâmetros (Pre-sell App)

- **O que faz:** Aplicação SPA de altíssima performance (HTML/JS Puro, React ou Vue) baseada em uma arquitetura de micro-compromissos (Quiz/Formulários).
- **Motivo de uso:** Landing pages pesadas (WordPress/Elementor) destroem a taxa de conversão nos EUA devido à latência. Hospedada em CDN global (Cloudflare Pages/Vercel), a aplicação captura dinamicamente os tokens de cliques (`{gclid}`, `{msclkid}`, `__ttclid__`) da URL via `URLSearchParams` e injeta-os no momento do redirecionamento no parâmetro `ds24tr` do checkout da Digistore24.

### 4. Gateway de Notificações e Webhooks (Digistore24 IPN Listener)

- **O que faz:** Endpoint de backend (API REST) preparado para escutar eventos assíncronos enviados pelo gateway de pagamento da Digistore24.
- **Motivo de uso:** O tráfego pago nos EUA exige respostas imediatas de conversão. Ao receber o payload do IPN (Instant Payment Notification) contendo o valor da comissão em dólar e o `Click ID` original associado, o sistema executa o batimento e o cruzamento exato do lead comprador.

### 5. Worker de ETL e Consolidação de Métricas (Dashboard Integrator)

- **O que faz:** Scripts agendados (_Cron Jobs_) que consomem endpoints de relatórios das APIs das AdNetworks (Google Ads Query Language - GAQL, Meta Graph API, TikTok Marketing API e Microsoft Advertising API).
- **Motivo de uso:** Consolida dados de custo financeiro real gasto por clique. O banco de dados do sistema executa um `JOIN` relacional usando o `Click ID` como chave primária entre o custo da plataforma de anúncio e o faturamento recebido pelo Webhook do Digistore24, gerando um dashboard unificado de ROI real.

---

## 📐 Fluxo Lógico do Clique e Dados
