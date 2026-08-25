[English](README.md) | [Português](README.pt-BR.md) | [Deutsch](README.de.md) | [中文](README.zh-CN.md)

<div align="center">

**vizuh/filament-clicktrail**

Atribuição ClickTrail dentro do seu painel Filament 3 — configurações, registros de atribuição somente leitura, diagnósticos de supressão e mapeamento de eventos. Nada para construir à mão.

</div>

[![CI](https://github.com/vizuh/clicktrail-filament/actions/workflows/ci.yml/badge.svg)](https://github.com/vizuh/clicktrail-filament/actions/workflows/ci.yml)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)

## Índice

- [Por quê](#por-quê)
- [Instalação](#instalação)
- [Início rápido](#início-rápido)
- [Mapeamento de eventos](#mapeamento-de-eventos)
- [Registros de atribuição](#registros-de-atribuição)
- [Configurações](#configurações)
- [Diagnósticos](#diagnósticos)
- [Contrato de consentimento](#contrato-de-consentimento)
- [Como é diferente](#como-é-diferente)
- [Testes](#testes)
- [Licença](#licença)

## Por quê

Dados de atribuição que ninguém consegue ver são dados em que ninguém confia. Este plugin traz os registros de primeiro/último toque armazenados pelo ClickTrail, snapshots de consentimento e diagnósticos de supressão direto para um painel Filament existente — estritamente somente leitura, porque o estado de atribuição pertence ao pipeline de captura, nunca a edições manuais.

Requer PHP >= 8.1, Laravel 12.60+ ou 13.10+, Filament 3.3.55+ e [`clicktrail/php-sdk`](https://github.com/vizuh/clicktrail-php).

## Instalação

```bash
composer require vizuh/filament-clicktrail
php artisan vendor:publish --tag=clicktrail-filament
php artisan migrate
```

## Início rápido

Registre o plugin em qualquer panel:

```php
use ClickTrail\Filament\ClickTrailPlugin;

public function panel(Panel $panel): Panel
{
    return $panel->plugin(ClickTrailPlugin::make());
}
// O painel passa a exibir o grupo de navegação "ClickTrail": a página de
// configurações, a tabela somente leitura de Attribution Records (que se
// atualiza a cada 60s) e o widget de estatísticas de diagnóstico.
// Nenhuma configuração adicional.
```

## Mapeamento de eventos

`Support\EventMap` converte eventos de modelos Eloquent nos nomes canônicos `Stable::EVENT_*`, então seus models de lead e pedido falam o vocabulário do ClickTrail sem código de cola:

```php
use App\Models\Lead;
use ClickTrail\Filament\Support\EventMap;

EventMap::resolve(new Lead(), 'created');  // 'lead.submitted' — nome canônico do evento
EventMap::resolve(new Lead(), 'deleted');  // 'sale.refunded' — exclusão mapeia para reembolso
```

Os basenames de modelo têm mapa padrão (`lead`, `appointment`, `sale`, `order`). Estenda ou sobrescreva por model pela chave de config `clicktrail-filament.event_map`; basename terminando em `refund` resolve para `sale.refunded`, e eventos contendo `attended` resolvem para `appointment.attended`.

## Registros de atribuição

A tabela `AttributionRecordResource` mostra os registros armazenados de primeiro/último toque com filtros por canal e uma coluna compacta com o snapshot de consentimento:

```php
TextColumn::make('first_channel')->badge();          // paid_search | organic_search | ...
SelectFilter::make('first_channel');                 // filtra pelo nome canônico do canal
TextColumn::make('consent_snapshot_summary');        // "analytics_storage=granted, ad_user_data=denied"
```

Não há páginas de criação/edição/exclusão nem rotas para elas: `canCreate()`, `canEdit()` e `canDelete()` retornam false. A tabela se atualiza a cada 60 segundos.

## Configurações

A página de settings edita o arquivo `clicktrail-filament.php` publicado:

```php
'site_id'           => env('CLICKTRAIL_SITE_ID', ''),   // emitido pelo coletor
'endpoint'          => env('CLICKTRAIL_ENDPOINT', 'https://collect.clicktrail.dev/v1/events/batch'),
'consent_resolver'  => env('CLICKTRAIL_CONSENT_RESOLVER', ''), // vazio => NullConsentResolver
'capability_gates'  => ['analytics' => true, 'advertising' => true, 'ad_user_data' => true],
```

Um gate de capacidade desligado significa que aquele uso não exige consentimento de CMP (semântica de gate-toggle). Observação: o salvamento atual escreve de volta no repositório de config em tempo de execução; persistência durável chega com o trabalho de settings-storage em andamento.

## Diagnósticos

O widget de estatísticas lê os contadores de `clicktrail_diagnostics` — uma métrica por motivo de supressão, em amarelo quando diferente de zero:

```php
Stat::make('adUserDataUnknownAtCapture', '12') // entregas suprimidas para esse motivo
    ->description('Última ocorrência há 2 horas')
```

Sem supressões registradas você vê uma métrica verde `Suppressions = 0`. A profundidade da fila mostra `-` até o job de entrega em fila ser lançado (adiado pendente de verificação ao vivo).

## Contrato de consentimento

Este plugin é consumidor de consentimento, não um CMP. Ele lê um `ConsentSnapshot` normalizado (granted / denied / unknown / not_applicable) pelo `ConsentResolverInterface` do adapter Laravel. Todo sinal não resolvido conta como **negado**: entregas suprimidas viram linhas de diagnóstico em vez de serem enviadas.

```php
if (! ConsentBehavior::can($snapshot, 'ad_user_data')) {
    // entrega suprimida; motivo gravado em clicktrail_diagnostics,
    // visível no widget de Diagnósticos imediatamente
}
```

## Como é diferente

| | Este plugin | CRUD genérico de admin |
|---|---|---|
| Registros de atribuição | Exibição somente leitura de estado do pipeline | Linhas editáveis convidam corrupção silenciosa de dados |
| Consentimento | Unknown = negado, aplicado antes da entrega | Muitas vezes apenas um flag informativo |
| Eventos | Nomes canônicos `Stable::EVENT_*` compartilhados com todo adapter ClickTrail | Nomes de evento inventados por projeto |

Ele não captura toques nem entrega lotes — isso é do pipeline do adapter Laravel; este plugin torna os resultados visíveis e auditáveis.

## Testes

Ainda não há suíte PHPUnit. O CI aplica lint em todos os arquivos PHP no PHP 8.1–8.3:

```bash
composer install --prefer-dist --no-interaction || echo "no deps"
find . -name '*.php' -not -path './vendor/*' -print0 | xargs -0 -n1 php -l   # termina limpo em caso de sucesso
```

## Licença

MIT.
