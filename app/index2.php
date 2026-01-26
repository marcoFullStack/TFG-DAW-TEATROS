<?php
require_once './config/db.php'; 

try {
    // 1. Obras destacadas con su primera imagen
    $stmtObras = $pdo->query("
        SELECT o.*, 
        (SELECT RutaImagen 
         FROM imagenes_obras 
         WHERE idObra = o.idObra 
         LIMIT 1) AS Imagen 
        FROM obras o 
        ORDER BY RAND() 
        LIMIT 3
    ");
    $obrasDestacadas = $stmtObras->fetchAll();

    // 2. Teatros recientes con su primera imagen
    $stmtTeatros = $pdo->query("
        SELECT t.*, 
        (SELECT RutaImagen 
         FROM imagenes_teatros 
         WHERE idTeatro = t.idTeatro 
         LIMIT 1) AS Imagen 
        FROM teatros t 
        ORDER BY idTeatro DESC 
        LIMIT 4
    ");
    $teatrosRecientes = $stmtTeatros->fetchAll();

    // 3. Próximas funciones
    $sqlFunciones = "
        SELECT h.FechaHora, 
               o.Titulo AS Obra, 
               t.Sala AS Teatro, 
               t.Municipio
        FROM horarios h
        JOIN obras o ON h.idObra = o.idObra
        JOIN teatros t ON h.idTeatro = t.idTeatro
        WHERE h.FechaHora >= NOW()
        ORDER BY h.FechaHora ASC
        LIMIT 5
    ";
    $proximasFunciones = $pdo->query($sqlFunciones)->fetchAll();

    // 4. Ranking de usuarios
    $rankingUsuarios = $pdo->query("
        SELECT Nombre, Puntos, FotoPerfil 
        FROM usuarios 
        ORDER BY Puntos DESC 
        LIMIT 5
    ")->fetchAll();

} catch (PDOException $e) {
    die("Error al cargar la página: " . $e->getMessage());
}

// Imágenes por defecto
$imgDefaultObra = "https://images.unsplash.com/photo-1507676184212-d03ab07a01bf?q=80&w=1000";
$imgDefaultTeatro = "https://images.unsplash.com/photo-1514306191717-452ec28c7814?q=80&w=1000";
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Red Teatral | Gran Escenario</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="./styles/styleIndex.css">
    <link rel="stylesheet" href="style.css">

    <style>
        .card-img-container {
            height: 250px;
            overflow: hidden;
        }
        .card-img-top-custom {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s;
        }
        .card-obra:hover .card-img-top-custom {
            transform: scale(1.1);
        }
    </style>
</head>

<body>

<nav class="navbar navbar-expand-lg navbar-dark sticky-top">
    <div class="container">
        <a class="navbar-brand" href="#">
            <i class="fas fa-masks-theater me-2"></i>RED TEATROS
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-center">
                <li class="nav-item"><a class="nav-link" href="#obras">Obras</a></li>
                <li class="nav-item"><a class="nav-link" href="#teatros">Teatros</a></li>
                <li class="nav-item"><a class="nav-link" href="#horarios">Cartelera</a></li>
                <li class="nav-item"><a class="btn btn-gold ms-lg-3" href="#">Área VIP</a></li>
            </ul>
        </div>
    </div>
</nav>

<div id="heroCarousel" class="carousel slide carousel-fade" data-bs-ride="carousel">
    <div class="carousel-inner">
        <div class="carousel-item active" style="background-image:url('https://images.unsplash.com/photo-1503095394537-f25d16bd7d5b?q=80&w=2000');">
            <div class="carousel-overlay"></div>
            <div class="container h-100 d-flex align-items-center">
                <div class="carousel-caption">
                    <h1 class="display-1">El Gran Teatro</h1>
                    <p class="lead fs-3">Vive la experiencia única del arte en vivo.</p>
                    <a href="#obras" class="btn btn-gold btn-lg mt-3">Ver Cartelera</a>
                </div>
            </div>
        </div>
        <div class="carousel-item" style="background-image:url('https://images.unsplash.com/photo-1514306191717-452ec28c7814?q=80&w=2000');">
            <div class="carousel-overlay"></div>
            <div class="container h-100 d-flex align-items-center">
                <div class="carousel-caption">
                    <h1 class="display-1">Luces y Sombras</h1>
                    <p class="lead fs-3">Los mejores actores de la región en un solo lugar.</p>
                    <a href="#teatros" class="btn btn-gold btn-lg mt-3">Nuestras Salas</a>
                </div>
            </div>
        </div>
    </div>
</div>

<section id="obras" class="py-5 container">
    <div class="text-center my-5">
        <h2 class="display-4" style="color: var(--dorado)">Obras en Escena</h2>
        <p style="color: var(--crema); opacity: 0.7;">Selección exclusiva de arte dramático</p>
    </div>
    <div class="row g-4">
        <?php foreach ($obrasDestacadas as $obra): ?>
        <div class="col-md-4">
            <div class="card h-100 card-obra">
                <div class="card-img-container">
                    <img src="<?= $obra['Imagen'] ?? $imgDefaultObra ?>" class="card-img-top-custom" alt="<?= $obra['Titulo'] ?>">
                </div>
                <div class="card-body d-flex flex-column">
                    <span style="color: var(--dorado); font-weight: bold;"><?= $obra['Autor'] ?></span>
                    <h3 class="h4 mt-2"><?= $obra['Titulo'] ?></h3>
                    <p class="small opacity-75 flex-grow-1"><?= substr($obra['Subtitulo'], 0, 100) ?>...</p>
                    <a href="<?= $obra['UrlDracor'] ?>" target="_blank" class="btn btn-outline-light btn-sm mt-3">
                        Explorar en Dracor
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- EL RESTO DEL HTML (horarios, ranking, teatros y footer) ESTÁ BIEN TAL COMO LO TENÍAS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
