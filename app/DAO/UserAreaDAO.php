<?php
// DAO/UserAreaDAO.php
declare(strict_types=1);

final class UserAreaDAO {
  public function __construct(private PDO $pdo) {}

  /* ===================== TEATROS ===================== */
  public function listTeatros(int $page, int $pp, string $q = ''): array {
    $off = ($page - 1) * $pp;

    if ($q !== '') {
      $like = '%' . $q . '%';
      $st = $this->pdo->prepare("
        SELECT t.*,
          (SELECT RutaImagen FROM imagenes_teatros it WHERE it.idTeatro=t.idTeatro ORDER BY it.idImagenTeatro ASC LIMIT 1) AS img
        FROM teatros t
        WHERE t.Sala LIKE :q1 OR t.Provincia LIKE :q2 OR t.Municipio LIKE :q3
        ORDER BY t.Provincia, t.Municipio, t.Sala
        LIMIT :lim OFFSET :off
      ");
      $st->bindValue(':q1', $like, PDO::PARAM_STR);
      $st->bindValue(':q2', $like, PDO::PARAM_STR);
      $st->bindValue(':q3', $like, PDO::PARAM_STR);
    } else {
      $st = $this->pdo->prepare("
        SELECT t.*,
          (SELECT RutaImagen FROM imagenes_teatros it WHERE it.idTeatro=t.idTeatro ORDER BY it.idImagenTeatro ASC LIMIT 1) AS img
        FROM teatros t
        ORDER BY t.Provincia, t.Municipio, t.Sala
        LIMIT :lim OFFSET :off
      ");
    }

    $st->bindValue(':lim', $pp, PDO::PARAM_INT);
    $st->bindValue(':off', $off, PDO::PARAM_INT);
    $st->execute();
    return $st->fetchAll(PDO::FETCH_ASSOC);
  }

  public function listTeatrosSimple(): array {
    return $this->pdo->query("
      SELECT idTeatro, Sala, Provincia, Municipio
      FROM teatros
      ORDER BY Provincia, Municipio, Sala
    ")->fetchAll(PDO::FETCH_ASSOC);
  }

  /* ===================== OBRAS ===================== */
  public function listObras(int $page, int $pp, string $q = ''): array {
    $off = ($page - 1) * $pp;

    if ($q !== '') {
      $like = '%' . $q . '%';
      $st = $this->pdo->prepare("
        SELECT o.*,
          (SELECT RutaImagen FROM imagenes_obras io WHERE io.idObra=o.idObra ORDER BY io.idImagenObra ASC LIMIT 1) AS img
        FROM obras o
        WHERE o.Titulo LIKE :q1 OR o.Autor LIKE :q2
        ORDER BY o.idObra DESC
        LIMIT :lim OFFSET :off
      ");
      $st->bindValue(':q1', $like, PDO::PARAM_STR);
      $st->bindValue(':q2', $like, PDO::PARAM_STR);
    } else {
      $st = $this->pdo->prepare("
        SELECT o.*,
          (SELECT RutaImagen FROM imagenes_obras io WHERE io.idObra=o.idObra ORDER BY io.idImagenObra ASC LIMIT 1) AS img
        FROM obras o
        ORDER BY o.idObra DESC
        LIMIT :lim OFFSET :off
      ");
    }

    $st->bindValue(':lim', $pp, PDO::PARAM_INT);
    $st->bindValue(':off', $off, PDO::PARAM_INT);
    $st->execute();
    return $st->fetchAll(PDO::FETCH_ASSOC);
  }

  public function listObrasSimple(): array {
    return $this->pdo->query("
      SELECT idObra, Titulo
      FROM obras
      ORDER BY Titulo
    ")->fetchAll(PDO::FETCH_ASSOC);
  }

  /* ===================== HORARIOS (por obra) ===================== */
  public function listHorariosPorObra(int $idObra): array {
    $st = $this->pdo->prepare("
      SELECT
        h.idHorario, h.FechaHora, h.Precio,
        t.idTeatro, t.Sala, t.Provincia, t.Municipio, t.CapacidadMax,
        COALESCE((
          SELECT SUM(c.Entradas)
          FROM compras_entradas c
          WHERE c.idHorario = h.idHorario
        ), 0) AS vendidas
      FROM horarios h
      INNER JOIN teatros t ON t.idTeatro = h.idTeatro
      WHERE h.idObra = ?
      ORDER BY h.FechaHora ASC
    ");
    $st->execute([$idObra]);
    return $st->fetchAll(PDO::FETCH_ASSOC);
  }

  /* ===================== MIS COMPRAS ===================== */
  public function listMisCompras(int $idUsuario): array {
    $st = $this->pdo->prepare("
      SELECT
        c.idCompra, c.Entradas, c.FechaCompra,
        h.FechaHora,
        o.Titulo AS obra,
        t.Sala AS teatro, t.Provincia, t.Municipio
      FROM compras_entradas c
      INNER JOIN horarios h ON h.idHorario = c.idHorario
      INNER JOIN obras o ON o.idObra = h.idObra
      INNER JOIN teatros t ON t.idTeatro = h.idTeatro
      WHERE c.idUsuario = ?
      ORDER BY c.FechaCompra DESC
    ");
    $st->execute([$idUsuario]);
    return $st->fetchAll(PDO::FETCH_ASSOC);
  }

  /* ===================== COMPRAR / CANCELAR (transacción) ===================== */
  public function comprarEntradas(int $idUsuario, int $idHorario, int $qty, int $pointsPerTicket): void {
    $this->pdo->beginTransaction();
    try {
      // Bloquea horario y capacidad
      $st = $this->pdo->prepare("
        SELECT h.idHorario, h.idTeatro, h.Precio, t.CapacidadMax
        FROM horarios h
        INNER JOIN teatros t ON t.idTeatro = h.idTeatro
        WHERE h.idHorario = ?
        FOR UPDATE
      ");
      $st->execute([$idHorario]);
      $row = $st->fetch(PDO::FETCH_ASSOC);
      if (!$row) throw new RuntimeException('Horario no existe.');

      $cap = (int)$row['CapacidadMax'];
      $idTeatro = (int)$row['idTeatro'];

      // Bloquea compras del horario
      $st2 = $this->pdo->prepare("
        SELECT COALESCE(SUM(Entradas), 0) AS vendidas
        FROM compras_entradas
        WHERE idHorario = ?
        FOR UPDATE
      ");
      $st2->execute([$idHorario]);
      $vendidas = (int)$st2->fetchColumn();

      $restantes = $cap - $vendidas;
      if ($qty > $restantes) {
        throw new RuntimeException('No quedan suficientes entradas. Quedan: ' . max(0, $restantes));
      }

      // Inserta compra
      $st3 = $this->pdo->prepare("INSERT INTO compras_entradas (idUsuario, idHorario, Entradas) VALUES (?, ?, ?)");
      $st3->execute([$idUsuario, $idHorario, $qty]);

      // Suma puntos
      $sumPoints = $qty * $pointsPerTicket;
      $st4 = $this->pdo->prepare("UPDATE usuarios SET Puntos = Puntos + ? WHERE idUsuario = ?");
      $st4->execute([$sumPoints, $idUsuario]);

      // Ranking visitas
      $st5 = $this->pdo->prepare("INSERT IGNORE INTO visitas_ranking (idUsuario, idTeatro) VALUES (?, ?)");
      $st5->execute([$idUsuario, $idTeatro]);

      $this->pdo->commit();
    } catch (Throwable $e) {
      if ($this->pdo->inTransaction()) $this->pdo->rollBack();
      throw $e;
    }
  }

  public function cancelarCompra(int $idUsuario, int $idCompra, int $pointsPerTicket): void {
    $this->pdo->beginTransaction();
    try {
      $st = $this->pdo->prepare("
        SELECT idCompra, idHorario, Entradas
        FROM compras_entradas
        WHERE idCompra = ? AND idUsuario = ?
        FOR UPDATE
      ");
      $st->execute([$idCompra, $idUsuario]);
      $c = $st->fetch(PDO::FETCH_ASSOC);
      if (!$c) throw new RuntimeException('No existe esa compra o no es tuya.');

      $entradas = (int)$c['Entradas'];

      $st2 = $this->pdo->prepare("DELETE FROM compras_entradas WHERE idCompra=? AND idUsuario=?");
      $st2->execute([$idCompra, $idUsuario]);

      $restPoints = $entradas * $pointsPerTicket;
      $st3 = $this->pdo->prepare("UPDATE usuarios SET Puntos = GREATEST(Puntos - ?, 0) WHERE idUsuario=?");
      $st3->execute([$restPoints, $idUsuario]);

      $this->pdo->commit();
    } catch (Throwable $e) {
      if ($this->pdo->inTransaction()) $this->pdo->rollBack();
      throw $e;
    }
  }

  /* ===================== GALERÍA (inserción) ===================== */
  public function insertarGaleriaRevision(int $idUsuario, int $idTeatro, string $rutaRel): void {
    $st = $this->pdo->prepare("
      INSERT INTO galeria_revision (idUsuario, idTeatro, RutaImagen, Estado)
      VALUES (?, ?, ?, 'pendiente')
    ");
    $st->execute([$idUsuario, $idTeatro, $rutaRel]);
  }

  public function getSalaTeatro(int $idTeatro): string {
    $st = $this->pdo->prepare("SELECT Sala FROM teatros WHERE idTeatro=?");
    $st->execute([$idTeatro]);
    return (string)($st->fetchColumn() ?: '');
  }

  public function getTituloObra(int $idObra): string {
    $st = $this->pdo->prepare("SELECT Titulo FROM obras WHERE idObra=?");
    $st->execute([$idObra]);
    return (string)($st->fetchColumn() ?: '');
  }
}
