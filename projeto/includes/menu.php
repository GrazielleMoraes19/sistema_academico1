<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$base = isset($base_path) ? $base_path : '';
$pagina_atual = isset($pagina_atual) ? $pagina_atual : '';
$tipo = isset($_SESSION['tipo']) ? $_SESSION['tipo'] : '';
$nome = isset($_SESSION['nome']) ? $_SESSION['nome'] : '';

// Conta mensagens não lidas
$msgs_nao_lidas = 0;
if (isset($_SESSION['usuario_id'])) {
    require_once (isset($base_path) ? $base_path : '') . 'conexao/conexao.php';
    $conn_m = getConexao();
    $stmt_m = $conn_m->prepare("SELECT COUNT(*) AS total FROM mensagens WHERE destinatario_id=? AND lida=0");
    $stmt_m->bind_param('i', $_SESSION['usuario_id']);
    $stmt_m->execute();
    $msgs_nao_lidas = $stmt_m->get_result()->fetch_assoc()['total'];
    $stmt_m->close();
}
?>
<div class="sidebar">
    <div class="sidebar-logo">
        <h1>Sistema<br>Acadêmico</h1>
        <span>Gestão Educacional</span>
    </div>

    <?php if (isset($_SESSION['usuario_id'])): ?>
    <div class="sidebar-user">
        <div class="user-nome"><?= htmlspecialchars($nome) ?></div>
        <div class="user-tipo"><?= ucfirst($tipo) ?></div>
    </div>
    <?php endif; ?>

    <nav class="sidebar-nav">
        <div class="nav-label">Principal</div>
        <a href="<?= $base ?>dashboard.php" class="nav-item <?= $pagina_atual==='dashboard'?'ativo':'' ?>">
            <svg class="nav-icone" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
            Dashboard
        </a>

        <div class="nav-label">📚 Pedagógico</div>
        <a href="<?= $base ?>pedagogico/diario.php" class="nav-item <?= $pagina_atual==='diario'?'ativo':'' ?>">
            <svg class="nav-icone" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
            Diário de Classe
        </a>
        <a href="<?= $base ?>pedagogico/boletim.php" class="nav-item <?= $pagina_atual==='boletim'?'ativo':'' ?>">
            <svg class="nav-icone" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            Boletim e Notas
        </a>
        <a href="<?= $base ?>pedagogico/horarios.php" class="nav-item <?= $pagina_atual==='horarios'?'ativo':'' ?>">
            <svg class="nav-icone" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            Horários e Turmas
        </a>
        <a href="<?= $base ?>pedagogico/desempenho.php" class="nav-item <?= $pagina_atual==='desempenho'?'ativo':'' ?>">
            <svg class="nav-icone" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
            Desempenho
        </a>

        <div class="nav-label">🏛 Administrativo</div>
        <a href="<?= $base ?>administrativo/matriculas.php" class="nav-item <?= $pagina_atual==='matriculas'?'ativo':'' ?>">
            <svg class="nav-icone" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
            Matrículas
        </a>
        <a href="<?= $base ?>administrativo/secretaria.php" class="nav-item <?= $pagina_atual==='secretaria'?'ativo':'' ?>">
            <svg class="nav-icone" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 19a2 2 0 01-2-2V7a2 2 0 012-2h4l2 2h4a2 2 0 012 2v1M5 19h14a2 2 0 002-2v-5a2 2 0 00-2-2H9a2 2 0 00-2 2v5a2 2 0 01-2 2z"/></svg>
            Secretaria Digital
        </a>

        <div class="nav-label">💰 Financeiro</div>
        <a href="<?= $base ?>financeiro/cobrancas.php" class="nav-item <?= $pagina_atual==='cobrancas'?'ativo':'' ?>">
            <svg class="nav-icone" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            Cobranças
        </a>
        <a href="<?= $base ?>financeiro/fluxo.php" class="nav-item <?= $pagina_atual==='fluxo'?'ativo':'' ?>">
            <svg class="nav-icone" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
            Fluxo de Caixa
        </a>

        <div class="nav-label">💬 Comunicação</div>
        <a href="<?= $base ?>comunicacao/mensagens.php" class="nav-item <?= $pagina_atual==='mensagens'?'ativo':'' ?>">
            <svg class="nav-icone" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
            Mensagens
            <?php if ($msgs_nao_lidas > 0): ?>
                <span style="margin-left:auto;background:var(--cor-acento);color:white;font-size:0.65rem;font-weight:700;padding:0.1rem 0.45rem;border-radius:20px"><?= $msgs_nao_lidas ?></span>
            <?php endif; ?>
        </a>
        <a href="<?= $base ?>comunicacao/ocorrencias.php" class="nav-item <?= $pagina_atual==='ocorrencias'?'ativo':'' ?>">
            <svg class="nav-icone" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            Ocorrências
        </a>
        <a href="<?= $base ?>avisos.php" class="nav-item <?= $pagina_atual==='avisos'?'ativo':'' ?>">
            <svg class="nav-icone" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
            Mural de Avisos
        </a>
        <a href="<?= $base ?>atividades.php" class="nav-item <?= $pagina_atual==='atividades'?'ativo':'' ?>">
            <svg class="nav-icone" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
            Atividades
        </a>

        <?php if ($tipo === 'administrador'): ?>
        <div class="nav-label">⚙ Admin</div>
        <a href="<?= $base ?>usuarios.php" class="nav-item <?= $pagina_atual==='usuarios'?'ativo':'' ?>">
            <svg class="nav-icone" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            Usuários
        </a>
        <?php endif; ?>

        <div class="nav-label">Conta</div>
        <a href="<?= $base ?>logout.php" class="nav-item logout">
            <svg class="nav-icone" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
            Sair
        </a>
    </nav>
    <div class="sidebar-footer" style="font-size:0.7rem;color:rgba(255,255,255,0.3)">v2.0 &mdash; <?= date('d/m/Y') ?></div>
</div>
