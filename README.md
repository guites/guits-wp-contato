# guits-wp-contato
Este plugin cria um formulário de contato simples em uma página de sua escolha.
* Mensagens de contato enviadas são salvas como comentários.
* Verificação de spam não intrusiva: apenas mensagens marcadas como spam são forçadas a resolver o desafio reCAPTCHA.
* Acessibilidade: todas as funcionalidades são independentes de javascript, exceto o reCAPTCHA.
* Leve: o reCAPTCHA e seus volumosos kilobytes são carregados apenas para usuários marcados como spam.
![Requisições Recaptcha](/assets/images/recaptcha.png)
## Verificação de SPAM via Akismet API
O plugin inclui uma verificação básica de spam através da API do akismet.
### Desafio reCAPTCHA para mensagens marcadas como SPAM
Você pode optar por salvar as mensagens marcadas como spam diretamente na pasta spam do wordpress, ou salvá-las apenas se o usuário (possivelmente um bot) resolver um desafio reCAPTCHA.
#### Configuração via painel ADM
O plugin cria um único menu no painel, de onde você gerencia todas as configurações necessária.

![Painel Admin](/assets/images/admin-view.png)
