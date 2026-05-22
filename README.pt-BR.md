# 📧 Aprovação por Email para GLPI 11

> **Simplifique as aprovações de tickets diretamente do seu inbox!** Sem login necessário, tokens seguros, respostas instantâneas.

[![Licença: GPL-3.0+](https://img.shields.io/badge/Licen%C3%A7a-GPLv3%2B-blue.svg)](https://github.com/celsocaninde/mailaprove/blob/main/LICENSE)
[![GLPI 11](https://img.shields.io/badge/GLPI-11.x-2C8DBF.svg)](https://github.com/glpi-project/glpi)
[![PHP 8.2+](https://img.shields.io/badge/PHP-8.2%2B-777BB4.svg)](https://www.php.net/)

---

## 🌍 Idioma / Language

- 🇧🇷 **Português (Brasil)** ← Você está aqui
- 🇺🇸 [**English**](README.md)

---

## ✨ Visão Geral

<div align="center">
  <img src="assets/icon.svg" alt="Mail Approve Icon" width="200" height="200">
</div>

**Mail Approve** é um poderoso plugin GLPI 11 que transforma fluxos de trabalho de tickets permitindo que usuários **aprovem, rejeitem e respondam pesquisas de satisfação diretamente do seu inbox de email**. Sem necessidade de login GLPI, sem complicações de autenticação – apenas ações seguras com validação de token criptográfico.

### 🎯 Perfeito Para:
- 📝 **Aprovações de Validação de Ticket** – Aprove/rejeite validações de tickets instantaneamente
- ✅ **Aceitação de Solução** – Aceite ou rejeite soluções propostas  
- ⭐ **Pesquisas de Satisfação** – Avalie soluções de tickets com classificação de estrelas
- 🔒 **Fluxos de Trabalho Seguros** – Autenticação baseada em token com hash SHA-256
- 📱 **Compatível com Mobile** – UI responsiva e bonita para todos os dispositivos

---

## 🚀 Funcionalidades

| Funcionalidade | Descrição |
|---------|-------------|
| 🔐 **Sem Login Necessário** | As ações são autenticadas através de tokens criptográficos seguros e de uso único |
| 🛡️ **Segurança Empresarial** | Compatível com padrões de segurança GLPI 11, proteção CSRF, hash SHA-256 de tokens |
| ⏱️ **Expiração Automática** | Tokens expiram automaticamente (configurável, padrão: 72 horas) |
| 📱 **Totalmente Responsivo** | Renderização perfeita em desktop, tablet e dispositivos móveis |
| 🎨 **UI Personalizável** | Formulários de rejeição, classificações de estrelas e respostas HTML estilizadas |
| 📊 **Log de Auditoria** | Trilha completa de auditoria de todas as ações de tokens e aprovações |
| ⚙️ **Configuração Fácil** | Configurações do plugin baseadas em GUI no painel admin do GLPI |
| 🌍 **Multi-Idioma** | Suporte para Inglês e Português (pt_BR) |

---

## 📦 Requisitos

- **GLPI**: 11.0.0 ou superior (< 11.99.99)
- **PHP**: 8.2 ou superior
- **Banco de Dados**: MySQL/MariaDB compatível

---

## 🔧 Instalação

### Passo 1: Download & Deploy
```bash
# Navegue até o diretório de plugins GLPI
cd /caminho/para/glpi/plugins/

# Clone ou baixe mailaprove
git clone https://github.com/celsocaninde/mailaprove.git
# OU
unzip mailaprove.zip
```

### Passo 2: Ativar no GLPI
1. Faça login no **GLPI** como **Super-Administrador**
2. Navegue até: **Configuração** → **Plugins**
3. Localize **"Approval By Mail"** na lista de plugins
4. Clique no botão **Instalar**
5. Clique no botão **Ativar**
6. ✅ Pronto para usar!

---

## ⚙️ Configuração

Após a instalação, acesse as configurações do plugin:

**Configuração** → **Plugins** → **Approval By Mail** (ou clique no ícone ⚙️)

### Opções Disponíveis:
- ⏱️ **Tempo de Expiração do Token** – Defina por quanto tempo os tokens permanecem válidos (padrão: 72 horas)
- 🔄 **Período de Retenção de Tokens** – Mantenha tokens usados para auditoria (padrão: 30 dias)
- 📋 **Retenção de Log de Auditoria** – Arquive logs de auditoria após N dias (padrão: 180 dias)
- ✅ **Ativar Validações** – Ativar/desativar aprovações de validação de ticket
- ✅ **Ativar Soluções** – Ativar/desativar aceitação de solução
- ✅ **Ativar Satisfação** – Ativar/desativar pesquisas de satisfação

---

## 📧 Configuração de Template de Notificação

Para ativar os recursos do Mail Approve, **adicione tags personalizadas aos seus templates de email** no GLPI.

### Passo 1: Acessar Templates
**Configuração** → **Notificações** → **Templates de Notificação**

### Passo 2: Adicionar Tags Personalizadas

#### 🎯 Para Validação de Ticket (ex: template "Validação de Ticket")
```html
<p>Para aprovar esta solicitação, clique aqui: 
   <a href="##ticket.validation.accepturl##" style="background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;">
      ✅ Aprovar
   </a>
</p>

<p>Para rejeitar esta solicitação, clique aqui: 
   <a href="##ticket.validation.rejecturl##" style="background: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;">
      ❌ Rejeitar
   </a>
</p>
```

#### ✅ Para Soluções (ex: template "Solução do Ticket")
```html
<p>Para aceitar esta solução, clique aqui: 
   <a href="##ticket.solution.accepturl##" style="background: #17a2b8; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;">
      ✓ Aceitar Solução
   </a>
</p>

<p>Para rejeitar esta solução, clique aqui: 
   <a href="##ticket.solution.rejecturl##" style="background: #ffc107; color: black; padding: 10px 20px; text-decoration: none; border-radius: 4px;">
      ✗ Rejeitar Solução
   </a>
</p>
```

#### ⭐ Para Pesquisas de Satisfação (ex: template "Satisfação do Ticket")
```html
<p>Por favor, avalie esta solução de ticket: 
   <a href="##ticket.satisfaction.url##" style="background: #6f42c1; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;">
      ⭐ Avalie Agora
   </a>
</p>
```

---

## 🔒 Segurança

### 🛡️ Destaques de Segurança:
- ✅ **Hash SHA-256 de Token** – Tokens são hashed no banco de dados, nunca armazenados em texto plano
- ✅ **Tokens de Uso Único** – Cada token pode ser usado apenas uma vez
- ✅ **Expiração Automática** – Tempo de vida do token configurável previne abuso
- ✅ **Proteção CSRF** – Conformidade completa com padrões CSRF do GLPI 11
- ✅ **Log de Auditoria** – Toda ação é registrada com IP, user agent, timestamp
- ✅ **Caminhos Sem Estado** – Endpoints de email contornam autenticação sem comprometer segurança

### 📊 Trilha de Auditoria:
Todas as aprovações e rejeições são registradas com:
- Identificador do usuário
- Endereço IP
- User agent (informações do navegador/cliente)
- Timestamp
- Tipo de ação e resultado

---

## 📁 Estrutura do Projeto

```
mailaprove/
├── README.md                 # Arquivo em Inglês
├── README.pt-BR.md          # Arquivo em Português
├── LICENSE                  # Licença GPLv3+
├── composer.json            # Dependências PHP
├── hook.php                 # Hooks do plugin
├── setup.php                # Metadados e instalação
├── assets/
│   └── icon.svg             # Ícone do plugin
├── ajax/
│   └── template.preview.php # Pré-visualização de template
├── front/
│   ├── approve.php          # Formulário de aprovação
│   ├── reject.php           # Formulário de rejeição
│   ├── solution_approve.php # Aceitação de solução
│   ├── solution_reject.php  # Rejeição de solução
│   ├── satisfaction.php     # Pesquisa de satisfação
│   ├── config.form.php      # UI de configuração
│   └── audit.php            # Visualizador de log
├── src/
│   ├── AuditLog.php         # Serviço de auditoria
│   ├── Config.php           # Gerenciamento de configuração
│   ├── NotificationHandler.php # Injetor de tags de email
│   ├── PublicAction.php     # Handler de endpoint público
│   └── Token.php            # Geração e validação de token
├── templates/
│   ├── action_confirm.php   # Página de confirmação
│   ├── reject_form.php      # UI do formulário de rejeição
│   └── satisfaction_form.php # UI da pesquisa de satisfação
└── locale/
    ├── en_US.po             # Traduções em Inglês
    └── pt_BR.po             # Traduções em Português
```

---

## 🎓 Exemplos de Uso

### ✅ Exemplo: Aprovação por Email

1. **Usuário recebe email** com aviso de validação
2. **Usuário clica** no link "Aprovar" do inbox
3. **Navegador abre** formulário seguro (sem login necessário)
4. **Ação é registrada** no GLPI automaticamente
5. **Log de auditoria atualizado** com timestamp e IP

### ⭐ Exemplo: Pesquisa de Satisfação

1. **Usuário recebe** email de solicitação de satisfação
2. **Usuário clica** no link "Avalie Agora"
3. **Formulário bonito de classificação de estrelas** abre
4. **Usuário envia** classificação + comentários opcionais
5. **Pesquisa registrada** no GLPI + trilha de auditoria criada

---

## 🔄 Endpoints da API

O Mail Approve expõe endpoints públicos para ações de email:

| Endpoint | Método | Propósito |
|----------|--------|---------|
| `/plugins/mailaprove/front/approve.php` | GET/POST | Aprovar validação de ticket |
| `/plugins/mailaprove/front/reject.php` | GET/POST | Rejeitar validação de ticket |
| `/plugins/mailaprove/front/solution_approve.php` | GET/POST | Aceitar solução |
| `/plugins/mailaprove/front/solution_reject.php` | GET/POST | Rejeitar solução |
| `/plugins/mailaprove/front/satisfaction.php` | GET/POST | Enviar classificação de satisfação |

**Autenticação**: Baseada em token (sem sessão necessária)

---

## 🐛 Resolução de Problemas

### ❓ Links não funcionam nos emails
- ✅ Verifique se as tags personalizadas foram adicionadas aos templates de notificação
- ✅ Verifique as configurações de expiração de token no config do plugin
- ✅ Garanta que as tags `##ticket.validation.accepturl##` estejam sendo usadas corretamente

### ❓ Erro "Token Expirado"
- ✅ O padrão de expiração é 72 horas – ajuste nas configurações do plugin
- ✅ Verifique sincronização de hora do servidor
- ✅ Revise logs de auditoria para detalhes do token

### ❓ Plugin não vai instalar
- ✅ Verifique se versão PHP ≥ 8.2
- ✅ Verifique se versão GLPI é 11.x
- ✅ Garanta permissões de arquivo: `chmod 755 mailaprove`
- ✅ Limpe cache do plugin GLPI: **Configuração** → **Plugins** → **Reinstalar**

---

## 📝 Localização

O Mail Approve suporta múltiplos idiomas:

- 🇺🇸 **Inglês (en_US)**
- 🇧🇷 **Português - Brasil (pt_BR)**

Para adicionar mais idiomas, edite arquivos `.po` em `locale/` e contribua!

---

## 🤝 Contribuindo

Bem-vindo de contribuições! Você pode:

- 🐛 Relatar bugs via [Issues](https://github.com/celsocaninde/mailaprove/issues)
- 💡 Sugerir recursos via [Discussions](https://github.com/celsocaninde/mailaprove/discussions)
- 🔧 Enviar pull requests para melhorias
- 🌍 Ajudar a traduzir para novos idiomas

Veja [CONTRIBUTING.md](.github/CONTRIBUTING.md) para detalhes.

---

## 📄 Licença

Este projeto é licenciado sob a **Licença Pública Geral GNU v3.0 ou posterior** (GPLv3+).

Veja arquivo [LICENSE](LICENSE) para detalhes.

---

## 👨‍💻 Autor

**Desenvolvimento comunitário**  
Construído com ❤️ para administradores e usuários de GLPI em todo o mundo.

---

## 🙏 Agradecimentos

- Time GLPI pelo excelente sistema de gerenciamento de tickets
- Contribuidores que submeteram correções e melhorias
- Comunidade por feedback e solicitações de recursos

---

## 📞 Suporte

- 📖 **Documentação**: [Wiki](https://github.com/celsocaninde/mailaprove/wiki)
- 🐛 **Issues**: [GitHub Issues](https://github.com/celsocaninde/mailaprove/issues)
- 💬 **Discussões**: [GitHub Discussions](https://github.com/celsocaninde/mailaprove/discussions)
- 📧 **Email**: contato@example.com

---

**Feito com ❤️ para GLPI** | ⭐ Se você achar este plugin útil, por favor dê uma estrela neste repositório!
