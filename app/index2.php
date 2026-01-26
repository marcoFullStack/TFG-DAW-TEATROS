<?php
<<<<<<< Updated upstream

    require_once "inc/header.php"

?>
=======
require_once './config/db.php'; 

try {
    // 1. Obras destacadas con su primera imagen
    // Usamos una subconsulta para traer solo una imagen de la galería de la obra
    $stmtObras = $pdo->query("SELECT o.*, 
        (SELECT RutaImagen FROM imagenes_obras WHERE idObra = o.idObra LIMIT 1) as Imagen 
        FROM obras o ORDER BY RAND() LIMIT 3");
    $obrasDestacadas = $stmtObras->fetchAll();

    // 2. Teatros recientes con su primera imagen
    $stmtTeatros = $pdo->query("SELECT t.*, 
        (SELECT RutaImagen FROM imagenes_teatros WHERE idTeatro = t.idTeatro LIMIT 1) as Imagen 
        FROM teatros t ORDER BY idTeatro DESC LIMIT 4");
    $teatrosRecientes = $stmtTeatros->fetchAll();

    // 3. Próximas funciones (igual que antes)
    $sqlFunciones = "SELECT h.FechaHora, o.Titulo as Obra, t.Sala as Teatro, t.Municipio
                     FROM horarios h
                     JOIN obras o ON h.idObra = o.idObra
                     JOIN teatros t ON h.idTeatro = t.idTeatro
                     WHERE h.FechaHora >= NOW()
                     ORDER BY h.FechaHora ASC LIMIT 5";
    $proximasFunciones = $pdo->query($sqlFunciones)->fetchAll();

    // 4. Ranking de usuarios
    $rankingUsuarios = $pdo->query("SELECT Nombre, Puntos, FotoPerfil FROM usuarios ORDER BY Puntos DESC LIMIT 5")->fetchAll();

} catch (PDOException $e) {
    die("Error al cargar la página: " . $e->getMessage());
}

// Imagen por defecto si no hay en la DB
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
        /* Ajuste para que las imágenes de las obras se vean bien */
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
            <a class="navbar-brand" href="#"><i class="fas fa-masks-theater me-2"></i>RED TEATROS</a>
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
            <div class="carousel-item active" style="background-image: url('https://images.unsplash.com/photo-1503095394537-f25d16bd7d5b?q=80&w=2000');">
                <div class="carousel-overlay"></div>
                <div class="container h-100 d-flex align-items-center">
                    <div class="carousel-caption">
                        <h1 class="display-1">El Gran Teatro</h1>
                        <p class="lead fs-3">Vive la experiencia única del arte en vivo.</p>
                        <a href="#obras" class="btn btn-gold btn-lg mt-3">Ver Cartelera</a>
                    </div>
                </div>
            </div>
            <div class="carousel-item" style="background-image: url('https://images.unsplash.com/photo-1514306191717-452ec28c7814?q=80&w=2000');">
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
            <?php foreach($obrasDestacadas as $obra): ?>
            <div class="col-md-4">
                <div class="card h-100 card-obra">
                    <div class="card-img-container">
                        <img src="<?= $obra['Imagen'] ?? $imgDefaultObra ?>" class="card-img-top-custom" alt="<?= $obra['Titulo'] ?>">
                    </div>
                    <div class="card-body d-flex flex-column">
                        <span style="color: var(--dorado); font-weight: bold;"><?= $obra['Autor'] ?></span>
                        <h3 class="h4 mt-2"><?= $obra['Titulo'] ?></h3>
                        <p class="small opacity-75 flex-grow-1"><?= substr($obra['Subtitulo'], 0, 100) ?>...</p>
                        <a href="<?= $obra['UrlDracor'] ?>" target="_blank" class="btn btn-outline-light btn-sm mt-3">Explorar en Dracor</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section id="horarios" class="py-5 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-7">
                    <h2 class="mb-5 display-5"><i class="far fa-clock me-3" style="color: var(--granate)"></i>Próximas Funciones</h2>
                    <?php foreach($proximasFunciones as $f): ?>
                    <div class="schedule-item d-flex justify-content-between align-items-center">
                        <div>
                            <span class="badge badge-custom mb-2"><?= date('d M - H:i', strtotime($f['FechaHora'])) ?>h</span>
                            <h4 class="h5 mb-0"><?= $f['Obra'] ?></h4>
                            <small class="text-muted"><i class="fas fa-map-marker-alt me-1"></i><?= $f['Teatro'] ?> (<?= $f['Municipio'] ?>)</small>
                        </div>
                        <button class="btn btn-gold">Reservar</button>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="col-lg-5 ps-lg-5 mt-5 mt-lg-0">
                    <div class="ranking-container shadow-lg">
                        <h3 class="h4 mb-4 text-center" style="color: var(--dorado)">Top Espectadores</h3>
                        <?php foreach($rankingUsuarios as $index => $u): ?>
                        <div class="d-flex align-items-center mb-4">
                            <span class="fw-bold me-3 fs-5 text-dorado">#<?= $index+1 ?></span>
                            <img src="<?= $u['FotoPerfil'] ?? 'https://via.placeholder.com/50' ?>" class="rounded-circle border border-warning me-3" width="50" height="50" style="object-fit: cover;">
                            <div class="flex-grow-1">
                                <div class="fw-bold"><?= $u['Nombre'] ?></div>
                                <div class="progress mt-1" style="height: 6px;">
                                    <div class="progress-bar" style="width: <?= min($u['Puntos'], 100) ?>%"></div>
                                </div>
                            </div>
                            <span class="ms-3 badge border border-warning"><?= $u['Puntos'] ?> pts</span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="teatros" class="py-5 container">
        <h2 class="text-center my-5 display-4" style="color: var(--dorado)">Nuestras Salas</h2>
        <div class="row g-4">
            <?php foreach($teatrosRecientes as $t): ?>
            <div class="col-md-3">
                <div class="card h-100 bg-transparent border-0 text-center card-teatro">
                    <div class="mb-3 overflow-hidden" style="height: 150px; border: 1px solid var(--dorado);">
                        <img src="<?= $t['Imagen'] ?? $imgDefaultTeatro ?>" class="w-100 h-100" style="object-fit: cover;">
                    </div>
                    <div class="p-2">
                        <h5 class="card-title text-uppercase mb-1"><?= $t['Sala'] ?></h5>
                        <p class="small opacity-75 mb-3"><?= $t['Municipio'] ?></p>
                        <div class="d-flex justify-content-center gap-2">
                            <a href="tel:<?= $t['Telefono'] ?>" class="btn btn-sm btn-outline-light"><i class="fas fa-phone"></i></a>
                            <a href="https://www.google.com/maps/search/?api=1&query=<?= $t['Latitud'] ?>,<?= $t['Longitud'] ?>" target="_blank" class="btn btn-sm btn-outline-light"><i class="fas fa-map-pin"></i></a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

    <footer>
        <div class="container text-center">
            <h2 class="mb-4" style="color: var(--dorado); letter-spacing: 5px;">RED REGIONAL</h2>
            <div class="social-icons mb-5">
                <a href="#"><i class="fab fa-instagram"></i></a>
                <a href="#"><i class="fab fa-facebook-f"></i></a>
                <a href="#"><i class="fab fa-twitter"></i></a>
            </div>
            <p style="color: var(--crema); opacity: 0.5;">© <?= date('Y') ?> | Patrimonio Cultural Regional | Todos los derechos reservados.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
>>>>>>> Stashed changes
