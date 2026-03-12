<?php
session_start();
if (!isset($_SESSION['usuario_id'])) { header('Location: ../login.php'); exit; }
$base_path = '../';
require_once '../conexao/conexao.php';
$conn = getConexao(); $tipo = $_SESSION['tipo']; $uid = $_SESSION['usuario_id'];
$msg=''; $erro='';

if ($_SERVER['REQUEST_METHOD']==='POST' && in_array($tipo,['administrador'])) {
    $turma_id=$_POST['turma_id']; $disc_id=$_POST['disciplina_id'];
    $dia=$_POST['dia_semana']; $hi=$_POST['hora_inicio']; $hf=$_POST['hora_fim']; $sala=trim($_POST['sala']??'');
    $st=$conn->prepare("INSERT INTO horarios (turma_id,disciplina_id,dia_semana,hora_inicio,hora_fim,sala) VALUES(?,?,?,?,?,?)");
    $st->bind_param('iiisss',$turma_id,$disc_id,$dia,$hi,$hf,$sala); $st->execute(); $st->close();
    $msg='Horário adicionado!';
}
if (isset($_GET['excluir']) && $tipo==='administrador') { $conn->query("DELETE FROM horarios WHERE id=".(int)$_GET['excluir']); $msg='Horário removido.'; }

$turmas = $conn->query("SELECT * FROM turmas ORDER BY nome")->fetch_all(MYSQLI_ASSOC);
$discs  = $conn->query("SELECT d.*, t.nome AS turma FROM disciplinas d JOIN turmas t ON d.turma_id=t.id ORDER BY d.nome")->fetch_all(MYSQLI_ASSOC);
$turma_sel = (int)($_GET['turma'] ?? ($turmas[0]['id'] ?? 0));
$horarios_raw = $conn->query("SELECT h.*, d.nome AS disciplina, t.nome AS turma FROM horarios h JOIN disciplinas d ON h.disciplina_id=d.id JOIN turmas t ON h.turma_id=t.id WHERE h.turma_id=$turma_sel ORDER BY h.dia_semana, h.hora_inicio")->fetch_all(MYSQLI_ASSOC);

$dias = [1=>'Segunda',2=>'Terça',3=>'Quarta',4=>'Quinta',5=>'Sexta'];
$grade = []; foreach ($horarios_raw as $h) $grade[$h['dia_semana']][] = $h;

$titulo_pagina='Horários e Turmas'; $pagina_atual='horarios'; include '../includes/header.php';
?>
<div class="wrapper"><?php include '../includes/menu.php'; ?>
<div class="main-content">
  <div class="topbar"><span class="topbar-titulo">📅 Horários e Turmas</span></div>
  <div class="content-area">
    <?php if ($msg): ?><div class="alerta alerta-sucesso">✓ <?= $msg ?></div><?php endif; ?>

    <div style="display:grid;grid-template-columns:<?= $tipo==='administrador'?'320px 1fr':'1fr' ?>;gap:1.5rem;align-items:start">
    <?php if ($tipo==='administrador'): ?>
    <div>
      <div class="card" style="margin-bottom:1rem">
        <div class="card-header"><span class="card-titulo">+ Novo Horário</span></div>
        <div class="card-body">
          <form method="POST">
            <div class="form-group"><label class="form-label">Turma</label>
              <select name="turma_id" class="form-control" required><?php foreach($turmas as $t): ?><option value="<?=$t['id']?>"><?= htmlspecialchars($t['nome']) ?></option><?php endforeach; ?></select></div>
            <div class="form-group"><label class="form-label">Disciplina</label>
              <select name="disciplina_id" class="form-control" required><?php foreach($discs as $d): ?><option value="<?=$d['id']?>"><?= htmlspecialchars($d['nome'].' ('.$d['turma'].')') ?></option><?php endforeach; ?></select></div>
            <div class="form-group"><label class="form-label">Dia</label>
              <select name="dia_semana" class="form-control"><?php foreach($dias as $n=>$d): ?><option value="<?=$n?>"><?=$d?></option><?php endforeach; ?></select></div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem">
              <div class="form-group"><label class="form-label">Início</label><input type="time" name="hora_inicio" class="form-control" value="07:00" required></div>
              <div class="form-group"><label class="form-label">Fim</label><input type="time" name="hora_fim" class="form-control" value="08:40" required></div>
            </div>
            <div class="form-group"><label class="form-label">Sala</label><input type="text" name="sala" class="form-control" placeholder="Ex: Sala 101"></div>
            <button type="submit" class="btn btn-sucesso btn-bloco">+ Adicionar</button>
          </form>
        </div>
      </div>

      <!-- Turmas cadastradas -->
      <div class="card">
        <div class="card-header"><span class="card-titulo">Turmas Cadastradas</span></div>
        <div class="card-body" style="padding:0">
          <?php foreach ($turmas as $t): ?>
          <div style="padding:.75rem 1.25rem;border-bottom:1px solid var(--cor-borda);display:flex;justify-content:space-between;align-items:center">
            <div>
              <div style="font-weight:600;font-size:.875rem;color:var(--cor-primaria)"><?= htmlspecialchars($t['nome']) ?></div>
              <div style="font-size:.72rem;color:var(--cor-texto-suave)"><?= htmlspecialchars($t['serie']) ?> · <?= ucfirst($t['turno']) ?> · <?= $t['ano_letivo'] ?></div>
            </div>
            <span class="turma-badge"><?= htmlspecialchars($t['sala'] ?? '—') ?></span>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <!-- Grade horária -->
    <div>
      <div style="display:flex;align-items:center;gap:1rem;margin-bottom:1.25rem;flex-wrap:wrap">
        <div class="secao-titulo" style="margin:0;border:0;padding:0;font-size:1.2rem">Grade de Horários</div>
        <form method="GET" style="display:flex;gap:.5rem;margin-left:auto">
          <select name="turma" class="form-control" style="width:auto">
            <?php foreach($turmas as $t): ?><option value="<?=$t['id']?>" <?= $turma_sel==$t['id']?'selected':'' ?>><?= htmlspecialchars($t['nome']) ?></option><?php endforeach; ?>
          </select>
          <button type="submit" class="btn btn-primario btn-sm">Ver</button>
        </form>
      </div>

      <div class="card">
        <div class="tabela-wrapper"><table>
          <thead><tr><th>Dia</th><th>Horário</th><th>Disciplina</th><th>Sala</th><?php if($tipo==='administrador'): ?><th>Ação</th><?php endif; ?></tr></thead>
          <tbody>
          <?php if (empty($horarios_raw)): ?>
          <tr><td colspan="5" style="text-align:center;padding:2rem;color:var(--cor-texto-suave)">Nenhum horário cadastrado para esta turma.</td></tr>
          <?php else: foreach ($dias as $n=>$d): if (!isset($grade[$n])) continue; ?>
          <?php foreach ($grade[$n] as $i=>$h): ?>
          <tr <?= $i===0?'style="border-top:2px solid var(--cor-borda)"':'' ?>>
            <td><?= $i===0?"<strong>$d</strong>":'' ?></td>
            <td style="white-space:nowrap"><span style="font-weight:600;font-family:var(--font-titulo)"><?= substr($h['hora_inicio'],0,5) ?></span><span style="color:var(--cor-texto-suave)"> – <?= substr($h['hora_fim'],0,5) ?></span></td>
            <td><?= htmlspecialchars($h['disciplina']) ?></td>
            <td><span class="turma-badge"><?= htmlspecialchars($h['sala']??'—') ?></span></td>
            <?php if($tipo==='administrador'): ?><td><a href="horarios.php?excluir=<?=$h['id']?>&turma=<?=$turma_sel?>" class="btn btn-perigo btn-sm" onclick="return confirm('Remover?')">✕</a></td><?php endif; ?>
          </tr>
          <?php endforeach; endforeach; endif; ?>
          </tbody>
        </table></div>
      </div>
    </div>
    </div>
  </div>
  <?php include '../includes/footer.php'; ?>
</div></div>
</body></html>
