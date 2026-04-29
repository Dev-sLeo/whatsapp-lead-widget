# WhatsApp Lead Widget — Plugin WordPress

Botão flutuante do WhatsApp com formulário de captura de leads integrado ao painel WordPress.

## 📦 Instalação

1. Copie a pasta `whatsapp-lead-widget` para `/wp-content/plugins/`
2. Ative o plugin em **Plugins → Plugins Instalados**
3. Acesse **WhatsApp Leads → Configurações** no menu lateral
4. Preencha o número do WhatsApp e o e-mail de notificação
5. Salve — o botão aparecerá automaticamente no seu site!

## ⚙️ Configurações disponíveis

| Campo | Descrição |
|---|---|
| Número do WhatsApp | Formato internacional sem símbolos. Ex: `5511999999999` |
| E-mail de notificação | Recebe um e-mail a cada novo lead |
| Posição do botão | Direita ou Esquerda da tela |
| Mensagem padrão | Texto enviado ao abrir o WhatsApp. Use `{nome}` para o nome do lead |
| Título / Subtítulo do modal | Personalize o cabeçalho do formulário |
| Campo Empresa | Ative/desative o campo opcional de empresa |

## 📋 Funcionalidades

- ✅ Botão flutuante animado com pulse ring
- ✅ Modal com design dark, igual ao screenshot de referência
- ✅ Validação de campos em tempo real
- ✅ Máscara automática de telefone
- ✅ Leads salvos no banco de dados WordPress
- ✅ Notificação por e-mail a cada novo lead (HTML formatado)
- ✅ Exportação de leads em CSV
- ✅ Paginação na listagem de leads
- ✅ Tela de sucesso com botão para abrir o WhatsApp
- ✅ Suporte a posição esquerda/direita
- ✅ Responsivo para mobile

## 🗂 Estrutura de Arquivos

```
whatsapp-lead-widget/
├── whatsapp-lead-widget.php   ← Plugin principal
├── assets/
│   ├── widget.css             ← Estilos do botão e modal (frontend)
│   ├── widget.js              ← Lógica do widget (frontend)
│   └── admin.css              ← Estilos do painel admin
└── README.md
```

## 🛡️ Segurança

- Nonce WordPress em todas as requisições AJAX
- Sanitização de todos os campos com funções nativas do WordPress
- Validação de e-mail server-side
- Verificação de permissões nas páginas administrativas
- Proteção contra acesso direto ao arquivo PHP

## 📧 E-mail de notificação

Cada lead gera um e-mail HTML com nome, e-mail, telefone, empresa, data e IP do visitante, enviado via `wp_mail()`.

## 📤 Exportação CSV

Acesse **WhatsApp Leads → Leads Capturados** e clique em **Exportar CSV** para baixar todos os leads em formato compatível com Excel (UTF-8 com BOM, separador `;`).
