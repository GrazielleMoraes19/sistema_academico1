<?php
session_start();
if (!isset($_SESSION['usuario_id'])) { header('Location: ../login.php'); exit; }
$base_path = '../';
require_once '../conexao/conexao.php';
$conn = getConexao(); $tipo = $_SESSION['tipo']; $uid = $_SESSION['usuario_id'];
$msg=''; $aba = $_GET['aba'] ?? 'recebidas';
$id  = (int)($_GET['id'] ?? 0);

// Enviar mensagem
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $dest  = (int)$_POST['destinatario_id'];
    $assunto = trim($_POST['assunto']);
    $corpo   = trim($_POST['corpo']);
    if ($dest && $assunto && $corpo) {
        $st=$conn->prepare("INSERT INTO mensagens (remetente_id,destinatario_id,assunto,corpo) VALUES(?,?,?,?)");
        $st->bind_param('iiss',$uid,$dest,$assunto,$corpo);
        $st->execute(); $st->close(); $msg='Mensagem enviada!'; $aba='enviadas';
    }
}
// Marcar lida
if ($id && $aba==='ver') {
    $conn->query("UPDATE mensagens SET lida=1 WHERE id=$id AND destinatario_id=$uid");
    $mensagem = $conn->query("SELECT m.*,r.nome AS rem_nome, d.nome AS dest_nome FROM mensagens m JOIN usuarios r ON m.remetente_id=r.id JOIN usuarios d ON m.destinatario_id=d.id WHERE m.id=$id AND (m.destinatario_id=$uid OR m.remetente_id=$uid)")->fetch_assoc();
}
// Excluir
if (isset($_GET['excluir'])) {
    $conn->query("DELETE FROM mensagens WHERE id=".(int)$_GET['excluir']." AND (remetente_id=$uid OR destinatario_id=$uid)");
    $msg='Mensagem excluída.'; $aba='recebidas';
}

$usuarios = $conn->query("SELECT id,nome,tipo FROM usuarios WHERE id != $uid ORDER BY tipo,nome")->fetch_all(MYSQLI_ASSOC);
$recebidas = $conn->query("SELECT m.*,u.nome AS rem_nome FROM mensagens m JOIN usuarios u ON m.remetente_id=u.id WHERE m.destinatario_id=$uid ORDER BY m.criado_em DESC")->fetch_all(MYSQLI_ASSOC);
$enviadas  = $conn->query("SELECT m.*,u.nome AS dest_nome FROM mensagens m JOIN usuarios u ON m.destinatario_id=u.id WHERE m.remetente_id=$uid ORDER BY m.criado_em DESC")->fetch_all(MYSQLI_ASSOC);
$nao_lidas = count(array_filter($recebidas,fn($m)=>!$m['lida']));

$titulo_pagina='Mensagens'; $pagina_atual='mensagens'; include '../includes/header.php';
?>
<style>
.msg-item { padding:.875rem 1.25rem; border-bottom:1px solid var(--cor-borda); cursor:pointer; transition:background .15s; display:flex; align-items:flex-start; gap:.75rem; }
.msg-item:hover { background:var(--cor-fundo); }
.msg-item.nao-lida { background:var(--cor-acento-claro); border-left:3px solid var(--cor-acento); }
.msg-avatar { width:36px; height:36px; border-radius:50%; background:var(--cor-secundaria); color:white; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:.875rem; flex-shrink:0; }
.tab-btn { padding:.5rem 1.25rem; border:none; background:transparent; font-family:var(--font-corpo); font-size:.875rem; cursor:pointer; border-bottom:2px solid transparent; color:var(--cor-texto-suave); transition:all .15s; }
.tab-btn.ativa { color:var(--cor-secundaria); border-bottom-color:var(--cor-secundaria); font-weight:600; }
</style>
<div class="wrapper"><?php include '../includes/menu.php'; ?>
<div class="main-content">
  <div class="topbar">
    <span class="topbar-titulo">💬 Mensagens</span>
    <div class="topbar-info">
      <?php if ($nao_lidas>0): ?><span style="background:var(--cor-acento);color:white;font-size:.72rem;font-weight:700;padding:.2rem .6rem;border-radius:20px"><?= $nao_lidas ?> nova(s)</span><?php endif; ?>
      <a href="mensagens.php?aba=nova" class="btn btn-sucesso btn-sm">✉ Nova Mensagem</a>
    </div>
  </div>
  <div class="content-area">
    <?php if ($msg): ?><div class="alerta alerta-sucesso">✓ <?= htmlspecialchars($msg) ?></div><?php endif; ?>

    <div style="display:grid;grid-template-columns:280px 1fr;gap:1.5rem;align-items:start">

    <!-- Painel lateral -->
    <div>
      <!-- Nova mensagem -->
      <div class="card" style="margin-bottom:1rem">
        <div class="card-header"><span class="card-titulo">✉ Nova Mensagem</span></div>
        <div class="card-body">
          <form method="POST">
            <div class="form-group"><label class="form-label">Para</label>
              <select name="destinatario_id" class="form-control" required>
                <option value="">Selecione...</option>
                <?php foreach($usuarios as $u): ?><option value="<?=$u['id']?>"><?= htmlspecialchars($u['nome'].' ('.ucfirst($u['tipo']).')') ?></option><?php endforeach; ?>
              </select></div>
            <div class="form-group"><label class="form-label">Assunto</label><input type="text" name="assunto" class="form-control" placeholder="Assunto da mensagem" required></div>
            <div class="form-group"><label class="form-label">Mensagem</label><textarea name="corpo" class="form-control" rows="4" placeholder="Digite sua mensagem..." required></textarea></div>
            <button type="submit" class="btn btn-sucesso btn-bloco">Enviar ➤</button>
          </form>
        </div>
      </div>

      <!-- Usuários online (visual) -->
      <div class="card">
        <div class="card-header"><span class="card-titulo" style="font-size:.9rem">Contatos</span></div>
        <div class="card-body" style="padding:0">
          <?php foreach (array_slice($usuarios,0,8) as $u): ?>
          <div style="padding:.6rem 1.25rem;border-bottom:1px solid var(--cor-borda);display:flex;align-items:center;gap:.75rem">
            <div style="width:8px;height:8px;border-radius:50%;background:var(--cor-sucesso);flex-shrink:0"></div>
            <div>
              <div style="font-size:.8rem;font-weight:600"><?= htmlspecialchars($u['nome']) ?></div>
              <div style="font-size:.68rem;color:var(--cor-texto-suave)"><?= ucfirst($u['tipo']) ?></div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <!-- Caixa de mensagens -->
    <div class="card">
      <!-- Abas -->
      <div style="border-bottom:1px solid var(--cor-borda);padding:0 .5rem;display:flex;gap:.25rem">
        <a href="mensagens.php?aba=recebidas" class="tab-btn <?=$aba==='recebidas'?'ativa':''?>">
          📥 Recebidas <?php if($nao_lidas>0): ?><span style="background:var(--cor-acento);color:white;font-size:.65rem;padding:.1rem .4rem;border-radius:20px;margin-left:.25rem"><?=$nao_lidas?></span><?php endif; ?>
        </a>
        <a href="mensagens.php?aba=enviadas" class="tab-btn <?=$aba==='enviadas'?'ativa':''?>">📤 Enviadas</a>
      </div>

      <?php if ($aba==='ver' && isset($mensagem)): ?>
      <div style="padding:1.5rem">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:1.25rem">
          <div>
            <h2 style="font-family:var(--font-titulo);font-size:1.25rem;color:var(--cor-primaria);margin-bottom:.35rem"><?= htmlspecialchars($mensagem['assunto']) ?></h2>
            <div style="font-size:.8rem;color:var(--cor-texto-suave)">
              De: <strong><?= htmlspecialchars($mensagem['rem_nome']) ?></strong> →
              Para: <strong><?= htmlspecialchars($mensagem['dest_nome']) ?></strong> ·
              <?= date('d/m/Y H:i',strtotime($mensagem['criado_em'])) ?>
            </div>
          </div>
          <div style="display:flex;gap:.5rem">
            <a href="mensagens.php?aba=recebidas" class="btn btn-outline btn-sm">← Voltar</a>
            <a href="mensagens.php?excluir=<?=$mensagem['id']?>" class="btn btn-perigo btn-sm" onclick="return confirm('Excluir?')">✕</a>
          </div>
        </div>
        <div style="background:var(--cor-fundo);border-radius:var(--radius);padding:1.25rem;line-height:1.8;font-size:.9rem">
          <?= nl2br(htmlspecialchars($mensagem['corpo'])) ?>
        </div>
        <!-- Responder -->
        <div style="margin-top:1.5rem;padding-top:1.25rem;border-top:1px solid var(--cor-borda)">
          <div style="font-weight:600;font-size:.8rem;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.75rem;color:var(--cor-texto-suave)">Responder</div>
          <form method="POST">
            <input type="hidden" name="destinatario_id" value="<?= $mensagem['remetente_id']==$uid?$mensagem['destinatario_id']:$mensagem['remetente_id'] ?>">
            <input type="hidden" name="assunto" value="Re: <?= htmlspecialchars($mensagem['assunto']) ?>">
            <div class="form-group"><textarea name="corpo" class="form-control" rows="3" placeholder="Sua resposta..."></textarea></div>
            <button type="submit" class="btn btn-sucesso">Responder ➤</button>
          </form>
        </div>
      </div>

      <?php elseif ($aba==='recebidas'): ?>
        <?php if (empty($recebidas)): ?>
          <div class="empty-state"><div class="empty-icone">📭</div><p>Nenhuma mensagem recebida.</p></div>
        <?php else: foreach ($recebidas as $m): ?>
        <a href="mensagens.php?aba=ver&id=<?=$m['id']?>" style="text-decoration:none;display:block">
          <div class="msg-item <?=$m['lida']==0?'nao-lida':''?>">
            <div class="msg-avatar"><?= strtoupper(substr($m['rem_nome'],0,1)) ?></div>
            <div style="flex:1;min-width:0">
              <div style="display:flex;justify-content:space-between;align-items:center">
                <span style="font-weight:<?=$m['lida']==0?'700':'500'?>;font-size:.875rem;color:var(--cor-primaria)"><?= htmlspecialchars($m['rem_nome']) ?></span>
                <span style="font-size:.72rem;color:var(--cor-texto-suave)"><?= date('d/m H:i',strtotime($m['criado_em'])) ?></span>
              </div>
              <div style="font-size:.8rem;font-weight:<?=$m['lida']==0?'600':'400'?>;color:var(--cor-texto)"><?= htmlspecialchars($m['assunto']) ?></div>
              <div style="font-size:.75rem;color:var(--cor-texto-suave);overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars(mb_strimwidth($m['corpo'],0,80,'...')) ?></div>
            </div>
            <?php if(!$m['lida']): ?><div style="width:8px;height:8px;border-radius:50%;background:var(--cor-acento);flex-shrink:0;margin-top:.25rem"></div><?php endif; ?>
          </div>
        </a>
        <?php endforeach; endif; ?>

      <?php elseif ($aba==='enviadas'): ?>
        <?php if (empty($enviadas)): ?>
          <div class="empty-state"><div class="empty-icone">📤</div><p>Nenhuma mensagem enviada.</p></div>
        <?php else: foreach ($enviadas as $m): ?>
        <div class="msg-item">
          <div class="msg-avatar" style="background:var(--cor-texto-suave)"><?= strtoupper(substr($m['dest_nome'],0,1)) ?></div>
          <div style="flex:1;min-width:0">
            <div style="display:flex;justify-content:space-between;align-items:center">
              <span style="font-weight:500;font-size:.875rem;color:var(--cor-primaria)">Para: <?= htmlspecialchars($m['dest_nome']) ?></span>
              <span style="font-size:.72rem;color:var(--cor-texto-suave)"><?= date('d/m H:i',strtotime($m['criado_em'])) ?></span>
            </div>
            <div style="font-size:.8rem;color:var(--cor-texto)"><?= htmlspecialchars($m['assunto']) ?></div>
            <div style="font-size:.75rem;color:var(--cor-texto-suave);overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars(mb_strimwidth($m['corpo'],0,80,'...')) ?></div>
          </div>
          <a href="mensagens.php?excluir=<?=$m['id']?>" class="btn btn-perigo btn-sm" onclick="return confirm('Excluir?')">✕</a>
        </div>
        <?php endforeach; endif; ?>
      <?php endif; ?>
    </div>

    </div>
  </div>
  <?php include '../includes/footer.php'; ?>
</div></div>
</body></html>
