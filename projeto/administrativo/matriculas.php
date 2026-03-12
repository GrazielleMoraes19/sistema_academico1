<?php
session_start();
if (!isset($_SESSION['usuario_id'])) { header('Location: ../login.php'); exit; }
$base_path = '../';
require_once '../conexao/conexao.php';
$conn = getConexao(); $tipo = $_SESSION['tipo']; $uid = $_SESSION['usuario_id'];
$msg=''; $erro='';

if ($_SERVER['REQUEST_METHOD']==='POST' && in_array($tipo,['administrador'])) {
    $aluno_id=(int)$_POST['aluno_id']; $turma_id=(int)$_POST['turma_id'];
    $ano=(int)$_POST['ano_letivo']; $data_mat=$_POST['data_matricula']; $status_m=$_POST['status'];
    $edit_id=(int)($_POST['edit_id']??0);
    if ($edit_id>0) {
        $st=$conn->prepare("UPDATE matriculas SET aluno_id=?,turma_id=?,ano_letivo=?,status=?,data_matricula=? WHERE id=?");
        $st->bind_param('iiissi',$aluno_id,$turma_id,$ano,$status_m,$data_mat,$edit_id);
    } else {
        $st=$conn->prepare("INSERT INTO matriculas (aluno_id,turma_id,ano_letivo,status,data_matricula) VALUES(?,?,?,?,?)");
        $st->bind_param('iiiss',$aluno_id,$turma_id,$ano,$status_m,$data_mat);
    }
    $st->execute(); $st->close(); $msg='Matrícula salva com sucesso!';
}
if (isset($_GET['excluir']) && $tipo==='administrador') { $conn->query("DELETE FROM matriculas WHERE id=".(int)$_GET['excluir']); $msg='Matrícula removida.'; }

$alunos  = $conn->query("SELECT id,nome FROM usuarios WHERE tipo='aluno' ORDER BY nome")->fetch_all(MYSQLI_ASSOC);
$turmas  = $conn->query("SELECT id,nome FROM turmas ORDER BY nome")->fetch_all(MYSQLI_ASSOC);
$lista   = $conn->query("SELECT m.*,u.nome AS aluno,t.nome AS turma FROM matriculas m JOIN usuarios u ON m.aluno_id=u.id JOIN turmas t ON m.turma_id=t.id ORDER BY m.ano_letivo DESC,u.nome")->fetch_all(MYSQLI_ASSOC);

$cores_status=['ativa'=>'prazo-ok','trancada'=>'prazo-alerta','cancelada'=>'prazo-vencido','concluida'=>'prazo-ok'];
$titulo_pagina='Matrículas'; $pagina_atual='matriculas'; include '../includes/header.php';
?>
<div class="wrapper"><?php include '../includes/menu.php'; ?>
<div class="main-content">
  <div class="topbar"><span class="topbar-titulo">🎓 Matrículas e Rematrículas</span>
    <?php if ($tipo==='administrador'): ?><div class="topbar-info"><a href="matriculas.php?acao=nova" class="btn btn-sucesso btn-sm">+ Nova Matrícula</a></div><?php endif; ?>
  </div>
  <div class="content-area">
    <?php if ($msg): ?><div class="alerta alerta-sucesso">✓ <?= $msg ?></div><?php endif; ?>
    <?php if ($erro): ?><div class="alerta alerta-erro">⚠ <?= $erro ?></div><?php endif; ?>

    <?php if (isset($_GET['acao']) && $tipo==='administrador'): ?>
    <div class="card" style="margin-bottom:1.5rem">
      <div class="card-header"><span class="card-titulo">Nova Matrícula</span></div>
      <div class="card-body">
        <form method="POST">
          <input type="hidden" name="edit_id" value="0">
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
            <div class="form-group"><label class="form-label">Aluno</label>
              <select name="aluno_id" class="form-control" required><?php foreach($alunos as $a): ?><option value="<?=$a['id']?>"><?= htmlspecialchars($a['nome']) ?></option><?php endforeach; ?></select></div>
            <div class="form-group"><label class="form-label">Turma</label>
              <select name="turma_id" class="form-control" required><?php foreach($turmas as $t): ?><option value="<?=$t['id']?>"><?= htmlspecialchars($t['nome']) ?></option><?php endforeach; ?></select></div>
            <div class="form-group"><label class="form-label">Ano Letivo</label>
              <input type="number" name="ano_letivo" class="form-control" value="<?= date('Y') ?>" min="2020" max="2030" required></div>
            <div class="form-group"><label class="form-label">Data da Matrícula</label>
              <input type="date" name="data_matricula" class="form-control" value="<?= date('Y-m-d') ?>" required></div>
            <div class="form-group"><label class="form-label">Status</label>
              <select name="status" class="form-control"><option value="ativa">Ativa</option><option value="trancada">Trancada</option><option value="cancelada">Cancelada</option><option value="concluida">Concluída</option></select></div>
          </div>
          <div style="display:flex;gap:.75rem">
            <button type="submit" class="btn btn-sucesso">✓ Matricular</button>
            <a href="matriculas.php" class="btn btn-outline">Cancelar</a>
          </div>
        </form>
      </div>
    </div>
    <?php endif; ?>

    <!-- Stats -->
    <div class="stats-grid" style="margin-bottom:1.5rem">
      <?php $ativas=count(array_filter($lista,fn($m)=>$m['status']==='ativa')); $trancadas=count(array_filter($lista,fn($m)=>$m['status']==='trancada')); ?>
      <div class="stat-card verde"><div class="stat-numero"><?= $ativas ?></div><div class="stat-label">Matrículas Ativas</div><div class="stat-icone">✓</div></div>
      <div class="stat-card dourado"><div class="stat-numero"><?= $trancadas ?></div><div class="stat-label">Trancadas</div><div class="stat-icone">⏸</div></div>
      <div class="stat-card azul"><div class="stat-numero"><?= count($lista) ?></div><div class="stat-label">Total de Matrículas</div><div class="stat-icone">📋</div></div>
    </div>

    <div class="card"><div class="tabela-wrapper"><table>
      <thead><tr><th>#</th><th>Aluno</th><th>Turma</th><th>Ano</th><th>Data Matrícula</th><th>Status</th><?php if($tipo==='administrador'): ?><th>Ações</th><?php endif; ?></tr></thead>
      <tbody>
      <?php if (empty($lista)): ?><tr><td colspan="7" style="text-align:center;padding:2rem;color:var(--cor-texto-suave)">Nenhuma matrícula cadastrada.</td></tr>
      <?php else: foreach ($lista as $m): ?>
      <tr>
        <td style="color:var(--cor-texto-suave)"><?= $m['id'] ?></td>
        <td><strong><?= htmlspecialchars($m['aluno']) ?></strong></td>
        <td><span class="turma-badge"><?= htmlspecialchars($m['turma']) ?></span></td>
        <td><?= $m['ano_letivo'] ?></td>
        <td><?= date('d/m/Y',strtotime($m['data_matricula'])) ?></td>
        <td><span class="prazo-badge <?= $cores_status[$m['status']] ?>"><?= ucfirst($m['status']) ?></span></td>
        <?php if($tipo==='administrador'): ?>
        <td>
          <?php if ($m['status']==='ativa'): ?>
            <form method="POST" style="display:inline">
              <input type="hidden" name="edit_id" value="<?= $m['id'] ?>">
              <input type="hidden" name="aluno_id" value="<?= $m['aluno_id'] ?>">
              <input type="hidden" name="turma_id" value="<?= $m['turma_id'] ?>">
              <input type="hidden" name="ano_letivo" value="<?= $m['ano_letivo'] ?>">
              <input type="hidden" name="data_matricula" value="<?= $m['data_matricula'] ?>">
              <select name="status" class="form-control" style="display:inline;width:auto;padding:.2rem .4rem;font-size:.75rem">
                <option value="ativa" <?= $m['status']==='ativa'?'selected':'' ?>>Ativa</option>
                <option value="trancada" <?= $m['status']==='trancada'?'selected':'' ?>>Trancada</option>
                <option value="cancelada" <?= $m['status']==='cancelada'?'selected':'' ?>>Cancelada</option>
                <option value="concluida" <?= $m['status']==='concluida'?'selected':'' ?>>Concluída</option>
              </select>
              <button type="submit" class="btn btn-primario btn-sm">OK</button>
            </form>
          <?php endif; ?>
          <a href="matriculas.php?excluir=<?= $m['id'] ?>" class="btn btn-perigo btn-sm" onclick="return confirm('Excluir matrícula?')">✕</a>
        </td>
        <?php endif; ?>
      </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table></div></div>
  </div>
  <?php include '../includes/footer.php'; ?>
</div></div>
</body></html>
