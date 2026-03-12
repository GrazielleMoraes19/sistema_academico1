# 🎓 Sistema de Gestão Acadêmica

Sistema web completo em PHP + MySQL para gestão acadêmica com módulos de avisos, atividades e controle de usuários.

---

## 📁 Estrutura de Pastas

```
/projeto
├── index.php           → Redireciona para login
├── login.php           → Tela de login
├── cadastro.php        → Cadastro de usuários
├── dashboard.php       → Painel principal
├── avisos.php          → Mural de avisos (CRUD)
├── atividades.php      → Atividades acadêmicas (CRUD)
├── usuarios.php        → Gerenciamento de usuários (admin)
├── logout.php          → Encerrar sessão
├── banco.sql           → Script SQL do banco de dados
│
├── /conexao
│   └── conexao.php     → Configuração e conexão MySQL
│
├── /includes
│   ├── header.php      → Cabeçalho HTML
│   ├── footer.php      → Rodapé HTML
│   └── menu.php        → Menu lateral (sidebar)
│
└── /css
    └── style.css       → Estilos completos do sistema
```

---

## ⚙️ Como Instalar e Executar no XAMPP

### Passo 1 — Instalar o XAMPP
Baixe em: https://www.apachefriends.org/
Instale e inicie os serviços **Apache** e **MySQL**.

### Passo 2 — Copiar os arquivos do projeto
Copie a pasta `projeto` para dentro de:
- **Windows:** `C:\xampp\htdocs\`
- **Linux/Mac:** `/opt/lampp/htdocs/`

Resultado: `C:\xampp\htdocs\projeto\`

### Passo 3 — Criar o banco de dados
1. Acesse o phpMyAdmin: http://localhost/phpmyadmin
2. Clique em **"Novo"** (ou "New") para criar um banco
3. Ou clique em **"SQL"** na barra superior e cole o conteúdo do arquivo `banco.sql`
4. Clique em **"Executar"**

### Passo 4 — Configurar a conexão (se necessário)
Abra `conexao/conexao.php` e ajuste:
```php
define('DB_HOST', 'localhost');   // host do MySQL
define('DB_USER', 'root');        // usuário (padrão XAMPP: root)
define('DB_PASS', '');            // senha (padrão XAMPP: vazio)
define('DB_NAME', 'sistema_academico');
```

### Passo 5 — Acessar o sistema
Abra o navegador e acesse:
```
http://localhost/projeto/
```

---

## 👤 Usuários de Teste (já incluídos no banco.sql)

| Tipo           | E-mail               | Senha    |
|----------------|----------------------|----------|
| Administrador  | admin@escola.com     | password |
| Professor      | carlos@escola.com    | password |
| Aluno          | ana@escola.com       | password |

> **Nota:** As senhas estão armazenadas com hash bcrypt (seguro).

---

## 🔐 Permissões por Tipo de Usuário

### Administrador
- ✅ Criar, editar e excluir avisos
- ✅ Ver todas as atividades
- ✅ Gerenciar usuários (alterar tipo, excluir)
- ✅ Acesso completo ao sistema

### Professor
- ✅ Criar, editar e excluir suas próprias atividades
- ✅ Ver todos os avisos
- ✅ Visualizar atividades

### Aluno
- ✅ Ver avisos
- ✅ Ver atividades e prazos
- ❌ Não pode criar/editar nada

---

## 🗄️ Estrutura do Banco de Dados

### Tabela `usuarios`
| Campo | Tipo | Descrição |
|-------|------|-----------|
| id | INT AUTO_INCREMENT PK | Identificador |
| nome | VARCHAR(100) | Nome completo |
| email | VARCHAR(150) UNIQUE | E-mail de login |
| senha | VARCHAR(255) | Hash bcrypt |
| tipo | ENUM | aluno/professor/administrador |
| criado_em | TIMESTAMP | Data de cadastro |

### Tabela `avisos`
| Campo | Tipo | Descrição |
|-------|------|-----------|
| id | INT AUTO_INCREMENT PK | Identificador |
| titulo | VARCHAR(200) | Título do aviso |
| descricao | TEXT | Conteúdo do aviso |
| data_publicacao | DATE | Data de publicação |
| autor_id | INT FK | Referência ao usuário |

### Tabela `atividades`
| Campo | Tipo | Descrição |
|-------|------|-----------|
| id | INT AUTO_INCREMENT PK | Identificador |
| titulo | VARCHAR(200) | Título da atividade |
| descricao | TEXT | Instruções |
| prazo | DATE | Data limite de entrega |
| turma | VARCHAR(100) | Turma destinatária |
| professor_id | INT FK | Referência ao professor |

---

## 🛠️ Tecnologias Utilizadas

- **PHP 7.4+** (Procedural com MySQLi)
- **MySQL 5.7+** (via phpMyAdmin)
- **HTML5** semântico
- **CSS3** com variáveis customizadas
- **Google Fonts** (Playfair Display + DM Sans)

---

## 🔒 Segurança Implementada

- Senhas com `password_hash()` / `password_verify()`
- Prepared statements em todas as queries (prevenção de SQL Injection)
- `htmlspecialchars()` em todas as saídas (prevenção de XSS)
- Controle de sessão com `session_regenerate_id()`
- Verificação de permissão em todas as páginas
