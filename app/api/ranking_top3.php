<?php
// app/api/ranking.php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/db.php';

function clamp_int($v, $min, $max) {
  $n = filter_var($v, FILTER_VALIDATE_INT);
  if ($n === false) $n = $min;
  if ($n < $min) $n = $min;
  if ($n > $max) $n = $max;
  return $n;
}

try {
  $q = trim((string)($_GET['q'] ?? ''));
  $page = clamp_int($_GET['page'] ?? 1, 1, 9999);

  // LIKE seguro
  $like = '%' . $q . '%';

  // Total usuarios (con filtro)
  $stCount = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE Nombre LIKE :like");
  $stCount->execute([':like' => $like]);
  $total = (int)$stCount->fetchColumn();

  // Top 3 (con filtro)
  $stTop = $pdo->prepare("
    SELECT idUsuario, Nombre, Puntos, FotoPerfil
    FROM usuarios
    WHERE Nombre LIKE :like
    ORDER BY Puntos DESC, Nombre ASC
    LIMIT 3
  ");
  $stTop->execute([':like' => $like]);
  $top3 = $stTop->fetchAll(PDO::FETCH_ASSOC);

  // Paginación del RESTO
  // Página 1: offset=3, limit=7
  // Página 2+: offset=(page-1)*10, limit=10  (así la página 2 arranca en posición 11)
  if ($page === 1) {
    $offset = 3;
    $limit = 7;
  } else {
    $offset = ($page - 1) * 10;
    $limit = 10;
  }

  // Si hay menos de 3 usuarios, offset debe ser ese número (para no saltarnos de más)
  $skip = min(3, $total);
  if ($page === 1) $offset = $skip;

  // Lista paginada
  $stList = $pdo->prepare("
    SELECT idUsuario, Nombre, Puntos
    FROM usuarios
    WHERE Nombre LIKE :like
    ORDER BY Puntos DESC, Nombre ASC
    LIMIT :lim OFFSET :off
  ");
  $stList->bindValue(':like', $like, PDO::PARAM_STR);
  $stList->bindValue(':lim', $limit, PDO::PARAM_INT);
  $stList->bindValue(':off', $offset, PDO::PARAM_INT);
  $stList->execute();
  $rows = $stList->fetchAll(PDO::FETCH_ASSOC);

  // Calculamos posiciones reales
  $startPos = $offset + 1;
  foreach ($rows as $i => $r) {
    $rows[$i]['Posicion'] = $startPos + $i;
    $rows[$i]['Puntos'] = (int)$rows[$i]['Puntos'];
  }
  foreach ($top3 as $i => $r) {
    $top3[$i]['Puntos'] = (int)$top3[$i]['Puntos'];
  }

  // Total pages (con regla especial 7 en la primera)
  $remaining = max($total - $skip, 0);
  if ($remaining <= 7) {
    $total_pages = 1;
  } else {
    $total_pages = 1 + (int)ceil(($remaining - 7) / 10);
  }

  echo json_encode([
    'ok' => true,
    'q' => $q,
    'page' => $page,
    'total' => $total,
    'total_pages' => $total_pages,
    'top3' => $top3,
    'rows' => $rows,
  ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode([
    'ok' => false,
    'error' => 'Error obteniendo ranking'
  ], JSON_UNESCAPED_UNICODE);
}
