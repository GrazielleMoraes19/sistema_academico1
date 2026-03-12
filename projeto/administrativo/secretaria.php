<?php
session_start();
if (!isset($_SESSION['usuario_id'])) { header('Location: ../login.php'); exit; }
$base_path = '../';
require_once '../conexao/conexao.php';
$conn = getConexao(); $tipo = $_SESSION['tipo']; $uid = $_SESSION['usuario_id'];
$msg=''; $erro='';

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $usuario_id = $tipo==='aluno' ? $uid : (int)$_POST['usuario_id'];
    $tipo_doc   = $_POST['tipo_doc'];
    $desc       = trim($_POST['descricao'] ?? '');
    $arq        = trim($_POST['arquivo_nome'] ?? '');
    $status_d   = in_array($tipo,['administrador']) ? $_POST['status_doc'] : 'pendente';
    $st=$conn->prepare("INSERT INTO documentos (usuario_id,tipo,descricao,arquivo_nome,status) VALUES(?,?,?,?,?)");
    $st->bind_param('issss',$usuario_id,$tipo_doc,$desc,$arq,$status_d);
    $st->execute(); $st->close(); $msg='Documento registrado!';
}
if (isset($_GET['aprovar']) && $tipo==='administrador') { $conn->query("UPDATE documentos SET status='aprovado' WHERE id=".(int)$_GET['aprovar']); $msg='Documento aprovado.'; }
if (isset($_GET['rejeitar']) && $tipo==='administrador') { $conn->query("UPDATE documentos SET status='rejeitado' WHERE id=".(int)$_GET['rejeitar']); $msg='Documento rejeitado.'; }
if (isset($_GET['excluir']) && $tipo==='administrador') { $conn->query("DELETE FROM documentos WHERE id=".(int)$_GET['excluir']); $msg='Documento removido.'; }

$usuarios = $conn->query("SELECT id,nome,tipo FROM usuarios ORDER BY tipo,nome")->fetch_all(MYSQLI_ASSOC);
$filtro_user = $tipo==='aluno' ? $uid : (int)($_GET['usuario'] ?? 0);
$where_d = $tipo==='aluno' ? "WHERE d.usuario_id=$uid" : ($filtro_user ? "WHERE d.usuario_id=$filtro_user" : "WHERE 1=1");
$docs = $conn->query("SELECT d.*,u.nome AS usuario FROM documentos d JOIN usuarios u ON d.usuario_id=u.id $where_d ORDER BY d.criado_em DESC")->fetch_all(MYSQLI_ASSOC);

$tipos_doc=['historico'=>'Histórico Escolar','declaracao'=>'Declaração','atestado'=>'Atestado','rg'=>'RG','cpf'=>'CPF','comprovante'=>'Comprovante de Residência','outro'=>'Outro'];
$cores_doc=['aprovado'=>'prazo-ok','pendente'=>'prazo-alerta','rejeitado'=>'prazo-vencido'];
$titulo_pagina='Secretaria Digital'; $pagina_atual='secretaria'; include '../includes/header.php';
?>
<div class="wrapper"><?php include '../includes/menu.php'; ?>
<div class="main-content">
  <div class="topbar"><span class="topbar-titulo">🗂 Secretaria Digital</span></div>
  <div class="content-area">
    <?php if ($msg): ?><div class="alerta alerta-sucesso">✓ <?= $msg ?></div><?php endif; ?>

    <div style="display:grid;grid-template-columns:320px 1fr;gap:1.5rem;align-items:start">

    <!-- Formulário -->
    <div class="card">
      <div class="card-header"><span class="card-titulo">+ Solicitar Documento</span></div>
      <div class="card-body">
        <form method="POST">
          <?php if ($tipo !== 'aluno'): ?>
          <div class="form-group"><label class="form-label">Aluno / Usuário</label>
            <select name="usuario_id" class="form-control">
              <?php foreach($usuarios as $u): ?><option value="<?=$u['id']?>"><?= htmlspecialchars($u['nome'].' ('.ucfirst($u['tipo']).')') ?></option><?php endforeach; ?>
            </select></div>
          <?php endif; ?>
          <div class="form-group"><label class="form-label">Tipo de Documento</label>
            <select name="tipo_doc" class="form-control">
              <?php foreach($tipos_doc as $k=>$v): ?><option value="<?=$k?>"><?=$v?></option><?php endforeach; ?>
            </select></div>
          <div class="form-group"><label class="form-label">Descrição</label>
            <input type="text" name="descricao" class="form-control" placeholder="Ex: Declaração de matrícula 2025"></div>
          <div class="form-group"><label class="form-label">Nome do Arquivo</label>
            <input type="text" name="arquivo_nome" class="form-control" placeholder="Ex: declaracao_joao.pdf"></div>
          <?php if ($tipo==='administrador'): ?>
          <div class="form-group"><label class="form-label">Status</label>
            <select name="status_doc" class="form-control"><option value="pendente">Pendente</option><option value="aprovado">Aprovado</option><option value="rejeitado">Rejeitado</option></select></div>
          <?php endif; ?>
          <button type="submit" class="btn btn-sucesso btn-bloco">+ Registrar</button>
        </form>
      </div>
    </div>

    <!-- Listagem -->
    <div>
      <?php if ($tipo !== 'aluno'): ?>
      <div style="display:flex;align-items:center;gap:1rem;margin-bottom:1rem;flex-wrap:wrap">
        <span style="font-family:var(--font-titulo);font-size:1.1rem;color:var(--cor-primaria)">Documentos</span>
        <form method="GET" style="display:flex;gap:.5rem;margin-left:auto">
          <select name="usuario" class="form-control" style="width:auto">
            <option value="">Todos os usuários</option>
            <?php foreach($usuarios as $u): ?><option value="<?=$u['id']?>" <?=$filtro_user==$u['id']?'selected':''?>><?= htmlspecialchars($u['nome']) ?></option><?php endforeach; ?>
          </select>
          <button type="submit" class="btn btn-primario btn-sm">Filtrar</button>
        </form>
      </div>
      <?php endif; ?>

      <div class="card"><div class="tabela-wrapper"><table>
        <thead><tr><th>#</th><th>Usuário</th><th>Tipo</th><th>Descrição</th><th>Arquivo</th><th>Status</th><th>Data</th><?php if($tipo==='administrador'): ?><th>Ações</th><?php endif; ?></tr></thead>
        <tbody>
        <?php if (empty($docs)): ?><tr><td colspan="8" style="text-align:center;padding:2rem;color:var(--cor-texto-suave)">Nenhum documento registrado.</td></tr>
        <?php else: foreach ($docs as $d): ?>
        <tr>
          <td style="color:var(--cor-texto-suave)"><?= $d['id'] ?></td>
          <td><strong><?= htmlspecialchars($d['usuario']) ?></strong></td>
          <td><?= $tipos_doc[$d['tipo']] ?? $d['tipo'] ?></td>
          <td><?= htmlspecialchars($d['descricao'] ?? '—') ?></td>
          <td style="font-size:.78rem;color:var(--cor-texto-suave)"><?= htmlspecialchars($d['arquivo_nome'] ?? '—') ?></td>
          <td><span class="prazo-badge <?= $cores_doc[$d['status']] ?>"><?= ucfirst($d['status']) ?></span></td>
          <td style="font-size:.78rem"><?= date('d/m/Y',strtotime($d['criado_em'])) ?></td>
          <?php if($tipo==='administrador'): ?>
          <td style="white-space:nowrap">
            <?php if($d['status']==='pendente'): ?>
            <a href="secretaria.php?aprovar=<?=$d['id']?>" class="btn btn-sucesso btn-sm">✓ Aprovar</a>
            <a href="secretaria.php?rejeitar=<?=$d['id']?>" class="btn btn-aviso btn-sm">✗ Rejeitar</a>
            <?php endif; ?>
            <a href="secretaria.php?excluir=<?=$d['id']?>" class="btn btn-perigo btn-sm" onclick="return confirm('Excluir?')">✕</a>
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
