# Relatório de Segurança – BarbaBook

## Vulnerabilidades identificadas e status

### 1. **CSRF (Cross-Site Request Forgery)** – CRÍTICO
- **Risco:** Formulários (login, criar agendamento, cadastros admin) não usam token CSRF. Um site malicioso pode enviar requisições em nome do usuário logado.
- **Correção:** Implementar token CSRF em todos os formulários que alteram estado (login, agendamentos, cadastros). Ver `includes/csrf.php` e uso nas páginas.

### 2. **Sessão sem endurecimento de cookie** – MÉDIO
- **Risco:** Cookie de sessão sem `HttpOnly`, `SameSite` ou `Secure` facilita roubo por XSS ou ataques de fixação.
- **Correção:** Configurar `session_set_cookie_params()` antes de `session_start()` com `httponly`, `samesite=Lax` ou `Strict`, e `secure` em HTTPS.

### 3. **Script criar-admin.php acessível pela web** – CRÍTICO
- **Risco:** Qualquer pessoa que acessar `/criar-admin.php` pela URL pode criar ou redefinir a senha do admin.
- **Correção:** Executar apenas via linha de comando (`php criar-admin.php`). Bloquear execução quando for requisição HTTP.

### 4. **Validação de forma_pagamento** – BAIXO
- **Risco:** Campo `forma_pagamento` no agendamento aceita qualquer string. Pode gravar valor inesperado (sanitizado na saída com htmlspecialchars, mas melhor validar na entrada).
- **Correção:** Validar contra lista fixa (pix, dinheiro, credito, debito, transferencia) antes do INSERT.

### 5. **Força bruta no login** – MÉDIO
- **Risco:** Não há limite de tentativas nem bloqueio temporário. Senha fraca pode ser descoberta por tentativa e erro.
- **Correção:** Implementar rate limiting (ex.: máximo de 5 tentativas por IP em 15 minutos) e/ou CAPTCHA após falhas.

### 6. **Credenciais no código** – MÉDIO
- **Risco:** Senha do banco em `config/database.php` (fallback `Ester2025`). Em produção deve vir apenas de variáveis de ambiente.
- **Correção:** Em produção usar somente `getenv('DB_PASS')` e não deixar valor padrão sensível no repositório.

### 7. **SQL Injection** – BAIXO (já mitigado)
- **Status:** Uso predominante de prepared statements. Único ponto a ajustar: `criar-admin.php` usa `$pdo->quote($email)` com e-mail fixo; preferir prepared statement por consistência.

### 8. **XSS (Cross-Site Scripting)** – BAIXO (já mitigado)
- **Status:** Saídas dinâmicas usam `htmlspecialchars()`. Manter esse padrão em qualquer novo campo exibido.

### 9. **Open Redirect** – OK
- **Status:** Redirecionamentos usam apenas `SITE_BASE` e caminhos fixos; não há redirecionamento para URL controlada pelo usuário.

### 10. **Controle de acesso (admin)** – OK
- **Status:** Páginas admin usam `admin_require_login()`. Dados filtrados por `estabelecimento_id`/efetivo evitam vazamento entre estabelecimentos.

---

## Correções implementadas (nesta revisão)

- [x] **Session cookie:** HttpOnly, SameSite=Lax, Secure quando HTTPS (`admin/bootstrap.php`).
- [x] **criar-admin.php:** execução apenas via CLI; acesso HTTP retorna 403.
- [x] **CSRF:** `includes/csrf.php` (csrf_token, csrf_field, csrf_validate); token no login, cadastro de estabelecimentos (criar + escolher), e formulários de agendamento (barbeiro e nails).
- [x] **forma_pagamento:** validação por whitelist (pix, dinheiro, credito, debito, transferencia) em `barbeiro/agendamentos.php` e `nails/agendamentos.php`.
- [x] **criar-admin:** uso de prepared statement em vez de `quote()`.

## Recomendações adicionais

- Em produção: HTTPS e `session.cookie_secure = 1`.
- Remover ou restringir acesso a `criar-admin.php` no servidor (ex.: fora do document root ou bloqueio por regra no servidor web).
- Considerar política de senha forte para administradores.
- Manter dependências (PHP, MySQL) atualizadas.
