# IArandú — Atualização da Landing Page e Estrutura de Preços & Planos

> **Documento direcionado à equipe de desenvolvimento IArandú**  
> **Solicitante:** Diego Maldonado  
> **Data:** 21 de Outubro de 2026  
> **Objetivo:** Implementar a nova estratégia comercial de retenção e expansão de agências de publicidade do Paraná com contratos públicos.

> **Estado da entrega:** esta pasta está disponível na branch `main` para revisão técnica. A página e as rotas executáveis estão na branch `feature/planos-e-precos`. Nenhuma alteração foi publicada em servidor por esta entrega; acesso à Hostinger não é necessário para revisar os arquivos.

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

## 3. O que está integrado na branch `feature/planos-e-precos`

Além da cópia isolada nesta pasta, os arquivos foram posicionados nos locais adequados da aplicação Laravel na branch `feature/planos-e-precos` para testes locais. A branch `main` contém somente esta pasta de entrega até que a integração seja revisada e aprovada:

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

Para validar a página executável, faça checkout da branch `feature/planos-e-precos` antes dos comandos abaixo. A pasta isolada na `main` serve para revisão e não altera as rotas do projeto nessa branch.

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

## 5. Próxima etapa de integração

Após a revisão do desenvolvedor e a aprovação do Diego, integrar os arquivos da branch `feature/planos-e-precos` à aplicação, validar a página no ambiente de destino e seguir o procedimento de publicação da equipe. Esta pasta não identifica nem altera a hospedagem de produção.
