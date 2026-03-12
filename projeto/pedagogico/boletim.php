<?php
session_start();
if (!isset($_SESSION['usuario_id'])) { header('Location: ../login.php'); exit; }
$base_path = '../';
require_once '../conexao/conexao.php';
$conn = getConexao();
$tipo = $_SESSION['tipo'];
$uid  = $_SESSION['usuario_id'];
$msg  = ''; $erro = '';

// POST: lançar nota
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($tipo,['professor','administrador'])) {
    $aluno_id = (int)$_POST['aluno_id'];
    $disc_id  = (int)$_POST['disciplina_id'];
    $bimestre = (int)$_POST['bimestre'];
    $nota     = (float)str_replace(',','.',$_POST['nota']);
    $tipo_n   = $_POST['tipo_nota'];
    $desc     = trim($_POST['descricao'] ?? '');
    $edit_id  = (int)($_POST['edit_id'] ?? 0);
    if ($nota < 0 || $nota > 10) { $erro = 'Nota deve ser entre 0 e 10.'; }
    else {
        if ($edit_id > 0) {
            $st = $conn->prepare("UPDATE notas SET aluno_id=?,disciplina_id=?,bimestre=?,nota=?,tipo=?,descricao=? WHERE id=?");
            $st->bind_param('iiidssi',$aluno_id,$disc_id,$bimestre,$nota,$tipo_n,$desc,$edit_id);
        } else {
            $st = $conn->prepare("INSERT INTO notas (aluno_id,disciplina_id,bimestre,nota,tipo,descricao) VALUES(?,?,?,?,?,?)");
            $st->bind_param('iiidss',$aluno_id,$disc_id,$bimestre,$nota,$tipo_n,$desc);
        }
        $st->execute(); $st->close(); $msg = 'Nota lançada com sucesso!';
    }
}
if (isset($_GET['excluir']) && in_array($tipo,['professor','administrador'])) {
    $conn->query("DELETE FROM notas WHERE id=".(int)$_GET['excluir']); $msg='Nota removida.';
}

// Alunos e disciplinas
$alunos = $conn->query("SELECT id, nome FROM usuarios WHERE tipo='aluno' ORDER BY nome")->fetch_all(MYSQLI_ASSOC);
$disciplinas = $conn->query("SELECT d.id, d.nome, t.nome AS turma FROM disciplinas d JOIN turmas t ON d.turma_id=t.id ORDER BY d.nome")->fetch_all(MYSQLI_ASSOC);

// Boletim: se for aluno, mostra só dele. Se professor/admin, pode selecionar
$aluno_sel = $tipo === 'aluno' ? $uid : (int)($_GET['aluno'] ?? ($alunos[0]['id'] ?? 0));
$boletim = [];
if ($aluno_sel) {
    $rows = $conn->query("SELECT n.*, d.nome AS disciplina FROM notas n JOIN disciplinas d ON n.disciplina_id=d.id WHERE n.aluno_id=$aluno_sel ORDER BY d.nome, n.bimestre, n.criado_em")->fetch_all(MYSQLI_ASSOC);
    foreach ($rows as $r) {
        $boletim[$r['disciplina']][$r['bimestre']][] = $r;
    }
}
$nome_aluno_sel = '';
if ($aluno_sel) {
    $tmp = $conn->query("SELECT nome FROM usuarios WHERE id=$aluno_sel")->fetch_assoc();
    $nome_aluno_sel = $tmp['nome'] ?? '';
}

$titulo_pagina='Boletim e Notas'; $pagina_atual='boletim';
include '../includes/header.php';
?>
<div class="wrapper">
<?php include '../includes/menu.php'; ?>
<div class="main-content">
  <div class="topbar">
    <span class="topbar-titulo">📊 Boletim e Notas</span>
  </div>
  <div class="content-area">
    <?php if ($msg): ?><div class="alerta alerta-sucesso">✓ <?= htmlspecialchars($msg) ?></div><?php endif; ?>
    <?php if ($erro): ?><div class="alerta alerta-erro">⚠ <?= htmlspecialchars($erro) ?></div><?php endif; ?>

    <div style="display:grid;grid-template-columns:<?= in_array($tipo,['professor','administrador'])?'380px 1fr':'1fr' ?>;gap:1.5rem;align-items:start">

    <?php if (in_array($tipo,['professor','administrador'])): ?>
    <!-- Formulário lançar nota -->
    <div class="card">
      <div class="card-header"><span class="card-titulo">+ Lançar Nota</span></div>
      <div class="card-body">
        <form method="POST">
          <input type="hidden" name="edit_id" value="0">
          <div class="form-group">
            <label class="form-label">Aluno</label>
            <select name="aluno_id" class="form-control" required>
              <?php foreach ($alunos as $a): ?><option value="<?= $a['id'] ?>"><?= htmlspecialchars($a['nome']) ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Disciplina</label>
            <select name="disciplina_id" class="form-control" required>
              <?php foreach ($disciplinas as $d): ?><option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['nome'].' ('.$d['turma'].')') ?></option><?php endforeach; ?>
            </select>
          </div>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem">
            <div class="form-group">
              <label class="form-label">Bimestre</label>
              <select name="bimestre" class="form-control">
                <option value="1">1º Bimestre</option><option value="2">2º Bimestre</option>
                <option value="3">3º Bimestre</option><option value="4">4º Bimestre</option>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">Nota (0–10)</label>
              <input type="number" name="nota" class="form-control" min="0" max="10" step="0.1" placeholder="0.0" required>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Tipo</label>
            <select name="tipo_nota" class="form-control">
              <option value="prova">Prova</option><option value="trabalho">Trabalho</option>
              <option value="participacao">Participação</option><option value="recuperacao">Recuperação</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Descrição</label>
            <input type="text" name="descricao" class="form-control" placeholder="Ex: 1ª Prova Bimestral">
          </div>
          <button type="submit" class="btn btn-sucesso btn-bloco">✓ Lançar Nota</button>
        </form>
      </div>
    </div>
    <?php endif; ?>

    <!-- Boletim -->
    <div>
      <?php if (!$tipo === 'aluno'): ?>
      <div style="display:flex;align-items:center;gap:1rem;margin-bottom:1.25rem">
        <div class="secao-titulo" style="margin:0;border:0;padding:0;font-size:1.2rem">Boletim</div>
        <form method="GET" style="display:flex;gap:.5rem;margin-left:auto">
          <select name="aluno" class="form-control" style="width:auto">
            <?php foreach ($alunos as $a): ?>
            <option value="<?= $a['id'] ?>" <?= $aluno_sel==$a['id']?'selected':'' ?>><?= htmlspecialchars($a['nome']) ?></option>
            <?php endforeach; ?>
          </select>
          <button type="submit" class="btn btn-primario btn-sm">Ver Boletim</button>
        </form>
      </div>
      <?php endif; ?>

      <?php if ($aluno_sel && !empty($boletim)): ?>
      <div class="card" style="margin-bottom:1rem">
        <div class="card-header">
          <span class="card-titulo">📋 <?= htmlspecialchars($nome_aluno_sel) ?></span>
          <span style="font-size:.78rem;color:var(--cor-texto-suave)">Ano letivo <?= date('Y') ?></span>
        </div>
        <div class="tabela-wrapper"><table>
          <thead><tr><th>Disciplina</th><th>1º Bim</th><th>2º Bim</th><th>3º Bim</th><th>4º Bim</th><th>Média</th><th>Situação</th></tr></thead>
          <tbody>
          <?php foreach ($boletim as $disc => $bims):
            $medias = [];
            for ($b=1;$b<=4;$b++) {
                $notas_bim = array_column($bims[$b] ?? [], 'nota');
                $medias[$b] = $notas_bim ? round(array_sum($notas_bim)/count($notas_bim),1) : null;
            }
            $vals = array_filter($medias, fn($v)=>$v!==null);
            $media_anual = $vals ? round(array_sum($vals)/count($vals),1) : null;
            $aprovado = $media_anual !== null && $media_anual >= 6.0;
          ?>
          <tr>
            <td><strong><?= htmlspecialchars($disc) ?></strong></td>
            <?php for($b=1;$b<=4;$b++): $m=$medias[$b]; ?>
            <td>
              <?php if ($m !== null): ?>
                <span style="font-weight:700;color:<?= $m>=6?'var(--cor-sucesso)':'var(--cor-erro)' ?>"><?= number_format($m,1) ?></span>
                <?php foreach (($bims[$b]??[]) as $n): ?>
                <div style="font-size:.68rem;color:var(--cor-texto-suave)"><?= ucfirst($n['tipo']) ?>: <?= number_format($n['nota'],1) ?>
                  <?php if (in_array($tipo,['professor','administrador'])): ?>
                  <a href="boletim.php?excluir=<?= $n['id'] ?>&aluno=<?= $aluno_sel ?>" style="color:var(--cor-erro);margin-left:.25rem" onclick="return confirm('Remover?')">✕</a>
                  <?php endif; ?>
                </div>
                <?php endforeach; ?>
              <?php else: ?><span style="color:var(--cor-borda)">—</span><?php endif; ?>
            </td>
            <?php endfor; ?>
            <td><span style="font-size:1rem;font-weight:700;font-family:var(--font-titulo);color:<?= $media_anual!==null?($aprovado?'var(--cor-sucesso)':'var(--cor-erro)'):'var(--cor-borda)' ?>"><?= $media_anual!==null?number_format($media_anual,1):'—' ?></span></td>
            <td><?php if ($media_anual!==null): ?><span class="prazo-badge <?= $aprovado?'prazo-ok':'prazo-vencido' ?>"><?= $aprovado?'✓ Aprovado':'✗ Reprovado' ?></span><?php else: ?>—<?php endif; ?></td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table></div>
      </div>
      <?php elseif ($aluno_sel): ?>
        <div class="empty-state"><div class="empty-icone">📊</div><p>Nenhuma nota lançada ainda para este aluno.</p></div>
      <?php endif; ?>
    </div>
    </div>
  </div>
  <?php include '../includes/footer.php'; ?>
</div></div>
</body></html>
