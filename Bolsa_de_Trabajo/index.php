<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Incluir la configuración de la base de datos
require_once 'config/database.php';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bolsa de Trabajo</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <style>
        .logo-empresa {
            width: 150px;
            height: 150px;
            border-radius: 15px;
            object-fit: cover;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }

        .logo-empresa:hover {
            transform: scale(1.05);
        }

        .hero {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
        }

        .hero section {
            display: flex;
            flex-direction: column;
            align-items: center;
            max-width: 800px;
            padding: 0 20px;
        }
    </style>
</head>
<body>
    <header>
        <nav class="navbar">
            <div class="logo">Bolsa de Trabajo</div>
            <div class="nav-links">
                <a href="views/auth/login.php">Iniciar Sesión</a>
            </div>
        </nav>
    </header>

    <main class="hero">
        <section>
            <img 
                src="assets/img/icons/logo.jpeg" 
                alt="Logo de Bolsa de Trabajo" 
                class="logo-empresa"
            >
            
            <h1>Bienvenidos a la <span class="text-enphasis">Bolsa de Trabajo</span></h1>
            
            <div class="hero-description">
                <p>Conecta a solicitantes de empleo con empresas de manera eficiente y rápida. Nuestra plataforma te permite buscar y publicar vacantes de forma sencilla. Ya seas un solicitante en busca de nuevas oportunidades o una empresa que busca talento, aquí encontrarás lo que necesitas.</p>
            </div>
            
            <div class="hero-buttons">
                <a class="button" href="views/auth/register_solicitante.php">Registro Solicitantes</a>
                <a class="button-y" href="views/auth/register_empresa.php">Registro Empresa</a>
            </div>
        </section>
    </main>

    <footer>
        <p>&copy; 2024 Bolsa de Trabajo - Todos los derechos reservados.</p>
    </footer>
</body>
</html>