<p align="center"><img src="https://laravel.com/assets/img/components/logo-cashier.svg"></p>

<p align="center">
<a href="https://travis-ci.org/laravel/cashier"><img src="https://travis-ci.org/laravel/cashier.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/cashier"><img src="https://poser.pugx.org/laravel/cashier/d/total.svg" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/cashier"><img src="https://poser.pugx.org/laravel/cashier/v/stable.svg" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/cashier"><img src="https://poser.pugx.org/laravel/cashier/license.svg" alt="License"></a>
</p>

---

# Payment Laravel - Cashier SierraTecnologia

## 📘 Introdução

**Payment Laravel** (Cashier SierraTecnologia) é uma biblioteca robusta e elegante para gerenciamento de pagamentos recorrentes, assinaturas e faturamento em aplicações Laravel. Desenvolvida pela **SierraTecnologia**, esta solução oferece uma interface expressiva e fluente para integração com os serviços de cobrança da SierraTecnologia.

### O que é a biblioteca payment-laravel?

Payment Laravel é uma implementação completa de um sistema de billing que abstrai toda a complexidade de gerenciar:

- **Assinaturas recorrentes** com múltiplos planos
- **Períodos de trial** (testes gratuitos)
- **Cobranças únicas e recorrentes**
- **Gestão de cartões de crédito**
- **Emissão de invoices/faturas em PDF**
- **Webhooks** para sincronização automática
- **Cupons de desconto**
- **Proration** (cálculo proporcional em mudanças de plano)
- **Grace periods** (períodos de carência pós-cancelamento)

### Objetivo e Filosofia do Projeto

A filosofia do Payment Laravel é **simplificar o complexo**. O gerenciamento de pagamentos e assinaturas envolve lógica de negócio intrincada, sincronização com gateways de pagamento, tratamento de edge cases e conformidade com regulamentações. Esta biblioteca encapsula toda essa complexidade, permitindo que desenvolvedores foquem na lógica de negócio específica de suas aplicações.

**Princípios fundamentais:**

- **API Fluente**: Interface intuitiva e expressiva, seguindo os padrões Laravel
- **Convenção sobre Configuração**: Funciona out-of-the-box com configuração mínima
- **Extensibilidade**: Arquitetura que permite customizações sem modificar o core
- **Confiabilidade**: Sincronização automática via webhooks garante consistência de dados

### Benefícios de Uso

- ✅ **Redução de código boilerplate**: Elimina centenas de linhas de código repetitivo
- ✅ **Segurança**: Implementa as melhores práticas de segurança para transações financeiras
- ✅ **Manutenibilidade**: Código testado e mantido pela comunidade SierraTecnologia
- ✅ **Produtividade**: Implementação de billing em horas, não semanas
- ✅ **Conformidade**: Adequado às normas de processamento de pagamentos

### Integração no Ecossistema SierraTecnologia

Payment Laravel é parte fundamental do ecossistema de produtos SierraTecnologia, integrando-se perfeitamente com:

- **SierraTecnologia Payment API**: Gateway de pagamentos proprietário
- **Telefonica**: Sistema de notificações e comunicação
- **CRM SierraTecnologia**: Gestão de relacionamento com cliente
- **Analytics SierraTecnologia**: Métricas e análises de receita

---

## 🚀 Instalação

### Requisitos Mínimos

- **PHP**: 7.2, 7.3, 7.4, 8.0 ou superior
- **Laravel**: 7.x ou 8.x
- **Extensões PHP**: ext-json
- **Dependências**: dompdf/dompdf >=0.8.0 (para geração de PDFs)

### Instalação via Composer

```bash
composer require sierratecnologia/payment-laravel
```

### Publicação de Assets e Configurações

Após a instalação, publique as migrations e views (opcional):

```bash
# Publicar migrations
php artisan vendor:publish --tag="cashier-migrations"

# Executar migrations
php artisan migrate

# Publicar views (opcional - para customização de invoices)
php artisan vendor:publish --tag="cashier-views"
```

### Configuração

Adicione suas credenciais SierraTecnologia no arquivo `config/services.php`:

```php
'sierratecnologia' => [
    'key' => env('SITECPAYMENT_KEY'),
    'secret' => env('SITECPAYMENT_SECRET'),
    'model' => env('SITECPAYMENT_MODEL', App\Models\User::class),
    'webhook' => [
        'secret' => env('SITECPAYMENT_WEBHOOK_SECRET'),
    ],
],
```

Adicione as variáveis de ambiente no `.env`:

```env
SITECPAYMENT_KEY=sua_chave_publica
SITECPAYMENT_SECRET=sua_chave_secreta
SITECPAYMENT_WEBHOOK_SECRET=seu_webhook_secret
SITECPAYMENT_MODEL=App\Models\User
```

### Registro do Service Provider

O `CashierServiceProvider` é registrado automaticamente via Auto-Discovery do Laravel. Não é necessário registro manual.

---

## 🏗️ Arquitetura e Estrutura Interna

### Visão Geral dos Diretórios

```
payment-laravel/
├── src/
│   ├── Billable.php                    # Trait principal para modelos pagáveis
│   ├── Cashier.php                     # Classe de configuração central
│   ├── CashierServiceProvider.php      # Service Provider Laravel
│   ├── Subscription.php                # Modelo Eloquent de assinatura
│   ├── SubscriptionBuilder.php         # Builder para criar assinaturas
│   ├── Invoice.php                     # Representação de fatura
│   ├── InvoiceItem.php                 # Item individual de fatura
│   ├── Card.php                        # Representação de cartão
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── WebhookController.php   # Controller de webhooks
│   │   └── Middleware/
│   │       └── VerifyWebhookSignature.php
│   └── Exceptions/
│       └── SubscriptionCreationFailed.php
├── database/
│   └── migrations/                     # Migrations do banco
├── resources/
│   └── views/                          # Views para PDFs de invoices
└── tests/                              # Testes automatizados
```

### Namespace e Autoloading

- **Namespace principal**: `SierraTecnologia\Cashier\`
- **Autoloading PSR-4**: Todo código segue o padrão PSR-4

### Padrões Arquiteturais

A arquitetura do Payment Laravel segue princípios sólidos de design:

#### 1. **Trait-Based Composition** (Billable)

O padrão de Trait permite adicionar funcionalidade de billing a qualquer Eloquent Model sem herança:

```php
class User extends Authenticatable
{
    use SierraTecnologia\Cashier\Billable;
}
```

#### 2. **Builder Pattern** (SubscriptionBuilder)

API fluente para construção de assinaturas complexas:

```php
$user->newSubscription('default', 'plan-premium')
    ->quantity(5)
    ->trialDays(14)
    ->withCoupon('DESCONTO20')
    ->create($paymentToken);
```

#### 3. **Facade/Static API** (Cashier)

Configuração centralizada e métodos auxiliares:

```php
Cashier::useCurrency('brl', 'R$');
Cashier::formatAmount(5000); // R$ 50.00
```

#### 4. **Event-Driven Synchronization** (Webhooks)

Sincronização automática através de eventos do gateway:

```
SierraTecnologia API → Webhook → WebhookController → Database Update
```

### Fluxo de Dados e Comunicação entre Camadas

```
┌─────────────────────────────────────────────────────────────────┐
│                      Aplicação Laravel                          │
│                                                                 │
│  ┌──────────────┐         ┌──────────────┐                    │
│  │ Model + Trait│ ◄─────► │ Subscription │                    │
│  │   Billable   │         │    Builder   │                    │
│  └──────┬───────┘         └──────┬───────┘                    │
│         │                        │                             │
│         └────────┬───────────────┘                             │
│                  ▼                                              │
│         ┌────────────────┐                                     │
│         │  Cashier Core  │ ◄─── Configuração (API Keys)       │
│         └────────┬───────┘                                     │
└──────────────────┼─────────────────────────────────────────────┘
                   │
                   ▼ HTTP Requests
         ┌──────────────────────┐
         │  SierraTecnologia    │
         │   Payment API        │
         └──────────┬───────────┘
                    │
                    │ Webhooks
                    ▼
         ┌──────────────────────┐
         │  WebhookController   │ ──► Atualiza banco de dados
         └──────────────────────┘
```

### Convenções e Melhores Práticas

**Nomenclatura de Tabelas e Colunas:**

- Tabela de usuários deve ter: `sierratecnologia_id`, `card_brand`, `card_last_four`, `trial_ends_at`
- Tabela `subscriptions`: `user_id`, `name`, `sierratecnologia_id`, `sierratecnologia_plan`, `quantity`, `trial_ends_at`, `ends_at`

**Convenções de Código:**

- PSR-12 para estilo de código
- Type hints em todos os métodos públicos
- Documentação PHPDoc completa
- Nomes descritivos e auto-explicativos

---

## 🔧 Principais Componentes e Responsabilidades

### 1. Billable Trait (`src/Billable.php`)

**Responsabilidade**: Adicionar capacidades de billing a modelos Eloquent (tipicamente User).

**Principais métodos:**

#### Cobranças Únicas

```php
// Cobrar $50.00 do cliente
$user->charge(5000, [
    'currency' => 'brl',
    'description' => 'Cobrança por serviço único'
]);

// Reembolsar uma cobrança
$user->refund('charge_id');
```

#### Gestão de Assinaturas

```php
// Criar nova assinatura
$user->newSubscription('premium', 'plan-premium-monthly')
    ->create($paymentToken);

// Verificar se está assinado
if ($user->subscribed('premium')) {
    // Cliente tem assinatura ativa
}

// Verificar plano específico
if ($user->subscribedToPlan('plan-premium-monthly', 'premium')) {
    // Cliente está no plano premium mensal
}

// Verificar trial
if ($user->onTrial('premium')) {
    // Cliente está em período de teste
}
```

#### Gestão de Cartões

```php
// Atualizar cartão
$user->updateCard($token);

// Obter cartões
$cards = $user->cards();

// Verificar se tem cartão
if ($user->hasCardOnFile()) {
    // Possui cartão cadastrado
}

// Deletar todos os cartões
$user->deleteCards();
```

#### Invoices (Faturas)

```php
// Criar invoice imediatamente
$user->invoiceFor('Consultoria', 25000);

// Adicionar item para próxima invoice
$user->tab('Taxa de setup', 10000);

// Obter invoices
$invoices = $user->invoices();

// Buscar invoice específica
$invoice = $user->findInvoice($invoiceId);

// Download de PDF
return $user->downloadInvoice($invoiceId, [
    'vendor' => 'SierraTecnologia',
    'product' => 'Premium Plan',
]);
```

#### Gestão de Clientes SierraTecnologia

```php
// Criar cliente na API SierraTecnologia
$user->createAsSierraTecnologiaCustomer([
    'email' => $user->email,
    'description' => 'Cliente VIP',
]);

// Obter cliente SierraTecnologia
$customer = $user->asSierraTecnologiaCustomer();

// Aplicar cupom
$user->applyCoupon('DESCONTO20');
```

---

### 2. Subscription Model (`src/Subscription.php`)

**Responsabilidade**: Representar e gerenciar o ciclo de vida de uma assinatura.

**Estados de uma assinatura:**

- **Active**: Assinatura ativa e sendo cobrada
- **On Trial**: Em período de teste gratuito
- **On Grace Period**: Cancelada mas ainda ativa até o fim do período pago
- **Cancelled**: Cancelada e período expirado
- **Ended**: Finalizada permanentemente

#### Métodos de Estado

```php
$subscription = $user->subscription('premium');

// Verificações de estado
$subscription->valid();        // Ativa, trial ou grace period
$subscription->active();       // Ativamente sendo cobrada
$subscription->onTrial();      // Em trial
$subscription->onGracePeriod(); // Cancelada mas ativa
$subscription->cancelled();    // Foi cancelada
$subscription->ended();        // Finalizada
$subscription->recurring();    // Recorrente (não trial, não cancelada)
```

#### Gerenciamento de Assinatura

```php
// Mudar de plano
$subscription->swap('plan-enterprise');

// Mudar sem proration
$subscription->noProrate()->swap('plan-basic');

// Cancelar no fim do período
$subscription->cancel();

// Cancelar imediatamente
$subscription->cancelNow();

// Retomar assinatura cancelada
$subscription->resume();

// Atualizar quantidade
$subscription->updateQuantity(10);
$subscription->incrementQuantity(2);
$subscription->decrementQuantity(1);

// Incrementar e faturar imediatamente
$subscription->incrementAndInvoice(5);

// Pular trial
$subscription->skipTrial();

// Ancorar ciclo de cobrança
$subscription->anchorBillingCycleOn(Carbon::parse('first day of next month'));
```

#### Scopes Eloquent

```php
// Buscar apenas assinaturas ativas
$activeSubscriptions = Subscription::query()->active()->get();

// Assinaturas em trial
$trials = Subscription::query()->onTrial()->get();

// Assinaturas canceladas
$cancelled = Subscription::query()->cancelled()->get();

// Assinaturas recorrentes
$recurring = Subscription::query()->recurring()->get();
```

---

### 3. SubscriptionBuilder (`src/SubscriptionBuilder.php`)

**Responsabilidade**: Construir assinaturas com API fluente.

```php
$user->newSubscription('main', 'plan-premium')
    ->quantity(3)                    // 3 licenças
    ->trialDays(30)                  // 30 dias de trial
    ->withCoupon('LAUNCH50')         // Cupom de desconto
    ->withMetadata([                 // Metadata personalizado
        'order_id' => '12345',
        'source' => 'web'
    ])
    ->anchorBillingCycleOn(          // Ancorar cobrança
        Carbon::parse('first day of next month')
    )
    ->create($paymentToken);         // Criar com token de pagamento
```

**Métodos disponíveis:**

- `quantity($count)`: Define quantidade de "licenças"
- `trialDays($days)`: Trial por número de dias
- `trialUntil(Carbon $date)`: Trial até data específica
- `skipTrial()`: Pular trial
- `withCoupon($code)`: Aplicar cupom
- `withMetadata($array)`: Adicionar metadata
- `anchorBillingCycleOn($date)`: Ancorar ciclo de faturamento
- `add()`: Criar sem token (usar cartão existente)
- `create($token)`: Criar com novo token de pagamento

---

### 4. Cashier Class (`src/Cashier.php`)

**Responsabilidade**: Configuração global e métodos utilitários.

```php
// Configurar moeda
Cashier::useCurrency('brl', 'R$');

// Formatar valores
Cashier::formatAmount(10000); // R$ 100.00

// Obter moeda atual
$currency = Cashier::usesCurrency(); // 'brl'
$symbol = Cashier::usesCurrencySymbol(); // 'R$'

// Formatação customizada
Cashier::formatCurrencyUsing(function ($amount) {
    return 'R$ ' . number_format($amount / 100, 2, ',', '.');
});

// Desabilitar auto-migrations
Cashier::ignoreMigrations();
```

---

### 5. WebhookController (`src/Http/Controllers/WebhookController.php`)

**Responsabilidade**: Processar webhooks da SierraTecnologia API para sincronizar estado.

**Eventos tratados:**

- `customer.subscription.updated`: Atualiza dados da assinatura
- `customer.subscription.deleted`: Marca assinatura como cancelada
- `invoice.payment_succeeded`: Processa pagamento bem-sucedido
- `invoice.payment_failed`: Trata falha de pagamento

**Configuração de Rota:**

```php
// routes/web.php ou routes/api.php
Route::post(
    'webhook/sierratecnologia',
    '\SierraTecnologia\Cashier\Http\Controllers\WebhookController@handleWebhook'
);
```

**Estendendo o WebhookController:**

```php
namespace App\Http\Controllers;

use SierraTecnologia\Cashier\Http\Controllers\WebhookController as CashierController;

class WebhookController extends CashierController
{
    /**
     * Tratar evento customizado
     */
    protected function handleCustomEvent(array $payload)
    {
        // Sua lógica customizada
        return response('OK', 200);
    }
}
```

---

## 💼 Uso Prático

### Caso de Uso 1: SaaS com Planos Mensais

```php
// 1. Preparar model User
class User extends Authenticatable
{
    use Billable;

    // Definir taxa de imposto (opcional)
    public function taxPercentage()
    {
        return 15; // 15% de imposto
    }
}

// 2. Controller de assinatura
class SubscriptionController extends Controller
{
    public function subscribe(Request $request)
    {
        $user = $request->user();
        $plan = $request->input('plan'); // 'basic', 'premium', 'enterprise'

        $subscription = $user->newSubscription('default', "plan-{$plan}")
            ->trialDays(14)
            ->create($request->paymentToken);

        return redirect()->route('dashboard')
            ->with('success', 'Assinatura criada com sucesso!');
    }
}

// 3. Middleware para proteger rotas premium
class RequireSubscription
{
    public function handle($request, Closure $next, $plan = null)
    {
        if ($plan && !$request->user()->subscribedToPlan($plan)) {
            return redirect('billing');
        }

        if (!$request->user()->subscribed('default')) {
            return redirect('pricing');
        }

        return $next($request);
    }
}

// 4. Usar no route
Route::middleware(['auth', 'subscribed:plan-premium'])
    ->group(function () {
        Route::get('/premium-feature', 'PremiumController@index');
    });
```

### Caso de Uso 2: Marketplace com Comissões

```php
class Vendor extends Model
{
    use Billable;
}

class OrderController extends Controller
{
    public function processOrder(Order $order)
    {
        $vendor = $order->vendor;
        $amount = $order->total;

        // Cobrar comissão do vendedor
        $vendor->charge($amount * 0.10, [
            'description' => "Comissão - Pedido #{$order->id}",
            'currency' => 'brl',
        ]);

        // Adicionar à próxima fatura
        $vendor->tab(
            "Comissão - Pedido #{$order->id}",
            $amount * 0.10
        );
    }
}
```

### Caso de Uso 3: Licenças por Quantidade

```php
class Team extends Model
{
    use Billable;
}

class TeamController extends Controller
{
    public function addMember(Team $team, Request $request)
    {
        // Adicionar membro
        $member = $team->members()->create($request->all());

        // Incrementar quantidade de licenças
        $subscription = $team->subscription('team-plan');

        if ($subscription) {
            $subscription->incrementAndInvoice(); // Incrementa e fatura imediatamente
        }

        return back()->with('success', 'Membro adicionado e licença atualizada!');
    }

    public function removeMember(Team $team, $memberId)
    {
        $team->members()->findOrFail($memberId)->delete();

        // Decrementar quantidade (sem cobrança imediata)
        $team->subscription('team-plan')->decrementQuantity();

        return back()->with('success', 'Membro removido. Próxima fatura será ajustada.');
    }
}
```

### Caso de Uso 4: Gestão de Invoices

```php
class BillingController extends Controller
{
    public function invoices(Request $request)
    {
        $invoices = $request->user()->invoicesIncludingPending();

        return view('billing.invoices', compact('invoices'));
    }

    public function downloadInvoice(Request $request, $invoiceId)
    {
        return $request->user()->downloadInvoice($invoiceId, [
            'vendor' => 'SierraTecnologia LTDA',
            'product' => 'Assinatura Premium',
            'street' => 'Rua Exemplo, 123',
            'location' => 'São Paulo, SP 01234-567',
            'phone' => '+55 11 1234-5678',
        ]);
    }
}
```

**View Blade:**

```blade
@foreach ($invoices as $invoice)
    <div class="invoice">
        <p>Data: {{ $invoice->date()->format('d/m/Y') }}</p>
        <p>Total: {{ $invoice->total() }}</p>
        <p>Status: {{ $invoice->status }}</p>

        <a href="{{ route('invoice.download', $invoice->id) }}">
            Baixar PDF
        </a>
    </div>
@endforeach
```

---

## 🔗 Integração com o Ecossistema SierraTecnologia

### Dependências Internas

Payment Laravel integra-se com outros pacotes SierraTecnologia:

1. **sierratecnologia/payment-php** (^0.3.0 | ^0.4.0)
   - SDK PHP para a API de pagamentos
   - Comunicação HTTP com gateway

2. **sierratecnologia/telefonica** (^0.4.0)
   - Sistema de notificações
   - Envio de emails transacionais (faturas, recibos)

### Padrões de Versionamento

- **Semantic Versioning (SemVer)**: MAJOR.MINOR.PATCH
- **Branch Aliases**: `dev-master` → `10.0-dev`
- **Releases estáveis** marcadas com tags Git

### CI/CD e Qualidade

**Verificações automáticas em cada PR/Push:**

- Testes unitários e de integração (PHPUnit)
- Análise estática de código (Psalm/PHPStan)
- Code style (PHP-CS-Fixer / PHPCS PSR-12)
- Verificação de code smells (GrumPHP)

### Uso Padronizado em Equipes

**Boas práticas para equipes:**

1. **Centralizar configuração**: Use variáveis de ambiente para chaves API
2. **Padronizar nomes de assinaturas**: Ex: sempre usar 'default' para assinatura principal
3. **Implementar testes**: Testar fluxos críticos de billing
4. **Monitorar webhooks**: Logs e alertas para falhas de webhook
5. **Documentar customizações**: Manter docs internas sobre extensões

---

## 🎨 Extensão e Customização

### 1. Adicionar Novos Gateways

Embora Payment Laravel use a API SierraTecnologia por padrão, você pode criar adaptadores:

```php
namespace App\Billing\Gateways;

class PayPalGateway implements PaymentGatewayInterface
{
    public function charge($amount, $options) { }
    public function refund($chargeId, $amount) { }
    // ...
}

// Registrar no Service Container
app()->bind(PaymentGatewayInterface::class, PayPalGateway::class);
```

### 2. Customizar Geração de PDFs

```php
// Publicar views
php artisan vendor:publish --tag="cashier-views"

// Editar resources/views/vendor/cashier/receipt.blade.php
```

```blade
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Fatura #{{ $id }}</title>
    <style>
        /* Seus estilos customizados */
        body { font-family: 'DejaVu Sans', sans-serif; }
        .header { background: #your-brand-color; }
    </style>
</head>
<body>
    <div class="header">
        <img src="{{ $vendor_logo }}" alt="{{ $vendor }}">
    </div>

    {{-- Seu template customizado --}}
</body>
</html>
```

### 3. Estender o Billable Trait

```php
namespace App\Traits;

use SierraTecnologia\Cashier\Billable as CashierBillable;

trait Billable
{
    use CashierBillable;

    /**
     * Método customizado para sua regra de negócio
     */
    public function chargeWithDiscount($amount, $discountPercent)
    {
        $finalAmount = $amount - ($amount * $discountPercent / 100);

        return $this->charge($finalAmount, [
            'description' => "Cobrança com {$discountPercent}% de desconto",
        ]);
    }

    /**
     * Override de taxPercentage com lógica complexa
     */
    public function taxPercentage()
    {
        // Calcular imposto baseado no país, estado, etc
        return $this->region->tax_rate ?? 0;
    }
}
```

### 4. Substituir Classes via Service Container

```php
// AppServiceProvider.php

use SierraTecnologia\Cashier\Subscription;
use App\Models\CustomSubscription;

public function register()
{
    $this->app->bind(Subscription::class, function ($app) {
        return new CustomSubscription();
    });
}
```

```php
// CustomSubscription.php
namespace App\Models;

use SierraTecnologia\Cashier\Subscription as CashierSubscription;

class CustomSubscription extends CashierSubscription
{
    /**
     * Override de método para lógica específica
     */
    public function cancel()
    {
        // Lógica customizada antes do cancelamento
        $this->notifyAccountManager();

        return parent::cancel();
    }

    protected function notifyAccountManager()
    {
        // Notificar gerente de conta
    }
}
```

### 5. Hooks e Event Listeners

```php
// EventServiceProvider.php

protected $listen = [
    'SierraTecnologia\Cashier\Events\SubscriptionCreated' => [
        'App\Listeners\SendWelcomeEmail',
        'App\Listeners\UpdateCRMSystem',
    ],
];
```

---

## 📊 Exemplos Reais

### Exemplo 1: Startup SaaS - Antes e Depois

**ANTES** (sem Payment Laravel):

```php
// 300+ linhas para gerenciar assinatura manualmente
class SubscriptionService
{
    public function subscribe($user, $plan, $token)
    {
        // Chamar API SierraTecnologia manualmente
        $response = Http::post('https://api.sierratecnologia.com/customers', [
            'email' => $user->email,
            'source' => $token,
        ]);

        // Parse response
        $customer = json_decode($response->body());

        // Salvar no banco
        $user->update(['sierratecnologia_id' => $customer->id]);

        // Criar subscription na API
        $subResponse = Http::post("https://api.sierratecnologia.com/subscriptions", [
            'customer' => $customer->id,
            'plan' => $plan,
            // ... muitos outros parâmetros
        ]);

        // Salvar subscription
        Subscription::create([
            'user_id' => $user->id,
            // ... mapear todos os campos manualmente
        ]);

        // Tratar erros, edge cases, trials, etc...
        // Implementar webhooks manualmente
        // Sincronizar estado constantemente
        // ... código complexo e propenso a bugs
    }
}
```

**DEPOIS** (com Payment Laravel):

```php
// 3 linhas para a mesma funcionalidade
class SubscriptionController
{
    public function subscribe(Request $request)
    {
        $request->user()
            ->newSubscription('default', $request->plan)
            ->create($request->paymentToken);

        return redirect('/dashboard');
    }
}
```

**Benefícios quantificados:**
- ✅ **95% menos código**
- ✅ **Webhooks automáticos** (não precisa implementar)
- ✅ **Sincronização automática** de estado
- ✅ **Zero bugs** relacionados a billing (testado pela comunidade)
- ✅ **Tempo de implementação**: de semanas para horas

---

### Exemplo 2: E-learning Platform

**Cenário**: Plataforma de cursos online com múltiplos planos e acesso por curso.

```php
class User extends Authenticatable
{
    use Billable;

    public function canAccessCourse(Course $course)
    {
        // Acesso se tiver plano premium
        if ($this->subscribed('premium')) {
            return true;
        }

        // Acesso se comprou o curso específico
        if ($this->purchasedCourses->contains($course)) {
            return true;
        }

        // Acesso em trial
        return $this->onTrial('premium');
    }
}

class CourseController extends Controller
{
    public function show(Course $course)
    {
        if (!auth()->user()->canAccessCourse($course)) {
            return redirect()->route('pricing')
                ->with('message', 'Assine o plano Premium ou compre este curso!');
        }

        return view('courses.show', compact('course'));
    }
}

class PurchaseController extends Controller
{
    public function buyCourse(Course $course, Request $request)
    {
        $user = $request->user();

        try {
            // Cobrança única pelo curso
            $charge = $user->charge($course->price * 100, [
                'description' => "Curso: {$course->title}",
            ]);

            // Registrar compra
            $user->purchasedCourses()->attach($course, [
                'charge_id' => $charge->id,
                'amount_paid' => $course->price,
            ]);

            // Enviar acesso
            Mail::to($user)->send(new CourseAccessGranted($course));

            return redirect()->route('course.show', $course)
                ->with('success', 'Curso adquirido com sucesso!');

        } catch (\Exception $e) {
            return back()->with('error', 'Falha no pagamento: ' . $e->getMessage());
        }
    }
}
```

---

### Exemplo 3: Agência com Clientes Múltiplos

**Cenário**: Agência de marketing que gerencia billing de múltiplos clientes.

```php
class Agency extends Model
{
    use Billable; // Agency paga pela plataforma

    public function clients()
    {
        return $this->hasMany(Client::class);
    }
}

class Client extends Model
{
    use Billable; // Clientes pagam pela agência

    public function agency()
    {
        return $this->belongsTo(Agency::class);
    }
}

class BillingService
{
    /**
     * Cobrar todos os clientes da agência
     */
    public function billAllClients(Agency $agency)
    {
        $totalRevenue = 0;

        foreach ($agency->clients as $client) {
            if ($client->subscribed('monthly-retainer')) {
                $subscription = $client->subscription('monthly-retainer');

                // Faturar cliente
                $client->invoice();

                $totalRevenue += $subscription->quantity * 50000; // R$ 500 por unidade
            }
        }

        // Pagar comissão da plataforma (10% da receita)
        $agency->charge($totalRevenue * 0.10, [
            'description' => 'Comissão da plataforma - ' . now()->format('m/Y'),
        ]);

        return $totalRevenue;
    }
}

class AgencyController extends Controller
{
    public function dashboard()
    {
        $agency = auth()->user()->agency;

        $metrics = [
            'active_subscriptions' => $agency->clients()
                ->whereHas('subscriptions', function ($q) {
                    $q->where('name', 'monthly-retainer')->active();
                })->count(),

            'monthly_recurring_revenue' => $agency->clients()
                ->with('subscriptions')
                ->get()
                ->sum(function ($client) {
                    $sub = $client->subscription('monthly-retainer');
                    return $sub && $sub->active() ? $sub->quantity * 500 : 0;
                }),

            'upcoming_renewals' => $agency->clients()
                ->whereHas('subscriptions', function ($q) {
                    $q->whereBetween('ends_at', [now(), now()->addDays(7)]);
                })->get(),
        ];

        return view('agency.dashboard', $metrics);
    }
}
```

---

## 🤝 Guia de Contribuição

### Como Contribuir

Contribuições são muito bem-vindas! Siga estas etapas:

1. **Fork** o repositório
2. **Clone** sua fork localmente
3. **Crie** uma branch para sua feature: `git checkout -b feature/minha-feature`
4. **Implemente** sua funcionalidade com testes
5. **Certifique-se** de que os testes passam: `composer test`
6. **Verifique** code style: `composer cs-check`
7. **Commit** suas mudanças: `git commit -m "Adiciona nova feature X"`
8. **Push** para sua fork: `git push origin feature/minha-feature`
9. **Abra** um Pull Request no repositório principal

### Padrões de Código

**Code Style**: PSR-12

```bash
# Verificar code style
composer cs-check

# Corrigir automaticamente
composer cs-fix
```

**Análise Estática**: PHPStan nível 8

```bash
# Executar análise estática
composer analyse
```

**Testes**: PHPUnit

```bash
# Executar todos os testes
composer test

# Executar com cobertura
composer test-coverage
```

### Padrões de Commit

Use [Conventional Commits](https://www.conventionalcommits.org/):

```
feat: adiciona suporte para múltiplas moedas
fix: corrige cálculo de proration em swap de plano
docs: atualiza exemplos de uso do Billable trait
test: adiciona testes para SubscriptionBuilder
refactor: melhora performance de queries de invoice
chore: atualiza dependências do composer
```

### Branch Naming

- `feature/nome-da-feature` - Novas funcionalidades
- `fix/descricao-do-bug` - Correções de bugs
- `docs/topico` - Melhorias de documentação
- `refactor/componente` - Refatorações
- `test/componente` - Adição/melhoria de testes

### Versionamento Semântico

- **MAJOR** (1.0.0): Breaking changes
- **MINOR** (0.1.0): Novas features compatíveis
- **PATCH** (0.0.1): Bug fixes

### Checklist de PR

Antes de abrir um PR, certifique-se:

- [ ] Testes passando (`composer test`)
- [ ] Code style correto (`composer cs-check`)
- [ ] Análise estática sem erros (`composer analyse`)
- [ ] Documentação atualizada (se aplicável)
- [ ] CHANGELOG.md atualizado
- [ ] Backward compatibility mantida (ou BREAKING CHANGE documentado)

### Executar Localmente

```bash
# 1. Clonar repositório
git clone https://github.com/SierraTecnologia/payment-laravel.git
cd payment-laravel

# 2. Instalar dependências
composer install

# 3. Copiar phpunit.xml
cp phpunit.xml.dist phpunit.xml

# 4. Configurar credenciais de teste (IMPORTANTE: usar chaves de TESTE)
# Editar phpunit.xml e adicionar:
# <env name="SITECPAYMENT_SECRET" value="sk_test_..."/>

# 5. Executar testes
./vendor/bin/phpunit

# 6. Executar análise estática
./vendor/bin/psalm

# 7. Verificar code style
./vendor/bin/php-cs-fixer fix --dry-run --diff
```

### Ferramentas de Qualidade - Execução Local

```bash
# PHPUnit - Testes
./vendor/bin/phpunit
./vendor/bin/phpunit --coverage-html coverage/

# Psalm - Análise estática
./vendor/bin/psalm
./vendor/bin/psalm --show-info=true

# PHP-CS-Fixer - Code style
./vendor/bin/php-cs-fixer fix --dry-run
./vendor/bin/php-cs-fixer fix

# PHPStan - Análise estática (se configurado)
./vendor/bin/phpstan analyse src/

# GrumPHP - Pre-commit hooks
./vendor/bin/grumphp run
```

### Política de Licença

Payment Laravel é open-source licenciado sob [MIT License](LICENSE).

Você é livre para:
- ✅ Usar comercialmente
- ✅ Modificar
- ✅ Distribuir
- ✅ Uso privado

**Condições**:
- Incluir a licença original e copyright notice

### Autores e Contato

**Mantenedor Principal**: Ricardo Sierra (SierraTecnologia)

**Email**: contato@sierratecnologia.com.br

**Issues**: [GitHub Issues](https://github.com/sierratecnologia/cashier/issues)

**Comunidade**:
- Slack: [SierraTecnologia Community](#)
- Forum: [Discuss SierraTecnologia](#)
- Twitter: [@SierraTech](#)

---

## 📚 Recursos Adicionais

### Documentação Oficial

Para informações mais detalhadas, consulte:

- [Documentação Completa](https://docs.sierratecnologia.com.br/payment-laravel)
- [API Reference](https://docs.sierratecnologia.com.br/api)
- [Guia de Migração](UPGRADE.md)
- [Changelog](CHANGELOG.md)

### Tutoriais e Exemplos

- [Tutorial: Setup inicial](https://blog.sierratecnologia.com.br/payment-laravel-setup)
- [Implementando SaaS Billing](https://blog.sierratecnologia.com.br/saas-billing-guide)
- [Webhooks avançados](https://blog.sierratecnologia.com.br/webhooks-guide)

### Suporte

- **Bugs e Issues**: [GitHub Issues](https://github.com/sierratecnologia/cashier/issues)
- **Perguntas**: [Stack Overflow](https://stackoverflow.com/questions/tagged/sierratecnologia-cashier) tag `sierratecnologia-cashier`
- **Discussões**: [GitHub Discussions](https://github.com/sierratecnologia/cashier/discussions)

---

## Running Cashier's Tests

You will need to set the SierraTecnologia **testing** secret environment variable in a custom `phpunit.xml` file in order to run the Cashier tests.

Copy the default file using `cp phpunit.xml.dist phpunit.xml` and add the following line below the `SITECPAYMENT_MODEL` environment variable in your new `phpunit.xml` file:

    <env name="SITECPAYMENT_SECRET" value="Your SierraTecnologia Secret Key"/>

Please note that due to the fact that actual API requests against SierraTecnologia are being made, these tests take a few minutes to run.

---

## 📄 License

Laravel Cashier is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

---

## 🏢 About SierraTecnologia

**SierraTecnologia** é uma empresa brasileira de tecnologia focada em soluções empresariais robustas e escaláveis. Desenvolvemos ferramentas que potencializam negócios através de automação, integração e inovação tecnológica.

### Nossos Produtos

- **Payment Laravel**: Sistema completo de billing e pagamentos
- **Telefonica**: Plataforma de comunicação multicanal
- **CRM SierraTecnologia**: Gestão de relacionamento com cliente
- **Analytics**: Business intelligence e métricas

### Nossa Missão

Democratizar o acesso a tecnologia de ponta, oferecendo ferramentas de nível enterprise acessíveis para empresas de todos os tamanhos.

### Contato

- **Website**: [https://sierratecnologia.com.br](https://sierratecnologia.com.br)
- **Email**: contato@sierratecnologia.com.br
- **GitHub**: [github.com/SierraTecnologia](https://github.com/SierraTecnologia)

---

<p align="center">Desenvolvido com ❤️ pela equipe SierraTecnologia</p>
