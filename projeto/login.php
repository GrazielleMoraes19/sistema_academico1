<?php
session_start();

if (isset($_SESSION['usuario_id'])) {
    header('Location: dashboard.php');
    exit;
}

require_once 'conexao/conexao.php';

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if (empty($email) || empty($senha)) {
        $erro = 'Por favor, preencha e-mail e senha.';
    } else {
        $conn = getConexao();
        $stmt = $conn->prepare("SELECT id, nome, email, senha, tipo FROM usuarios WHERE email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $resultado = $stmt->get_result();
        $usuario = $resultado->fetch_assoc();

        if ($usuario && password_verify($senha, $usuario['senha'])) {
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['nome']       = $usuario['nome'];
            $_SESSION['email']      = $usuario['email'];
            $_SESSION['tipo']       = $usuario['tipo'];
            session_regenerate_id(true);
            header('Location: dashboard.php');
            exit;
        } else {
            $erro = 'E-mail ou senha incorretos.';
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Entrar — Sistema Acadêmico</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<div class="login-page">
    <!-- Lado Esquerdo: Branding -->
    <div class="login-left">
        <div class="login-branding">
            <span class="icone">🎓</span>
            <h1>Sistema de Gestão Acadêmica</h1>
            <p>Plataforma integrada para alunos, professores e administradores acompanharem atividades, avisos e mais.</p>
        </div>
    </div>

    <!-- Lado Direito: Formulário -->
    <div class="login-right">
        <div class="login-box">
            <h2>Bem-vindo de volta</h2>
            <p class="subtitulo">Faça login para acessar o painel</p>

            <?php if ($erro): ?>
                <div class="alerta alerta-erro">⚠ <?= htmlspecialchars($erro) ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label" for="email">E-mail</label>
                    <input type="email" id="email" name="email" class="form-control"
                           placeholder="seu@email.com"
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                           required autofocus>
                </div>

                <div class="form-group">
                    <label class="form-label" for="senha">Senha</label>
                    <input type="password" id="senha" name="senha" class="form-control"
                           placeholder="••••••••" required>
                </div>

                <button type="submit" class="btn btn-primario btn-bloco btn-lg" style="margin-top:0.5rem">
                    Entrar no Sistema
                </button>
            </form>

            <div class="login-link">
                Não tem conta?
                <a href="cadastro.php">Cadastre-se aqui</a>
            </div>
        </div>
    </div>
</div>

</body>
</html>
