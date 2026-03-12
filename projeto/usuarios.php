<?php
session_start();

if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'administrador') {
    header('Location: dashboard.php');
    exit;
}

require_once 'conexao/conexao.php';

$conn = getConexao();
$msg  = '';
$erro = '';
$acao = $_GET['acao'] ?? 'listar';
$id   = (int)($_GET['id'] ?? 0);

// Excluir (não pode excluir a si mesmo)
if ($acao === 'excluir' && $id > 0 && $id !== (int)$_SESSION['usuario_id']) {
    $stmt = $conn->prepare("DELETE FROM usuarios WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();
    $msg  = 'Usuário removido com sucesso.';
    $acao = 'listar';
}

// Alterar tipo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['alterar_tipo'])) {
    $user_id   = (int)$_POST['user_id'];
    $novo_tipo = $_POST['novo_tipo'];
    if (in_array($novo_tipo, ['aluno', 'professor', 'administrador']) && $user_id !== (int)$_SESSION['usuario_id']) {
        $stmt = $conn->prepare("UPDATE usuarios SET tipo = ? WHERE id = ?");
        $stmt->bind_param('si', $novo_tipo, $user_id);
        $stmt->execute();
        $stmt->close();
        $msg = 'Tipo de usuário alterado com sucesso.';
    }
}

// Listar
$usuarios = $conn->query("SELECT id, nome, email, tipo, criado_em FROM usuarios ORDER BY tipo, nome")->fetch_all(MYSQLI_ASSOC);

$titulo_pagina = 'Gerenciar Usuários';
$pagina_atual  = 'usuarios';
include 'includes/header.php';
?>

<div class="wrapper">
    <?php include 'includes/menu.php'; ?>

    <div class="main-content">
        <div class="topbar">
            <span class="topbar-titulo">👥 Gerenciar Usuários</span>
            <div class="topbar-info">
                <span><?= count($usuarios) ?> usuário(s) cadastrado(s)</span>
            </div>
        </div>

        <div class="content-area">

            <?php if ($msg): ?>
                <div class="alerta alerta-sucesso">✓ <?= htmlspecialchars($msg) ?></div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <span class="card-titulo">Todos os Usuários</span>
                    <a href="cadastro.php" class="btn btn-sucesso btn-sm" target="_blank">+ Novo Usuário</a>
                </div>
                <div class="tabela-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Nome</th>
                                <th>E-mail</th>
                                <th>Tipo</th>
                                <th>Cadastrado em</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($usuarios as $u): ?>
                        <tr>
                            <td style="color:var(--cor-texto-suave)"><?= $u['id'] ?></td>
                            <td>
                                <strong><?= htmlspecialchars($u['nome']) ?></strong>
                                <?php if ((int)$u['id'] === (int)$_SESSION['usuario_id']): ?>
                                    <span style="font-size:0.7rem;background:#e8f4fd;color:#1a5a8a;padding:0.1rem 0.4rem;border-radius:4px;margin-left:0.3rem">Você</span>
                                <?php endif; ?>
                            </td>
                            <td style="color:var(--cor-texto-suave)"><?= htmlspecialchars($u['email']) ?></td>
                            <td>
                                <span class="badge-tipo badge-<?= $u['tipo'] ?>"><?= ucfirst($u['tipo']) ?></span>
                            </td>
                            <td style="color:var(--cor-texto-suave)">
                                <?= date('d/m/Y', strtotime($u['criado_em'])) ?>
                            </td>
                            <td>
                                <?php if ((int)$u['id'] !== (int)$_SESSION['usuario_id']): ?>
                                <form method="POST" action="usuarios.php" style="display:inline-flex;gap:0.35rem;align-items:center">
                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                    <select name="novo_tipo" class="form-control" style="padding:0.25rem 0.5rem;font-size:0.78rem;width:auto">
                                        <option value="aluno" <?= $u['tipo']==='aluno'?'selected':'' ?>>Aluno</option>
                                        <option value="professor" <?= $u['tipo']==='professor'?'selected':'' ?>>Professor</option>
                                        <option value="administrador" <?= $u['tipo']==='administrador'?'selected':'' ?>>Admin</option>
                                    </select>
                                    <button type="submit" name="alterar_tipo" class="btn btn-primario btn-sm">Salvar</button>
                                </form>
                                <a href="usuarios.php?acao=excluir&id=<?= $u['id'] ?>"
                                   class="btn btn-perigo btn-sm"
                                   onclick="return confirm('Confirma exclusão do usuário <?= addslashes(htmlspecialchars($u['nome'])) ?>?')">
                                   Excluir
                                </a>
                                <?php else: ?>
                                <span style="font-size:0.78rem;color:var(--cor-texto-suave)">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        <?php include 'includes/footer.php'; ?>
    </div>
</div>

</body>
</html>
