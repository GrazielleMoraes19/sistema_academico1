<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

require_once 'conexao/conexao.php';

$conn  = getConexao();
$tipo  = $_SESSION['tipo'];
$uid   = $_SESSION['usuario_id'];
$acao  = $_GET['acao'] ?? 'listar';
$id    = (int)($_GET['id'] ?? 0);
$msg   = '';
$erro  = '';

// ===== AÇÃO: CRIAR / EDITAR =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tipo === 'administrador') {
    $titulo     = trim($_POST['titulo'] ?? '');
    $descricao  = trim($_POST['descricao'] ?? '');
    $data_pub   = $_POST['data_publicacao'] ?? date('Y-m-d');
    $post_id    = (int)($_POST['id'] ?? 0);

    if (empty($titulo) || empty($descricao)) {
        $erro = 'Título e descrição são obrigatórios.';
    } else {
        if ($post_id > 0) {
            // Editar
            $stmt = $conn->prepare("UPDATE avisos SET titulo=?, descricao=?, data_publicacao=? WHERE id=?");
            $stmt->bind_param('sssi', $titulo, $descricao, $data_pub, $post_id);
            $stmt->execute();
            $msg = 'Aviso atualizado com sucesso!';
        } else {
            // Criar
            $stmt = $conn->prepare("INSERT INTO avisos (titulo, descricao, data_publicacao, autor_id) VALUES (?, ?, ?, ?)");
            $stmt->bind_param('sssi', $titulo, $descricao, $data_pub, $uid);
            $stmt->execute();
            $msg = 'Aviso criado com sucesso!';
        }
        $stmt->close();
        $acao = 'listar';
    }
}

// ===== AÇÃO: EXCLUIR =====
if ($acao === 'excluir' && $tipo === 'administrador' && $id > 0) {
    $stmt = $conn->prepare("DELETE FROM avisos WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();
    $msg  = 'Aviso excluído com sucesso.';
    $acao = 'listar';
}

// ===== DADOS PARA EDIÇÃO =====
$aviso_edicao = null;
if ($acao === 'editar' && $tipo === 'administrador' && $id > 0) {
    $stmt = $conn->prepare("SELECT * FROM avisos WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $aviso_edicao = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// ===== DADOS PARA VISUALIZAR =====
$aviso_detalhe = null;
if ($acao === 'ver' && $id > 0) {
    $stmt = $conn->prepare("SELECT a.*, u.nome AS autor_nome FROM avisos a JOIN usuarios u ON a.autor_id = u.id WHERE a.id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $aviso_detalhe = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// ===== LISTAR AVISOS =====
$avisos = $conn->query("SELECT a.*, u.nome AS autor_nome FROM avisos a JOIN usuarios u ON a.autor_id = u.id ORDER BY a.data_publicacao DESC, a.id DESC")->fetch_all(MYSQLI_ASSOC);

$titulo_pagina = 'Mural de Avisos';
$pagina_atual  = 'avisos';
include 'includes/header.php';
?>

<div class="wrapper">
    <?php include 'includes/menu.php'; ?>

    <div class="main-content">
        <div class="topbar">
            <span class="topbar-titulo">📢 Mural de Avisos</span>
            <div class="topbar-info">
                <?php if ($tipo === 'administrador'): ?>
                    <a href="avisos.php?acao=novo" class="btn btn-sucesso btn-sm">+ Novo Aviso</a>
                <?php endif; ?>
            </div>
        </div>

        <div class="content-area">

            <?php if ($msg): ?>
                <div class="alerta alerta-sucesso">✓ <?= htmlspecialchars($msg) ?></div>
            <?php endif; ?>
            <?php if ($erro): ?>
                <div class="alerta alerta-erro">⚠ <?= htmlspecialchars($erro) ?></div>
            <?php endif; ?>

            <?php // ===== FORMULÁRIO NOVO/EDITAR ===== ?>
            <?php if (($acao === 'novo' || $acao === 'editar') && $tipo === 'administrador'): ?>
            <div class="card" style="margin-bottom:1.5rem">
                <div class="card-header">
                    <span class="card-titulo"><?= $acao === 'editar' ? '✏ Editar Aviso' : '+ Novo Aviso' ?></span>
                </div>
                <div class="card-body">
                    <form method="POST" action="avisos.php">
                        <input type="hidden" name="id" value="<?= $aviso_edicao['id'] ?? 0 ?>">

                        <div class="form-group">
                            <label class="form-label">Título</label>
                            <input type="text" name="titulo" class="form-control"
                                   value="<?= htmlspecialchars($aviso_edicao['titulo'] ?? '') ?>"
                                   placeholder="Título do aviso" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Descrição</label>
                            <textarea name="descricao" class="form-control" rows="4"
                                      placeholder="Descreva o aviso em detalhes..."
                                      required><?= htmlspecialchars($aviso_edicao['descricao'] ?? '') ?></textarea>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Data de Publicação</label>
                            <input type="date" name="data_publicacao" class="form-control"
                                   value="<?= $aviso_edicao['data_publicacao'] ?? date('Y-m-d') ?>" required>
                        </div>

                        <div style="display:flex;gap:0.75rem">
                            <button type="submit" class="btn btn-sucesso">
                                <?= $acao === 'editar' ? '💾 Salvar Alterações' : '✓ Publicar Aviso' ?>
                            </button>
                            <a href="avisos.php" class="btn btn-outline">Cancelar</a>
                        </div>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <?php // ===== DETALHE DO AVISO ===== ?>
            <?php if ($acao === 'ver' && $aviso_detalhe): ?>
            <div class="card" style="margin-bottom:1.5rem">
                <div class="card-header">
                    <span class="card-titulo"><?= htmlspecialchars($aviso_detalhe['titulo']) ?></span>
                    <a href="avisos.php" class="btn btn-outline btn-sm">← Voltar</a>
                </div>
                <div class="card-body">
                    <div style="color:var(--cor-texto-suave);font-size:0.8rem;margin-bottom:1rem">
                        📅 Publicado em <?= date('d/m/Y', strtotime($aviso_detalhe['data_publicacao'])) ?>
                        &nbsp;·&nbsp; ✍ <?= htmlspecialchars($aviso_detalhe['autor_nome']) ?>
                    </div>
                    <div style="line-height:1.8;color:var(--cor-texto)">
                        <?= nl2br(htmlspecialchars($aviso_detalhe['descricao'])) ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php // ===== LISTA DE AVISOS ===== ?>
            <div class="secao-titulo">
                Todos os Avisos
                <span style="font-size:0.8rem;font-weight:400;color:var(--cor-texto-suave)"><?= count($avisos) ?> publicações</span>
            </div>

            <?php if (empty($avisos)): ?>
                <div class="empty-state">
                    <div class="empty-icone">📭</div>
                    <p>Nenhum aviso publicado ainda.</p>
                    <?php if ($tipo === 'administrador'): ?>
                        <a href="avisos.php?acao=novo" class="btn btn-primario" style="margin-top:1rem">+ Criar Primeiro Aviso</a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <?php foreach ($avisos as $av): ?>
                <div class="aviso-item">
                    <h3><?= htmlspecialchars($av['titulo']) ?></h3>
                    <p><?= htmlspecialchars($av['descricao']) ?></p>
                    <div class="aviso-meta">
                        <span class="aviso-data">
                            📅 <?= date('d/m/Y', strtotime($av['data_publicacao'])) ?>
                            &nbsp;·&nbsp; ✍ <?= htmlspecialchars($av['autor_nome']) ?>
                        </span>
                        <div class="aviso-acoes">
                            <a href="avisos.php?acao=ver&id=<?= $av['id'] ?>" class="btn btn-outline btn-sm">Ver</a>
                            <?php if ($tipo === 'administrador'): ?>
                                <a href="avisos.php?acao=editar&id=<?= $av['id'] ?>" class="btn btn-aviso btn-sm">Editar</a>
                                <a href="avisos.php?acao=excluir&id=<?= $av['id'] ?>"
                                   class="btn btn-perigo btn-sm"
                                   onclick="return confirm('Confirma exclusão deste aviso?')">Excluir</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>

        </div><!-- /content-area -->

        <?php include 'includes/footer.php'; ?>
    </div>
</div>

</body>
</html>
