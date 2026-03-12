<?php
session_start();
if (!isset($_SESSION['usuario_id'])) { header('Location: ../login.php'); exit; }
$base_path = '../';
require_once '../conexao/conexao.php';
$conn = getConexao(); $tipo = $_SESSION['tipo']; $uid = $_SESSION['usuario_id'];

$alunos = $conn->query("SELECT id,nome FROM usuarios WHERE tipo='aluno' ORDER BY nome")->fetch_all(MYSQLI_ASSOC);

// Médias por aluno e disciplina
$stats = $conn->query("
    SELECT u.id, u.nome,
        COUNT(DISTINCT n.disciplina_id) AS total_discs,
        ROUND(AVG(n.nota),1) AS media_geral,
        MIN(n.nota) AS menor_nota,
        MAX(n.nota) AS maior_nota,
        SUM(CASE WHEN n.nota < 6 THEN 1 ELSE 0 END) AS abaixo_media
    FROM usuarios u
    LEFT JOIN notas n ON n.aluno_id = u.id
    WHERE u.tipo='aluno'
    GROUP BY u.id, u.nome
    ORDER BY media_geral DESC
")->fetch_all(MYSQLI_ASSOC);

// Aluno selecionado para detalhe
$aluno_sel = $tipo === 'aluno' ? $uid : (int)($_GET['aluno'] ?? 0);
$detalhe = [];
if ($aluno_sel) {
    $detalhe = $conn->query("SELECT d.nome AS disc, ROUND(AVG(n.nota),1) AS media, COUNT(n.id) AS qtd FROM notas n JOIN disciplinas d ON n.disciplina_id=d.id WHERE n.aluno_id=$aluno_sel GROUP BY d.nome ORDER BY media DESC")->fetch_all(MYSQLI_ASSOC);
}

$titulo_pagina='Desempenho'; $pagina_atual='desempenho'; include '../includes/header.php';
?>
<div class="wrapper"><?php include '../includes/menu.php'; ?>
<div class="main-content">
  <div class="topbar"><span class="topbar-titulo">📈 Acompanhamento de Desempenho</span></div>
  <div class="content-area">

    <!-- Cards resumo -->
    <?php if (in_array($tipo,['professor','administrador'])): ?>
    <div class="stats-grid" style="margin-bottom:1.5rem">
      <?php
        $total_alunos = count($stats);
        $em_risco = count(array_filter($stats, fn($s)=>$s['media_geral']!==null && $s['media_geral']<6));
        $media_escola = $stats ? round(array_sum(array_column($stats,'media_geral'))/max(1,count(array_filter($stats,fn($s)=>$s['media_geral']!==null))),1) : 0;
      ?>
      <div class="stat-card azul"><div class="stat-numero"><?= $total_alunos ?></div><div class="stat-label">Total de Alunos</div><div class="stat-icone">👥</div></div>
      <div class="stat-card vermelho"><div class="stat-numero"><?= $em_risco ?></div><div class="stat-label">Em Risco (média &lt; 6)</div><div class="stat-icone">⚠</div></div>
      <div class="stat-card verde"><div class="stat-numero"><?= $media_escola ?: '—' ?></div><div class="stat-label">Média Geral da Escola</div><div class="stat-icone">📊</div></div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;align-items:start">
    <!-- Ranking de alunos -->
    <div class="card">
      <div class="card-header"><span class="card-titulo">Ranking de Desempenho</span></div>
      <div class="card-body" style="padding:0">
        <?php foreach ($stats as $i=>$s): $m=$s['media_geral']; ?>
        <div style="padding:.875rem 1.25rem;border-bottom:1px solid var(--cor-borda);display:flex;align-items:center;gap:.75rem">
          <span style="font-size:.8rem;font-weight:700;color:var(--cor-texto-suave);width:1.5rem"><?= $i+1 ?>º</span>
          <div style="flex:1">
            <div style="font-weight:600;font-size:.875rem;color:var(--cor-primaria)"><?= htmlspecialchars($s['nome']) ?></div>
            <div style="height:5px;background:var(--cor-fundo-alt);border-radius:99px;margin-top:.35rem;overflow:hidden">
              <div style="height:100%;width:<?= $m?min(100,$m*10):0 ?>%;background:<?= $m>=8?'var(--cor-sucesso)':($m>=6?'var(--cor-secundaria)':'var(--cor-erro)') ?>;border-radius:99px;transition:width .6s"></div>
            </div>
          </div>
          <span style="font-size:1rem;font-weight:700;font-family:var(--font-titulo);color:<?= $m>=8?'var(--cor-sucesso)':($m>=6?'var(--cor-primaria)':'var(--cor-erro)') ?>"><?= $m ?? '—' ?></span>
          <a href="desempenho.php?aluno=<?= $s['id'] ?>" class="btn btn-outline btn-sm">Ver</a>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Detalhe aluno -->
    <div>
      <div style="display:flex;align-items:center;gap:1rem;margin-bottom:1rem;flex-wrap:wrap">
        <span style="font-family:var(--font-titulo);font-size:1.1rem;color:var(--cor-primaria)">Detalhe por Disciplina</span>
        <form method="GET" style="display:flex;gap:.5rem;margin-left:auto">
          <select name="aluno" class="form-control" style="width:auto">
            <option value="">Selecione um aluno...</option>
            <?php foreach ($alunos as $a): ?><option value="<?=$a['id']?>" <?=$aluno_sel==$a['id']?'selected':''?>><?= htmlspecialchars($a['nome']) ?></option><?php endforeach; ?>
          </select>
          <button type="submit" class="btn btn-primario btn-sm">Ver</button>
        </form>
      </div>
      <?php if (!empty($detalhe)): ?>
      <div class="card">
        <div class="card-body" style="padding:0">
        <?php foreach ($detalhe as $d): ?>
        <div style="padding:.875rem 1.25rem;border-bottom:1px solid var(--cor-borda)">
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.4rem">
            <span style="font-weight:600;font-size:.875rem"><?= htmlspecialchars($d['disc']) ?></span>
            <span style="font-weight:700;font-family:var(--font-titulo);color:<?= $d['media']>=6?'var(--cor-sucesso)':'var(--cor-erro)' ?>"><?= $d['media'] ?></span>
          </div>
          <div style="height:7px;background:var(--cor-fundo-alt);border-radius:99px;overflow:hidden">
            <div style="height:100%;width:<?= min(100,$d['media']*10) ?>%;background:<?= $d['media']>=8?'var(--cor-sucesso)':($d['media']>=6?'var(--cor-secundaria)':'var(--cor-erro)') ?>;border-radius:99px"></div>
          </div>
          <div style="font-size:.7rem;color:var(--cor-texto-suave);margin-top:.2rem"><?= $d['qtd'] ?> avaliação(ões) lançada(s)</div>
        </div>
        <?php endforeach; ?>
        </div>
      </div>
      <?php else: ?><div class="empty-state"><div class="empty-icone">📈</div><p>Selecione um aluno para ver o detalhamento.</p></div><?php endif; ?>
    </div>
    </div>
    <?php else: ?>
    <!-- Visão do aluno -->
    <?php if (!empty($detalhe)): ?>
    <div class="boas-vindas" style="margin-bottom:1.5rem">
      <h2>Seu Desempenho</h2>
      <p>Acompanhe sua média em cada disciplina abaixo.</p>
    </div>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1rem">
    <?php foreach ($detalhe as $d): ?>
    <div class="card">
      <div class="card-body">
        <div style="font-size:.78rem;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:var(--cor-texto-suave);margin-bottom:.5rem"><?= htmlspecialchars($d['disc']) ?></div>
        <div style="font-family:var(--font-titulo);font-size:2.5rem;font-weight:700;color:<?= $d['media']>=6?'var(--cor-sucesso)':'var(--cor-erro)' ?>"><?= $d['media'] ?></div>
        <div style="height:6px;background:var(--cor-fundo-alt);border-radius:99px;overflow:hidden;margin:.5rem 0">
          <div style="height:100%;width:<?= min(100,$d['media']*10) ?>%;background:<?= $d['media']>=6?'var(--cor-sucesso)':'var(--cor-erro)' ?>;border-radius:99px"></div>
        </div>
        <span class="prazo-badge <?= $d['media']>=6?'prazo-ok':'prazo-vencido' ?>"><?= $d['media']>=6?'✓ Aprovado':'✗ Atenção' ?></span>
      </div>
    </div>
    <?php endforeach; ?>
    </div>
    <?php else: ?><div class="empty-state"><div class="empty-icone">📈</div><p>Nenhuma nota lançada ainda.</p></div><?php endif; ?>
    <?php endif; ?>
  </div>
  <?php include '../includes/footer.php'; ?>
</div></div>
</body></html>
