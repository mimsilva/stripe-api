<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Documentação da API Stripe</title>
    <!-- Fontes -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Figtree', sans-serif;
            line-height: 1.6;
            color: #333;
            padding-top: 2rem;
            padding-bottom: 4rem;
        }
        .container {
            max-width: 1200px;
        }
        h1 {
            color: #6772e5;
            margin-bottom: 1.5rem;
            font-weight: 600;
        }
        h2 {
            color: #424770;
            margin-top: 2.5rem;
            margin-bottom: 1rem;
            font-weight: 600;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #e6ebf1;
        }
        h3 {
            color: #424770;
            margin-top: 1.5rem;
            font-weight: 500;
        }
        .endpoint {
            background-color: #f6f9fc;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            border-left: 4px solid #6772e5;
        }
        .endpoint h3 {
            margin-top: 0;
            display: flex;
            align-items: center;
        }
        .method {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            color: white;
            font-weight: 500;
            font-size: 0.875rem;
            margin-right: 0.75rem;
        }
        .post {
            background-color: #4CAF50;
        }
        .get {
            background-color: #2196F3;
        }
        pre {
            background-color: #f8f9fa;
            padding: 1rem;
            border-radius: 4px;
            overflow-x: auto;
        }
        code {
            font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', 'Consolas', 'source-code-pro', monospace;
            font-size: 0.875rem;
        }
        .response {
            background-color: #f0f4f8;
        }
        .nav-pills .nav-link.active {
            background-color: #6772e5;
        }
        .nav-pills .nav-link {
            color: #6772e5;
        }
        .tab-content {
            padding-top: 1rem;
        }
        .table {
            margin-bottom: 2rem;
        }
        .table th {
            background-color: #f6f9fc;
        }
        .required {
            color: #e25950;
            font-weight: 500;
        }
        .optional {
            color: #6b7c93;
            font-weight: normal;
        }
        .url {
            word-break: break-all;
            font-family: monospace;
            background-color: #f8f9fa;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
        }
        .sidebar {
            position: sticky;
            top: 2rem;
        }
        .sidebar .nav-link {
            padding: 0.25rem 0;
            color: #6b7c93;
        }
        .sidebar .nav-link:hover {
            color: #6772e5;
        }
        .sidebar .nav-link.active {
            color: #6772e5;
            font-weight: 500;
        }
        @media (max-width: 768px) {
            .sidebar {
                position: static;
                margin-bottom: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row">
            <div class="col-md-3 d-none d-md-block">
                <div class="sidebar">
                    <h5>Endpoints</h5>
                    <nav class="nav flex-column">
                        <a class="nav-link" href="#connected-accounts">Contas Conectadas</a>
                        <a class="nav-link" href="#products">Produtos</a>
                        <a class="nav-link" href="#subscriptions">Assinaturas</a>
                        <a class="nav-link" href="#checkout">Sessões de Checkout</a>
                        <a class="nav-link" href="#payments">Pagamentos</a>
                        <a class="nav-link" href="#account-management">Gerenciamento de Conta</a>
                        <a class="nav-link" href="#webhooks">Webhooks</a>
                    </nav>
                </div>
            </div>
            <div class="col-md-9">
                <h1>Documentação da API Stripe</h1>
                <p class="lead">
                    Esta documentação fornece detalhes sobre como usar os endpoints de integração com o Stripe.
                    Todas as requisições devem incluir os cabeçalhos e autenticação apropriados.
                </p>

                <div class="alert alert-info">
                    <strong>URL Base:</strong> <code>{{ url('/api') }}</code>
                </div>

                <h2 id="connected-accounts">Contas Conectadas</h2>
                
                <div class="endpoint">
                    <h3><span class="method post">POST</span> Criar Conta Conectada</h3>
                    <p>Cria uma nova conta Stripe Connect Express para um fornecedor/vendedor.</p>
                    
                    <div class="url mb-3">{{ url('/api/stripe/connected-accounts') }}</div>
                    
                    <h4>Parâmetros da Requisição</h4>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Parâmetro</th>
                                <th>Tipo</th>
                                <th>Descrição</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><span class="required">email</span></td>
                                <td>string</td>
                                <td>Endereço de email da conta conectada</td>
                            </tr>
                            <tr>
                                <td><span class="required">name</span></td>
                                <td>string</td>
                                <td>Nome comercial da conta conectada</td>
                            </tr>
                            <tr>
                                <td><span class="required">business_type</span></td>
                                <td>string</td>
                                <td>Tipo de negócio (individual ou empresa)</td>
                            </tr>
                            <tr>
                                <td><span class="required">country</span></td>
                                <td>string</td>
                                <td>Código de país de duas letras (ex: BR)</td>
                            </tr>
                            <tr>
                                <td><span class="required">phone</span></td>
                                <td>string</td>
                                <td>Número de telefone do negócio</td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <h4>Exemplo de Requisição</h4>
                    <pre><code>{
  "email": "fornecedor@exemplo.com",
  "name": "Vinhos do Fornecedor",
  "business_type": "individual",
  "country": "BR",
  "phone": "+5511999999999"
}</code></pre>
                    
                    <h4>Exemplo de Resposta</h4>
                    <pre class="response"><code>{
  "success": true,
  "account_id": "acct_1RBG5u2HvPqSv6Bf",
  "onboarding_url": "https://connect.stripe.com/express/onboarding/acct_1RBG5u2HvPqSv6Bf"
}</code></pre>
                </div>

                <h2 id="products">Produtos</h2>
                
                <div class="endpoint">
                    <h3><span class="method post">POST</span> Criar Produto</h3>
                    <p>Cria um novo produto em uma conta conectada.</p>
                    
                    <div class="url mb-3">{{ url('/api/stripe/products') }}</div>
                    
                    <h4>Parâmetros da Requisição</h4>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Parâmetro</th>
                                <th>Tipo</th>
                                <th>Descrição</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><span class="required">account_id</span></td>
                                <td>string</td>
                                <td>ID da conta conectada</td>
                            </tr>
                            <tr>
                                <td><span class="required">name</span></td>
                                <td>string</td>
                                <td>Nome do produto</td>
                            </tr>
                            <tr>
                                <td><span class="optional">description</span></td>
                                <td>string</td>
                                <td>Descrição do produto</td>
                            </tr>
                            <tr>
                                <td><span class="required">price</span></td>
                                <td>number</td>
                                <td>Preço na moeda especificada</td>
                            </tr>
                            <tr>
                                <td><span class="required">currency</span></td>
                                <td>string</td>
                                <td>Código de moeda de três letras (ex: BRL)</td>
                            </tr>
                            <tr>
                                <td><span class="optional">interval</span></td>
                                <td>string</td>
                                <td>Para assinaturas: dia, semana, mês ou ano</td>
                            </tr>
                            <tr>
                                <td><span class="optional">interval_count</span></td>
                                <td>integer</td>
                                <td>Número de intervalos entre cobranças da assinatura</td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <h4>Exemplo de Requisição (Produto Único)</h4>
                    <pre><code>{
  "account_id": "acct_1RBG5u2HvPqSv6Bf",
  "name": "Garrafa de Vinho Premium",
  "description": "Vinho premium exclusivo de nossa vinícola",
  "price": 89.90,
  "currency": "BRL"
}</code></pre>
                    
                    <h4>Exemplo de Requisição (Produto de Assinatura)</h4>
                    <pre><code>{
  "account_id": "acct_1RBG5u2HvPqSv6Bf",
  "name": "Clube do Vinho Premium",
  "description": "Assinatura mensal de vinhos premium",
  "price": 199.90,
  "currency": "BRL",
  "interval": "month",
  "interval_count": 1
}</code></pre>
                    
                    <h4>Exemplo de Resposta</h4>
                    <pre class="response"><code>{
  "success": true,
  "product_id": "prod_S5ngut4c6rWGZk",
  "price_id": "price_1RBc5DCS9XK9taAuuwYNvmz1"
}</code></pre>
                </div>
                
                <div class="endpoint">
                    <h3><span class="method get">GET</span> Listar Preços da Conta Conectada</h3>
                    <p>Lista todos os preços de uma conta conectada.</p>
                    
                    <div class="url mb-3">{{ url('/api/stripe/list-prices?account_id=acct_1RBG5u2HvPqSv6Bf') }}</div>
                    
                    <h4>Parâmetros da Query</h4>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Parâmetro</th>
                                <th>Tipo</th>
                                <th>Descrição</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><span class="required">account_id</span></td>
                                <td>string</td>
                                <td>ID da conta conectada</td>
                            </tr>
                            <tr>
                                <td><span class="optional">limit</span></td>
                                <td>integer</td>
                                <td>Número máximo de preços a retornar (padrão: 100)</td>
                            </tr>
                            <tr>
                                <td><span class="optional">active</span></td>
                                <td>boolean</td>
                                <td>Filtrar por status ativo</td>
                            </tr>
                            <tr>
                                <td><span class="optional">product_id</span></td>
                                <td>string</td>
                                <td>Filtrar por ID do produto</td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <h4>Exemplo de Resposta</h4>
                    <pre class="response"><code>{
  "success": true,
  "account_id": "acct_1RBG5u2HvPqSv6Bf",
  "prices": [
    {
      "id": "price_1RBc5DCS9XK9taAuuwYNvmz1",
      "product": "prod_S5ngut4c6rWGZk",
      "unit_amount": 199.9,
      "currency": "brl",
      "active": true,
      "recurring": {
        "interval": "month",
        "interval_count": 1
      },
      "created": "2025-04-08 12:34:15"
    }
  ],
  "products": {
    "prod_S5ngut4c6rWGZk": {
      "id": "prod_S5ngut4c6rWGZk",
      "name": "Clube do Vinho Premium",
      "description": "Assinatura mensal de vinhos premium",
      "active": true
    }
  },
  "count": 1,
  "api_mode": "test"
}</code></pre>
                </div>

                <h2 id="subscriptions">Assinaturas</h2>
                
                <div class="endpoint">
                    <h3><span class="method post">POST</span> Criar Assinatura de Cliente</h3>
                    <p>Cria uma assinatura para um cliente com detalhes de pagamento direto.</p>
                    
                    <div class="url mb-3">{{ url('/api/stripe/subscriptions/customer') }}</div>
                    
                    <h4>Parâmetros da Requisição</h4>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Parâmetro</th>
                                <th>Tipo</th>
                                <th>Descrição</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><span class="required">price_id</span></td>
                                <td>string</td>
                                <td>ID do preço para assinar</td>
                            </tr>
                            <tr>
                                <td><span class="required">account_id</span></td>
                                <td>string</td>
                                <td>ID da conta conectada (fornecedor)</td>
                            </tr>
                            <tr>
                                <td><span class="required">email</span></td>
                                <td>string</td>
                                <td>Endereço de email do cliente</td>
                            </tr>
                            <tr>
                                <td><span class="required">name</span></td>
                                <td>string</td>
                                <td>Nome completo do cliente</td>
                            </tr>
                            <tr>
                                <td><span class="required">payment_type</span></td>
                                <td>string</td>
                                <td>Tipo de método de pagamento (cartão, pix ou boleto)</td>
                            </tr>
                            <tr>
                                <td><span class="optional">phone</span></td>
                                <td>string</td>
                                <td>Número de telefone do cliente</td>
                            </tr>
                            <tr>
                                <td><span class="optional">address_line1</span></td>
                                <td>string</td>
                                <td>Endereço do cliente (linha 1)</td>
                            </tr>
                            <tr>
                                <td><span class="optional">address_city</span></td>
                                <td>string</td>
                                <td>Cidade do cliente</td>
                            </tr>
                            <tr>
                                <td><span class="optional">address_state</span></td>
                                <td>string</td>
                                <td>Estado do cliente</td>
                            </tr>
                            <tr>
                                <td><span class="optional">address_postal_code</span></td>
                                <td>string</td>
                                <td>CEP do cliente</td>
                            </tr>
                            <tr>
                                <td><span class="optional">address_country</span></td>
                                <td>string</td>
                                <td>Código do país do cliente</td>
                            </tr>
                            <tr>
                                <td><span class="optional">tax_id</span></td>
                                <td>string</td>
                                <td>CPF/CNPJ do cliente (para métodos brasileiros)</td>
                            </tr>
                            <tr>
                                <td><span class="optional">application_fee_percent</span></td>
                                <td>number</td>
                                <td>Porcentagem do valor da assinatura a ser cobrada como taxa</td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <h4>Parâmetros Adicionais para Pagamentos com Cartão</h4>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Parâmetro</th>
                                <th>Tipo</th>
                                <th>Descrição</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><span class="required">card_number</span></td>
                                <td>string</td>
                                <td>Número do cartão de crédito</td>
                            </tr>
                            <tr>
                                <td><span class="required">card_exp_month</span></td>
                                <td>integer</td>
                                <td>Mês de expiração do cartão (1-12)</td>
                            </tr>
                            <tr>
                                <td><span class="required">card_exp_year</span></td>
                                <td>integer</td>
                                <td>Ano de expiração do cartão (4 dígitos)</td>
                            </tr>
                            <tr>
                                <td><span class="required">card_cvc</span></td>
                                <td>string</td>
                                <td>Código de segurança do cartão</td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <h4>Exemplo de Requisição (Pagamento com Cartão)</h4>
                    <pre><code>{
  "price_id": "price_1RBc5DCS9XK9taAuuwYNvmz1",
  "account_id": "acct_1RBG5u2HvPqSv6Bf",
  "email": "cliente@exemplo.com",
  "name": "João Silva",
  "payment_type": "card",
  "phone": "+5511999999999",
  "address_line1": "Rua Exemplo, 123",
  "address_city": "São Paulo",
  "address_state": "SP",
  "address_postal_code": "01234-567",
  "address_country": "BR",
  "application_fee_percent": 10,
  "card_number": "4242424242424242",
  "card_exp_month": 12,
  "card_exp_year": 2025,
  "card_cvc": "123"
}</code></pre>
                    
                    <h4>Exemplo de Requisição (Pagamento com PIX)</h4>
                    <pre><code>{
  "price_id": "price_1RBc5DCS9XK9taAuuwYNvmz1",
  "account_id": "acct_1RBG5u2HvPqSv6Bf",
  "email": "cliente@exemplo.com",
  "name": "João Silva",
  "payment_type": "pix",
  "phone": "+5511999999999",
  "address_line1": "Rua Exemplo, 123",
  "address_city": "São Paulo",
  "address_state": "SP",
  "address_postal_code": "01234-567",
  "address_country": "BR",
  "tax_id": "123.456.789-00",
  "application_fee_percent": 10
}</code></pre>
                    
                    <h4>Exemplo de Resposta (Pagamento com Cartão)</h4>
                    <pre class="response"><code>{
  "success": true,
  "customer": {
    "id": "cus_S5trj88mDs0Q2P",
    "email": "cliente@exemplo.com",
    "name": "João Silva"
  },
  "payment_method": {
    "id": "card_1RBi3dCS9XK9taAuUMEGuhhx",
    "type": "card",
    "card": {
      "brand": "visa",
      "last4": "4242",
      "exp_month": 12,
      "exp_year": 2025
    }
  },
  "subscription": {
    "id": "sub_1RBi3fCS9XK9taAuJYabyYX6",
    "status": "active",
    "current_period_start": "2025-04-08 19:43:42",
    "current_period_end": "2025-05-08 19:43:42"
  },
  "payment": {
    "id": "pi_1RBi3gCS9XK9taAuB6VxQbJX",
    "status": "succeeded",
    "amount": 199.9,
    "currency": "brl"
  }
}</code></pre>
                </div>
                
                <div class="endpoint">
                    <h3><span class="method post">POST</span> Cancelar Assinatura de Cliente</h3>
                    <p>Cancela uma assinatura existente.</p>
                    
                    <div class="url mb-3">{{ url('/api/stripe/subscriptions/cancel') }}</div>
                    
                    <h4>Parâmetros da Requisição</h4>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Parâmetro</th>
                                <th>Tipo</th>
                                <th>Descrição</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><span class="required">subscription_id</span></td>
                                <td>string</td>
                                <td>ID da assinatura a ser cancelada</td>
                            </tr>
                            <tr>
                                <td><span class="optional">cancel_at_period_end</span></td>
                                <td>boolean</td>
                                <td>Se verdadeiro, cancela no final do período atual; se falso, cancela imediatamente</td>
                            </tr>
                            <tr>
                                <td><span class="optional">cancellation_reason</span></td>
                                <td>string</td>
                                <td>Motivo do cancelamento</td>
                            </tr>
                            <tr>
                                <td><span class="optional">connected_account_id</span></td>
                                <td>string</td>
                                <td>ID da conta conectada, se a assinatura estiver em uma conta conectada</td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <h4>Exemplo de Requisição (Cancelamento Imediato)</h4>
                    <pre><code>{
  "subscription_id": "sub_1RBi3fCS9XK9taAuJYabyYX6",
  "cancel_at_period_end": false,
  "cancellation_reason": "Cliente solicitou cancelamento",
  "connected_account_id": "acct_1RAHMHCS9XK9taAu"
}</code></pre>
                    
                    <h4>Exemplo de Requisição (Cancelamento no Final do Período)</h4>
                    <pre><code>{
  "subscription_id": "sub_1RBi3fCS9XK9taAuJYabyYX6",
  "cancel_at_period_end": true,
  "cancellation_reason": "Cliente solicitou cancelamento mas deseja usar até o final do período",
  "connected_account_id": "acct_1RAHMHCS9XK9taAu"
}</code></pre>
                    
                    <h4>Exemplo de Resposta (Cancelamento Imediato)</h4>
                    <pre class="response"><code>{
  "success": true,
  "message": "Assinatura cancelada imediatamente.",
  "subscription_id": "sub_1RBi3fCS9XK9taAuJYabyYX6",
  "status": "canceled",
  "cancel_at_period_end": false,
  "current_period_end": "2025-05-08 19:43:42",
  "canceled_at": "2025-04-08 20:15:30"
}</code></pre>
                    
                    <h4>Exemplo de Resposta (Cancelamento no Final do Período)</h4>
                    <pre class="response"><code>{
  "success": true,
  "message": "Assinatura configurada para cancelar no final do período atual.",
  "subscription_id": "sub_1RBi3fCS9XK9taAuJYabyYX6",
  "status": "active",
  "cancel_at_period_end": true,
  "current_period_end": "2025-05-08 19:43:42",
  "cancel_at": "2025-05-08 19:43:42"
}</code></pre>
                </div>

                <h2 id="checkout">Sessões de Checkout</h2>
                
                <div class="endpoint">
                    <h3><span class="method post">POST</span> Criar Sessão de Checkout</h3>
                    <p>Cria uma sessão de Checkout do Stripe para pagamento de assinatura com divisão.</p>
                    
                    <div class="url mb-3">{{ url('/api/stripe/subscriptions/customers') }}</div>
                    
                    <h4>Parâmetros da Requisição</h4>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Parâmetro</th>
                                <th>Tipo</th>
                                <th>Descrição</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><span class="required">price_id</span></td>
                                <td>string</td>
                                <td>ID do preço para assinar</td>
                            </tr>
                            <tr>
                                <td><span class="required">account_id</span></td>
                                <td>string</td>
                                <td>ID da conta conectada (fornecedor)</td>
                            </tr>
                            <tr>
                                <td><span class="optional">application_fee_percent</span></td>
                                <td>number</td>
                                <td>Porcentagem do valor da assinatura a ser cobrada como taxa (padrão: 10)</td>
                            </tr>
                            <tr>
                                <td><span class="required">success_url</span></td>
                                <td>string</td>
                                <td>URL para redirecionar após pagamento bem-sucedido</td>
                            </tr>
                            <tr>
                                <td><span class="required">cancel_url</span></td>
                                <td>string</td>
                                <td>URL para redirecionar se o cliente cancelar</td>
                            </tr>
                            <tr>
                                <td><span class="optional">customer_email</span></td>
                                <td>string</td>
                                <td>Pré-preenche o email do cliente na página de checkout</td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <h4>Exemplo de Requisição</h4>
                    <pre><code>{
  "price_id": "price_1RBc5DCS9XK9taAuuwYNvmz1",
  "account_id": "acct_1RBG5u2HvPqSv6Bf",
  "application_fee_percent": 10,
  "success_url": "https://seu-site.com/sucesso",
  "cancel_url": "https://seu-site.com/cancelar",
  "customer_email": "cliente@exemplo.com"
}</code></pre>
                    
                    <h4>Exemplo de Resposta</h4>
                    <pre class="response"><code>{
  "success": true,
  "checkout_url": "https://checkout.stripe.com/c/pay/cs_test_a1YkJXipSBIj1E3fv7tPWEj9ieT3eOjKLu98cNJHWwH8g6L7xymKfgV9Jc",
  "session_id": "cs_test_a1YkJXipSBIj1E3fv7tPWEj9ieT3eOjKLu98cNJHWwH8g6L7xymKfgV9Jc",
  "price_location": "platform_account",
  "price_details": {
    "id": "price_1RBc5DCS9XK9taAuuwYNvmz1",
    "product": "prod_S5ngut4c6rWGZk",
    "unit_amount": 199.9,
    "currency": "brl"
  }
}</code></pre>
                </div>

                <h2 id="payments">Pagamentos</h2>
                
                <div class="endpoint">
                    <h3><span class="method post">POST</span> Processar Pagamento Dividido</h3>
                    <p>Processa um pagamento e divide entre múltiplas contas conectadas.</p>
                    
                    <div class="url mb-3">{{ url('/api/stripe/payments/split') }}</div>
                    
                    <h4>Parâmetros da Requisição</h4>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Parâmetro</th>
                                <th>Tipo</th>
                                <th>Descrição</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><span class="required">payment_method</span></td>
                                <td>string</td>
                                <td>ID do método de pagamento a ser usado</td>
                            </tr>
                            <tr>
                                <td><span class="required">amount</span></td>
                                <td>number</td>
                                <td>Valor total a ser cobrado</td>
                            </tr>
                            <tr>
                                <td><span class="required">currency</span></td>
                                <td>string</td>
                                <td>Código de moeda de três letras (ex: BRL)</td>
                            </tr>
                            <tr>
                                <td><span class="optional">description</span></td>
                                <td>string</td>
                                <td>Descrição do pagamento</td>
                            </tr>
                            <tr>
                                <td><span class="required">transfers</span></td>
                                <td>array</td>
                                <td>Array de objetos de transferência</td>
                            </tr>
                            <tr>
                                <td><span class="required">transfers[].account</span></td>
                                <td>string</td>
                                <td>ID da conta conectada para transferir</td>
                            </tr>
                            <tr>
                                <td><span class="required">transfers[].amount</span></td>
                                <td>number</td>
                                <td>Valor a transferir para esta conta</td>
                            </tr>
                            <tr>
                                <td><span class="required">customer_email</span></td>
                                <td>string</td>
                                <td>Endereço de email do cliente</td>
                            </tr>
                            <tr>
                                <td><span class="required">customer_name</span></td>
                                <td>string</td>
                                <td>Nome completo do cliente</td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <h4>Exemplo de Requisição</h4>
                    <pre><code>{
  "payment_method": "pm_1RBi3dCS9XK9taAuUMEGuhhx",
  "amount": 100.00,
  "currency": "brl",
  "description": "Pedido #12345",
  "transfers": [
    {
      "account": "acct_1RBG5u2HvPqSv6Bf",
      "amount": 70.00
    },
    {
      "account": "acct_1RBG6v3HwQrSw7Cg",
      "amount": 20.00
    }
  ],
  "customer_email": "cliente@exemplo.com",
  "customer_name": "João Silva"
}</code></pre>
                    
                    <h4>Exemplo de Resposta</h4>
                    <pre class="response"><code>{
  "success": true,
  "payment_intent_id": "pi_1RBi3gCS9XK9taAuB6VxQbJX",
  "status": "succeeded",
  "transfers": [
    "tr_1RBi3hCS9XK9taAuXZ5kSLsq",
    "tr_1RBi3hCS9XK9taAuaTfEkdCm"
  ]
}</code></pre>
                </div>

                <h2 id="account-management">Gerenciamento de Conta</h2>
                
                <div class="endpoint">
                    <h3><span class="method get">GET</span> Verificar Requisitos da Conta</h3>
                    <p>Verifica os requisitos e status de uma conta conectada.</p>
                    
                    <div class="url mb-3">{{ url('/api/stripe/connected-account/requirements?account_id=acct_1RBG5u2HvPqSv6Bf') }}</div>
                    
                    <h4>Parâmetros da Query</h4>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Parâmetro</th>
                                <th>Tipo</th>
                                <th>Descrição</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><span class="required">account_id</span></td>
                                <td>string</td>
                                <td>ID da conta conectada a verificar</td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <h4>Exemplo de Resposta</h4>
                    <pre class="response"><code>{
  "success": true,
  "account_id": "acct_1RBG5u2HvPqSv6Bf",
  "charges_enabled": false,
  "payouts_enabled": false,
  "requirements": {
    "disabled_reason": "requirements.pending_verification",
    "currently_due": [
      "individual.verification.document"
    ],
    "eventually_due": [
      "individual.verification.document"
    ],
    "past_due": [],
    "pending_verification": []
  },
  "capabilities": {
    "card_payments": "pending",
    "transfers": "pending"
  },
  "business_profile": {
    "name": "Vinhos do Fornecedor",
    "support_phone": "+5511999999999"
  },
  "business_type": "individual",
  "created": "2025-04-08 15:23:45",
  "details_submitted": true,
  "next_steps": {
    "description": "A conta precisa completar o processo de onboarding para habilitar cobranças",
    "action": "Gere um novo link de onboarding e envie para o dono da conta",
    "endpoint": "/api/stripe/connected-account/create-link",
    "payload_example": {
      "account_id": "acct_1RBG5u2HvPqSv6Bf",
      "refresh_url": "https://seu-site.com/refresh",
      "return_url": "https://seu-site.com/return"
    }
  }
}</code></pre>
                </div>
                
                <div class="endpoint">
                    <h3><span class="method post">POST</span> Criar Link de Onboarding da Conta</h3>
                    <p>Cria um novo link de onboarding para uma conta conectada completar a verificação.</p>
                    
                    <div class="url mb-3">{{ url('/api/stripe/connected-account/onboarding-link') }}</div>
                    
                    <h4>Parâmetros da Requisição</h4>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Parâmetro</th>
                                <th>Tipo</th>
                                <th>Descrição</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><span class="required">account_id</span></td>
                                <td>string</td>
                                <td>ID da conta conectada</td>
                            </tr>
                            <tr>
                                <td><span class="required">refresh_url</span></td>
                                <td>string</td>
                                <td>URL para redirecionar se o link expirar</td>
                            </tr>
                            <tr>
                                <td><span class="required">return_url</span></td>
                                <td>string</td>
                                <td>URL para redirecionar após o onboarding ser completado</td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <h4>Exemplo de Requisição</h4>
                    <pre><code>{
  "account_id": "acct_1RBG5u2HvPqSv6Bf",
  "refresh_url": "https://seu-site.com/refresh",
  "return_url": "https://seu-site.com/return"
}</code></pre>
                    
                    <h4>Exemplo de Resposta</h4>
                    <pre class="response"><code>{
  "success": true,
  "account_id": "acct_1RBG5u2HvPqSv6Bf",
  "charges_enabled": false,
  "payouts_enabled": false,
  "details_submitted": true,
  "onboarding_url": "https://connect.stripe.com/express/onboarding/acct_1RBG5u2HvPqSv6Bf",
  "expires_at": "2025-04-09 19:43:42",
  "instructions": "Envie este URL para o dono da conta conectada. Ele precisa acessar este link e fornecer todas as informações solicitadas pelo Stripe para habilitar cobranças."
}</code></pre>
                </div>

                <h2 id="webhooks">Webhooks</h2>
                
                <div class="endpoint">
                    <h3><span class="method post">POST</span> Webhook de Checkout</h3>
                    <p>Endpoint para receber eventos de webhook do Stripe Checkout.</p>
                    
                    <div class="url mb-3">{{ url('/api/stripe/webhooks/checkout') }}</div>
                    
                    <p>Este endpoint é usado pelo Stripe para notificar sua aplicação sobre eventos de checkout. Você deve configurar esta URL no seu Painel do Stripe em Webhooks.</p>
                    
                    <h4>Eventos Importantes</h4>
                    <ul>
                        <li><code>checkout.session.completed</code> - Quando um checkout é completado com sucesso</li>
                        <li><code>customer.subscription.created</code> - Quando uma nova assinatura é criada</li>
                        <li><code>invoice.paid</code> - Quando uma fatura é paga</li>
                    </ul>
                </div>
                
                <div class="endpoint">
                    <h3><span class="method post">POST</span> Webhook de Conta</h3>
                    <p>Endpoint para receber eventos de webhook do Stripe Connect para contas.</p>
                    
                    <div class="url mb-3">{{ url('/api/stripe/webhooks/accounts') }}</div>
                    
                    <p>Este endpoint é usado pelo Stripe para notificar sua aplicação sobre eventos de contas conectadas. Você deve configurar esta URL no seu Painel do Stripe em Webhooks.</p>
                    
                    <h4>Eventos Importantes</h4>
                    <ul>
                        <li><code>account.updated</code> - Quando uma conta conectada é atualizada</li>
                        <li><code>account.application.authorized</code> - Quando sua aplicação é autorizada a acessar uma conta conectada</li>
                        <li><code>account.application.deauthorized</code> - Quando sua aplicação é desautorizada de uma conta conectada</li>
                    </ul>
                </div>

                <h2 id="error-handling">Tratamento de Erros</h2>
                <p>Todos os endpoints retornam erros em um formato consistente:</p>
                
                <pre class="response"><code>{
  "success": false,
  "message": "Mensagem de erro descrevendo o que deu errado"
}</code></pre>
                
                <p>Códigos de status HTTP comuns:</p>
                <ul>
                    <li><strong>400</strong> - Requisição Inválida (parâmetros inválidos)</li>
                    <li><strong>401</strong> - Não Autorizado (autenticação necessária)</li>
                    <li><strong>404</strong> - Não Encontrado (recurso não existe)</li>
                    <li><strong>500</strong> - Erro Interno do Servidor (algo deu errado no servidor)</li>
                </ul>
                
                <h2 id="testing">Testes</h2>
                <p>
                    Para testes, você pode usar os seguintes números de cartão de teste:
                </p>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Número do Cartão</th>
                            <th>Descrição</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>4242 4242 4242 4242</code></td>
                            <td>Pagamento bem-sucedido</td>
                        </tr>
                        <tr>
                            <td><code>4000 0000 0000 0002</code></td>
                            <td>Cartão recusado</td>
                        </tr>
                        <tr>
                            <td><code>4000 0000 0000 3220</code></td>
                            <td>Autenticação 3D Secure necessária</td>
                        </tr>
                    </tbody>
                </table>
                <p>
                    Para qualquer cartão de teste, você pode usar:
                </p>
                <ul>
                    <li>Qualquer data de expiração futura</li>
                    <li>Qualquer CVC de 3 dígitos</li>
                    <li>Qualquer CEP</li>
                </ul>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Destacar a seção ativa na barra lateral com base na posição de rolagem
            const sections = document.querySelectorAll('h2[id]');
            const navLinks = document.querySelectorAll('.sidebar .nav-link');
            
            function setActiveLink() {
                let currentSection = '';
                
                sections.forEach(section => {
                    const sectionTop = section.offsetTop;
                    const sectionHeight = section.offsetHeight;
                    
                    if (window.scrollY >= sectionTop - 100) {
                        currentSection = section.getAttribute('id');
                    }
                });
                
                navLinks.forEach(link => {
                    link.classList.remove('active');
                    if (link.getAttribute('href') === '#' + currentSection) {
                        link.classList.add('active');
                    }
                });
            }
            
            window.addEventListener('scroll', setActiveLink);
            setActiveLink();
        });
    </script>
</body>
</html>