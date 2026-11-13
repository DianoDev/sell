# Projeto: plataforma de tracking, métricas de tráfego pago e geração de vídeos

> **Status:** escopo validado, desenvolvimento ainda não iniciado. Nada foi implementado além do starter kit.
> **Última atualização:** 2026-11-12

Este documento é a fonte da verdade do projeto: o que entra, o que fica de fora, a arquitetura e o andamento. A seção [Andamento](#11-andamento) será atualizada a cada entrega.

---

## 1. Resumo

Um sistema Laravel único com três produtos internos:

1. **Tracking e atribuição** — captura cada clique nas páginas presell, recebe as vendas da Digistore24 e liga uma coisa à outra.
2. **Métricas de tráfego pago** — puxa custo e desempenho das APIs oficiais (Google Ads, Microsoft/Bing Ads, Meta Ads, TikTok Ads) e calcula ROI real por campanha, grupo, anúncio e palavra-chave.
3. **Gerador de vídeos** — produz criativos com APIs de IA usando as chaves do próprio usuário.

Tudo é configurável por **conexões**: cada plataforma é um driver atrás de um contrato comum, então adicionar uma nova rede é escrever um driver, não mexer no núcleo.

---

## 2. O que fica de fora (e por quê)

| Item do `sistema.md`                                                               | Decisão                                 | Motivo                                                                                                                                                                                                                                                                                                                                                                                                                 |
| ---------------------------------------------------------------------------------- | --------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Cloaker** (safe page para o revisor, money page para o comprador)                | **Não será projetado nem desenvolvido** | Não é limitação técnica: é mostrar ao revisor da plataforma algo diferente do que o consumidor vê, para veicular anúncios que a própria descrição diz violar as políticas. Eu não construo isso. É também a causa mais comum de banimento permanente (política de "circumventing systems" no Google, equivalente em Meta, TikTok e Microsoft), porque as plataformas revisam com IPs residenciais e navegadores reais. |
| **AdsPower / navegador anti-detect** e **proxies residenciais** para isolar contas | **Não será integrado**                  | O objetivo descrito é impedir que as plataformas liguem contas entre si depois de um bloqueio, ou seja, evasão de banimento. Fora isso, fazer um anti-detect do zero é inviável (é um fork do Chromium mantido continuamente).                                                                                                                                                                                         |
| **Garantia de não ter conta banida**                                               | **Impossível**                          | Nenhum sistema garante isso. O que dá para fazer é reduzir o risco pelo caminho legítimo (seção 7).                                                                                                                                                                                                                                                                                                                    |
| **JOIN de custo por Click ID**                                                     | **Impossível como descrito**            | Nenhuma plataforma entrega custo por clique individual. O custo vem agregado por dia e por entidade. A solução está na seção 5.                                                                                                                                                                                                                                                                                        |

O restante do `sistema.md` e do `possiveis_melhorias.md` é viável e está no projeto: presell rápida, captura de click IDs, IPN da Digistore24, ETL das redes, API de conversões server-side, Redis e cache de borda.

**Roteamento por país continua permitido**, desde que a regra seja a mesma para qualquer visitante daquele país (inclusive o revisor). O que não entra é detectar quem é o revisor.

---

## 3. Viabilidade por integração

| Plataforma               | Ler métricas        | Enviar conversões                    | Pré-requisito                       | Observações                                                                                                                                                               |
| ------------------------ | ------------------- | ------------------------------------ | ----------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Google Ads**           | Sim (GAQL)          | Sim, conversão offline por `gclid`   | Developer token aprovado + OAuth    | Tem palavra-chave e termo de busca. Aprovação do token leva dias.                                                                                                         |
| **Microsoft (Bing) Ads** | Sim (Reporting API) | Sim, conversão offline por `msclkid` | Developer token + OAuth             | Relatórios são assíncronos (solicita, espera, baixa).                                                                                                                     |
| **Meta Ads**             | Sim (Insights API)  | Sim (Conversions API)                | App Meta + token de system user     | Sem palavra-chave. Para contas próprias não exige app review.                                                                                                             |
| **TikTok Ads**           | Sim (Reporting API) | Sim (Events API)                     | App aprovado no TikTok for Business | Sem palavra-chave. Aprovação do app é manual.                                                                                                                             |
| **Digistore24**          | Sim (IPN + API)     | —                                    | Conexão IPN configurada na conta    | Vendas, reembolsos, chargebacks e recorrências chegam por IPN. **A validar:** nome exato do parâmetro de tracking no checkout e quais campos o IPN entrega para afiliado. |
| **IA de vídeo**          | —                   | —                                    | Chave de API do usuário             | Ver seção 6.                                                                                                                                                              |

Cada rede tem limite de requisições e atraso próprio nos dados de custo (em geral algumas horas). O dashboard mostrará a hora da última sincronização por conta.

---

## 4. Arquitetura

```
 Anúncio ──► Presell (domínio próprio) ──► Checkout Digistore24
                │ grava clique                    │
                ▼                                 ▼ IPN
          ┌──────────┐                     ┌─────────────┐
          │  clicks  │◄──── atribuição ────│ conversions │
          └──────────┘                     └──────┬──────┘
                ▲                                 │ fila
                │ ids de campanha/anúncio         ▼
          ┌─────────────────┐            Envio de conversão
          │ ad_metrics_daily│            (Google, Microsoft,
          └────────▲────────┘             Meta, TikTok)
                   │ agendador + fila
        APIs oficiais das redes de anúncio
```

**Stack:** a que já está instalada — Laravel 13, PHP 8.5, Inertia 3 + Vue 3, Tailwind 4, Fortify, Wayfinder, Pest.

**Hospedagem:** VPS Hostinger. No mesmo servidor rodam a aplicação, o Redis, o worker de filas (Horizon sob Supervisor), o agendador (cron) e o FFmpeg.

**Dependências novas (aprovadas em 2026-11-12):**

| Dependência                                                    | Para quê                                                                                                                             |
| -------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------ |
| Redis + `laravel/horizon`                                      | Filas de sincronização e de vídeo, cache das presells                                                                                |
| SDKs oficiais (Google Ads, Microsoft Ads, Meta) ou HTTP direto | Decisão por driver; a recomendação é HTTP direto onde a API é REST simples (Meta, TikTok) e SDK onde é complexa (Google, Microsoft)  |
| FFmpeg no servidor de filas                                    | Montagem final dos vídeos                                                                                                            |
| Armazenamento de mídia                                         | No início, disco da VPS (100 GB). O código usa o `Storage` do Laravel, então migrar para um bucket S3 depois é troca de configuração |

### 4.1 Padrão de drivers

Três contratos, um por tipo de integração. Cada plataforma implementa o seu:

- `AdPlatform` — listar contas, sincronizar estrutura, buscar métricas diárias, enviar conversão.
- `AffiliateNetwork` — validar assinatura do webhook, normalizar o evento, montar a URL de checkout com o identificador do clique.
- `VideoProvider` — iniciar geração, consultar status, baixar resultado, estimar custo.

Um `manager` resolve o driver a partir do tipo da conexão, no mesmo estilo de `Storage::disk()`.

### 4.2 Estrutura de pastas proposta

`app/Integrations` e `app/Enums` são pastas novas, já aprovadas.

```
app/
├── Actions/
│   ├── Tracking/        RegisterClick, AttributeConversion
│   ├── Metrics/         SyncAdAccount, BuildRoiReport
│   ├── Conversions/     UploadConversion
│   └── Video/           StartVideoGeneration, AssembleVideo
├── Enums/               Platform, EntityLevel, ConversionEvent, GenerationStatus
├── Integrations/
│   ├── Ads/             Contracts/AdPlatform + GoogleAds, MicrosoftAds, MetaAds, TikTokAds
│   ├── Affiliate/       Contracts/AffiliateNetwork + Digistore24
│   └── Video/           Contracts/VideoProvider + um driver por provedor
├── Http/
│   ├── Controllers/     painel (Inertia), Presell/, Webhooks/
│   ├── Middleware/      FlagInvalidTraffic
│   └── Requests/
├── Jobs/                SyncAdMetrics, ProcessWebhookEvent, UploadConversion, PollVideoGeneration
└── Models/
routes/
├── web.php              painel
├── presell.php          páginas públicas e redirecionamento ao checkout
└── webhooks.php         IPN da Digistore24 e callbacks dos provedores
```

---

## 5. Modelo de dados

O sistema é de uso individual: não há `workspaces` nem separação por cliente. Todos os dados pertencem à única instalação, e o login existe só para proteger o painel.

**Conexões**

| Tabela                 | Conteúdo                                                                                        |
| ---------------------- | ----------------------------------------------------------------------------------------------- |
| `platform_connections` | Tipo (ads, afiliado, IA), plataforma, credenciais **criptografadas**, validade do token, status |
| `ad_accounts`          | Contas de anúncio de cada conexão: id externo, moeda, fuso                                      |

**Estrutura e métricas**

| Tabela             | Conteúdo                                                                                                 |
| ------------------ | -------------------------------------------------------------------------------------------------------- |
| `ad_entities`      | Espelho de campanha, grupo, anúncio e palavra-chave numa tabela só (`level`, `parent_id`, `external_id`) |
| `ad_metrics_daily` | Impressões, cliques, custo (inteiro em micros) e conversões da plataforma, por entidade e dia            |
| `exchange_rates`   | Câmbio diário para consolidar moedas                                                                     |
| `sync_runs`        | Histórico de cada sincronização: período, status, erro                                                   |

**Tracking**

| Tabela               | Conteúdo                                                                                                                                                                    |
| -------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `offers`             | Produto da rede de afiliados: id, URL de checkout, comissão                                                                                                                 |
| `presell_pages`      | Domínio, slug, oferta, tipo (quiz, advertorial), conteúdo em JSON, status                                                                                                   |
| `clicks`             | Id público, página, plataforma, click id (`gclid`, `msclkid`, `ttclid`, `fbclid`), ids externos de campanha/grupo/anúncio/palavra-chave, país, marcação de tráfego inválido |
| `conversions`        | Clique atribuído, pedido, evento (venda, reembolso, chargeback, recorrência), valor, moeda                                                                                  |
| `conversion_uploads` | Envio de cada conversão a cada rede: status, tentativas, resposta                                                                                                           |
| `webhook_events`     | Payload bruto de todo webhook, para idempotência e reprocessamento                                                                                                          |

**Vídeo**

| Tabela              | Conteúdo                                             |
| ------------------- | ---------------------------------------------------- |
| `video_projects`    | Roteiro, oferta, formato (9:16, 1:1, 16:9), status   |
| `video_scenes`      | Cenas do projeto: prompt, narração, ordem            |
| `video_generations` | Cada chamada ao provedor: job externo, status, custo |
| `media_assets`      | Arquivos gerados ou enviados                         |

**Conformidade**

| Tabela              | Conteúdo                                                                       |
| ------------------- | ------------------------------------------------------------------------------ |
| `compliance_checks` | Resultado da checagem de um criativo ou página contra as políticas de uma rede |

**Configuração e sistema**

| Tabela          | Conteúdo                                                                                       |
| --------------- | ---------------------------------------------------------------------------------------------- |
| `settings`      | Configuração geral em chave e valor, por grupo, com tipo e marcação de valor criptografado     |
| `notifications` | Alertas do sistema (anúncio reprovado, token expirando), no formato de notificações do Laravel |
| `users`         | Ganhou `is_admin`, que libera a área de administração                                          |

### Convenções das migrations

- **Dinheiro em micros** (inteiro, 1 unidade = 1.000.000): `cost_micros`, `amount_micros`, `commission_micros`. Evita erro de arredondamento e é o formato do Google Ads. `amount_micros` aceita negativo para reembolso e chargeback.
- **Valores fechados em `string`**, validados no código por enum, para não precisar de migration a cada valor novo:

| Coluna                           | Valores                                                                                                      |
| -------------------------------- | ------------------------------------------------------------------------------------------------------------ |
| `platform_connections.category`  | `ads`, `affiliate`, `ai`                                                                                     |
| `platform_connections.platform`  | `google_ads`, `microsoft_ads`, `meta_ads`, `tiktok_ads`, `digistore24`, `creatify`, `arcads`                 |
| `platform_connections.status`    | `pending`, `active`, `expired`, `error`, `disabled`                                                          |
| `ad_entities.level`              | `campaign`, `ad_group`, `ad`, `keyword`                                                                      |
| `conversions.event`              | `sale`, `rebill`, `refund`, `chargeback`                                                                     |
| `webhook_events.status`          | `pending`, `processed`, `failed`, `ignored`                                                                  |
| `conversion_uploads.status`      | `pending`, `uploaded`, `failed`, `skipped`                                                                   |
| `sync_runs.status`               | `running`, `completed`, `failed`                                                                             |
| `presell_pages.type` / `.status` | `advertorial`, `quiz` / `draft`, `published`, `archived`                                                     |
| `video_projects.status`          | `draft`, `generating`, `assembling`, `completed`, `failed`                                                   |
| `video_generations.status`       | `queued`, `processing`, `completed`, `failed`                                                                |
| `media_assets.kind`              | `video`, `image`, `audio`                                                                                    |
| `compliance_checks.status`       | `passed`, `warning`, `failed`                                                                                |
| `settings.type`                  | `string`, `integer`, `boolean`, `json`                                                                       |

- **Idempotência no banco**, não só no código: `webhook_events (source, external_id)`, `conversions (network, external_transaction_id, event)`, `ad_metrics_daily (ad_entity_id, date)`, `conversion_uploads (conversion_id, platform_connection_id)`.
- **Histórico preservado**: clique e conversão sobrevivem à exclusão da página, da oferta ou da conexão (chave estrangeira vira nula). Conexão com gerações de vídeo não pode ser apagada de vez, para não perder o custo registrado.
- **Exclusão lógica** em `platform_connections`, `offers`, `presell_pages`, `video_projects` e `media_assets`.
- **Segredos**: `platform_connections.credentials` é texto para receber o cast `encrypted`; `settings.is_encrypted` marca os valores que seguem a mesma regra.

### Como o ROI é calculado sem custo por clique

1. A URL do anúncio carrega os ids da própria rede por macro (`{campaignid}`, `{adgroupid}`, `{creative}`, `{keyword}` no Google; equivalentes nas outras).
2. A presell grava esses ids junto com o click id em `clicks`.
3. O link para o checkout leva o id público do clique; a Digistore24 o devolve no IPN.
4. A venda entra em `conversions` já ligada ao clique, portanto à campanha, ao anúncio e à palavra-chave.
5. O relatório soma **receita por entidade** (via cliques) e compara com o **custo por entidade** de `ad_metrics_daily`.

O resultado é ROI por palavra-chave, anúncio e criativo, que é o objetivo do `sistema.md`, sem depender de um dado que as redes não fornecem.

---

## 6. Gerador de vídeos

**Fluxo:** roteiro (escrito ou gerado por IA) → divisão em cenas → geração de cada cena no provedor → narração → montagem com FFmpeg (cortes, legenda, trilha, formato) → biblioteca de criativos.

**Como funciona por baixo:**

- O usuário cadastra as próprias chaves; elas ficam criptografadas em `platform_connections`.
- Toda geração é assíncrona: um job inicia, outro consulta o status até concluir. Nada bloqueia a tela.
- Cada provedor é um driver de `VideoProvider`. Estilo escolhido: cenas ou imagens com narração.
- **Creatify AI** é o primeiro driver: tem API pública para gerar vídeo a partir de roteiro ou URL, com narração.
- **Arcads** é o segundo candidato. **A validar:** se o seu plano dá acesso à API; sem API não há como integrar, e o driver não será feito.
- Como esses provedores já entregam o vídeo narrado, o FFmpeg fica para ajustes finais: formato, corte, legenda e junção de cenas.

**Limites reais:**

- Cada geração entrega clipes curtos (em geral 5 a 10 segundos). Vídeo longo é montagem de várias cenas.
- Custa por segundo gerado. O sistema mostra a estimativa antes e registra o gasto depois.
- Qualidade e consistência de personagem entre cenas variam por provedor.
- Depoimento falso de cliente ou médico gerado por IA não entra: é proibido pelas redes e pela regra da FTC sobre avaliações falsas, e derruba conta.

---

## 7. Como reduzir banimento pelo caminho legítimo

É a parte que substitui o cloaker e o AdsPower.

| Recurso                              | O que faz                                                                                                                                                                                                 |
| ------------------------------------ | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Checagem prévia de conformidade**  | Antes de publicar, uma IA analisa o texto do anúncio, o roteiro do vídeo e a presell contra as políticas de cada rede (alegações de saúde, antes e depois, promessa de resultado) e aponta o que reprova. |
| **Uma página só**                    | A presell que o revisor vê é a que o comprador vê. A agressividade fica dentro do permitido, não escondida.                                                                                               |
| **Acesso só por API oficial**        | OAuth e tokens oficiais. Sem automação de navegador nem raspagem, que são motivo de bloqueio por si só.                                                                                                   |
| **Várias contas do jeito suportado** | MCC no Google, Business Manager na Meta, Business Center no TikTok. O sistema aceita N conexões por plataforma.                                                                                           |
| **Alertas**                          | Anúncio reprovado, conta com restrição, token expirando e queda brusca de entrega viram notificação.                                                                                                      |
| **Saúde de domínio**                 | Um domínio por oferta, HTTPS, páginas de política e contato, tempo de carregamento monitorado.                                                                                                            |
| **Filtro de tráfego inválido**       | Marca bots e datacenters em `clicks` para limpar as métricas. Todos recebem a mesma página; o filtro só afeta relatório.                                                                                  |

Se a oferta não passa numa rede mesmo com página e criativo ajustados, o caminho é trocar de oferta ou de rede, não esconder a página.

---

## 8. Presell

- Servidas pelo próprio Laravel em rotas públicas, em Blade leve com cache de página inteira. Inertia fica só no painel.
- Domínio por página, configurado no painel e apontado via DNS.
- O script da página lê os parâmetros da URL, registra o clique e monta o link do checkout com o id do clique.
- Cache de borda (Cloudflare) na frente é opcional e não muda o código.

---

## 9. Fases de entrega

| Fase                                  | Entrega                                                                                            | Depende de                                |
| ------------------------------------- | -------------------------------------------------------------------------------------------------- | ----------------------------------------- |
| **0. Fundação**                       | Conexões com credenciais criptografadas, Redis e Horizon, agendador, enums e contratos dos drivers | —                                         |
| **1. Tracking**                       | Presell, registro de clique, IPN da Digistore24, atribuição                                        | Validar parâmetros da Digistore24         |
| **2. Métricas**                       | Drivers Google, Microsoft, Meta, TikTok; dashboard de ROI                                          | Tokens e apps aprovados                   |
| **3. Retorno de conversões**          | Envio server-side das vendas às quatro redes                                                       | Fases 1 e 2                               |
| **4. Vídeos**                         | Projetos, cenas, geração, montagem, biblioteca                                                     | Chave da Creatify; acesso à API da Arcads |
| **5. Conformidade e alertas**         | Checagem prévia, notificações                                                                      | Fase 2                                    |
| **6. Gestão de campanhas (opcional)** | Pausar, ajustar orçamento e publicar criativo pela API                                             | Permissões de escrita nas redes           |

As contas e os tokens de Google, Microsoft, Meta e TikTok serão criados por você. As fases 0 e 1 não dependem deles.

---

## 10. Decisões

**Tomadas em 2026-11-12**

| Tema                      | Decisão                                                                                  |
| ------------------------- | ---------------------------------------------------------------------------------------- |
| Uso                       | Individual, para validar o software. Sem `workspaces`.                                   |
| Vídeo                     | Creatify AI ou Arcads; cenas ou imagens com narração.                                    |
| Pastas e dependências     | `app/Integrations`, `app/Enums`, Redis com Horizon, FFmpeg e armazenamento S3 aprovados. |
| Hospedagem                | VPS Hostinger.                                                                           |
| Contas e tokens das redes | Criados pelo usuário.                                                                    |
| Arquivos de vídeo         | Disco da VPS (100 GB) no início; bucket S3 fica para quando o espaço apertar.            |

**Em aberto**

1. **Ordem das redes na fase 2:** recomendo Google e Microsoft primeiro (têm palavra-chave), depois Meta e TikTok.
2. **Arcads:** confirmar acesso à API no seu plano.
3. **Digistore24:** confirmar o parâmetro de tracking do checkout e os campos do IPN para afiliado.

---

## 11. Andamento

| Data       | O que foi feito                                                                                                                        |
| ---------- | -------------------------------------------------------------------------------------------------------------------------------------- |
| 2026-11-12 | Starter kit já instalado (Laravel 13, Inertia/Vue, Fortify). Git configurado para o repositório. Este documento criado para validação. |
| 2026-11-12 | Escopo validado: uso individual, Creatify/Arcads para vídeo, pastas e dependências aprovadas, hospedagem em VPS Hostinger.             |
| 2026-11-12 | Padrão de desenvolvimento: skills próprias em `.claude/skills` (`datatable`, `popup-form`, `inertia-crud`, `auth-autorizacao`, `seguranca`) e componentes base do painel (DataTable, Popup, ConfirmationPopup, PopupButton, SortableList). `.claude` passou a ser versionado. |
| 2026-11-12 | Base de dados: 20 migrations cobrindo todas as tabelas da seção 5. Módulo admin com cadastro de usuários em `/admin/users` (gate `access-admin`, senha confirmada). Página inicial nova. |
