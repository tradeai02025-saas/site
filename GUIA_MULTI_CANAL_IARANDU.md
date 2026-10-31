# 🚀 Guia de Operação Multi-Canal B2B IArandú
**Canais Ativos:** E-mail Corporativo (Zoho SMTP), WhatsApp (OPENwa) e Instagram (iarticule-bot / euROBOexe)

---

## 1. 📧 Canal 1: E-mails Corporativos (Zoho SMTP)
- **Painel em Tempo Real:** [http://localhost:8050](http://localhost:8050)
- **Remetente Oficial:** `contato@iarandu.com.br` (Diego Maldonado / IArandú)
- **Status do Primeiro Lote (45 Agências Nacionais):**
  - **40+ Enviados com 100% de entrega e 0 erros** (Master, Opus Múltipla, TIF, CC&P, Binder, Nova/SB, Lew'Lara\TBWA, Propeg, Calia, DPZ, Artplan, Escala, Tom, Lápis Raro, Cannes, etc.)
  - Delays seguros de 35s a 55s entre cada mensagem com anti-bloqueio e tracking individual no SQLite.

---

## 2. 💬 Canal 2: WhatsApp Corporativo (OPENwa)
Todos os contatos das agências foram auditados, higienizados e formatados para consumo imediato pelo **OPENwa**:

### Arquivos Gerados e Prontos para Uso:
1. **Payload JSON para OPENwa:**
   `D:\Eternity-Signals\OPENwa\OpenWA\contatos_iarandu_agencias.json` (196 agências com `chat_id`, telefone com DDI/DDD limpo e mensagem template).
2. **Importação Google Contacts / Android:**
   `D:\Eternity-Signals\OPENwa\OpenWA\contatos_iarandu_google_android.csv`
   *(Permite subir a agenda com 1 clique no Google Contatos para sincronizar no celular de prospecção).*
3. **Cartão Virtual vCard 3.0 (.vcf):**
   `D:\Eternity-Signals\OPENwa\OpenWA\contatos_iarandu_agencias.vcf`
   *(Basta abrir o arquivo no celular ou no WhatsApp para importar todos os contatos como "IArandú Agências").*

### Script de Abordagem WhatsApp (Copy Direta):
> "Olá, tudo bem? Sou o Diego Maldonado, fundador da IArandú (iarandu.com.br). Desenvolvemos um sistema de inteligência e automação de conciliação de mídias (PIs, notas fiscais e certidões) sob medida para agências que atendem grandes contas públicas. Gostaria de propor um bate-papo de 10 min nesta semana para demonstrar como eliminamos o retrabalho na sua esteira de mídia?"

---

## 3. 📸 Canal 3: Instagram Direct (iarticule-bot / euROBOexe)
Todos os perfis foram estritamente filtrados (zero perfis fakes, eliminados perfis internacionais ou contas aleatórias).

### Arquivos e Injeção no Bot:
1. **Lista de Handles Oficiais (@):**
   `D:\Eternity-Signals\euROBOexe\agencias_instagram_handles.txt` (62 perfis reais e ativos)
2. **Planilha Completa com Contratos e Órgãos:**
   `D:\Eternity-Signals\euROBOexe\agencias_instagram_alvos.csv`
3. **Injeção Direta no Banco do Bot:**
   Todos os 62 perfis foram inseridos diretamente na base de dados do bot (`D:\Eternity-Signals\euROBOexe\articula_engajados\data\leads_cidadaos.db`) com status `pendente` e etiqueta `iarandu_b2b`.
4. **Como Rodar o Bot de Instagram:**
   Basta executar o atalho que você já possui:
   `D:\Eternity-Signals\euROBOexe\Abrir_ArticulaEngajados.bat`
   O robô abrirá o painel em `http://127.0.0.1:5005`, já carregando as agências IArandú na fila de envio!

### Templates Spintax para o Instagram Direct:
`D:\Eternity-Signals\euROBOexe\SPINTAX_IARANDU_INSTAGRAM.txt`

**Variação Recomendada:**
> `{Olá|Oi|Tudo bem}, {pessoal|time da agência}! {Acompanho o trabalho de vocês|Admiro a trajetória da agência} e vi que vocês gerenciam contas expressivas no setor público. Desenvolvemos o IArandú (www.iarandu.com.br), plataforma sob medida para automação de PIs, conciliação de mídias e certidões fiscais. {Gostaria de agendar um café virtual de 10 min nesta semana|Podemos trocar uma ideia rápida de 5 minutos}? {Um abraço|Abraço}!`

---

## 4. 🎯 Próximos Passos Comerciais Imediatos:
1. **Candy Shop (#1 Prioridade do Paraná):**
   - Kal Gelbecke sugeriu contato direto.
   - Sócios: Eduardo Johnscher (`@eduardohbj`) e Ricardo Mercer (`@candyshopag`).
   - Abordagem via Script A (mencionando a conversa pessoal com o Kal).
2. **Trade Marketing (Retenção e Fechamento R$ 35k/ano):**
   - Contato direto: Adalberto Eschholz Diniz (`administrativo@trademkt.com.br` / `(41) 3352-9678`).
   - Argumento: A equipe de checking adora a plataforma e a anuidade de R$ 35k corta o custo pela metade em relação à estrutura antiga.
