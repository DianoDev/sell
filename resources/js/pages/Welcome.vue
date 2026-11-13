<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { dashboard, login } from '@/routes';

const page = usePage();

const appName = computed(() => page.props.name);
const isSignedIn = computed(() => Boolean(page.props.auth.user));

const journey = [
    {
        stage: 'Anúncio',
        detail: 'O identificador do clique chega na URL, junto com campanha, anúncio e palavra-chave.',
    },
    {
        stage: 'Presell',
        detail: 'A página grava o clique antes de qualquer coisa acontecer.',
    },
    {
        stage: 'Checkout',
        detail: 'O clique segue no link até o checkout da Digistore24.',
    },
    {
        stage: 'Venda',
        detail: 'A notificação de venda volta trazendo o mesmo clique.',
    },
    {
        stage: 'Retorno',
        detail: 'Receita e custo se encontram na mesma linha.',
    },
];

const sampleRows = [
    {
        keyword: 'joint support supplement',
        network: 'Google Ads',
        cost: 'US$ 412,80',
        revenue: 'US$ 1.036,00',
        roi: '+151%',
        positive: true,
    },
    {
        keyword: 'sleep aid capsules',
        network: 'Microsoft Ads',
        cost: 'US$ 198,40',
        revenue: 'US$ 444,00',
        roi: '+124%',
        positive: true,
    },
    {
        keyword: 'vídeo 9:16, gancho B',
        network: 'TikTok Ads',
        cost: 'US$ 265,10',
        revenue: 'US$ 296,00',
        roi: '+12%',
        positive: true,
    },
    {
        keyword: 'carrossel, público frio',
        network: 'Meta Ads',
        cost: 'US$ 301,75',
        revenue: 'US$ 148,00',
        roi: '−51%',
        positive: false,
    },
];

const capabilities = [
    {
        name: 'Rastreamento',
        description:
            'Cada clique das suas páginas fica registrado e é ligado à venda quando ela acontece, inclusive reembolsos e recorrências.',
        sources: 'Páginas presell, Digistore24',
    },
    {
        name: 'Métricas de tráfego',
        description:
            'Custo, cliques e impressões entram todos os dias pelas APIs oficiais e são comparados com a receita real, não com a estimada pela rede.',
        sources: 'Google Ads, Microsoft Ads, Meta Ads, TikTok Ads',
    },
    {
        name: 'Retorno de conversões',
        description:
            'As vendas confirmadas voltam para as redes pelo servidor, para que o algoritmo de cada uma aprenda com o que realmente vendeu.',
        sources: 'Conversões offline, Conversions API, Events API',
    },
    {
        name: 'Vídeos com IA',
        description:
            'Roteiro, cenas e narração viram criativos prontos para anunciar, com o custo de cada geração registrado.',
        sources: 'Suas chaves de API',
    },
];
</script>

<template>
    <Head title="Do clique à venda">
        <link rel="preconnect" href="https://fonts.bunny.net" />
        <link
            rel="stylesheet"
            href="https://fonts.bunny.net/css?family=chakra-petch:500,600"
        />
    </Head>

    <div class="landing min-h-screen">
        <header
            class="mx-auto flex max-w-6xl items-center justify-between px-6 py-6"
        >
            <span class="display text-lg font-semibold tracking-wide">
                {{ appName }}
            </span>

            <Link
                :href="isSignedIn ? dashboard() : login()"
                class="action action-quiet"
            >
                {{ isSignedIn ? 'Abrir o painel' : 'Entrar' }}
            </Link>
        </header>

        <main class="mx-auto max-w-6xl px-6">
            <section class="pt-14 pb-20 md:pt-24">
                <h1
                    class="display max-w-4xl text-5xl leading-[1.02] font-semibold md:text-7xl"
                >
                    Do clique à venda, numa linha só.
                </h1>

                <p
                    class="mt-8 max-w-2xl text-lg leading-relaxed text-(--muted)"
                >
                    {{ appName }} registra cada clique das suas páginas, recebe
                    as vendas da Digistore24 e mostra o retorno real por
                    campanha, anúncio e palavra-chave.
                </p>

                <Link
                    :href="isSignedIn ? dashboard() : login()"
                    class="action action-solid mt-10"
                >
                    {{ isSignedIn ? 'Abrir o painel' : 'Entrar no painel' }}
                </Link>

                <ol class="trace mt-20" aria-label="Caminho de um clique">
                    <li
                        v-for="(step, index) in journey"
                        :key="step.stage"
                        class="trace-step"
                        :style="{ '--i': index }"
                    >
                        <span class="trace-node" aria-hidden="true" />
                        <h2 class="display text-xl font-medium">
                            {{ step.stage }}
                        </h2>
                        <p class="mt-2 text-sm leading-relaxed text-(--muted)">
                            {{ step.detail }}
                        </p>
                    </li>
                </ol>
            </section>

            <section
                class="grid gap-10 border-t border-(--line) py-20 lg:grid-cols-[1fr_1.6fr]"
            >
                <div>
                    <h2 class="display text-3xl font-semibold">
                        O retorno que a rede não mostra
                    </h2>
                    <p class="mt-4 max-w-md leading-relaxed text-(--muted)">
                        As redes informam o custo. A venda acontece em outro
                        lugar. Aqui as duas pontas aparecem lado a lado, até o
                        nível da palavra-chave e do criativo.
                    </p>
                </div>

                <figure class="ledger">
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[34rem] text-left text-sm">
                            <thead>
                                <tr class="text-(--muted)">
                                    <th
                                        scope="col"
                                        class="ledger-cell font-normal"
                                    >
                                        Palavra-chave ou criativo
                                    </th>
                                    <th
                                        scope="col"
                                        class="ledger-cell font-normal"
                                    >
                                        Rede
                                    </th>
                                    <th
                                        scope="col"
                                        class="ledger-cell text-right font-normal"
                                    >
                                        Custo
                                    </th>
                                    <th
                                        scope="col"
                                        class="ledger-cell text-right font-normal"
                                    >
                                        Receita
                                    </th>
                                    <th
                                        scope="col"
                                        class="ledger-cell text-right font-normal"
                                    >
                                        ROI
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="tabular-nums">
                                <tr
                                    v-for="row in sampleRows"
                                    :key="row.keyword"
                                    class="border-t border-(--line)"
                                >
                                    <td class="ledger-cell">
                                        {{ row.keyword }}
                                    </td>
                                    <td class="ledger-cell text-(--muted)">
                                        {{ row.network }}
                                    </td>
                                    <td class="ledger-cell text-right">
                                        {{ row.cost }}
                                    </td>
                                    <td class="ledger-cell text-right">
                                        {{ row.revenue }}
                                    </td>
                                    <td
                                        class="ledger-cell display text-right text-base font-semibold"
                                        :class="
                                            row.positive
                                                ? 'text-(--gain)'
                                                : 'text-(--loss)'
                                        "
                                    >
                                        {{ row.roi }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <figcaption
                        class="border-t border-(--line) px-5 py-3 text-xs text-(--muted)"
                    >
                        Dados de exemplo, para mostrar o formato do relatório.
                    </figcaption>
                </figure>
            </section>

            <section class="border-t border-(--line) py-20">
                <h2 class="display text-3xl font-semibold">
                    O que o sistema faz
                </h2>

                <dl class="mt-10">
                    <div
                        v-for="capability in capabilities"
                        :key="capability.name"
                        class="grid gap-2 border-t border-(--line) py-6 md:grid-cols-[14rem_1fr_16rem] md:gap-8"
                    >
                        <dt class="display text-lg font-medium text-(--signal)">
                            {{ capability.name }}
                        </dt>
                        <dd class="max-w-xl leading-relaxed">
                            {{ capability.description }}
                        </dd>
                        <dd class="text-sm text-(--muted)">
                            {{ capability.sources }}
                        </dd>
                    </div>
                </dl>
            </section>
        </main>

        <footer class="border-t border-(--line)">
            <div
                class="mx-auto flex max-w-6xl flex-col gap-4 px-6 py-8 text-sm text-(--muted) sm:flex-row sm:items-center sm:justify-between"
            >
                <p>Uso interno. O acesso é restrito a contas cadastradas.</p>
                <Link
                    :href="isSignedIn ? dashboard() : login()"
                    class="action action-quiet"
                >
                    {{ isSignedIn ? 'Abrir o painel' : 'Entrar' }}
                </Link>
            </div>
        </footer>
    </div>
</template>

<style scoped>
.landing {
    --ink: #0d1533;
    --panel: #131e45;
    --line: #27366c;
    --text: #e8ecfa;
    --muted: #97a4cc;
    --signal: #ffb23e;
    --gain: #5fd3a6;
    --loss: #ff8a80;

    background:
        radial-gradient(
            60rem 30rem at 85% -10%,
            rgb(124 155 255 / 0.16),
            transparent 70%
        ),
        var(--ink);
    color: var(--text);
    font-family: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif;
}

.display {
    font-family: 'Chakra Petch', 'Instrument Sans', ui-sans-serif, sans-serif;
    letter-spacing: -0.01em;
}

.action {
    display: inline-flex;
    align-items: center;
    border-radius: 0.25rem;
    font-weight: 600;
    transition:
        background-color 150ms,
        border-color 150ms,
        color 150ms;
}

.action:focus-visible {
    outline: 2px solid var(--signal);
    outline-offset: 3px;
}

.action-solid {
    padding: 0.85rem 1.6rem;
    background: var(--signal);
    color: var(--ink);
}

.action-solid:hover {
    background: #ffc468;
}

.action-quiet {
    padding: 0.5rem 1.1rem;
    border: 1px solid var(--line);
    color: var(--text);
    font-size: 0.875rem;
}

.action-quiet:hover {
    border-color: var(--signal);
    color: var(--signal);
}

.ledger {
    border: 1px solid var(--line);
    border-radius: 0.5rem;
    background: var(--panel);
}

.ledger-cell {
    padding: 0.85rem 1.25rem;
    white-space: nowrap;
}

/* The path of one click: a line, five stops and a pulse that travels it. */
.trace {
    position: relative;
    display: grid;
    gap: 2rem;
    padding-left: 2rem;
}

.trace::before {
    content: '';
    position: absolute;
    top: 0.4rem;
    bottom: 0;
    left: 0.4rem;
    width: 1px;
    background: var(--line);
}

.trace-step {
    position: relative;
}

.trace-node {
    position: absolute;
    top: 0.4rem;
    left: -2rem;
    width: 0.85rem;
    height: 0.85rem;
    border: 1px solid var(--signal);
    border-radius: 9999px;
    background: var(--ink);
    animation: arrive 6s linear infinite;
    animation-delay: calc(var(--i) * 1.2s);
}

@media (min-width: 768px) {
    .trace {
        grid-template-columns: repeat(5, minmax(0, 1fr));
        gap: 1.5rem;
        padding-top: 2.5rem;
        padding-left: 0;
    }

    .trace::before {
        top: 0.4rem;
        right: 0;
        bottom: auto;
        left: 0;
        width: auto;
        height: 1px;
    }

    .trace::after {
        content: '';
        position: absolute;
        top: calc(0.4rem - 1px);
        left: 0;
        width: 4rem;
        height: 3px;
        border-radius: 9999px;
        background: linear-gradient(90deg, transparent, var(--signal));
        animation: travel 6s linear infinite;
    }

    .trace-node {
        top: -2.5rem;
        left: 0;
    }
}

@keyframes travel {
    from {
        transform: translateX(-4rem);
        left: 0;
    }

    to {
        transform: translateX(-4rem);
        left: 100%;
    }
}

@keyframes arrive {
    0%,
    100% {
        background: var(--ink);
        box-shadow: 0 0 0 0 rgb(255 178 62 / 0);
    }

    2% {
        background: var(--signal);
        box-shadow: 0 0 0 6px rgb(255 178 62 / 0.18);
    }

    22% {
        background: var(--ink);
        box-shadow: 0 0 0 0 rgb(255 178 62 / 0);
    }
}

@media (prefers-reduced-motion: reduce) {
    .trace::after {
        display: none;
    }

    .trace-node {
        animation: none;
        background: var(--signal);
    }
}
</style>
