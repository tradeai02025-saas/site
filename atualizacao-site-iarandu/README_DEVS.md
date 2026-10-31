# IArandú — Atualização da Landing Page e Estrutura de Preços & Planos

> **Documento direcionado à equipe de desenvolvimento IArandú**  
> **Solicitante:** Diego Maldonado  
> **Data:** 21 de Outubro de 2026  
> **Objetivo:** Implementar a nova estratégia comercial de retenção e expansão de agências de publicidade do Paraná com contratos públicos.

---

## 1. Contexto de Negócio & Decisão Estratégica

Para reter agências parceiras e acelerar o fechamento de novos contratos com agências de publicidade que gerenciam verbas públicas no Paraná, foram estabelecidos **dois níveis de planos comerciais** no site institucional:

1. **Plano Basic — R$ 35.000 / ano** (ou 12x de ~R$ 2.916,66/mês)
   - Focado em entrada rápida e retenção de agências.
   - Inclui: Gestão completa de PIs (emissão, conferência em massa e baixa com histórico auditável), base de fornecedores e veículos com reaproveitamento de dados bancários, módulo de representantes comerciais com aprovação prévia, coleta e alertas automáticos de vencimento de documentos fiscais/certidões, e suporte direto via WhatsApp com os fundadores.
2. **Plano Full — R$ 70.000 / ano** (ou 12x de ~R$ 5.833,33/mês)
   - Plano de maior valor e parceria técnica contínua.
   - **Grande Diferencial:** **Desenvolvedor Dedicado Free** — construção sob demanda de features, regras de negócio, telas e integrações personalizadas para a agência sem cobrança extra de horas de desenvolvimento.
   - Inclui: Tudo do Basic + trilhas de auditoria customizadas para prestação de contas de contratos públicos, conexões sob medida com ERPs contábeis/BI, SLA de atendimento crítico em 2h e treinamento contínuo ilimitado.

---

## 2. O que contém esta pasta (`atualizacao-site-iarandu/`)

Esta pasta foi criada de forma isolada para que vocês possam inspecionar com facilidade todos os arquivos modificados e adicionados sem interferir em branches ou fluxos legados:

```
atualizacao-site-iarandu/
├── README_DEVS.md                 # Este guia explicativo
├── views/
│   ├── landing.blade.php          # View completa da landing page oficial com a seção #planos integrada
│   └── login.blade.php            # View de login com logo oficial e link de retorno para o site/planos
├── routes/
│   └── rotas_adicionais.php       # Trecho das rotas públicas ('/' e '/planos') adicionadas em routes/web.php
└── assets/
    ├── css/                       # Estilos da landing page (layouts-site.css, landing_page.css)
    ├── js/                        # Scripts da landing page (layouts-site.js, landing_page.js)
    └── img/
        ├── logo-header.png        # Logo oficial em alta resolução para o cabeçalho (1182x299 transparente)
        └── logo-footer.png        # Logo oficial em alta resolução para o rodapé (1182x299 transparente)
```

---

## 3. O que já está integrado no código-fonte principal

Além do backup isolado nesta pasta, os arquivos já foram posicionados nos locais adequados da aplicação Laravel para testes locais imediatos:

| Arquivo no Projeto | O que foi feito |
|---|---|
| `resources/views/landing.blade.php` | Nova view Blade da landing page idêntica a `www.iarandu.com.br`, contendo a seção `#planos` e botões de WhatsApp direcionados para o Diego. |
| `resources/views/auth/login.blade.php` | Atualizado para usar o logotipo oficial do cabeçalho e adicionado botão de retorno ao site / planos. |
| `routes/web.php` | Adicionadas rotas públicas `Route::get('/', ...)` e `Route::get('/planos', ...)`. Caso o usuário já esteja autenticado, é redirecionado para o `/dashboard`. |
| `config/app.php` | Nome padrão da aplicação ajustado para `'IArandú'`. |
| `.env.example` | Corrigido `APP_NAME="IArandú"` (removida referência legada a "Trade SaaS"). |
| `public/assets/` e `public/img/` | Inseridos os novos assets oficiais de imagens, estilos e scripts. |

---

## 4. Como Rodar e Testar Localmente

Para validar o funcionamento em qualquer máquina:

```bash
# 1. Instalar dependências (caso seja clonado do zero)
composer install

# 2. Configurar o .env com APP_NAME="IArandú" e banco sqlite/mysql
cp .env.example .env
php artisan key:generate

# 3. Rodar as migrações
php artisan migrate

# 4. Iniciar o servidor de desenvolvimento
php artisan serve
```

Acesse:
- **Landing page com planos:** `http://localhost:8000/#planos`
- **Página de Login:** `http://localhost:8000/login`

---

## 5. Como Subir para Produção (VPS Nginx)

Quando aprovado pelo Diego:
1. Fazer o merge da branch `feature/planos-e-precos` (ou copiar os arquivos desta pasta) para a branch `main`.
2. Na VPS de produção:
   ```bash
   git pull origin main
   php artisan view:clear
   php artisan route:clear
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```
3. Nenhuma alteração de DNS no **Registro.br** é necessária, pois os apontamentos já respondem diretamente na VPS.
