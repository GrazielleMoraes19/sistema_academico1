<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

require_once 'conexao/conexao.php';

$conn   = getConexao();
$tipo   = $_SESSION['tipo'];
$nome   = $_SESSION['nome'];
$uid    = $_SESSION['usuario_id'];

// Contagem de avisos
$r = $conn->query("SELECT COUNT(*) AS total FROM avisos");
$total_avisos = $r->fetch_assoc()['total'];

// Contagem de atividades
$r2 = $conn->query("SELECT COUNT(*) AS total FROM atividades");
$total_atividades = $r2->fetch_assoc()['total'];

// Contagem de usuários (só admin)
$total_usuarios = 0;
if ($tipo === 'administrador') {
    $r3 = $conn->query("SELECT COUNT(*) AS total FROM usuarios");
    $total_usuarios = $r3->fetch_assoc()['total'];
}

// Atividades próximas do vencimento (7 dias)
$atividades_proximas = [];
$stmt = $conn->prepare("SELECT titulo, prazo, turma FROM atividades WHERE prazo >= CURDATE() AND prazo <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) ORDER BY prazo ASC LIMIT 5");
$stmt->execute();
$atividades_proximas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Avisos recentes
$avisos_recentes = $conn->query("SELECT titulo, data_publicacao FROM avisos ORDER BY data_publicacao DESC LIMIT 4")->fetch_all(MYSQLI_ASSOC);

$titulo_pagina = 'Dashboard';
$pagina_atual  = 'dashboard';
include 'includes/header.php';
?>

<div class="wrapper">
    <?php include 'includes/menu.php'; ?>

    <div class="main-content">
        <!-- Topbar -->
        <div class="topbar">
            <span class="topbar-titulo">Dashboard</span>
            <div class="topbar-info">
                <span><?= date('d/m/Y') ?></span>
                <span class="badge-tipo badge-<?= $tipo ?>"><?= ucfirst($tipo) ?></span>
            </div>
        </div>

        <!-- Conteúdo -->
        <div class="content-area">

            <!-- Boas-vindas -->
            <div class="boas-vindas">
                <h2>Olá, <?= htmlspecialchars(explode(' ', $nome)[0]) ?>! 👋</h2>
                <p>
                    <?php if ($tipo === 'administrador'): ?>
                        Você está acessando como <strong>Administrador</strong>. Gerencie usuários, avisos e acompanhe as atividades.
                    <?php elseif ($tipo === 'professor'): ?>
                        Você está acessando como <strong>Professor</strong>. Cadastre atividades e acompanhe os avisos do sistema.
                    <?php else: ?>
                        Você está acessando como <strong>Aluno</strong>. Confira os avisos e acompanhe os prazos das suas atividades.
                    <?php endif; ?>
                </p>
            </div>

            <!-- Cards de estatísticas -->
            <div class="stats-grid">
                <div class="stat-card azul">
                    <div class="stat-numero"><?= $total_avisos ?></div>
                    <div class="stat-label">Avisos Publicados</div>
                    <div class="stat-icone">📢</div>
                </div>

                <div class="stat-card dourado">
                    <div class="stat-numero"><?= $total_atividades ?></div>
                    <div class="stat-label">Atividades Cadastradas</div>
                    <div class="stat-icone">📝</div>
                </div>

                <div class="stat-card verde">
                    <div class="stat-numero"><?= count($atividades_proximas) ?></div>
                    <div class="stat-label">Vencem em 7 dias</div>
                    <div class="stat-icone">⏰</div>
                </div>

                <?php if ($tipo === 'administrador'): ?>
                <div class="stat-card vermelho">
                    <div class="stat-numero"><?= $total_usuarios ?></div>
                    <div class="stat-label">Usuários Cadastrados</div>
                    <div class="stat-icone">👥</div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Grid de informações -->
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-top:0.5rem">

                <!-- Avisos recentes -->
                <div class="card">
                    <div class="card-header">
                        <span class="card-titulo">📢 Avisos Recentes</span>
                        <a href="avisos.php" class="btn btn-outline btn-sm">Ver todos</a>
                    </div>
                    <div class="card-body" style="padding:0">
                        <?php if (empty($avisos_recentes)): ?>
                            <div class="empty-state"><div class="empty-icone">📭</div><p>Nenhum aviso publicado.</p></div>
                        <?php else: ?>
                            <?php foreach ($avisos_recentes as $av): ?>
                            <div style="padding:0.875rem 1.5rem;border-bottom:1px solid var(--cor-borda);display:flex;justify-content:space-between;align-items:center">
                                <div>
                                    <div style="font-size:0.875rem;font-weight:500;color:var(--cor-primaria)"><?= htmlspecialchars($av['titulo']) ?></div>
                                </div>
                                <div style="font-size:0.75rem;color:var(--cor-texto-suave);white-space:nowrap">
                                    <?= date('d/m', strtotime($av['data_publicacao'])) ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Atividades próximas -->
                <div class="card">
                    <div class="card-header">
                        <span class="card-titulo">⏰ Prazos Próximos</span>
                        <a href="atividades.php" class="btn btn-outline btn-sm">Ver todos</a>
                    </div>
                    <div class="card-body" style="padding:0">
                        <?php if (empty($atividades_proximas)): ?>
                            <div class="empty-state"><div class="empty-icone">✅</div><p>Nenhuma atividade vence em breve.</p></div>
                        <?php else: ?>
                            <?php foreach ($atividades_proximas as $at):
                                $dias = (strtotime($at['prazo']) - time()) / 86400;
                                $cls  = $dias <= 2 ? 'prazo-vencido' : ($dias <= 4 ? 'prazo-alerta' : 'prazo-ok');
                            ?>
                            <div style="padding:0.875rem 1.5rem;border-bottom:1px solid var(--cor-borda)">
                                <div style="display:flex;justify-content:space-between;align-items:center">
                                    <div style="font-size:0.875rem;font-weight:500;color:var(--cor-primaria)"><?= htmlspecialchars($at['titulo']) ?></div>
                                    <span class="prazo-badge <?= $cls ?>"><?= date('d/m', strtotime($at['prazo'])) ?></span>
                                </div>
                                <span class="turma-badge" style="margin-top:0.25rem"><?= htmlspecialchars($at['turma']) ?></span>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

            </div><!-- /grid -->

            <!-- Ações rápidas -->
            <div class="card" style="margin-top:1.5rem">
                <div class="card-header">
                    <span class="card-titulo">🚀 Ações Rápidas</span>
                </div>
                <div class="card-body" style="display:flex;gap:0.75rem;flex-wrap:wrap">
                    <a href="avisos.php" class="btn btn-primario">📢 Ver Avisos</a>
                    <a href="atividades.php" class="btn btn-primario">📝 Ver Atividades</a>
                    <?php if ($tipo === 'administrador'): ?>
                        <a href="avisos.php?acao=novo" class="btn btn-sucesso">+ Novo Aviso</a>
                        <a href="usuarios.php" class="btn btn-aviso">👥 Gerenciar Usuários</a>
                    <?php endif; ?>
                    <?php if ($tipo === 'professor'): ?>
                        <a href="atividades.php?acao=nova" class="btn btn-sucesso">+ Nova Atividade</a>
                    <?php endif; ?>
                    <a href="logout.php" class="btn btn-outline" style="margin-left:auto">Sair</a>
                </div>
            </div>

        </div><!-- /content-area -->

        <?php include 'includes/footer.php'; ?>
    </div><!-- /main-content -->
</div><!-- /wrapper -->

</body>
</html>
