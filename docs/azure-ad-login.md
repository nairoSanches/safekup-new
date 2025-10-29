# Login com Microsoft Entra ID (Azure AD)

Este projeto suporta login via Microsoft Entra ID (antigo Azure AD) usando OAuth2 / OpenID Connect e Microsoft Graph.

## 1) Registrar um aplicativo no Entra ID

- Acesse Azure Portal → Microsoft Entra ID → App registrations → New registration
- Nome: Safekup
- Account type: Single tenant (ou o que preferir)
- Redirect URI (Web): `http://SEU_HOST/php/login/ms_callback.php`
- Após criar, anote:
  - Application (client) ID → `AZURE_CLIENT_ID`
  - Directory (tenant) ID → `AZURE_TENANT_ID`
  - Crie um Client Secret → `AZURE_CLIENT_SECRET`

## 2) Scopes

- Conceda permissões Microsoft Graph (delegated): `User.Read`
- Consentimento de admin pode ser necessário.

## 3) Configurar variáveis no `/etc/safekup/.env`

```
AZURE_TENANT_ID=xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx
AZURE_CLIENT_ID=xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx
AZURE_CLIENT_SECRET=SEU_SEGREDO
AZURE_REDIRECT_URI=http://SEU_HOST/php/login/ms_callback.php
AZURE_SCOPES="openid profile email offline_access User.Read"
```

## 4) Fluxo

- `index.html` tem um botão “Entrar com Microsoft” que chama `php/login/ms_login.php`.
- `ms_login.php` redireciona para `login.microsoftonline.com` com `state` e `nonce`.
- Após login, o Azure chama `ms_callback.php`, que troca o `code` por tokens e usa o `access_token` no Graph `GET /me` para obter `userPrincipalName`.
- A sessão `$_SESSION['login']` é preenchida e o usuário é redirecionado para o painel.

## 5) Observações de segurança

- Em produção, use HTTPS no redirect URI.
- Limite o escopo ao necessário.
- Opcionalmente, valide a assinatura do `id_token` via JWKS (a implementação atual confia no token para acesso ao Graph e não usa o `id_token`).
- Garanta que a extensão cURL esteja habilitada no PHP (padrão na maioria das builds).

## 6) Laravel

- No Laravel, você pode replicar isso via Socialite (SocialiteProviders/Azure) ou TheNetworg/oauth2-azure. Os stubs atuais focam no login LDAP, mas podem ser adaptados para redirecionar para o Microsoft login e tratar o callback em rotas Laravel.
