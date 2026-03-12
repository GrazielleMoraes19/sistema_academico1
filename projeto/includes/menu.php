<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$base = isset($base_path) ? $base_path : '';
$pagina_atual = isset($pagina_atual) ? $pagina_atual : '';
$tipo = isset($_SESSION['tipo']) ? $_SESSION['tipo'] : '';
$nome = isset($_SESSION['nome']) ? $_SESSION['nome'] : '';
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

        <a href="<?= $base ?>dashboard.php"
           class="nav-item <?= $pagina_atual === 'dashboard' ? 'ativo' : '' ?>">
            <svg class="nav-icone" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            </svg>
            Dashboard
        </a>

        <div class="nav-label">Módulos</div>

        <a href="<?= $base ?>avisos.php"
           class="nav-item <?= $pagina_atual === 'avisos' ? 'ativo' : '' ?>">
            <svg class="nav-icone" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
            </svg>
            Mural de Avisos
        </a>

        <a href="<?= $base ?>atividades.php"
           class="nav-item <?= $pagina_atual === 'atividades' ? 'ativo' : '' ?>">
            <svg class="nav-icone" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
            </svg>
            Atividades
        </a>

        <?php if ($tipo === 'administrador'): ?>
        <div class="nav-label">Administração</div>
        <a href="<?= $base ?>usuarios.php"
           class="nav-item <?= $pagina_atual === 'usuarios' ? 'ativo' : '' ?>">
            <svg class="nav-icone" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
            </svg>
            Usuários
        </a>
        <?php endif; ?>

        <div class="nav-label" style="margin-top:auto">Conta</div>
        <a href="<?= $base ?>logout.php" class="nav-item logout">
            <svg class="nav-icone" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
            </svg>
            Sair do Sistema
        </a>
    </nav>

    <div class="sidebar-footer" style="font-size:0.7rem;color:rgba(255,255,255,0.3)">
        v1.0 &mdash; <?= date('d/m/Y') ?>
    </div>
</div>
