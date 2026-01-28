<?php
// app/DAO/HomeDAO.php

declare(strict_types=1);

function dao_getProvincias(PDO $pdo): array {
    try {
        return $pdo->query("SELECT DISTINCT Provincia FROM teatros ORDER BY Provincia")
                   ->fetchAll(PDO::FETCH_COLUMN);
    } catch (Throwable $e) {
        return [];
    }
}

function dao_getTeatrosDestacados(PDO $pdo, ?int $limit = null): array {
    try {
        $sql = "
            SELECT
              t.idTeatro AS id, t.Sala, t.Provincia, t.Municipio, t.Direccion, t.CapacidadMax,
              (SELECT RutaImagen
               FROM imagenes_teatros it
               WHERE it.idTeatro = t.idTeatro
               ORDER BY it.idImagenTeatro ASC
               LIMIT 1) AS img
            FROM teatros t
            ORDER BY t.Provincia, t.Municipio, t.Sala
        ";

        if ($limit !== null) {
            $sql .= " LIMIT :lim";
            $st = $pdo->prepare($sql);
            $st->bindValue(':lim', $limit, PDO::PARAM_INT);
            $st->execute();
            return $st->fetchAll();
        }

        return $pdo->query($sql)->fetchAll();
    } catch (Throwable $e) {
        return [];
    }
}


function dao_getCartelera(PDO $pdo, ?int $limit = null): array {
    try {
        $sql = "
            SELECT
              h.idHorario,
              h.FechaHora,
              t.idTeatro AS idTeatro, t.Sala AS teatro, t.Provincia, t.Municipio,
              o.idObra AS idObra, o.Titulo AS titulo, o.Autor AS autor, o.Anio AS anio, o.UrlDracor AS url,
              (SELECT RutaImagen
               FROM imagenes_obras io
               WHERE io.idObra = o.idObra
               ORDER BY io.idImagenObra ASC
               LIMIT 1) AS img
            FROM horarios h
            INNER JOIN teatros t ON t.idTeatro = h.idTeatro
            INNER JOIN obras o   ON o.idObra   = h.idObra
            ORDER BY h.FechaHora ASC
        ";

        if ($limit !== null) {
            $sql .= " LIMIT :lim";
            $st = $pdo->prepare($sql);
            $st->bindValue(':lim', $limit, PDO::PARAM_INT);
            $st->execute();
            return $st->fetchAll();
        }

        return $pdo->query($sql)->fetchAll();
    } catch (Throwable $e) {
        return [];
    }
}

function dao_countTeatros(PDO $pdo): int {
    try {
        return (int)$pdo->query("SELECT COUNT(DISTINCT idTeatro) FROM teatros")->fetchColumn();
    } catch (Throwable $e) {
        return 0;
    }
}

function dao_countObras(PDO $pdo): int {
    try {
        return (int)$pdo->query("SELECT COUNT(DISTINCT idObra) FROM obras")->fetchColumn();
    } catch (Throwable $e) {
        return 0;
    }
}


//borrar luego

function dao_getGaleriaAleatoria(PDO $pdo, int $limit = 4): array {
    $sql = "SELECT g.RutaImagen, t.Sala, u.Nombre as NombreUsuario 
            FROM galeria_revision g
            JOIN teatros t ON g.idTeatro = t.idTeatro
            JOIN usuarios u ON g.idUsuario = u.idUsuario
            WHERE g.Estado = 'aprobada'
            ORDER BY RAND() 
            LIMIT :limit";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}