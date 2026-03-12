<?php
// Verifica se sessão já está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Profundidade de pasta para caminhos relativos
$base = isset($base_path) ? $base_path : '';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($titulo_pagina) ? htmlspecialchars($titulo_pagina) . ' — ' : '' ?>Sistema Acadêmico</title>
    <link rel="stylesheet" href="<?= $base ?>css/style.css">
</head>
<body>
