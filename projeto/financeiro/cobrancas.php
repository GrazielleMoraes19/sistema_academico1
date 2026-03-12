<?php
session_start();
if (!isset($_SESSION['usuario_id'])) { header('Location: ../login.php'); exit; }
$base_path = '../';
require_once '../conexao/conexao.php';
$conn = getConexao(); $tipo = $_SESSION['tipo']; $uid = $_SESSION['usuario_id'];
$msg='';

// Atualizar status de vencimento automaticamente
$conn->query("UPDATE financeiro SET status='atrasado' WHERE status='pendente' AND vencimento < CURDATE()");

if ($_SERVER['REQUEST_METHOD']==='POST' && in_array($tipo,['administrador'])) {
    $aluno_id=(int)$_POST['aluno_id']; $desc=trim($_POST['descricao']); $valor=(float)str_replace(',','.',$_POST['valor']);
    $venc=$_POST['vencimento']; $tipo_f=$_POST['tipo_f']; $status_f=$_POST['status_f'];
    $st=$conn->prepare("INSERT INTO financeiro (aluno_id,descricao,valor,vencimento,tipo,status) VALUES(?,?,?,?,?,?)");
    $st->bind_param('isdsss',$aluno_id,$desc,$valor,$venc,$tipo_f,$status_f);
    $st->execute(); $st->close(); $msg='Cobrança gerada!';
}
if (isset($_GET['pagar']) && in_array($tipo,['administrador'])) {
    $conn->query("UPDATE financeiro SET status='pago', pago_em=CURDATE() WHERE id=".(int)$_GET['pagar']);
    $msg='Pagamento registrado!';
}
if (isset($_GET['cancelar']) && $tipo==='administrador') { $conn->query("UPDATE financeiro SET status='cancelado' WHERE id=".(int)$_GET['cancelar']); $msg='Cobrança cancelada.'; }

$alunos = $conn->query("SELECT id,nome FROM usuarios WHERE tipo='aluno' ORDER BY nome")->fetch_all(MYSQLI_ASSOC);
$filtro_aluno = $tipo==='aluno' ? $uid : (int)($_GET['aluno']??0);
$where_f = $tipo==='aluno' ? "WHERE f.aluno_id=$uid" : ($filtro_aluno?"WHERE f.aluno_id=$filtro_aluno":"WHERE 1=1");
$cobr = $conn->query("SELECT f.*,u.nome AS aluno FROM financeiro f JOIN usuarios u ON f.aluno_id=u.id $where_f ORDER BY f.vencimento DESC")->fetch_all(MYSQLI_ASSOC);

$total_pago = array_sum(array_column(array_filter($cobr,fn($c)=>$c['status']==='pago'),'valor'));
$total_pend = array_sum(array_column(array_filter($cobr,fn($c)=>in_array($c['status'],['pendente','atrasado'])),'valor'));
$total_atrasado = array_sum(array_column(array_filter($cobr,fn($c)=>$c['status']==='atrasado'),'valor'));

$cores=['pago'=>'prazo-ok','pendente'=>'prazo-alerta','atrasado'=>'prazo-vencido','cancelado'=>'turma-badge'];
$titulo_pagina='Cobranças'; $pagina_atual='cobrancas'; include '../includes/header.php';
?>
<div class="wrapper"><?php include '../includes/menu.php'; ?>
<div class="main-content">
  <div class="topbar"><span class="topbar-titulo">💳 Cobranças e Mensalidades</span></div>
  <div class="content-area">
    <?php if ($msg): ?><div class="alerta alerta-sucesso">✓ <?= $msg ?></div><?php endif; ?>

    <div class="stats-grid" style="margin-bottom:1.5rem">
      <div class="stat-card verde"><div class="stat-numero">R$ <?= number_format($total_pago,0,'.','.') ?></div><div class="stat-label">Total Recebido</div><div class="stat-icone">✓</div></div>
      <div class="stat-card dourado"><div class="stat-numero">R$ <?= number_format($total_pend,0,'.','.') ?></div><div class="stat-label">A Receber</div><div class="stat-icone">⏳</div></div>
      <div class="stat-card vermelho"><div class="stat-numero">R$ <?= number_format($total_atrasado,0,'.','.') ?></div><div class="stat-label">Em Atraso</div><div class="stat-icone">⚠</div></div>
    </div>

    <div style="display:grid;grid-template-columns:<?= $tipo==='administrador'?'300px 1fr':'1fr' ?>;gap:1.5rem;align-items:start">

    <?php if ($tipo==='administrador'): ?>
    <div class="card">
      <div class="card-header"><span class="card-titulo">+ Nova Cobrança</span></div>
      <div class="card-body">
        <form method="POST">
          <div class="form-group"><label class="form-label">Aluno</label>
            <select name="aluno_id" class="form-control"><?php foreach($alunos as $a): ?><option value="<?=$a['id']?>"><?= htmlspecialchars($a['nome']) ?></option><?php endforeach; ?></select></div>
          <div class="form-group"><label class="form-label">Descrição</label><input type="text" name="descricao" class="form-control" placeholder="Ex: Mensalidade Março/2025" required></div>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem">
            <div class="form-group"><label class="form-label">Valor (R$)</label><input type="number" name="valor" class="form-control" step="0.01" placeholder="650.00" required></div>
            <div class="form-group"><label class="form-label">Vencimento</label><input type="date" name="vencimento" class="form-control" value="<?= date('Y-m-d',strtotime('+10 days')) ?>" required></div>
          </div>
          <div class="form-group"><label class="form-label">Tipo</label>
            <select name="tipo_f" class="form-control"><option value="mensalidade">Mensalidade</option><option value="matricula">Matrícula</option><option value="material">Material</option><option value="outro">Outro</option></select></div>
          <div class="form-group"><label class="form-label">Status</label>
            <select name="status_f" class="form-control"><option value="pendente">Pendente</option><option value="pago">Pago</option></select></div>
          <button type="submit" class="btn btn-sucesso btn-bloco">+ Gerar Cobrança</button>
        </form>
      </div>
    </div>
    <?php endif; ?>

    <div>
      <?php if ($tipo !== 'aluno'): ?>
      <div style="display:flex;align-items:center;gap:1rem;margin-bottom:1rem;flex-wrap:wrap">
        <span style="font-family:var(--font-titulo);font-size:1.1rem;color:var(--cor-primaria)">Lançamentos</span>
        <form method="GET" style="display:flex;gap:.5rem;margin-left:auto">
          <select name="aluno" class="form-control" style="width:auto">
            <option value="">Todos os alunos</option>
            <?php foreach($alunos as $a): ?><option value="<?=$a['id']?>" <?=$filtro_aluno==$a['id']?'selected':''?>><?= htmlspecialchars($a['nome']) ?></option><?php endforeach; ?>
          </select>
          <button type="submit" class="btn btn-primario btn-sm">Filtrar</button>
        </form>
      </div>
      <?php endif; ?>

      <div class="card"><div class="tabela-wrapper"><table>
        <thead><tr><?php if($tipo!=='aluno'): ?><th>Aluno</th><?php endif; ?><th>Descrição</th><th>Valor</th><th>Vencimento</th><th>Status</th><th>Pago em</th><?php if($tipo==='administrador'): ?><th>Ações</th><?php endif; ?></tr></thead>
        <tbody>
        <?php if (empty($cobr)): ?><tr><td colspan="7" style="text-align:center;padding:2rem;color:var(--cor-texto-suave)">Nenhuma cobrança.</td></tr>
        <?php else: foreach ($cobr as $c): ?>
        <tr>
          <?php if($tipo!=='aluno'): ?><td><strong><?= htmlspecialchars($c['aluno']) ?></strong></td><?php endif; ?>
          <td><?= htmlspecialchars($c['descricao']) ?><div style="font-size:.72rem;color:var(--cor-texto-suave)"><?= ucfirst($c['tipo']) ?></div></td>
          <td><strong style="font-family:var(--font-titulo)">R$ <?= number_format($c['valor'],2,',','.') ?></strong></td>
          <td><?= date('d/m/Y',strtotime($c['vencimento'])) ?></td>
          <td><span class="prazo-badge <?= $cores[$c['status']] ?>"><?= ucfirst($c['status']) ?></span></td>
          <td style="font-size:.78rem;color:var(--cor-texto-suave)"><?= $c['pago_em']?date('d/m/Y',strtotime($c['pago_em'])):'—' ?></td>
          <?php if($tipo==='administrador'): ?>
          <td style="white-space:nowrap">
            <?php if(in_array($c['status'],['pendente','atrasado'])): ?>
            <a href="cobrancas.php?pagar=<?=$c['id']?><?= $filtro_aluno?"&aluno=$filtro_aluno":'' ?>" class="btn btn-sucesso btn-sm" onclick="return confirm('Registrar pagamento?')">✓ Pago</a>
            <a href="cobrancas.php?cancelar=<?=$c['id']?>" class="btn btn-aviso btn-sm" onclick="return confirm('Cancelar?')">Cancelar</a>
            <?php else: ?><span style="color:var(--cor-texto-suave);font-size:.78rem">—</span><?php endif; ?>
          </td>
          <?php endif; ?>
        </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table></div></div>
    </div>
    </div>
  </div>
  <?php include '../includes/footer.php'; ?>
</div></div>
</body></html>
