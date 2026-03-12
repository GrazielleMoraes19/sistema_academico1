<?php
session_start();
if (!isset($_SESSION['usuario_id'])) { header('Location: login.php'); exit; }
require_once 'conexao/conexao.php';
$conn = getConexao();
$tipo = $_SESSION['tipo']; $nome = $_SESSION['nome']; $uid = $_SESSION['usuario_id'];

$total_avisos   = $conn->query("SELECT COUNT(*) FROM avisos")->fetch_row()[0];
$total_atividades = $conn->query("SELECT COUNT(*) FROM atividades")->fetch_row()[0];
$msgs_nao_lidas = $conn->query("SELECT COUNT(*) FROM mensagens WHERE destinatario_id=$uid AND lida=0")->fetch_row()[0];
$total_alunos   = $conn->query("SELECT COUNT(*) FROM usuarios WHERE tipo='aluno'")->fetch_row()[0];

$proximas = $conn->query("SELECT titulo,prazo,turma FROM atividades WHERE prazo>=CURDATE() AND prazo<=DATE_ADD(CURDATE(),INTERVAL 7 DAY) ORDER BY prazo ASC LIMIT 4")->fetch_all(MYSQLI_ASSOC);
$avisos_rec = $conn->query("SELECT titulo,data_publicacao FROM avisos ORDER BY data_publicacao DESC LIMIT 3")->fetch_all(MYSQLI_ASSOC);

// Para aluno: notas e financeiro
$notas_aluno = [];
$financeiro_aluno = [];
if ($tipo === 'aluno') {
    $notas_aluno = $conn->query("SELECT d.nome AS disc, ROUND(AVG(n.nota),1) AS media FROM notas n JOIN disciplinas d ON n.disciplina_id=d.id WHERE n.aluno_id=$uid GROUP BY d.nome ORDER BY media ASC LIMIT 4")->fetch_all(MYSQLI_ASSOC);
    $financeiro_aluno = $conn->query("SELECT descricao,valor,vencimento,status FROM financeiro WHERE aluno_id=$uid AND status IN('pendente','atrasado') ORDER BY vencimento ASC LIMIT 3")->fetch_all(MYSQLI_ASSOC);
}
// Para professor: aulas recentes
$aulas_prof = [];
if ($tipo === 'professor') {
    $aulas_prof = $conn->query("SELECT da.data_aula,d.nome AS disciplina,t.nome AS turma FROM diario_aulas da JOIN disciplinas d ON da.disciplina_id=d.id JOIN turmas t ON da.turma_id=t.id WHERE da.professor_id=$uid ORDER BY da.data_aula DESC LIMIT 4")->fetch_all(MYSQLI_ASSOC);
}
// Admin: devedores e ocorrências
$ocorr_recentes = $conn->query("SELECT o.titulo,u.nome AS aluno,o.tipo,o.gravidade FROM ocorrencias o JOIN usuarios u ON o.aluno_id=u.id ORDER BY o.criado_em DESC LIMIT 4")->fetch_all(MYSQLI_ASSOC);

$titulo_pagina='Dashboard'; $pagina_atual='dashboard'; include 'includes/header.php';
?>
<div class="wrapper">
<?php include 'includes/menu.php'; ?>
<div class="main-content">
  <div class="topbar">
    <span class="topbar-titulo">Dashboard</span>
    <div class="topbar-info">
      <span><?= date('d/m/Y') ?></span>
      <span class="badge-tipo badge-<?= $tipo ?>"><?= ucfirst($tipo) ?></span>
    </div>
  </div>
  <div class="content-area">

    <div class="boas-vindas">
      <h2>Olá, <?= htmlspecialchars(explode(' ',$nome)[0]) ?>! 👋</h2>
      <p><?php
        if ($tipo==='administrador') echo 'Painel completo de gestão. Monitore todos os módulos do sistema.';
        elseif ($tipo==='professor') echo 'Bem-vindo ao seu espaço. Gerencie atividades, notas e comunicações.';
        else echo 'Acompanhe suas notas, atividades, mensagens e situação financeira.';
      ?></p>
    </div>

    <!-- Stats principais -->
    <div class="stats-grid" style="margin-bottom:1.5rem">
      <div class="stat-card azul"><div class="stat-numero"><?= $total_avisos ?></div><div class="stat-label">Avisos</div><div class="stat-icone">📢</div></div>
      <div class="stat-card dourado"><div class="stat-numero"><?= $total_atividades ?></div><div class="stat-label">Atividades</div><div class="stat-icone">📝</div></div>
      <div class="stat-card verde"><div class="stat-numero"><?= $msgs_nao_lidas ?></div><div class="stat-label">Mensagens Novas</div><div class="stat-icone">💬</div></div>
      <?php if ($tipo==='administrador'): ?>
      <div class="stat-card vermelho"><div class="stat-numero"><?= $total_alunos ?></div><div class="stat-label">Alunos</div><div class="stat-icone">👥</div></div>
      <?php endif; ?>
    </div>

    <!-- Grid principal -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:1.5rem">

      <!-- Avisos recentes -->
      <div class="card">
        <div class="card-header"><span class="card-titulo">📢 Avisos Recentes</span><a href="avisos.php" class="btn btn-outline btn-sm">Ver todos</a></div>
        <div class="card-body" style="padding:0">
          <?php foreach ($avisos_rec as $av): ?>
          <div style="padding:.75rem 1.25rem;border-bottom:1px solid var(--cor-borda);display:flex;justify-content:space-between">
            <span style="font-size:.875rem;font-weight:500"><?= htmlspecialchars($av['titulo']) ?></span>
            <span style="font-size:.72rem;color:var(--cor-texto-suave)"><?= date('d/m',strtotime($av['data_publicacao'])) ?></span>
          </div>
          <?php endforeach; ?>
          <?php if (empty($avisos_rec)): ?><div style="padding:1.5rem;text-align:center;color:var(--cor-texto-suave);font-size:.85rem">Nenhum aviso.</div><?php endif; ?>
        </div>
      </div>

      <!-- Prazos próximos -->
      <div class="card">
        <div class="card-header"><span class="card-titulo">⏰ Prazos Próximos</span><a href="atividades.php" class="btn btn-outline btn-sm">Ver todos</a></div>
        <div class="card-body" style="padding:0">
          <?php foreach ($proximas as $at): $dias=ceil((strtotime($at['prazo'])-time())/86400); ?>
          <div style="padding:.75rem 1.25rem;border-bottom:1px solid var(--cor-borda);display:flex;justify-content:space-between;align-items:center">
            <div>
              <div style="font-size:.875rem;font-weight:500"><?= htmlspecialchars($at['titulo']) ?></div>
              <span class="turma-badge"><?= htmlspecialchars($at['turma']) ?></span>
            </div>
            <span class="prazo-badge <?= $dias<=2?'prazo-vencido':($dias<=5?'prazo-alerta':'prazo-ok') ?>"><?= $dias ?>d</span>
          </div>
          <?php endforeach; ?>
          <?php if (empty($proximas)): ?><div style="padding:1.5rem;text-align:center;color:var(--cor-texto-suave);font-size:.85rem">✅ Nenhum prazo nos próximos 7 dias.</div><?php endif; ?>
        </div>
      </div>

      <?php if ($tipo==='aluno'): ?>
      <!-- Notas do aluno -->
      <div class="card">
        <div class="card-header"><span class="card-titulo">📊 Suas Notas</span><a href="pedagogico/boletim.php" class="btn btn-outline btn-sm">Boletim</a></div>
        <div class="card-body" style="padding:0">
          <?php if (empty($notas_aluno)): ?><div style="padding:1.5rem;text-align:center;color:var(--cor-texto-suave);font-size:.85rem">Nenhuma nota lançada.</div>
          <?php else: foreach ($notas_aluno as $n): ?>
          <div style="padding:.75rem 1.25rem;border-bottom:1px solid var(--cor-borda)">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.3rem">
              <span style="font-size:.875rem;font-weight:500"><?= htmlspecialchars($n['disc']) ?></span>
              <span style="font-weight:700;font-family:var(--font-titulo);color:<?= $n['media']>=6?'var(--cor-sucesso)':'var(--cor-erro)' ?>"><?= $n['media'] ?></span>
            </div>
            <div style="height:4px;background:var(--cor-fundo-alt);border-radius:99px;overflow:hidden">
              <div style="height:100%;width:<?= min(100,$n['media']*10) ?>%;background:<?= $n['media']>=6?'var(--cor-sucesso)':'var(--cor-erro)' ?>;border-radius:99px"></div>
            </div>
          </div>
          <?php endforeach; endif; ?>
        </div>
      </div>

      <!-- Financeiro do aluno -->
      <div class="card">
        <div class="card-header"><span class="card-titulo">💳 Situação Financeira</span><a href="financeiro/cobrancas.php" class="btn btn-outline btn-sm">Ver tudo</a></div>
        <div class="card-body" style="padding:0">
          <?php if (empty($financeiro_aluno)): ?><div style="padding:1.5rem;text-align:center;color:var(--cor-sucesso);font-size:.85rem">✅ Nenhuma pendência financeira!</div>
          <?php else: foreach ($financeiro_aluno as $f): ?>
          <div style="padding:.75rem 1.25rem;border-bottom:1px solid var(--cor-borda);display:flex;justify-content:space-between;align-items:center">
            <div>
              <div style="font-size:.875rem;font-weight:500"><?= htmlspecialchars($f['descricao']) ?></div>
              <div style="font-size:.72rem;color:var(--cor-texto-suave)">Vence: <?= date('d/m/Y',strtotime($f['vencimento'])) ?></div>
            </div>
            <div style="text-align:right">
              <div style="font-weight:700;font-family:var(--font-titulo)">R$ <?= number_format($f['valor'],2,',','.') ?></div>
              <span class="prazo-badge <?= $f['status']==='atrasado'?'prazo-vencido':'prazo-alerta' ?>"><?= ucfirst($f['status']) ?></span>
            </div>
          </div>
          <?php endforeach; endif; ?>
        </div>
      </div>

      <?php elseif ($tipo==='professor'): ?>
      <!-- Aulas recentes -->
      <div class="card">
        <div class="card-header"><span class="card-titulo">📖 Últimas Aulas</span><a href="pedagogico/diario.php" class="btn btn-outline btn-sm">Diário</a></div>
        <div class="card-body" style="padding:0">
          <?php if (empty($aulas_prof)): ?><div style="padding:1.5rem;text-align:center;color:var(--cor-texto-suave);font-size:.85rem">Nenhuma aula registrada.</div>
          <?php else: foreach ($aulas_prof as $a): ?>
          <div style="padding:.75rem 1.25rem;border-bottom:1px solid var(--cor-borda);display:flex;justify-content:space-between;align-items:center">
            <div>
              <div style="font-size:.875rem;font-weight:500"><?= htmlspecialchars($a['disciplina']) ?></div>
              <span class="turma-badge"><?= htmlspecialchars($a['turma']) ?></span>
            </div>
            <span style="font-size:.75rem;color:var(--cor-texto-suave)"><?= date('d/m',strtotime($a['data_aula'])) ?></span>
          </div>
          <?php endforeach; endif; ?>
        </div>
      </div>

      <?php elseif ($tipo==='administrador'): ?>
      <!-- Ocorrências recentes -->
      <div class="card">
        <div class="card-header"><span class="card-titulo">⚠ Ocorrências Recentes</span><a href="comunicacao/ocorrencias.php" class="btn btn-outline btn-sm">Ver todas</a></div>
        <div class="card-body" style="padding:0">
          <?php if (empty($ocorr_recentes)): ?><div style="padding:1.5rem;text-align:center;color:var(--cor-sucesso);font-size:.85rem">✅ Sem ocorrências recentes.</div>
          <?php else: foreach ($ocorr_recentes as $oc): ?>
          <div style="padding:.75rem 1.25rem;border-bottom:1px solid var(--cor-borda);display:flex;justify-content:space-between;align-items:center">
            <div>
              <div style="font-size:.875rem;font-weight:500"><?= htmlspecialchars($oc['titulo']) ?></div>
              <div style="font-size:.72rem;color:var(--cor-texto-suave)"><?= htmlspecialchars($oc['aluno']) ?></div>
            </div>
            <span class="prazo-badge <?= $oc['gravidade']==='grave'?'prazo-vencido':($oc['gravidade']==='moderada'?'prazo-alerta':'prazo-ok') ?>"><?= ucfirst($oc['tipo']) ?></span>
          </div>
          <?php endforeach; endif; ?>
        </div>
      </div>
      <?php endif; ?>
    </div>

    <!-- Acesso rápido por módulo -->
    <div class="card">
      <div class="card-header"><span class="card-titulo">🚀 Acesso Rápido</span></div>
      <div class="card-body">
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:.75rem">
          <a href="pedagogico/diario.php" class="btn btn-outline" style="flex-direction:column;padding:1rem;height:auto;gap:.3rem;text-align:center"><span style="font-size:1.5rem">📖</span><span style="font-size:.78rem">Diário de Classe</span></a>
          <a href="pedagogico/boletim.php" class="btn btn-outline" style="flex-direction:column;padding:1rem;height:auto;gap:.3rem;text-align:center"><span style="font-size:1.5rem">📊</span><span style="font-size:.78rem">Boletim</span></a>
          <a href="pedagogico/horarios.php" class="btn btn-outline" style="flex-direction:column;padding:1rem;height:auto;gap:.3rem;text-align:center"><span style="font-size:1.5rem">📅</span><span style="font-size:.78rem">Horários</span></a>
          <a href="pedagogico/desempenho.php" class="btn btn-outline" style="flex-direction:column;padding:1rem;height:auto;gap:.3rem;text-align:center"><span style="font-size:1.5rem">📈</span><span style="font-size:.78rem">Desempenho</span></a>
          <a href="administrativo/matriculas.php" class="btn btn-outline" style="flex-direction:column;padding:1rem;height:auto;gap:.3rem;text-align:center"><span style="font-size:1.5rem">🎓</span><span style="font-size:.78rem">Matrículas</span></a>
          <a href="administrativo/secretaria.php" class="btn btn-outline" style="flex-direction:column;padding:1rem;height:auto;gap:.3rem;text-align:center"><span style="font-size:1.5rem">🗂</span><span style="font-size:.78rem">Secretaria</span></a>
          <a href="financeiro/cobrancas.php" class="btn btn-outline" style="flex-direction:column;padding:1rem;height:auto;gap:.3rem;text-align:center"><span style="font-size:1.5rem">💳</span><span style="font-size:.78rem">Cobranças</span></a>
          <a href="financeiro/fluxo.php" class="btn btn-outline" style="flex-direction:column;padding:1rem;height:auto;gap:.3rem;text-align:center"><span style="font-size:1.5rem">📊</span><span style="font-size:.78rem">Fluxo de Caixa</span></a>
          <a href="comunicacao/mensagens.php" class="btn btn-outline" style="flex-direction:column;padding:1rem;height:auto;gap:.3rem;text-align:center;position:relative"><span style="font-size:1.5rem">💬</span><span style="font-size:.78rem">Mensagens</span><?php if($msgs_nao_lidas>0):?><span style="position:absolute;top:.5rem;right:.5rem;background:var(--cor-acento);color:white;font-size:.6rem;font-weight:700;padding:.1rem .3rem;border-radius:20px"><?=$msgs_nao_lidas?></span><?php endif;?></a>
          <a href="comunicacao/ocorrencias.php" class="btn btn-outline" style="flex-direction:column;padding:1rem;height:auto;gap:.3rem;text-align:center"><span style="font-size:1.5rem">⚠</span><span style="font-size:.78rem">Ocorrências</span></a>
          <a href="avisos.php" class="btn btn-outline" style="flex-direction:column;padding:1rem;height:auto;gap:.3rem;text-align:center"><span style="font-size:1.5rem">📢</span><span style="font-size:.78rem">Avisos</span></a>
          <a href="atividades.php" class="btn btn-outline" style="flex-direction:column;padding:1rem;height:auto;gap:.3rem;text-align:center"><span style="font-size:1.5rem">📝</span><span style="font-size:.78rem">Atividades</span></a>
        </div>
      </div>
    </div>

  </div>
  <?php include 'includes/footer.php'; ?>
</div></div>
</body></html>
