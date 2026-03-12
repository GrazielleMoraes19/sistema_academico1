<?php
session_start();
if (!isset($_SESSION['usuario_id'])) { header('Location: ../login.php'); exit; }
$base_path = '../';
require_once '../conexao/conexao.php';
$conn = getConexao();
$tipo = $_SESSION['tipo'];
$uid  = $_SESSION['usuario_id'];
$msg  = ''; $erro = '';
$acao = $_GET['acao'] ?? 'listar';
$id   = (int)($_GET['id'] ?? 0);

// Busca turmas e disciplinas para o professor
if ($tipo === 'professor') {
    $turmas_prof = $conn->query("SELECT DISTINCT t.id, t.nome FROM turmas t JOIN disciplinas d ON d.turma_id=t.id WHERE d.professor_id=$uid")->fetch_all(MYSQLI_ASSOC);
    $discs_prof  = $conn->query("SELECT d.id, d.nome, t.nome AS turma FROM disciplinas d JOIN turmas t ON d.turma_id=t.id WHERE d.professor_id=$uid")->fetch_all(MYSQLI_ASSOC);
} else {
    $turmas_prof = $conn->query("SELECT id, nome FROM turmas ORDER BY nome")->fetch_all(MYSQLI_ASSOC);
    $discs_prof  = $conn->query("SELECT d.id, d.nome, t.nome AS turma FROM disciplinas d JOIN turmas t ON d.turma_id=t.id ORDER BY d.nome")->fetch_all(MYSQLI_ASSOC);
}

// Salvar aula
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($tipo, ['professor','administrador'])) {
    $disc_id   = (int)$_POST['disciplina_id'];
    $turma_id  = (int)$_POST['turma_id'];
    $data_aula = $_POST['data_aula'];
    $conteudo  = trim($_POST['conteudo']);
    $obs       = trim($_POST['observacoes'] ?? '');
    $edit_id   = (int)($_POST['edit_id'] ?? 0);
    if (empty($conteudo) || empty($data_aula)) { $erro = 'Preencha todos os campos obrigatórios.'; }
    else {
        if ($edit_id > 0) {
            $st = $conn->prepare("UPDATE diario_aulas SET disciplina_id=?,turma_id=?,data_aula=?,conteudo=?,observacoes=? WHERE id=?");
            $st->bind_param('iisssi',$disc_id,$turma_id,$data_aula,$conteudo,$obs,$edit_id);
        } else {
            $st = $conn->prepare("INSERT INTO diario_aulas (disciplina_id,turma_id,professor_id,data_aula,conteudo,observacoes) VALUES(?,?,?,?,?,?)");
            $st->bind_param('iiisss',$disc_id,$turma_id,$uid,$data_aula,$conteudo,$obs);
        }
        $st->execute(); $st->close();
        $msg = 'Aula registrada com sucesso!'; $acao = 'listar';
    }
}
if ($acao === 'excluir' && $id > 0 && in_array($tipo,['professor','administrador'])) {
    $conn->query("DELETE FROM diario_aulas WHERE id=$id"); $msg='Registro excluído.'; $acao='listar';
}
$edit_aula = null;
if ($acao === 'editar' && $id > 0) {
    $edit_aula = $conn->query("SELECT * FROM diario_aulas WHERE id=$id")->fetch_assoc();
}

// Listar
$filtro_turma = (int)($_GET['turma'] ?? 0);
$where = $tipo === 'professor' ? "WHERE da.professor_id=$uid" : "WHERE 1=1";
if ($filtro_turma) $where .= " AND da.turma_id=$filtro_turma";
$aulas = $conn->query("SELECT da.*, d.nome AS disciplina, t.nome AS turma, u.nome AS professor FROM diario_aulas da JOIN disciplinas d ON da.disciplina_id=d.id JOIN turmas t ON da.turma_id=t.id JOIN usuarios u ON da.professor_id=u.id $where ORDER BY da.data_aula DESC LIMIT 50")->fetch_all(MYSQLI_ASSOC);

$titulo_pagina='Diário de Classe'; $pagina_atual='diario';
include '../includes/header.php';
?>
<div class="wrapper">
<?php include '../includes/menu.php'; ?>
<div class="main-content">
  <div class="topbar">
    <span class="topbar-titulo">📖 Diário de Classe</span>
    <div class="topbar-info">
      <?php if (in_array($tipo,['professor','administrador'])): ?>
        <a href="diario.php?acao=novo" class="btn btn-sucesso btn-sm">+ Registrar Aula</a>
      <?php endif; ?>
    </div>
  </div>
  <div class="content-area">
    <?php if ($msg): ?><div class="alerta alerta-sucesso">✓ <?= htmlspecialchars($msg) ?></div><?php endif; ?>
    <?php if ($erro): ?><div class="alerta alerta-erro">⚠ <?= htmlspecialchars($erro) ?></div><?php endif; ?>

    <?php if (in_array($acao,['novo','editar']) && in_array($tipo,['professor','administrador'])): ?>
    <div class="card" style="margin-bottom:1.5rem">
      <div class="card-header"><span class="card-titulo"><?= $acao==='editar'?'✏ Editar Registro':'+ Novo Registro de Aula' ?></span></div>
      <div class="card-body">
        <form method="POST">
          <input type="hidden" name="edit_id" value="<?= $edit_aula['id'] ?? 0 ?>">
          <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:1rem">
            <div class="form-group">
              <label class="form-label">Disciplina</label>
              <select name="disciplina_id" class="form-control" required>
                <option value="">Selecione...</option>
                <?php foreach ($discs_prof as $d): ?>
                <option value="<?= $d['id'] ?>" <?= ($edit_aula['disciplina_id']??0)==$d['id']?'selected':'' ?>><?= htmlspecialchars($d['nome'].' — '.$d['turma']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">Turma</label>
              <select name="turma_id" class="form-control" required>
                <option value="">Selecione...</option>
                <?php foreach ($turmas_prof as $t): ?>
                <option value="<?= $t['id'] ?>" <?= ($edit_aula['turma_id']??0)==$t['id']?'selected':'' ?>><?= htmlspecialchars($t['nome']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">Data da Aula</label>
              <input type="date" name="data_aula" class="form-control" value="<?= $edit_aula['data_aula'] ?? date('Y-m-d') ?>" required>
            </div>
            <div class="form-group" style="grid-column:1/-1">
              <label class="form-label">Conteúdo Ministrado *</label>
              <textarea name="conteudo" class="form-control" rows="3" placeholder="Descreva o conteúdo dado em aula..." required><?= htmlspecialchars($edit_aula['conteudo'] ?? '') ?></textarea>
            </div>
            <div class="form-group" style="grid-column:1/-1">
              <label class="form-label">Observações</label>
              <textarea name="observacoes" class="form-control" rows="2" placeholder="Observações gerais, comportamento, recados..."><?= htmlspecialchars($edit_aula['observacoes'] ?? '') ?></textarea>
            </div>
          </div>
          <div style="display:flex;gap:.75rem">
            <button type="submit" class="btn btn-sucesso">💾 Salvar</button>
            <a href="diario.php" class="btn btn-outline">Cancelar</a>
          </div>
        </form>
      </div>
    </div>
    <?php endif; ?>

    <!-- Filtro -->
    <div style="display:flex;align-items:center;gap:1rem;margin-bottom:1.25rem;flex-wrap:wrap">
      <div class="secao-titulo" style="margin:0;border:0;padding:0;font-size:1.2rem">Registros de Aulas</div>
      <form method="GET" style="display:flex;gap:.5rem;margin-left:auto">
        <select name="turma" class="form-control" style="width:auto">
          <option value="">Todas as turmas</option>
          <?php foreach ($turmas_prof as $t): ?>
          <option value="<?= $t['id'] ?>" <?= $filtro_turma==$t['id']?'selected':'' ?>><?= htmlspecialchars($t['nome']) ?></option>
          <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-primario btn-sm">Filtrar</button>
      </form>
    </div>

    <?php if (empty($aulas)): ?>
      <div class="empty-state"><div class="empty-icone">📖</div><p>Nenhum registro de aula ainda.</p></div>
    <?php else: ?>
    <div class="card"><div class="tabela-wrapper"><table>
      <thead><tr><th>Data</th><th>Disciplina</th><th>Turma</th><th>Conteúdo</th><th>Professor</th><th>Ações</th></tr></thead>
      <tbody>
      <?php foreach ($aulas as $a): ?>
      <tr>
        <td><strong><?= date('d/m/Y', strtotime($a['data_aula'])) ?></strong><div style="font-size:.72rem;color:var(--cor-texto-suave)"><?= ['','Seg','Ter','Qua','Qui','Sex','Sáb','Dom'][date('N',strtotime($a['data_aula']))] ?></div></td>
        <td><?= htmlspecialchars($a['disciplina']) ?></td>
        <td><span class="turma-badge"><?= htmlspecialchars($a['turma']) ?></span></td>
        <td>
          <div class="td-titulo"><?= htmlspecialchars(mb_strimwidth($a['conteudo'],0,80,'...')) ?></div>
          <?php if ($a['observacoes']): ?><div class="td-sub">📝 <?= htmlspecialchars(mb_strimwidth($a['observacoes'],0,60,'...')) ?></div><?php endif; ?>
        </td>
        <td style="font-size:.8rem"><?= htmlspecialchars($a['professor']) ?></td>
        <td>
          <?php if (in_array($tipo,['professor','administrador'])): ?>
          <a href="diario.php?acao=editar&id=<?= $a['id'] ?>" class="btn btn-aviso btn-sm">Editar</a>
          <a href="diario.php?acao=excluir&id=<?= $a['id'] ?>" class="btn btn-perigo btn-sm" onclick="return confirm('Excluir?')">Excluir</a>
          <?php else: ?><span style="color:var(--cor-texto-suave);font-size:.78rem">—</span><?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table></div></div>
    <?php endif; ?>
  </div>
  <?php include '../includes/footer.php'; ?>
</div></div>
</body></html>
