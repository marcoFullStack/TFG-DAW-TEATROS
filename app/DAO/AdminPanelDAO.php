<?php
// app/DAO/AdminPanelDAO.php
declare(strict_types=1);

require_once __DIR__ . '/../models/Obra.php';
require_once __DIR__ . '/../models/Teatro.php';
require_once __DIR__ . '/../models/Horario.php';
require_once __DIR__ . '/../models/Usuario.php';



final class AdminPanelDAO {
  private PDO $pdo;

  public function __construct(PDO $pdo) { $this->pdo = $pdo; }

  /* ===================== PAGINACIÓN HELPERS ===================== */

  private function clampPerPage(int $pp): int {
    return max(5, min(100, $pp));
  }

  /* ===================== OBRAS ===================== */

  public function countObras(string $q = ''): int {
    if ($q !== '') {
      $st = $this->pdo->prepare("SELECT COUNT(*) FROM obras WHERE Titulo LIKE ? OR Autor LIKE ?");
      $like = '%' . $q . '%';
      $st->execute([$like, $like]);
      return (int)$st->fetchColumn();
    }
    return (int)$this->pdo->query("SELECT COUNT(*) FROM obras")->fetchColumn();
  }

 public function listObras(int $page, int $perPage, string $q = ''): array {
  $perPage = $this->clampPerPage($perPage);
  $offset  = max(0, ($page - 1) * $perPage);

  if ($q !== '') {
    $sql = "SELECT * FROM obras
            WHERE Titulo LIKE :q1 OR Autor LIKE :q2
            ORDER BY idObra DESC
            LIMIT :lim OFFSET :off";
    $st = $this->pdo->prepare($sql);

    $like = '%' . $q . '%';
    $st->bindValue(':q1', $like, PDO::PARAM_STR);
    $st->bindValue(':q2', $like, PDO::PARAM_STR);
  } else {
    $sql = "SELECT * FROM obras
            ORDER BY idObra DESC
            LIMIT :lim OFFSET :off";
    $st = $this->pdo->prepare($sql);
  }

  $st->bindValue(':lim', (int)$perPage, PDO::PARAM_INT);
  $st->bindValue(':off', (int)$offset, PDO::PARAM_INT);

  $st->execute();
  return $st->fetchAll(PDO::FETCH_ASSOC);
}

  public function getObraById(int $idObra): ?array {
    $st = $this->pdo->prepare("SELECT * FROM obras WHERE idObra=? LIMIT 1");
    $st->execute([$idObra]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
  }

public function createObra(Obra $o): int {
  $sql = "INSERT INTO obras (Titulo, Autor, Subtitulo, Anio, UrlDracor) VALUES (?, ?, ?, ?, ?)";
  $st = $this->pdo->prepare($sql);
  $ok = $st->execute([
    $o->getTitulo(),
    $o->getAutor(),
    $o->getSubtitulo(),
    $o->getAnio(),
    $o->getUrlDracor()
  ]);

  if ($ok) {
    $id = (int)$this->pdo->lastInsertId();
    $o->setIdObra($id);
    return $id;
  }
  return 0;
}


 public function updateObra(Obra $o): bool {
  $id = (int)($o->getIdObra() ?? 0);
  if ($id <= 0) return false;

  $sql = "UPDATE obras SET Titulo=?, Autor=?, Subtitulo=?, Anio=?, UrlDracor=? WHERE idObra=?";
  $st = $this->pdo->prepare($sql);
  return $st->execute([
    $o->getTitulo(),
    $o->getAutor(),
    $o->getSubtitulo(),
    $o->getAnio(),
    $o->getUrlDracor(),
    $id
  ]);
}

  public function deleteObra(int $idObra): bool {
    $st = $this->pdo->prepare("DELETE FROM obras WHERE idObra=?");
    return $st->execute([$idObra]);
  }

  /* --- Imágenes obras --- */

  public function listImagenesObra(int $idObra): array {
    $st = $this->pdo->prepare("SELECT * FROM imagenes_obras WHERE idObra=? ORDER BY idImagenObra ASC");
    $st->execute([$idObra]);
    return $st->fetchAll(PDO::FETCH_ASSOC);
  }

  public function addImagenObra(int $idObra, string $ruta): bool {
    $st = $this->pdo->prepare("INSERT INTO imagenes_obras (idObra, RutaImagen) VALUES (?, ?)");
    return $st->execute([$idObra, $ruta]);
  }

  public function getImagenObra(int $idImagenObra): ?array {
    $st = $this->pdo->prepare("SELECT * FROM imagenes_obras WHERE idImagenObra=? LIMIT 1");
    $st->execute([$idImagenObra]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
  }

  public function deleteImagenObra(int $idImagenObra): bool {
    $st = $this->pdo->prepare("DELETE FROM imagenes_obras WHERE idImagenObra=?");
    return $st->execute([$idImagenObra]);
  }

  /* ===================== TEATROS ===================== */

  public function countTeatros(string $q = ''): int {
    if ($q !== '') {
      $st = $this->pdo->prepare("SELECT COUNT(*) FROM teatros
        WHERE Sala LIKE ? OR Provincia LIKE ? OR Municipio LIKE ?");
      $like = '%' . $q . '%';
      $st->execute([$like, $like, $like]);
      return (int)$st->fetchColumn();
    }
    return (int)$this->pdo->query("SELECT COUNT(*) FROM teatros")->fetchColumn();
  }

 public function listTeatros(int $page, int $perPage, string $q = ''): array {
  $perPage = $this->clampPerPage($perPage);
  $offset  = max(0, ($page - 1) * $perPage);

  if ($q !== '') {
    $sql = "SELECT * FROM teatros
            WHERE Sala LIKE :q1 OR Provincia LIKE :q2 OR Municipio LIKE :q3
            ORDER BY Provincia, Municipio, Sala
            LIMIT :lim OFFSET :off";
    $st = $this->pdo->prepare($sql);

    $like = '%' . $q . '%';
    $st->bindValue(':q1', $like, PDO::PARAM_STR);
    $st->bindValue(':q2', $like, PDO::PARAM_STR);
    $st->bindValue(':q3', $like, PDO::PARAM_STR);
  } else {
    $sql = "SELECT * FROM teatros
            ORDER BY Provincia, Municipio, Sala
            LIMIT :lim OFFSET :off";
    $st = $this->pdo->prepare($sql);
  }

  $st->bindValue(':lim', (int)$perPage, PDO::PARAM_INT);
  $st->bindValue(':off', (int)$offset, PDO::PARAM_INT);

  $st->execute();
  return $st->fetchAll(PDO::FETCH_ASSOC);
}


  public function getTeatroById(int $idTeatro): ?array {
    $st = $this->pdo->prepare("SELECT * FROM teatros WHERE idTeatro=? LIMIT 1");
    $st->execute([$idTeatro]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
  }

 public function createTeatro(Teatro $t): int {
  $sql = "INSERT INTO teatros
    (Sala, Entidad, Provincia, Municipio, Direccion, CP, Telefono, Email, CapacidadMax, Latitud, Longitud)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

  $st = $this->pdo->prepare($sql);
  $ok = $st->execute([
    $t->getSala(),
    $t->getEntidad(),
    $t->getProvincia(),
    $t->getMunicipio(),
    $t->getDireccion(),
    $t->getCP(),
    $t->getTelefono(),
    $t->getEmail(),
    $t->getCapacidadMax(),
    $t->getLatitud(),
    $t->getLongitud()
  ]);

  if ($ok) {
    $id = (int)$this->pdo->lastInsertId();
    $t->setIdTeatro($id);
    return $id;
  }
  return 0;
}

  public function updateTeatro(Teatro $t): bool {
  $id = (int)($t->getIdTeatro() ?? 0);
  if ($id <= 0) return false;

  $sql = "UPDATE teatros SET
    Sala=?, Entidad=?, Provincia=?, Municipio=?, Direccion=?, CP=?, Telefono=?, Email=?, CapacidadMax=?, Latitud=?, Longitud=?
    WHERE idTeatro=?";

  $st = $this->pdo->prepare($sql);
  return $st->execute([
    $t->getSala(),
    $t->getEntidad(),
    $t->getProvincia(),
    $t->getMunicipio(),
    $t->getDireccion(),
    $t->getCP(),
    $t->getTelefono(),
    $t->getEmail(),
    $t->getCapacidadMax(),
    $t->getLatitud(),
    $t->getLongitud(),
    $id
  ]);
}


  public function deleteTeatro(int $idTeatro): bool {
    $st = $this->pdo->prepare("DELETE FROM teatros WHERE idTeatro=?");
    return $st->execute([$idTeatro]);
  }

  /* --- Imágenes teatros --- */

  public function listImagenesTeatro(int $idTeatro): array {
    $st = $this->pdo->prepare("SELECT * FROM imagenes_teatros WHERE idTeatro=? ORDER BY idImagenTeatro ASC");
    $st->execute([$idTeatro]);
    return $st->fetchAll(PDO::FETCH_ASSOC);
  }

  public function addImagenTeatro(int $idTeatro, string $ruta): bool {
    $st = $this->pdo->prepare("INSERT INTO imagenes_teatros (idTeatro, RutaImagen) VALUES (?, ?)");
    return $st->execute([$idTeatro, $ruta]);
  }

  public function getImagenTeatro(int $idImagenTeatro): ?array {
    $st = $this->pdo->prepare("SELECT * FROM imagenes_teatros WHERE idImagenTeatro=? LIMIT 1");
    $st->execute([$idImagenTeatro]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
  }

  public function deleteImagenTeatro(int $idImagenTeatro): bool {
    $st = $this->pdo->prepare("DELETE FROM imagenes_teatros WHERE idImagenTeatro=?");
    return $st->execute([$idImagenTeatro]);
  }

  /* ===================== USUARIOS ===================== */

  public function countUsuarios(string $q = ''): int {
    if ($q !== '') {
      $st = $this->pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE Nombre LIKE ? OR Email LIKE ?");
      $like = '%' . $q . '%';
      $st->execute([$like, $like]);
      return (int)$st->fetchColumn();
    }
    return (int)$this->pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
  }

 public function listUsuarios(int $page, int $perPage, string $q = ''): array {
  $perPage = $this->clampPerPage($perPage);
  $offset  = max(0, ($page - 1) * $perPage);

  if ($q !== '') {
    $sql = "SELECT idUsuario, Nombre, Email, FotoPerfil, Puntos, FechaAlta
            FROM usuarios
            WHERE Nombre LIKE :q1 OR Email LIKE :q2
            ORDER BY idUsuario DESC
            LIMIT :lim OFFSET :off";
    $st = $this->pdo->prepare($sql);

    $like = '%' . $q . '%';
    $st->bindValue(':q1', $like, PDO::PARAM_STR);
    $st->bindValue(':q2', $like, PDO::PARAM_STR);
  } else {
    $sql = "SELECT idUsuario, Nombre, Email, FotoPerfil, Puntos, FechaAlta
            FROM usuarios
            ORDER BY idUsuario DESC
            LIMIT :lim OFFSET :off";
    $st = $this->pdo->prepare($sql);
  }

  $st->bindValue(':lim', (int)$perPage, PDO::PARAM_INT);
  $st->bindValue(':off', (int)$offset, PDO::PARAM_INT);

  $st->execute();
  return $st->fetchAll(PDO::FETCH_ASSOC);
}

  public function getUsuarioById(int $idUsuario): ?array {
    $st = $this->pdo->prepare("SELECT * FROM usuarios WHERE idUsuario=? LIMIT 1");
    $st->execute([$idUsuario]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
  }

  public function updateUsuario(int $idUsuario, string $nombre, string $email, int $puntos): bool {
    $st = $this->pdo->prepare("UPDATE usuarios SET Nombre=?, Email=?, Puntos=? WHERE idUsuario=?");
    return $st->execute([$nombre, $email, $puntos, $idUsuario]);
  }

  public function deleteUsuario(int $idUsuario): bool {
    $st = $this->pdo->prepare("DELETE FROM usuarios WHERE idUsuario=?");
    return $st->execute([$idUsuario]);
  }

  /* ===================== HORARIOS (CRUD) ===================== */

  public function countHorarios(int $idTeatro = 0): int {
    if ($idTeatro > 0) {
      $st = $this->pdo->prepare("SELECT COUNT(*) FROM horarios WHERE idTeatro=?");
      $st->execute([$idTeatro]);
      return (int)$st->fetchColumn();
    }
    return (int)$this->pdo->query("SELECT COUNT(*) FROM horarios")->fetchColumn();
  }

public function listHorarios(int $page, int $perPage, int $idTeatro = 0): array {
  $perPage = $this->clampPerPage($perPage);
  $offset = max(0, ($page - 1) * $perPage);

  $sql = "
    SELECT
      h.idHorario, h.idTeatro, h.idObra, h.FechaHora,
      t.Sala AS teatro, t.Provincia, t.Municipio,
      o.Titulo AS obra, o.Autor AS autor
    FROM horarios h
    INNER JOIN teatros t ON t.idTeatro = h.idTeatro
    INNER JOIN obras o   ON o.idObra   = h.idObra
  ";

  $params = [
    ':lim' => $perPage,
    ':off' => $offset,
  ];

  if ($idTeatro > 0) {
    $sql .= " WHERE h.idTeatro = :idTeatro ";
    $params[':idTeatro'] = $idTeatro;
  }

  $sql .= " ORDER BY h.FechaHora ASC LIMIT :lim OFFSET :off";

  $st = $this->pdo->prepare($sql);
  $st->bindValue(':lim', (int)$params[':lim'], PDO::PARAM_INT);
  $st->bindValue(':off', (int)$params[':off'], PDO::PARAM_INT);

  if (isset($params[':idTeatro'])) {
    $st->bindValue(':idTeatro', (int)$params[':idTeatro'], PDO::PARAM_INT);
  }

  $st->execute();
  return $st->fetchAll(PDO::FETCH_ASSOC);
}


  public function getHorarioById(int $idHorario): ?array {
    $st = $this->pdo->prepare("SELECT * FROM horarios WHERE idHorario=? LIMIT 1");
    $st->execute([$idHorario]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
  }

public function createHorario(Horario $h): bool {
  $st = $this->pdo->prepare("INSERT INTO horarios (idTeatro, idObra, FechaHora, Precio) VALUES (?, ?, ?, ?)");
  $ok = $st->execute([
    $h->getIdTeatro(),
    $h->getIdObra(),
    $h->getFechaHora(),
    $h->getPrecio()
  ]);

  if ($ok) {
    $h->setIdHorario((int)$this->pdo->lastInsertId());
  }
  return $ok;
}



public function updateHorario(Horario $h): bool {
  $id = (int)($h->getIdHorario() ?? 0);
  if ($id <= 0) return false;

  $st = $this->pdo->prepare("UPDATE horarios SET idTeatro=?, idObra=?, FechaHora=?, Precio=? WHERE idHorario=?");
  return $st->execute([
    $h->getIdTeatro(),
    $h->getIdObra(),
    $h->getFechaHora(),
    $h->getPrecio(),
    $id
  ]);
}


  public function deleteHorario(int $idHorario): bool {
    $st = $this->pdo->prepare("DELETE FROM horarios WHERE idHorario=?");
    return $st->execute([$idHorario]);
  }

  /* Listas simples para selects */
  public function listTeatrosSimple(): array {
    return $this->pdo->query("SELECT idTeatro, Sala, Provincia, Municipio FROM teatros ORDER BY Provincia, Municipio, Sala")
      ->fetchAll(PDO::FETCH_ASSOC);
  }

  public function listObrasSimple(): array {
    return $this->pdo->query("SELECT idObra, Titulo FROM obras ORDER BY Titulo")
      ->fetchAll(PDO::FETCH_ASSOC);
  }

  /* ===================== GALERIA REVISION ===================== */

  public function countGaleria(?string $estado = 'pendiente'): int {
    if ($estado === null || $estado === '') {
      return (int)$this->pdo->query("SELECT COUNT(*) FROM galeria_revision")->fetchColumn();
    }
    $st = $this->pdo->prepare("SELECT COUNT(*) FROM galeria_revision WHERE Estado=?");
    $st->execute([$estado]);
    return (int)$st->fetchColumn();
  }

 public function listGaleria(int $page, int $perPage, ?string $estado = 'pendiente'): array {
  $perPage = $this->clampPerPage($perPage);
  $offset = max(0, ($page - 1) * $perPage);

  $sql = "
    SELECT
      g.idImagen, g.RutaImagen, g.Estado, g.FechaSubida,
      u.idUsuario, u.Nombre AS usuario, u.Email AS emailUsuario,
      t.idTeatro, t.Sala AS teatro, t.Provincia, t.Municipio
    FROM galeria_revision g
    INNER JOIN usuarios u ON u.idUsuario = g.idUsuario
    INNER JOIN teatros  t ON t.idTeatro  = g.idTeatro
  ";

  $params = [
    ':lim' => $perPage,
    ':off' => $offset,
  ];

  if ($estado !== null && $estado !== '') {
    $sql .= " WHERE g.Estado = :estado ";
    $params[':estado'] = $estado;
  }

  $sql .= " ORDER BY g.FechaSubida DESC LIMIT :lim OFFSET :off";

  $st = $this->pdo->prepare($sql);

  if (isset($params[':estado'])) {
    $st->bindValue(':estado', $params[':estado'], PDO::PARAM_STR);
  }
  $st->bindValue(':lim', (int)$params[':lim'], PDO::PARAM_INT);
  $st->bindValue(':off', (int)$params[':off'], PDO::PARAM_INT);

  $st->execute();
  return $st->fetchAll(PDO::FETCH_ASSOC);
}
  public function setGaleriaEstado(int $idImagen, string $estado): bool {
    $allowed = ['pendiente','aprobada','rechazada'];
    if (!in_array($estado, $allowed, true)) return false;

    $st = $this->pdo->prepare("UPDATE galeria_revision SET Estado=? WHERE idImagen=?");
    return $st->execute([$estado, $idImagen]);
  }

  public function getGaleriaItem(int $idImagen): ?array {
    $st = $this->pdo->prepare("SELECT * FROM galeria_revision WHERE idImagen=? LIMIT 1");
    $st->execute([$idImagen]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
  }

  public function deleteGaleriaItem(int $idImagen): bool {
    $st = $this->pdo->prepare("DELETE FROM galeria_revision WHERE idImagen=?");
    return $st->execute([$idImagen]);
  }
  /* ===================== PACK HELPERS (total + rows) ===================== */

public function obrasPage(int $page, int $perPage, string $q=''): array {
  $perPage = $this->clampPerPage($perPage);
  $offset  = max(0, ($page - 1) * $perPage);

  $where = '';
  $params = [];
  if ($q !== '') {
    $where = "WHERE Titulo LIKE :q OR Autor LIKE :q";
    $params[':q'] = '%'.$q.'%';
  }

  // MySQL 8+: COUNT(*) OVER() evita hacer count() aparte
  $sql = "
    SELECT
      o.*,
      COUNT(*) OVER() AS total_rows
    FROM obras o
    $where
    ORDER BY o.idObra DESC
    LIMIT :lim OFFSET :off
  ";
  $st = $this->pdo->prepare($sql);
  foreach ($params as $k => $v) $st->bindValue($k, $v, PDO::PARAM_STR);
  $st->bindValue(':lim', $perPage, PDO::PARAM_INT);
  $st->bindValue(':off', $offset, PDO::PARAM_INT);
  $st->execute();

  $rows = $st->fetchAll(PDO::FETCH_ASSOC);
  $total = $rows ? (int)$rows[0]['total_rows'] : 0;

  // limpiar columna extra
  foreach ($rows as &$r) unset($r['total_rows']);

  return ['total' => $total, 'rows' => $rows];
}

public function teatrosPage(int $page, int $perPage, string $q=''): array {
  $perPage = $this->clampPerPage($perPage);
  $offset  = max(0, ($page - 1) * $perPage);

  $where = '';
  $params = [];
  if ($q !== '') {
    $where = "WHERE Sala LIKE :q OR Provincia LIKE :q OR Municipio LIKE :q";
    $params[':q'] = '%'.$q.'%';
  }

  $sql = "
    SELECT
      t.*,
      COUNT(*) OVER() AS total_rows
    FROM teatros t
    $where
    ORDER BY t.Provincia, t.Municipio, t.Sala
    LIMIT :lim OFFSET :off
  ";
  $st = $this->pdo->prepare($sql);
  foreach ($params as $k => $v) $st->bindValue($k, $v, PDO::PARAM_STR);
  $st->bindValue(':lim', $perPage, PDO::PARAM_INT);
  $st->bindValue(':off', $offset, PDO::PARAM_INT);
  $st->execute();

  $rows = $st->fetchAll(PDO::FETCH_ASSOC);
  $total = $rows ? (int)$rows[0]['total_rows'] : 0;
  foreach ($rows as &$r) unset($r['total_rows']);

  return ['total' => $total, 'rows' => $rows];
}

public function usuariosPage(int $page, int $perPage, string $q=''): array {
  $perPage = $this->clampPerPage($perPage);
  $offset  = max(0, ($page - 1) * $perPage);

  $where = '';
  $params = [];
  if ($q !== '') {
    $where = "WHERE Nombre LIKE :q OR Email LIKE :q";
    $params[':q'] = '%'.$q.'%';
  }

  $sql = "
    SELECT
      idUsuario, Nombre, Email, FotoPerfil, Puntos, FechaAlta,
      COUNT(*) OVER() AS total_rows
    FROM usuarios
    $where
    ORDER BY idUsuario DESC
    LIMIT :lim OFFSET :off
  ";
  $st = $this->pdo->prepare($sql);
  foreach ($params as $k => $v) $st->bindValue($k, $v, PDO::PARAM_STR);
  $st->bindValue(':lim', $perPage, PDO::PARAM_INT);
  $st->bindValue(':off', $offset, PDO::PARAM_INT);
  $st->execute();

  $rows = $st->fetchAll(PDO::FETCH_ASSOC);
  $total = $rows ? (int)$rows[0]['total_rows'] : 0;
  foreach ($rows as &$r) unset($r['total_rows']);

  return ['total' => $total, 'rows' => $rows];
}

public function horariosPage(int $page, int $perPage, int $idTeatro=0): array {
  $perPage = $this->clampPerPage($perPage);
  $offset  = max(0, ($page - 1) * $perPage);

  $where = '';
  if ($idTeatro > 0) $where = "WHERE h.idTeatro = :idTeatro";

  $sql = "
    SELECT
      h.idHorario, h.idTeatro, h.idObra, h.FechaHora,
      t.Sala AS teatro, t.Provincia, t.Municipio,
      o.Titulo AS obra, o.Autor AS autor,
      COUNT(*) OVER() AS total_rows
    FROM horarios h
    INNER JOIN teatros t ON t.idTeatro = h.idTeatro
    INNER JOIN obras o   ON o.idObra   = h.idObra
    $where
    ORDER BY h.FechaHora ASC
    LIMIT :lim OFFSET :off
  ";
  $st = $this->pdo->prepare($sql);
  if ($idTeatro > 0) $st->bindValue(':idTeatro', $idTeatro, PDO::PARAM_INT);
  $st->bindValue(':lim', $perPage, PDO::PARAM_INT);
  $st->bindValue(':off', $offset, PDO::PARAM_INT);
  $st->execute();

  $rows = $st->fetchAll(PDO::FETCH_ASSOC);
  $total = $rows ? (int)$rows[0]['total_rows'] : 0;
  foreach ($rows as &$r) unset($r['total_rows']);

  return ['total' => $total, 'rows' => $rows];
}

public function galeriaPage(int $page, int $perPage, ?string $estado='pendiente'): array {
  $perPage = $this->clampPerPage($perPage);
  $offset  = max(0, ($page - 1) * $perPage);

  $where = '';
  if ($estado !== null && $estado !== '') $where = "WHERE g.Estado = :estado";

  $sql = "
    SELECT
      g.idImagen, g.RutaImagen, g.Estado, g.FechaSubida,
      u.idUsuario, u.Nombre AS usuario, u.Email AS emailUsuario,
      t.idTeatro, t.Sala AS teatro, t.Provincia, t.Municipio,
      COUNT(*) OVER() AS total_rows
    FROM galeria_revision g
    INNER JOIN usuarios u ON u.idUsuario = g.idUsuario
    INNER JOIN teatros  t ON t.idTeatro  = g.idTeatro
    $where
    ORDER BY g.FechaSubida DESC
    LIMIT :lim OFFSET :off
  ";
  $st = $this->pdo->prepare($sql);
  if ($where) $st->bindValue(':estado', $estado, PDO::PARAM_STR);
  $st->bindValue(':lim', $perPage, PDO::PARAM_INT);
  $st->bindValue(':off', $offset, PDO::PARAM_INT);
  $st->execute();

  $rows = $st->fetchAll(PDO::FETCH_ASSOC);
  $total = $rows ? (int)$rows[0]['total_rows'] : 0;
  foreach ($rows as &$r) unset($r['total_rows']);

  return ['total' => $total, 'rows' => $rows];
}
public function updateUsuarioObj(Usuario $u): bool {
  $id = (int)($u->getIdUsuario() ?? 0);
  if ($id <= 0) return false;

  $sql = "UPDATE usuarios
          SET Nombre = ?, Email = ?, Puntos = ?, FotoPerfil = ?
          WHERE idUsuario = ?";
  $st = $this->pdo->prepare($sql);

  return $st->execute([
    $u->getNombre(),
    $u->getEmail(),
    $u->getPuntos(),
    $u->getFotoPerfil(),
    $id
  ]);
}
  
}
