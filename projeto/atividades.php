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

// ===== CRIAR / EDITAR (professor e admin) =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($tipo, ['professor', 'administrador'])) {
    $titulo    = trim($_POST['titulo'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $prazo     = $_POST['prazo'] ?? '';
    $turma     = trim($_POST['turma'] ?? '');
    $post_id   = (int)($_POST['id'] ?? 0);

    if (empty($titulo) || empty($descricao) || empty($prazo) || empty($turma)) {
        $erro = 'Todos os campos são obrigatórios.';
    } else {
        if ($post_id > 0) {
            $stmt = $conn->prepare("UPDATE atividades SET titulo=?, descricao=?, prazo=?, turma=? WHERE id=?");
            $stmt->bind_param('ssssi', $titulo, $descricao, $prazo, $turma, $post_id);
            $stmt->execute();
            $msg = 'Atividade atualizada com sucesso!';
        } else {
            $stmt = $conn->prepare("INSERT INTO atividades (titulo, descricao, prazo, turma, professor_id) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param('ssssi', $titulo, $descricao, $prazo, $turma, $uid);
            $stmt->execute();
            $msg = 'Atividade cadastrada com sucesso!';
        }
        $stmt->close();
        $acao = 'listar';
    }
}

// ===== EXCLUIR (professor dono ou admin) =====
if ($acao === 'excluir' && $id > 0) {
    if ($tipo === 'administrador') {
        $stmt = $conn->prepare("DELETE FROM atividades WHERE id = ?");
        $stmt->bind_param('i', $id);
    } elseif ($tipo === 'professor') {
        $stmt = $conn->prepare("DELETE FROM atividades WHERE id = ? AND professor_id = ?");
        $stmt->bind_param('ii', $id, $uid);
    } else {
        $stmt = null;
    }
    if ($stmt) {
        $stmt->execute();
        $stmt->close();
        $msg = 'Atividade excluída.';
    }
    $acao = 'listar';
}

// ===== DADOS PARA EDIÇÃO =====
$at_edicao = null;
if ($acao === 'editar' && $id > 0) {
    if ($tipo === 'administrador') {
        $stmt = $conn->prepare("SELECT * FROM atividades WHERE id = ?");
        $stmt->bind_param('i', $id);
    } else {
        $stmt = $conn->prepare("SELECT * FROM atividades WHERE id = ? AND professor_id = ?");
        $stmt->bind_param('ii', $id, $uid);
    }
    $stmt->execute();
    $at_edicao = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$at_edicao) { $acao = 'listar'; }
}

// ===== LISTAR =====
if ($tipo === 'aluno' || $tipo === 'administrador') {
    $atividades = $conn->query("SELECT at.*, u.nome AS prof_nome FROM atividades at JOIN usuarios u ON at.professor_id = u.id ORDER BY at.prazo ASC")->fetch_all(MYSQLI_ASSOC);
} else {
    // Professor vê suas próprias
    $stmt = $conn->prepare("SELECT at.*, u.nome AS prof_nome FROM atividades at JOIN usuarios u ON at.professor_id = u.id ORDER BY at.prazo ASC");
    $stmt->execute();
    $atividades = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

function statusPrazo($prazo) {
    $diff = (strtotime($prazo) - time()) / 86400;
    if ($diff < 0)        return ['vencido', 'prazo-vencido', '⛔ Vencido'];
    if ($diff <= 3)       return ['urgente', 'prazo-vencido', '🔴 ' . ceil($diff) . 'd restante(s)'];
    if ($diff <= 7)       return ['alerta', 'prazo-alerta', '🟡 ' . ceil($diff) . 'd restante(s)'];
    return ['ok', 'prazo-ok', '🟢 ' . ceil($diff) . 'd restante(s)'];
}

$titulo_pagina = 'Atividades';
$pagina_atual  = 'atividades';
include 'includes/header.php';
?>

<div class="wrapper">
    <?php include 'includes/menu.php'; ?>

    <div class="main-content">
        <div class="topbar">
            <span class="topbar-titulo">📝 Atividades Acadêmicas</span>
            <div class="topbar-info">
                <?php if (in_array($tipo, ['professor', 'administrador'])): ?>
                    <a href="atividades.php?acao=nova" class="btn btn-sucesso btn-sm">+ Nova Atividade</a>
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

            <?php // ===== FORMULÁRIO ===== ?>
            <?php if (in_array($acao, ['nova', 'editar']) && in_array($tipo, ['professor', 'administrador'])): ?>
            <div class="card" style="margin-bottom:1.5rem">
                <div class="card-header">
                    <span class="card-titulo"><?= $acao === 'editar' ? '✏ Editar Atividade' : '+ Nova Atividade' ?></span>
                </div>
                <div class="card-body">
                    <form method="POST" action="atividades.php">
                        <input type="hidden" name="id" value="<?= $at_edicao['id'] ?? 0 ?>">

                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
                            <div class="form-group" style="grid-column:1/-1">
                                <label class="form-label">Título da Atividade</label>
                                <input type="text" name="titulo" class="form-control"
                                       value="<?= htmlspecialchars($at_edicao['titulo'] ?? '') ?>"
                                       placeholder="Ex: Trabalho de Matemática" required>
                            </div>

                            <div class="form-group" style="grid-column:1/-1">
                                <label class="form-label">Descrição / Instruções</label>
                                <textarea name="descricao" class="form-control" rows="4"
                                          placeholder="Descreva a atividade e as instruções para os alunos..."
                                          required><?= htmlspecialchars($at_edicao['descricao'] ?? '') ?></textarea>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Prazo de Entrega</label>
                                <input type="date" name="prazo" class="form-control"
                                       value="<?= $at_edicao['prazo'] ?? date('Y-m-d', strtotime('+7 days')) ?>"
                                       min="<?= date('Y-m-d') ?>" required>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Turma</label>
                                <input type="text" name="turma" class="form-control"
                                       value="<?= htmlspecialchars($at_edicao['turma'] ?? '') ?>"
                                       placeholder="Ex: 3º Ano A" required>
                            </div>
                        </div>

                        <div style="display:flex;gap:0.75rem;margin-top:0.25rem">
                            <button type="submit" class="btn btn-sucesso">
                                <?= $acao === 'editar' ? '💾 Salvar Alterações' : '✓ Cadastrar Atividade' ?>
                            </button>
                            <a href="atividades.php" class="btn btn-outline">Cancelar</a>
                        </div>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <?php // ===== LISTA ===== ?>
            <div class="secao-titulo">
                <?= $tipo === 'aluno' ? 'Suas Atividades' : 'Todas as Atividades' ?>
                <span style="font-size:0.8rem;font-weight:400;color:var(--cor-texto-suave)"><?= count($atividades) ?> cadastradas</span>
            </div>

            <?php if (empty($atividades)): ?>
                <div class="empty-state">
                    <div class="empty-icone">📋</div>
                    <p>Nenhuma atividade cadastrada ainda.</p>
                    <?php if (in_array($tipo, ['professor', 'administrador'])): ?>
                        <a href="atividades.php?acao=nova" class="btn btn-primario" style="margin-top:1rem">+ Cadastrar Primeira Atividade</a>
                    <?php endif; ?>
                </div>
            <?php else: ?>

                <!-- Filtro por turma (visual) -->
                <div style="margin-bottom:1rem;font-size:0.8rem;color:var(--cor-texto-suave)">
                    Mostrando todas as atividades ordenadas por prazo
                </div>

                <div style="display:flex;flex-direction:column;gap:0.875rem">
                <?php foreach ($atividades as $at):
                    [$status, $cls, $label] = statusPrazo($at['prazo']);
                ?>
                <div class="atividade-item">
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:1rem">
                        <div style="flex:1">
                            <h3 style="font-family:var(--font-titulo);font-size:1rem;color:var(--cor-primaria);margin-bottom:0.4rem">
                                <?= htmlspecialchars($at['titulo']) ?>
                            </h3>
                            <p style="font-size:0.875rem;color:var(--cor-texto-suave);line-height:1.6;margin-bottom:0.6rem">
                                <?= nl2br(htmlspecialchars(mb_strimwidth($at['descricao'], 0, 200, '...'))) ?>
                            </p>
                            <div style="display:flex;align-items:center;gap:0.6rem;flex-wrap:wrap">
                                <span class="turma-badge">🏫 <?= htmlspecialchars($at['turma']) ?></span>
                                <span style="font-size:0.75rem;color:var(--cor-texto-suave)">
                                    Prof. <?= htmlspecialchars($at['prof_nome']) ?>
                                </span>
                                <span style="font-size:0.75rem;color:var(--cor-texto-suave)">
                                    📅 Prazo: <?= date('d/m/Y', strtotime($at['prazo'])) ?>
                                </span>
                            </div>
                        </div>
                        <div style="display:flex;flex-direction:column;align-items:flex-end;gap:0.5rem;flex-shrink:0">
                            <span class="prazo-badge <?= $cls ?>"><?= $label ?></span>
                            <?php if (in_array($tipo, ['professor', 'administrador'])): ?>
                            <div style="display:flex;gap:0.35rem;margin-top:0.25rem">
                                <a href="atividades.php?acao=editar&id=<?= $at['id'] ?>" class="btn btn-aviso btn-sm">Editar</a>
                                <a href="atividades.php?acao=excluir&id=<?= $at['id'] ?>"
                                   class="btn btn-perigo btn-sm"
                                   onclick="return confirm('Confirma exclusão desta atividade?')">Excluir</a>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                </div>

            <?php endif; ?>

        </div><!-- /content-area -->

        <?php include 'includes/footer.php'; ?>
    </div>
</div>

</body>
</html>
