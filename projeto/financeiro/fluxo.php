<?php
session_start();
if (!isset($_SESSION['usuario_id'])) { header('Location: ../login.php'); exit; }
if ($_SESSION['tipo'] !== 'administrador') { header('Location: ../dashboard.php'); exit; }
$base_path = '../';
require_once '../conexao/conexao.php';
$conn = getConexao();
$conn->query("UPDATE financeiro SET status='atrasado' WHERE status='pendente' AND vencimento < CURDATE()");

$ano = (int)($_GET['ano'] ?? date('Y'));
$meses_nomes = ['','Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];

// Dados mensais
$mensal = [];
for ($m=1; $m<=12; $m++) {
    $r = $conn->query("SELECT SUM(CASE WHEN status='pago' THEN valor ELSE 0 END) AS recebido, SUM(CASE WHEN status IN('pendente','atrasado') THEN valor ELSE 0 END) AS pendente, COUNT(*) AS total FROM financeiro WHERE YEAR(vencimento)=$ano AND MONTH(vencimento)=$m")->fetch_assoc();
    $mensal[$m] = $r;
}
$total_rec = array_sum(array_column($mensal,'recebido'));
$total_pen = array_sum(array_column($mensal,'pendente'));
$inadimplencia = $total_rec + $total_pen > 0 ? round($total_pen/($total_rec+$total_pen)*100,1) : 0;

// Top devedores
$devedores = $conn->query("SELECT u.nome, SUM(f.valor) AS divida, COUNT(*) AS qtd FROM financeiro f JOIN usuarios u ON f.aluno_id=u.id WHERE f.status='atrasado' GROUP BY u.id, u.nome ORDER BY divida DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);

$titulo_pagina='Fluxo de Caixa'; $pagina_atual='fluxo'; include '../includes/header.php';
?>
<div class="wrapper"><?php include '../includes/menu.php'; ?>
<div class="main-content">
  <div class="topbar">
    <span class="topbar-titulo">📊 Gestão de Fluxo de Caixa</span>
    <div class="topbar-info">
      <form method="GET" style="display:flex;gap:.5rem;align-items:center">
        <label style="font-size:.8rem">Ano:</label>
        <select name="ano" class="form-control" style="width:auto">
          <?php for($y=date('Y')-2;$y<=date('Y')+1;$y++): ?><option value="<?=$y?>" <?=$ano==$y?'selected':''?>><?=$y?></option><?php endfor; ?>
        </select>
        <button type="submit" class="btn btn-primario btn-sm">Ver</button>
      </form>
    </div>
  </div>
  <div class="content-area">

    <div class="stats-grid" style="margin-bottom:1.5rem">
      <div class="stat-card verde"><div class="stat-numero">R$ <?= number_format($total_rec,0,'.','.') ?></div><div class="stat-label">Total Recebido <?= $ano ?></div><div class="stat-icone">✓</div></div>
      <div class="stat-card vermelho"><div class="stat-numero">R$ <?= number_format($total_pen,0,'.','.') ?></div><div class="stat-label">Total Pendente/Atrasado</div><div class="stat-icone">⚠</div></div>
      <div class="stat-card dourado"><div class="stat-numero"><?= $inadimplencia ?>%</div><div class="stat-label">Taxa de Inadimplência</div><div class="stat-icone">📉</div></div>
      <div class="stat-card azul"><div class="stat-numero">R$ <?= number_format($total_rec+$total_pen,0,'.','.') ?></div><div class="stat-label">Faturamento Total Previsto</div><div class="stat-icone">💰</div></div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 300px;gap:1.5rem;align-items:start">

    <!-- Tabela mensal -->
    <div class="card">
      <div class="card-header"><span class="card-titulo">Fluxo Mensal — <?= $ano ?></span></div>
      <div class="tabela-wrapper"><table>
        <thead><tr><th>Mês</th><th>Recebido</th><th>Pendente</th><th>Total Cobranças</th><th>% Recebimento</th></tr></thead>
        <tbody>
        <?php $max_rec = max(1, max(array_column($mensal,'recebido') ?: [1])); ?>
        <?php foreach ($mensal as $m=>$d): $total_m=($d['recebido']??0)+($d['pendente']??0); $pct=$total_m>0?round(($d['recebido']/$total_m)*100):0; ?>
        <tr>
          <td><strong><?= $meses_nomes[$m] ?></strong></td>
          <td>
            <div style="font-weight:700;color:var(--cor-sucesso)">R$ <?= number_format($d['recebido'],2,',','.') ?></div>
            <div style="height:4px;background:var(--cor-fundo-alt);border-radius:99px;overflow:hidden;margin-top:.25rem;width:100px">
              <div style="height:100%;width:<?= $max_rec>0?min(100,($d['recebido']/$max_rec)*100):0 ?>%;background:var(--cor-sucesso);border-radius:99px"></div>
            </div>
          </td>
          <td style="color:<?= $d['pendente']>0?'var(--cor-erro)':'var(--cor-texto-suave)' ?>">R$ <?= number_format($d['pendente'],2,',','.') ?></td>
          <td style="color:var(--cor-texto-suave)">R$ <?= number_format($total_m,2,',','.') ?> <span style="font-size:.72rem">(<?= $d['total'] ?> lç.)</span></td>
          <td>
            <div style="display:flex;align-items:center;gap:.5rem">
              <div style="flex:1;height:6px;background:var(--cor-fundo-alt);border-radius:99px;overflow:hidden">
                <div style="height:100%;width:<?=$pct?>%;background:<?=$pct>=80?'var(--cor-sucesso)':($pct>=50?'var(--cor-acento)':'var(--cor-erro)')?>; border-radius:99px"></div>
              </div>
              <span style="font-size:.78rem;font-weight:700;color:var(--cor-primaria)"><?=$pct?>%</span>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <tr style="background:var(--cor-fundo-alt);font-weight:700">
          <td>TOTAL</td>
          <td style="color:var(--cor-sucesso)">R$ <?= number_format($total_rec,2,',','.') ?></td>
          <td style="color:var(--cor-erro)">R$ <?= number_format($total_pen,2,',','.') ?></td>
          <td>R$ <?= number_format($total_rec+$total_pen,2,',','.') ?></td>
          <td><strong><?= $inadimplencia > 0 ? 100-$inadimplencia : 0 ?>% recebido</strong></td>
        </tr>
        </tbody>
      </table></div>
    </div>

    <!-- Top devedores -->
    <div>
      <div class="card">
        <div class="card-header"><span class="card-titulo">⚠ Maiores Devedores</span></div>
        <div class="card-body" style="padding:0">
          <?php if (empty($devedores)): ?>
          <div style="padding:2rem;text-align:center;color:var(--cor-texto-suave)">✅ Nenhum devedor em atraso!</div>
          <?php else: foreach ($devedores as $i=>$d): ?>
          <div style="padding:.875rem 1.25rem;border-bottom:1px solid var(--cor-borda)">
            <div style="display:flex;justify-content:space-between;align-items:center">
              <div>
                <div style="font-weight:600;font-size:.875rem"><?= htmlspecialchars($d['nome']) ?></div>
                <div style="font-size:.72rem;color:var(--cor-texto-suave)"><?= $d['qtd'] ?> parcela(s) em atraso</div>
              </div>
              <span style="font-weight:700;font-family:var(--font-titulo);color:var(--cor-erro)">R$ <?= number_format($d['divida'],0,'.','.') ?></span>
            </div>
          </div>
          <?php endforeach; endif; ?>
        </div>
      </div>
    </div>

    </div>
  </div>
  <?php include '../includes/footer.php'; ?>
</div></div>
</body></html>
