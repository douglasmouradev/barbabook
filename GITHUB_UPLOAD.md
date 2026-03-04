# Enviar o BarbaBook para o GitHub

O projeto já está versionado com Git (branch `main`) e o primeiro commit foi feito.

## Passos

### 1. Criar o repositório no GitHub

1. Acesse **https://github.com/new**
2. **Repository name:** por exemplo `barbabook` ou `BarbaBook`
3. Descrição (opcional): *Sistema de agendamento para barbeiros e nail design (PHP 8 + MySQL 8)*
4. Escolha **Public**
5. **Não** marque "Add a README" (o projeto já tem um)
6. Clique em **Create repository**

### 2. Conectar e enviar (push)

No terminal, na pasta do projeto (`/Users/douglas/Desktop/barbabook`), rode (troque `SEU_USUARIO` pelo seu usuário do GitHub):

```bash
cd /Users/douglas/Desktop/barbabook

git remote add origin https://github.com/SEU_USUARIO/barbabook.git
git push -u origin main
```

Se o repositório tiver outro nome (ex.: `BarbaBook`), use:

```bash
git remote add origin https://github.com/SEU_USUARIO/BarbaBook.git
git push -u origin main
```

### 3. Autenticação

- Se o GitHub pedir usuário e senha, use um **Personal Access Token** em vez da senha da conta (em GitHub → Settings → Developer settings → Personal access tokens).
- Ou use o **GitHub CLI**: `gh auth login` e depois `git push -u origin main`.

---

**Importante:** O arquivo `config/database.php` (com sua senha local) **não** foi enviado. Quem clonar o projeto deve copiar `config/database.example.php` para `config/database.php` e configurar o banco. Isso está explicado no README.
