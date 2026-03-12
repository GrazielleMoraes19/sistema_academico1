<?php
session_start();

if (isset($_SESSION['usuario_id'])) {
    header('Location: dashboard.php');
    exit;
}

require_once 'conexao/conexao.php';

$erro    = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome  = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $senha2 = $_POST['senha2'] ?? '';
    $tipo  = $_POST['tipo'] ?? 'aluno';

    // Validações
    if (empty($nome) || empty($email) || empty($senha)) {
        $erro = 'Todos os campos são obrigatórios.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = 'E-mail inválido.';
    } elseif (strlen($senha) < 6) {
        $erro = 'A senha deve ter pelo menos 6 caracteres.';
    } elseif ($senha !== $senha2) {
        $erro = 'As senhas não coincidem.';
    } elseif (!in_array($tipo, ['aluno', 'professor', 'administrador'])) {
        $erro = 'Tipo de usuário inválido.';
    } else {
        $conn = getConexao();

        // Verifica e-mail duplicado
        $check = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
        $check->bind_param('s', $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $erro = 'Este e-mail já está cadastrado.';
        } else {
            $hash = password_hash($senha, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO usuarios (nome, email, senha, tipo) VALUES (?, ?, ?, ?)");
            $stmt->bind_param('ssss', $nome, $email, $hash, $tipo);

            if ($stmt->execute()) {
                $sucesso = 'Cadastro realizado com sucesso! Você já pode fazer login.';
            } else {
                $erro = 'Erro ao cadastrar. Tente novamente.';
            }
            $stmt->close();
        }
        $check->close();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro — Sistema Acadêmico</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<div class="login-page">
    <div class="login-left">
        <div class="login-branding">
            <span class="icone">🎓</span>
            <h1>Crie sua Conta</h1>
            <p>Junte-se à plataforma e tenha acesso a todas as funcionalidades do sistema acadêmico.</p>
        </div>
    </div>

    <div class="login-right">
        <div class="login-box">
            <h2>Novo Cadastro</h2>
            <p class="subtitulo">Preencha os dados abaixo</p>

            <?php if ($erro): ?>
                <div class="alerta alerta-erro">⚠ <?= htmlspecialchars($erro) ?></div>
            <?php endif; ?>

            <?php if ($sucesso): ?>
                <div class="alerta alerta-sucesso">✓ <?= htmlspecialchars($sucesso) ?></div>
                <a href="login.php" class="btn btn-primario btn-bloco" style="margin-bottom:1rem">Ir para o Login</a>
            <?php else: ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label" for="nome">Nome Completo</label>
                    <input type="text" id="nome" name="nome" class="form-control"
                           placeholder="Seu nome completo"
                           value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>"
                           required autofocus>
                </div>

                <div class="form-group">
                    <label class="form-label" for="email">E-mail</label>
                    <input type="email" id="email" name="email" class="form-control"
                           placeholder="seu@email.com"
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                           required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="tipo">Tipo de Usuário</label>
                    <select id="tipo" name="tipo" class="form-control">
                        <option value="aluno" <?= (($_POST['tipo'] ?? '') === 'aluno') ? 'selected' : '' ?>>Aluno</option>
                        <option value="professor" <?= (($_POST['tipo'] ?? '') === 'professor') ? 'selected' : '' ?>>Professor</option>
                        <option value="administrador" <?= (($_POST['tipo'] ?? '') === 'administrador') ? 'selected' : '' ?>>Administrador</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="senha">Senha</label>
                    <input type="password" id="senha" name="senha" class="form-control"
                           placeholder="Mínimo 6 caracteres" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="senha2">Confirmar Senha</label>
                    <input type="password" id="senha2" name="senha2" class="form-control"
                           placeholder="Repita a senha" required>
                </div>

                <button type="submit" class="btn btn-primario btn-bloco btn-lg">
                    Criar Conta
                </button>
            </form>

            <?php endif; ?>

            <div class="login-link">
                Já tem conta?
                <a href="login.php">Fazer login</a>
            </div>
        </div>
    </div>
</div>

</body>
</html>
