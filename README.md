<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.2-777BB4?style=flat-square&logo=php&logoColor=white" alt="PHP"/>
  <img src="https://img.shields.io/badge/MySQL-8-4479A1?style=flat-square&logo=mysql&logoColor=white" alt="MySQL"/>
  <img src="https://img.shields.io/badge/Agendamento-Online-0ea5e9?style=flat-square" alt="Agendamento"/>
</p>

<h1 align="center">BarbaBook</h1>

<p align="center">
  <strong>Agendamento online para barbearia e nail design</strong> — módulos Barbeiro e Nails, painel admin e suporte multibeneficiário.
</p>

<p align="center">
  <a href="https://portifolio-douglas-moura.vercel.app">Portfólio</a> ·
  <a href="https://github.com/douglasmouradev/barbabook">Repositório</a> ·
  <a href="https://wa.me/5571997087082?text=Ol%C3%A1%20Douglas%2C%20tenho%20interesse%20no%20BarbaBook.">Solicitar implantação</a>
</p>

---

# BarbaBook

Sistema de agendamento para **barbeiros** e **nail design**, desenvolvido para **tdesksolutions.com.br**.

## Destaques

- **PHP 8.2+** com PDO e MySQL 8
- Página inicial: escolha entre **Barbeiro** ou **Nails**
- **Barbeiro** → Agendamentos de barbeiros e serviços (corte, barba, etc.)
- **Nails** → Agendamentos de unhas e afins (manicure, pedicure, alongamento, nail design)

## Requisitos

- PHP 8.2 ou superior (recomendado 8.3)
- MySQL 8 ou MariaDB 10.6+
- Extensões PHP: `pdo_mysql`, `json`, `mbstring`

## Instalação

1. **Clone ou copie** os arquivos para o servidor (ou subdiretório, ex: `https://tdesksolutions.com.br/barbabook/`).

2. **Crie o banco e importe o schema:**
   ```bash
   mysql -u seu_usuario -p < database/schema.sql
   ```
   Ou no MySQL:
   ```sql
   source /caminho/para/barbabook/database/schema.sql
   ```

3. **Configure a conexão:** copie `config/database.example.php` para `config/database.php` e ajuste usuário/senha do MySQL, ou use variáveis de ambiente:
   - `DB_HOST` (padrão: localhost)
   - `DB_PORT` (padrão: 3306)
   - `DB_NAME` (padrão: barbabook)
   - `DB_USER`
   - `DB_PASS`

4. **Se o site estiver em um subdiretório** (ex: `/barbabook`), defina:
   - `SITE_BASE=/barbabook` em `config/app.php` ou variável de ambiente `SITE_BASE`.

## Estrutura

```
barbabook/
├── config/
│   ├── app.php          # SITE_BASE
│   ├── database.php     # Credenciais DB
│   └── bootstrap.php    # Conexão PDO
├── database/
│   └── schema.sql      # Tabelas e dados iniciais
├── includes/
│   ├── header.php
│   └── footer.php
├── assets/
│   ├── css/style.css
│   └── js/app.js
├── barbeiro/
│   └── agendamentos.php # Aba de agendamentos barbeiro
├── nails/
│   └── agendamentos.php # Aba de agendamentos unhas/nail design
├── index.php            # Página inicial (escolha Barbeiro / Nails)
└── README.md
```

## Uso

- **Início:** em `index.php` o usuário escolhe **Barbeiro** ou **Nails**.
- **Barbeiro:** redireciona para `/barbeiro/agendamentos.php` (serviços e agendamentos de barbeiros).
- **Nails:** redireciona para `/nails/agendamentos.php` (agendamentos de unhas e afins).

Em cada página de agendamento é possível criar novo agendamento (cliente, serviço, profissional, data/hora) e ver os próximos agendamentos e a lista de serviços.

## Admin e multibeneficiário

- **Login:** `/admin/login.php`. Na primeira vez, o e-mail e a senha informados criam o primeiro administrador.
- **Criar admin padrão (CLI):** `php criar-admin.php` — cria usuário `admin@barbabook.com` / senha `admin` (troque após o primeiro login).
- **Multibeneficiário:** várias barbearias e nails podem usar o sistema; cada uma vê apenas seus agendamentos. Execute também `database/multitenant.sql` após o schema.

## Domínio

Projeto preparado para o domínio **tdesksolutions.com.br**. Ajuste `SITE_BASE` em `config/app.php` se a aplicação estiver em um subdiretório.
