<?php
session_start();
if (!isset($_SESSION['usuario_id'])) { header('Location: ../login.php'); exit; }
$base_path = '../';
require_once '../conexao/conexao.php';
$conn = getConexao(); $tipo = $_SESSION['tipo']; $uid = $_SESSION['usuario_id'];
$msg='';

if ($_SERVER['REQUEST_METHOD']==='POST' && in_array($tipo,['professor','administrador'])) {
    $aluno_id=(int)$_POST['aluno_id']; $titulo=trim($_POST['titulo']); $desc=trim($_POST['descricao']);
    $tipo_oc=$_POST['tipo_oc']; $grav=$_POST['gravidade'];
    $st=$conn->prepare("INSERT INTO ocorrencias (aluno_id,autor_id,titulo,descricao,tipo,gravidade) VALUES(?,?,?,?,?,?)");
    $st->bind_param('iissss',$aluno_id,$uid,$titulo,$desc,$tipo_oc,$grav);
    $st->execute(); $st->close(); $msg='Ocorrência registrada!';
}
if (isset($_GET['excluir']) && in_array($tipo,['administrador'])) { $conn->query("DELETE FROM ocorrencias WHERE id=".(int)$_GET['excluir']); $msg='Ocorrência removida.'; }

$alunos = $conn->query("SELECT id,nome FROM usuarios WHERE tipo='aluno' ORDER BY nome")->fetch_all(MYSQLI_ASSOC);
$where_oc = $tipo==='aluno' ? "WHERE o.aluno_id=$uid" : "WHERE 1=1";
$filtro_a = (int)($_GET['aluno']??0); if ($filtro_a) $where_oc = "WHERE o.aluno_id=$filtro_a";
$ocorr = $conn->query("SELECT o.*,u.nome AS aluno,a.nome AS autor FROM ocorrencias o JOIN usuarios u ON o.aluno_id=u.id JOIN usuarios a ON o.autor_id=a.id $where_oc ORDER BY o.criado_em DESC")->fetch_all(MYSQLI_ASSOC);

$cores_tipo=['disciplinar'=>'prazo-vencido','elogio'=>'prazo-ok','academica'=>'prazo-alerta','saude'=>'prazo-alerta','outro'=>'turma-badge'];
$cores_grav=['leve'=>'prazo-ok','moderada'=>'prazo-alerta','grave'=>'prazo-vencido'];
$titulo_pagina='Ocorrências'; $pagina_atual='ocorrencias'; include '../includes/header.php';
?>
<div class="wrapper"><?php include '../includes/menu.php'; ?>
<div class="main-content">
  <div class="topbar"><span class="topbar-titulo">⚠ Ocorrências e Registros</span></div>
  <div class="content-area">
    <?php if ($msg): ?><div class="alerta alerta-sucesso">✓ <?= $msg ?></div><?php endif; ?>

    <div style="display:grid;grid-template-columns:<?= in_array($tipo,['professor','administrador'])?'320px 1fr':'1fr' ?>;gap:1.5rem;align-items:start">

    <?php if (in_array($tipo,['professor','administrador'])): ?>
    <div class="card">
      <div class="card-header"><span class="card-titulo">+ Nova Ocorrência</span></div>
      <div class="card-body">
        <form method="POST">
          <div class="form-group"><label class="form-label">Aluno</label>
            <select name="aluno_id" class="form-control" required><?php foreach($alunos as $a): ?><option value="<?=$a['id']?>"><?= htmlspecialchars($a['nome']) ?></option><?php endforeach; ?></select></div>
          <div class="form-group"><label class="form-label">Título</label><input type="text" name="titulo" class="form-control" placeholder="Descreva brevemente..." required></div>
          <div class="form-group"><label class="form-label">Descrição Detalhada</label><textarea name="descricao" class="form-control" rows="4" required></textarea></div>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem">
            <div class="form-group"><label class="form-label">Tipo</label>
              <select name="tipo_oc" class="form-control">
                <option value="disciplinar">Disciplinar</option><option value="elogio">Elogio</option>
                <option value="academica">Acadêmica</option><option value="saude">Saúde</option><option value="outro">Outro</option>
              </select></div>
            <div class="form-group"><label class="form-label">Gravidade</label>
              <select name="gravidade" class="form-control">
                <option value="leve">Leve</option><option value="moderada">Moderada</option><option value="grave">Grave</option>
              </select></div>
          </div>
          <button type="submit" class="btn btn-sucesso btn-bloco">Registrar</button>
        </form>
      </div>
    </div>
    <?php endif; ?>

    <div>
      <?php if ($tipo !== 'aluno'): ?>
      <div style="display:flex;align-items:center;gap:1rem;margin-bottom:1rem;flex-wrap:wrap">
        <span style="font-family:var(--font-titulo);font-size:1.1rem;color:var(--cor-primaria)">Ocorrências Registradas</span>
        <form method="GET" style="display:flex;gap:.5rem;margin-left:auto">
          <select name="aluno" class="form-control" style="width:auto">
            <option value="">Todos os alunos</option>
            <?php foreach($alunos as $a): ?><option value="<?=$a['id']?>" <?=$filtro_a==$a['id']?'selected':''?>><?= htmlspecialchars($a['nome']) ?></option><?php endforeach; ?>
          </select>
          <button type="submit" class="btn btn-primario btn-sm">Filtrar</button>
        </form>
      </div>
      <?php endif; ?>

      <?php if (empty($ocorr)): ?>
        <div class="empty-state"><div class="empty-icone">✅</div><p>Nenhuma ocorrência registrada.</p></div>
      <?php else: ?>
      <div style="display:flex;flex-direction:column;gap:.875rem">
      <?php foreach ($ocorr as $oc): ?>
      <div class="aviso-item" style="border-left-color:<?= $oc['tipo']==='elogio'?'var(--cor-sucesso)':($oc['gravidade']==='grave'?'var(--cor-erro)':($oc['gravidade']==='moderada'?'var(--cor-aviso)':'var(--cor-acento)')) ?>">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:1rem">
          <div style="flex:1">
            <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:.35rem;flex-wrap:wrap">
              <h3 style="font-family:var(--font-titulo);font-size:.95rem;color:var(--cor-primaria)"><?= htmlspecialchars($oc['titulo']) ?></h3>
              <span class="prazo-badge <?= $cores_tipo[$oc['tipo']] ?>"><?= ucfirst($oc['tipo']) ?></span>
              <span class="prazo-badge <?= $cores_grav[$oc['gravidade']] ?>"><?= ucfirst($oc['gravidade']) ?></span>
            </div>
            <p style="font-size:.85rem;color:var(--cor-texto-suave);line-height:1.6;margin-bottom:.5rem"><?= nl2br(htmlspecialchars($oc['descricao'])) ?></p>
            <div style="font-size:.75rem;color:var(--cor-texto-suave)">
              👤 Aluno: <strong><?= htmlspecialchars($oc['aluno']) ?></strong> · 
              ✍ Registrado por: <?= htmlspecialchars($oc['autor']) ?> · 
              📅 <?= date('d/m/Y H:i',strtotime($oc['criado_em'])) ?>
            </div>
          </div>
          <?php if ($tipo==='administrador'): ?>
          <a href="ocorrencias.php?excluir=<?=$oc['id']?>" class="btn btn-perigo btn-sm" onclick="return confirm('Excluir ocorrência?')" style="flex-shrink:0">✕</a>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
    </div>
  </div>
  <?php include '../includes/footer.php'; ?>
</div></div>
</body></html>
