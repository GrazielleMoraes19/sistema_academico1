<?php
// ============================================
// CONEXÃO COM O BANCO DE DADOS
// ============================================

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'sistema_academico1');
define('DB_CHARSET', 'utf8mb4');

function getConexao() {
    static $conn = null;

    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

        if ($conn->connect_error) {
            die('<div style="font-family:sans-serif;padding:2rem;color:#c0392b;">
                <h2>Erro de Conexão</h2>
                <p>Não foi possível conectar ao banco de dados.</p>
                <p><small>' . $conn->connect_error . '</small></p>
                <p>Verifique se o XAMPP está rodando e o banco <strong>sistema_academico1</strong> foi criado.</p>
            </div>');
        }

        $conn->set_charset(DB_CHARSET);
    }

    return $conn;
}
