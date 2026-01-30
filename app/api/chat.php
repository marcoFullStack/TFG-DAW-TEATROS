<?php
declare(strict_types=1);
ini_set('max_execution_time', '60');

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/db.php'; // debe exponer getConexion()

$raw = file_get_contents('php://input');
$payload = json_decode($raw, true);
$userMsg = trim((string)($payload['message'] ?? ''));

if ($userMsg === '') {
  echo json_encode(['reply' => 'Escribe una pregunta, por favor.'], JSON_UNESCAPED_UNICODE);
  exit;
}

try {
  $pdo = getConexion();
  $intent = detect_intent($userMsg);
  $reply  = handle_intent($pdo, $intent, $userMsg);

  echo json_encode(['reply' => $reply], JSON_UNESCAPED_UNICODE);
  exit;

} catch (Throwable $e) {
  error_log("Chat error: " . $e->getMessage());
  echo json_encode(['reply' => help_text()], JSON_UNESCAPED_UNICODE);
  exit;
}

/* =========================================================
   ROUTER / INTENTS
========================================================= */

function detect_intent(string $msg): array {
  $m = mb_strtolower(trim($msg));
  $m = preg_replace('/\s+/', ' ', $m);

  // ayuda
  if (preg_match('/^(ayuda|help|hola|buenas|qué puedes hacer|que puedes hacer)$/u', $m)) {
    return ['type' => 'ayuda'];
  }

  // --- PRECIO OBRA ---
  // "precio obra <titulo>" / "precio de obra <titulo>"
  if (preg_match('/^precio\s+(?:de\s+)?obra\s+(.+)$/iu', $msg, $ma)) {
    return ['type' => 'precio_obra', 'obra' => trim($ma[1])];
  }
  // ✅ NUEVO: "precios <titulo>" (plural)
if (preg_match('/^precios\s+(.+)$/iu', $msg, $ma)) {
  return ['type' => 'precio_obra', 'obra' => trim($ma[1])];
}

  // ✅ NUEVO: "precio <titulo>"
if (preg_match('/^precio\s+(.+)$/iu', $msg, $ma)) {
  return ['type' => 'precio_obra', 'obra' => trim($ma[1])];
}

  // --- TEATROS ---
  if (preg_match('/^teatros?\s+(en|de)\s+([a-záéíóúñ\s]+)$/iu', $msg, $ma)) {
    return ['type' => 'teatros_por_provincia', 'provincia' => title_case_es(trim($ma[2]))];
  }
  if (preg_match('/^teatros?\s+en\s+municipio\s+(.+)$/iu', $msg, $ma)) {
    return ['type' => 'teatros_por_municipio', 'municipio' => trim($ma[1])];
  }
  if (preg_match('/^teatro\s+(.+)$/iu', $msg, $ma)) {
    return ['type' => 'teatro_por_nombre', 'q' => trim($ma[1])];
  }
  if (preg_match('/capacidad\s*(?:>=|mayor\s+que|más\s+de)\s*(\d+)/iu', $msg, $ma)) {
    return ['type' => 'teatros_por_capacidad', 'min' => (int)$ma[1]];
  }
  if (preg_match('/^provincias$/iu', $msg)) return ['type' => 'listar_provincias'];
  if (preg_match('/^municipios$/iu', $msg)) return ['type' => 'listar_municipios'];

  // --- OBRAS ---
  // --- OBRAS ---
// "obra <titulo>"
if (preg_match('/^obra\s+(.+)$/iu', $msg, $ma)) {
  return ['type' => 'obra_por_titulo', 'titulo' => trim($ma[1])];
}

// primero año y década, antes que "obras de <autor>"
if (preg_match('/^obras?\s+(?:del|de)\s+año\s+(\d{4})$/iu', $msg, $ma)) {
  return ['type' => 'obras_por_anio', 'anio' => (int)$ma[1]];
}

// "obras de 1930s" / "obras del 1930s"
if (preg_match('/^obras?\s+(?:de|del)\s+(\d{4})s$/iu', $msg, $ma)) {
  return ['type' => 'obras_por_decada', 'decada' => (int)$ma[1]];
}

// "obras 1930s"
if (preg_match('/^obras?\s+(\d{4})s$/iu', $msg, $ma)) {
  return ['type' => 'obras_por_decada', 'decada' => (int)$ma[1]];
}

// "obras de <autor>" (pero NO permitir "1930s" como autor)
if (preg_match('/^obras?\s+de\s+(?!\d{4}s\b)(.+)$/iu', $msg, $ma)) {
  return ['type' => 'obras_por_autor', 'autor' => trim($ma[1])];
}

// "obras en teatro <nombre>"
if (preg_match('/^obras?\s+en\s+teatro\s+(.+)$/iu', $msg, $ma)) {
  return ['type' => 'obras_en_teatro', 'teatro' => trim($ma[1])];
}

// "obras en <provincia>"
if (preg_match('/^obras?\s+en\s+([a-záéíóúñ\s]+)$/iu', $msg, $ma)) {
  return ['type' => 'obras_en_provincia', 'provincia' => title_case_es(trim($ma[1]))];
}

// "obras <teatro>" (ej: "obras lope de vega")
if (preg_match('/^obras?\s+(.+)$/iu', $msg, $ma)) {
  return ['type' => 'obras_en_teatro', 'teatro' => trim($ma[1])];
}

if (preg_match('/^autores$/iu', $msg)) return ['type' => 'listar_autores'];

  // --- HORARIOS / CARTELERA ---
  if (preg_match('/^(cartelera\s+hoy|horarios\s+hoy)$/iu', $msg)) {
    return ['type' => 'horarios_hoy'];
  }
  if (preg_match('/^cartelera\s+mañana$/iu', $msg)) {
    return ['type' => 'horarios_manana'];
  }
  if (preg_match('/^cartelera\s+semana$/iu', $msg)) {
    return ['type' => 'horarios_semana'];
  }

  // "horario(s) teatro <nombre>"
if (preg_match('/^horarios?\s+teatro\s+(.+)$/iu', $msg, $ma)) {
  return ['type' => 'horarios_por_teatro', 'teatro' => trim($ma[1])];
}


// "horarios del teatro <nombre>"
if (preg_match('/^horarios?\s+del\s+teatro\s+(.+)$/iu', $msg, $ma)) {
  return ['type' => 'horarios_por_teatro', 'teatro' => trim($ma[1])];
}
// ✅ NUEVO: "horarios del <nombre>" (sin 'teatro')
if (preg_match('/^horarios?\s+del\s+(.+)$/iu', $msg, $ma)) {
  return ['type' => 'horarios_por_teatro', 'teatro' => trim($ma[1])];
}

// ✅ "horarios <nombre_teatro>" (sin 'teatro')
if (preg_match('/^horarios?\s+(.+)$/iu', $msg, $ma)) {
  return ['type' => 'horarios_por_teatro', 'teatro' => trim($ma[1])];
}


  if (preg_match('/^horarios?\s+en\s+provincia\s+(.+)$/iu', $msg, $ma)) {
    return ['type' => 'horarios_por_provincia', 'provincia' => title_case_es(trim($ma[1]))];
  }

  if (preg_match('/^horarios?\s+para\s+obra\s+(.+)$/iu', $msg, $ma)) {
    return ['type' => 'horarios_por_obra', 'obra' => trim($ma[1])];
  }

  if (preg_match('/^(?:horarios?\s+del\s+)?(\d{4}-\d{2}-\d{2})$/iu', $msg, $ma)) {
    return ['type' => 'horarios_por_fecha', 'fecha' => trim($ma[1])];
  }

  // --- RANKING ---
  if (preg_match('/^(ranking|ranking usuarios|top usuarios)$/iu', $msg)) return ['type' => 'ranking_usuarios'];

  // --- RESUMEN / CONTADORES ---
  if (preg_match('/^resumen$/iu', $msg)) return ['type' => 'resumen_bbdd'];
  if (preg_match('/cu[aá]ntos\s+teatros/i', $m)) return ['type' => 'contar_teatros'];
  if (preg_match('/cu[aá]ntas\s+obras/i', $m)) return ['type' => 'contar_obras'];
  if (preg_match('/cu[aá]ntos\s+horarios/i', $m)) return ['type' => 'contar_horarios'];

  // fallback
  return ['type' => 'busqueda_general', 'q' => $msg];
}

function handle_intent(PDO $pdo, array $intent, string $userMsg): string {
  switch ($intent['type']) {

    case 'ayuda':
      return help_text();

    // ---------- PRECIO OBRA ----------
    case 'precio_obra': {
      $obra = $intent['obra'];

      $stmt = $pdo->prepare("
        SELECT h.FechaHora, h.Precio, t.Sala AS Teatro, t.Provincia, t.Municipio, o.Titulo AS Obra
        FROM horarios h
        JOIN teatros t ON t.idTeatro = h.idTeatro
        JOIN obras   o ON o.idObra   = h.idObra
        WHERE LOWER(o.Titulo) LIKE LOWER(:o)
          AND h.FechaHora >= NOW()
        ORDER BY h.FechaHora ASC
        LIMIT 40
      ");
      $stmt->execute([':o' => "%$obra%"]);
      $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

      if (!$rows) {
  // ✅ Fallback: buscar también pasados si no hay futuros
  $stmt2 = $pdo->prepare("
    SELECT h.FechaHora, h.Precio, t.Sala AS Teatro, t.Provincia, t.Municipio, o.Titulo AS Obra
    FROM horarios h
    JOIN teatros t ON t.idTeatro = h.idTeatro
    JOIN obras   o ON o.idObra   = h.idObra
    WHERE LOWER(o.Titulo) LIKE LOWER(:o)
    ORDER BY h.FechaHora DESC
    LIMIT 40
  ");
  $stmt2->execute([':o' => "%$obra%"]);
  $rows = $stmt2->fetchAll(PDO::FETCH_ASSOC);

  if (!$rows) {
    return "No encuentro ningún horario (ni pasado ni futuro) para la obra \"$obra\".\n\nPrueba: \"horarios para obra $obra\".";
  }
}


      $precios = array_values(array_filter(array_map(
        fn($r) => is_numeric($r['Precio']) ? (float)$r['Precio'] : null,
        $rows
      ), fn($v) => $v !== null));

      $min = $precios ? min($precios) : null;
      $max = $precios ? max($precios) : null;
      $avg = $precios ? array_sum($precios) / count($precios) : null;

      $oTitle = $rows[0]['Obra'];

      $out = [];
      $out[] = "💶 Precios para **$oTitle** (próximas funciones):";
      if ($min !== null && $max !== null && $avg !== null) {
        $out[] = "• Mín: " . number_format($min, 2, '.', '') . "€ · Máx: " . number_format($max, 2, '.', '') . "€ · Media: " . number_format($avg, 2, '.', '') . "€";
      }

      $out[] = "";
      $out[] = "📅 Próximas funciones (con precio):";
      foreach ($rows as $r) {
        $p = is_numeric($r['Precio']) ? number_format((float)$r['Precio'], 2, '.', '') : (string)$r['Precio'];
        $out[] = "• {$r['FechaHora']} — {$r['Teatro']} ({$r['Municipio']}, {$r['Provincia']}) · {$p}€";
      }

      return implode("\n", $out);
    }

    // ---------- TEATROS ----------
    case 'teatros_por_provincia': {
      $prov = $intent['provincia'];
      $stmt = $pdo->prepare("SELECT Sala, Municipio, Direccion, Telefono, Email, CapacidadMax
                             FROM teatros
                             WHERE Provincia = :prov
                             ORDER BY Sala
                             LIMIT 30");
      $stmt->execute([':prov' => $prov]);
      $rows = $stmt->fetchAll();
      return reply_teatros("Teatros en $prov", $rows, false);
    }

    case 'teatros_por_municipio': {
      $mun = $intent['municipio'];
      $stmt = $pdo->prepare("SELECT Sala, Provincia, Municipio, Direccion, Telefono, Email, CapacidadMax
                             FROM teatros
                             WHERE Municipio LIKE :mun
                             ORDER BY Sala
                             LIMIT 30");
      $stmt->execute([':mun' => "%$mun%"]);
      $rows = $stmt->fetchAll();
      return reply_teatros("Teatros en el municipio \"$mun\"", $rows, true);
    }

    case 'teatro_por_nombre': {
      $q = $intent['q'];
      $stmt = $pdo->prepare("SELECT Sala, Provincia, Municipio, Direccion, CP, Telefono, Email, CapacidadMax
                             FROM teatros
                             WHERE Sala LIKE :q
                             ORDER BY Provincia, Municipio, Sala
                             LIMIT 15");
      $stmt->execute([':q' => "%$q%"]);
      $rows = $stmt->fetchAll();
      if (!$rows) return "No encuentro ningún teatro que coincida con \"$q\".\n\nPrueba: \"teatros en Salamanca\" o \"teatros en municipio Salamanca\".";
      return reply_teatros("Coincidencias de teatro: \"$q\"", $rows, true, true);
    }

    case 'teatros_por_capacidad': {
      $min = (int)$intent['min'];
      $stmt = $pdo->prepare("SELECT Sala, Provincia, Municipio, CapacidadMax
                             FROM teatros
                             WHERE CapacidadMax >= :min
                             ORDER BY CapacidadMax DESC
                             LIMIT 30");
      $stmt->execute([':min' => $min]);
      $rows = $stmt->fetchAll();
      if (!$rows) return "No hay teatros con capacidad ≥ $min.";
      return reply_simple_list("Teatros con capacidad ≥ $min", $rows, function($t){
        return "• {$t['Sala']} — {$t['Municipio']} ({$t['Provincia']}) · Capacidad {$t['CapacidadMax']}";
      });
    }

    case 'listar_provincias': {
      $rows = $pdo->query("SELECT Provincia, COUNT(*) AS Num
                           FROM teatros
                           GROUP BY Provincia
                           ORDER BY Num DESC, Provincia ASC")->fetchAll();
      return reply_simple_list("Provincias con teatros", $rows, fn($r) => "• {$r['Provincia']} — {$r['Num']}");
    }

    case 'listar_municipios': {
      $rows = $pdo->query("SELECT Municipio, Provincia, COUNT(*) AS Num
                           FROM teatros
                           GROUP BY Municipio, Provincia
                           ORDER BY Num DESC, Municipio ASC
                           LIMIT 25")->fetchAll();
      return reply_simple_list("Municipios con teatros (top)", $rows, fn($r) => "• {$r['Municipio']} ({$r['Provincia']}) — {$r['Num']}");
    }

    // ---------- OBRAS ----------
    case 'obras_en_provincia': {
      $prov = $intent['provincia'];

      $stmt = $pdo->prepare("
        SELECT DISTINCT o.Titulo, o.Autor, o.Anio
        FROM horarios h
        JOIN teatros t ON t.idTeatro = h.idTeatro
        JOIN obras o   ON o.idObra   = h.idObra
        WHERE t.Provincia = :p
        ORDER BY o.Anio IS NULL, o.Anio DESC, o.Titulo ASC
        LIMIT 40
      ");
      $stmt->execute([':p' => $prov]);
      $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

      if (!$rows) return "No hay obras con horarios registradas en la provincia de $prov.";

      return reply_simple_list("Obras con funciones en $prov", $rows, function($o){
        $y = !empty($o['Anio']) ? " ({$o['Anio']})" : "";
        return "• {$o['Titulo']} — {$o['Autor']}{$y}";
      });
    }

    case 'obras_en_teatro': {
      $teatro = $intent['teatro'];

      $stmt = $pdo->prepare("
        SELECT DISTINCT o.Titulo, o.Autor, o.Anio, t.Sala AS Teatro, t.Provincia, t.Municipio
        FROM horarios h
        JOIN teatros t ON t.idTeatro = h.idTeatro
        JOIN obras o   ON o.idObra   = h.idObra
        WHERE LOWER(t.Sala) LIKE LOWER(:s)
        ORDER BY o.Anio IS NULL, o.Anio DESC, o.Titulo ASC
        LIMIT 40
      ");
      $stmt->execute([':s' => "%$teatro%"]);
      $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

      if (!$rows) return "No encuentro obras (con horarios) para el teatro \"$teatro\".\n\nPrueba: \"obras en teatro $teatro\".";

      $t0 = $rows[0];
      $titulo = "Obras con funciones en {$t0['Teatro']} ({$t0['Municipio']}, {$t0['Provincia']})";

      return reply_simple_list($titulo, $rows, function($o){
        $y = !empty($o['Anio']) ? " ({$o['Anio']})" : "";
        return "• {$o['Titulo']} — {$o['Autor']}{$y}";
      });
    }

    case 'obra_por_titulo': {
      $titulo = $intent['titulo'];
      $stmt = $pdo->prepare("SELECT idObra, Titulo, Autor, Subtitulo, Anio
                             FROM obras
                             WHERE Titulo LIKE :t
                             ORDER BY Anio IS NULL, Anio DESC
                             LIMIT 10");
      $stmt->execute([':t' => "%$titulo%"]);
      $rows = $stmt->fetchAll();
      return reply_obras("Obras que coinciden con \"$titulo\"", $rows);
    }

    case 'obras_por_autor': {
      $autor = $intent['autor'];
      $stmt = $pdo->prepare("SELECT idObra, Titulo, Autor, Subtitulo, Anio
                             FROM obras
                             WHERE LOWER(REPLACE(Autor, ',', '')) LIKE LOWER(:a)
                             ORDER BY Anio IS NULL, Anio DESC
                             LIMIT 40");
      $stmt->execute([':a' => '%' . str_replace(',', '', $autor) . '%']);
      $rows = $stmt->fetchAll();
      if (!$rows) {
        $tops = $pdo->query("SELECT Autor, COUNT(*) AS Num
                             FROM obras
                             GROUP BY Autor
                             ORDER BY Num DESC, Autor ASC
                             LIMIT 8")->fetchAll();
        $out = [];
        $out[] = "No he encontrado obras de \"$autor\".";
        $out[] = "Autores disponibles (ejemplos):";
        foreach ($tops as $a) $out[] = "• {$a['Autor']} ({$a['Num']})";
        return implode("\n", $out);
      }
      return reply_obras_list("Obras de \"$autor\"", $rows);
    }

    case 'obras_por_anio': {
      $anio = (int)$intent['anio'];
      $stmt = $pdo->prepare("SELECT Titulo, Autor, Anio
                             FROM obras
                             WHERE Anio = :a
                             ORDER BY Titulo
                             LIMIT 40");
      $stmt->execute([':a' => $anio]);
      $rows = $stmt->fetchAll();
      if (!$rows) return "No hay obras registradas del año $anio.";
      return reply_simple_list("Obras del año $anio", $rows, fn($o) => "• {$o['Titulo']} — {$o['Autor']}");
    }

    case 'obras_por_decada': {
      $dec = (int)$intent['decada'];
      $stmt = $pdo->prepare("SELECT Titulo, Autor, Anio
                             FROM obras
                             WHERE Anio BETWEEN :a AND :b
                             ORDER BY Anio ASC, Titulo ASC
                             LIMIT 60");
      $stmt->execute([':a' => $dec, ':b' => $dec + 9]);
      $rows = $stmt->fetchAll();
      if (!$rows) return "No hay obras registradas en la década de {$dec}s.";
      return reply_simple_list("Obras de la década {$dec}s", $rows, function($o){
        $y = $o['Anio'] ? " ({$o['Anio']})" : "";
        return "• {$o['Titulo']} — {$o['Autor']}{$y}";
      });
    }

    case 'listar_autores': {
      $rows = $pdo->query("SELECT Autor, COUNT(*) AS Num
                           FROM obras
                           GROUP BY Autor
                           ORDER BY Num DESC, Autor ASC
                           LIMIT 25")->fetchAll();
      return reply_simple_list("Autores (top)", $rows, fn($a) => "• {$a['Autor']} — {$a['Num']} obras");
    }

    // ---------- HORARIOS ----------
    case 'horarios_hoy': {
      $rows = $pdo->query("SELECT h.FechaHora, h.Precio, t.Sala AS Teatro, t.Provincia, t.Municipio, o.Titulo AS Obra
                           FROM horarios h
                           JOIN teatros t ON t.idTeatro = h.idTeatro
                           JOIN obras o ON o.idObra = h.idObra
                           WHERE DATE(h.FechaHora) = CURDATE()
                           ORDER BY h.FechaHora
                           LIMIT 50")->fetchAll();
      return reply_horarios("Cartelera de hoy", $rows);
    }

    case 'horarios_manana': {
      $rows = $pdo->query("SELECT h.FechaHora, h.Precio, t.Sala AS Teatro, t.Provincia, t.Municipio, o.Titulo AS Obra
                           FROM horarios h
                           JOIN teatros t ON t.idTeatro = h.idTeatro
                           JOIN obras o ON o.idObra = h.idObra
                           WHERE DATE(h.FechaHora) = DATE_ADD(CURDATE(), INTERVAL 1 DAY)
                           ORDER BY h.FechaHora
                           LIMIT 50")->fetchAll();
      return reply_horarios("Cartelera de mañana", $rows);
    }

    case 'horarios_semana': {
      $rows = $pdo->query("SELECT h.FechaHora, h.Precio, t.Sala AS Teatro, t.Provincia, t.Municipio, o.Titulo AS Obra
                           FROM horarios h
                           JOIN teatros t ON t.idTeatro = h.idTeatro
                           JOIN obras o ON o.idObra = h.idObra
                           WHERE h.FechaHora >= NOW() AND h.FechaHora < DATE_ADD(NOW(), INTERVAL 7 DAY)
                           ORDER BY h.FechaHora
                           LIMIT 80")->fetchAll();
      return reply_horarios("Cartelera (próximos 7 días)", $rows);
    }

    case 'horarios_por_teatro': {
      $teatro = $intent['teatro'];
      $stmt = $pdo->prepare("SELECT h.FechaHora, h.Precio, t.Sala AS Teatro, t.Provincia, t.Municipio, o.Titulo AS Obra
                             FROM horarios h
                             JOIN teatros t ON t.idTeatro = h.idTeatro
                             JOIN obras o ON o.idObra = h.idObra
                             WHERE LOWER(t.Sala) LIKE LOWER(:s)
                             ORDER BY h.FechaHora
                             LIMIT 60");
      $stmt->execute([':s' => "%$teatro%"]);
      $rows = $stmt->fetchAll();
      if (!$rows) return "No encuentro horarios para \"$teatro\".\n\nPrueba con: \"horarios teatro $teatro\" o \"teatro $teatro\".";
      return reply_horarios("Horarios del teatro \"$teatro\"", $rows);
    }

    case 'horarios_por_provincia': {
      $prov = $intent['provincia'];
      $stmt = $pdo->prepare("SELECT h.FechaHora, h.Precio, t.Sala AS Teatro, t.Provincia, t.Municipio, o.Titulo AS Obra
                             FROM horarios h
                             JOIN teatros t ON t.idTeatro = h.idTeatro
                             JOIN obras o ON o.idObra = h.idObra
                             WHERE t.Provincia = :p AND h.FechaHora >= NOW()
                             ORDER BY h.FechaHora
                             LIMIT 80");
      $stmt->execute([':p' => $prov]);
      $rows = $stmt->fetchAll();
      return reply_horarios("Próximos horarios en la provincia de $prov", $rows);
    }

    case 'horarios_por_obra': {
      $obra = $intent['obra'];
      $stmt = $pdo->prepare("SELECT h.FechaHora, h.Precio, t.Sala AS Teatro, t.Provincia, t.Municipio, o.Titulo AS Obra
                             FROM horarios h
                             JOIN teatros t ON t.idTeatro = h.idTeatro
                             JOIN obras o ON o.idObra = h.idObra
                             WHERE LOWER(o.Titulo) LIKE LOWER(:o)
                             ORDER BY h.FechaHora
                             LIMIT 80");
      $stmt->execute([':o' => "%$obra%"]);
      $rows = $stmt->fetchAll();
      if (!$rows) return "No encuentro horarios para la obra \"$obra\".";
      return reply_horarios("Horarios para la obra \"$obra\"", $rows);
    }

    case 'horarios_por_fecha': {
      $fecha = $intent['fecha'];
      $stmt = $pdo->prepare("SELECT h.FechaHora, h.Precio, t.Sala AS Teatro, t.Provincia, t.Municipio, o.Titulo AS Obra
                             FROM horarios h
                             JOIN teatros t ON t.idTeatro = h.idTeatro
                             JOIN obras o ON o.idObra = h.idObra
                             WHERE DATE(h.FechaHora) = :f
                             ORDER BY h.FechaHora
                             LIMIT 80");
      $stmt->execute([':f' => $fecha]);
      $rows = $stmt->fetchAll();
      return reply_horarios("Cartelera del día $fecha", $rows);
    }

    // ---------- RANKING ----------
    case 'ranking_usuarios': {
      $rows = $pdo->query("SELECT Nombre, Puntos
                           FROM usuarios
                           ORDER BY Puntos DESC
                           LIMIT 10")->fetchAll();
      if (!$rows) return "No hay usuarios en el ranking todavía.";
      $out = ["🏆 Ranking de usuarios:"];
      $i = 1;
      foreach ($rows as $u) {
        $out[] = "{$i}. {$u['Nombre']} — {$u['Puntos']} pts";
        $i++;
      }
      return implode("\n", $out);
    }

    // ---------- RESUMEN / CONTADORES ----------
    case 'resumen_bbdd': {
      $t = (int)$pdo->query("SELECT COUNT(*) FROM teatros")->fetchColumn();
      $o = (int)$pdo->query("SELECT COUNT(*) FROM obras")->fetchColumn();
      $h = (int)$pdo->query("SELECT COUNT(*) FROM horarios")->fetchColumn();
      $u = (int)$pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
      return "📊 Resumen BBDD:\n• Teatros: $t\n• Obras: $o\n• Horarios: $h\n• Usuarios: $u";
    }
    case 'contar_teatros':
      return "Hay " . (int)$pdo->query("SELECT COUNT(*) FROM teatros")->fetchColumn() . " teatros.";
    case 'contar_obras':
      return "Hay " . (int)$pdo->query("SELECT COUNT(*) FROM obras")->fetchColumn() . " obras.";
    case 'contar_horarios':
      return "Hay " . (int)$pdo->query("SELECT COUNT(*) FROM horarios")->fetchColumn() . " horarios.";

    // ---------- BÚSQUEDA GENERAL ----------
    case 'busqueda_general': {
      $q = trim((string)$intent['q']);
      return general_search($pdo, $q);
    }

    default:
      return help_text();
  }
}

/* =========================================================
   RESPUESTAS
========================================================= */

function help_text(): string {
  return implode("\n", [
    "Puedo buscar información en la base de datos de Red Teatros.",
    "",
    "Ejemplos de preguntas:",
    "• teatros en Salamanca",
    
    "• teatro Principal",
    "• capacidad mayor que 300",
    "• obra El refugio",
    "• obras de Federico García Lorca",
    "• obras en teatro Lope de Vega (o: \"obras lope de vega\")",
    "• obras del año 1938",
    "• obras de 1930s (o: \"obras 1930s\")",
    "• cartelera hoy / cartelera mañana / cartelera semana",
    "• horarios del teatro Principal (o: \"horarios teatro Clunia\")",
    "• horarios para obra El refugio",
    "• precio obra El refugio (o: \"precio de obra El refugio\")",
    "• ranking usuarios",
    "• resumen",
    "",
    "Si escribes algo suelto, también hago búsqueda general."
  ]);
}
function pad_right(string $s, int $len): string {
  $s = trim($s);
  $w = mb_strlen($s);
  if ($w >= $len) return $s;
  return $s . str_repeat(' ', $len - $w);
}
function e(string $s): string {
  return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function p(string $text): string {
  // párrafo: separa cada registro con línea en blanco
  return rtrim($text) . "\n\n";
}

function h2(string $text): string {
  // título + salto
  return rtrim($text) . "\n";
}

function hr_small(): string {
  // separador sencillo
  return "------------------------------\n\n";
}


function reply_teatros(string $title, array $rows, bool $showProv = false, bool $detailed = false): string {
  if (!$rows) {
    return h2("🎭 $title") . p("No he encontrado resultados.");
  }

  $out = [];
  $out[] = h2("🎭 $title (" . count($rows) . ")");
  $out[] = "";

  $i = 1;
  foreach ($rows as $t) {
    $name = (string)($t['Sala'] ?? '');
    $mun  = (string)($t['Municipio'] ?? '');
    $prov = (string)($t['Provincia'] ?? '');
    $cap  = !empty($t['CapacidadMax']) ? (string)$t['CapacidadMax'] : '';

    $loc = $mun;
    if ($showProv && $prov !== '') $loc .= " ($prov)";

    $para = "{$i}. $name\n📍 $loc";
    if ($cap !== '') $para .= " · 👥 $cap";

    if ($detailed) {
      if (!empty($t['Direccion'])) $para .= "\n🧭 " . $t['Direccion'];
      if (!empty($t['Telefono']))  $para .= "\n☎️ " . $t['Telefono'];
      if (!empty($t['Email']))     $para .= "\n✉️ " . $t['Email'];
    }

    $out[] = p($para);
    $i++;
  }

  return rtrim(implode("", $out));
}


function reply_obras(string $title, array $rows): string {
  if (!$rows) {
    return h2("📚 " . e($title)) . p("No he encontrado obras que coincidan.<br><br>Prueba: <code>obra El refugio</code> o <code>obras de Federico García Lorca</code>.");
  }

  if (count($rows) === 1) {
    $o = $rows[0];
    $out = [];
    $out[] = h2("🎭 " . e((string)$o['Titulo']));
    $out[] = p("👤 Autor: " . e((string)($o['Autor'] ?? '-')));
    if (!empty($o['Anio']))      $out[] = p("📅 Año: " . e((string)$o['Anio']));
    if (!empty($o['Subtitulo'])) $out[] = p("📝 " . e((string)$o['Subtitulo']));
    return implode("", $out);
  }

  return reply_obras_list($title, $rows);
}

function reply_obras_list(string $title, array $rows): string {
  $out = [];
  $out[] = h2("📚 $title (" . count($rows) . ")");
  $out[] = "";

  $i = 1;
  foreach ($rows as $o) {
    $titulo = (string)($o['Titulo'] ?? '');
    $anio   = !empty($o['Anio']) ? " (" . $o['Anio'] . ")" : "";
    $autor  = !empty($o['Autor']) ? " — " . $o['Autor'] : "";
    $out[] = p("$i. $titulo$anio$autor");
    $i++;
  }

  return rtrim(implode("", $out));
}



function reply_horarios(string $title, array $rows): string {
  if (!$rows) {
    return h2("📅 $title") . p("No hay funciones registradas.");
  }

  $out = [];
  $out[] = h2("📅 $title (" . count($rows) . ")");
  $out[] = "";

  foreach ($rows as $h) {
    $fh     = (string)($h['FechaHora'] ?? '');
    $obra   = (string)($h['Obra'] ?? '');
    $teatro = (string)($h['Teatro'] ?? '');
    $mun    = (string)($h['Municipio'] ?? '');
    $prov   = (string)($h['Provincia'] ?? '');
    $precio = (string)($h['Precio'] ?? '');

   $out[] = p("$fh\n🎭 $obra\n🏛️ $teatro ($mun, $prov)\n💶 {$precio}€");

  }

  return rtrim(implode("", $out));
}


function reply_simple_list(string $title, array $rows, callable $fmt): string {
  if (!$rows) {
    return h2("📌 $title") . p("No hay resultados.");
  }

  $out = [];
  $out[] = h2("📌 $title (" . count($rows) . ")");
  $out[] = "";

  foreach ($rows as $r) {
    $out[] = p((string)$fmt($r));
  }

  return rtrim(implode("", $out));
}



/* =========================================================
   BÚSQUEDA GENERAL (teatros + obras + horarios)
========================================================= */

function general_search(PDO $pdo, string $q): string {
  $qTrim = trim($q);
  if ($qTrim === '') return help_text();

  // 1) busca teatro por nombre
  $stmt = $pdo->prepare("SELECT Sala, Provincia, Municipio, CapacidadMax
                         FROM teatros
                         WHERE Sala LIKE :q
                         ORDER BY Provincia, Municipio, Sala
                         LIMIT 6");
  $stmt->execute([':q' => "%$qTrim%"]);
  $teatros = $stmt->fetchAll();

  // 2) busca obra por título o autor
  $stmt = $pdo->prepare("SELECT Titulo, Autor, Anio
                         FROM obras
                         WHERE Titulo LIKE :q OR Autor LIKE :q
                         ORDER BY Anio IS NULL, Anio DESC
                         LIMIT 6");
  $stmt->execute([':q' => "%$qTrim%"]);
  $obras = $stmt->fetchAll();

  // 3) busca horarios próximos por obra o teatro
  $stmt = $pdo->prepare("SELECT h.FechaHora, h.Precio, t.Sala AS Teatro, t.Provincia, t.Municipio, o.Titulo AS Obra
                         FROM horarios h
                         JOIN teatros t ON t.idTeatro = h.idTeatro
                         JOIN obras o ON o.idObra = h.idObra
                         WHERE (t.Sala LIKE :q OR o.Titulo LIKE :q)
                           AND h.FechaHora >= NOW()
                         ORDER BY h.FechaHora
                         LIMIT 8");
  $stmt->execute([':q' => "%$qTrim%"]);
  $hor = $stmt->fetchAll();

  if (!$teatros && !$obras && !$hor) {
    return "No encuentro resultados para \"$qTrim\".\n\nPrueba: \"teatros en Salamanca\", \"obra El refugio\" o \"cartelera hoy\".";
  }

  $out = ["🔎 Resultados para \"$qTrim\":"];

  if ($teatros) {
    $out[] = "";
    $out[] = "🎭 Teatros:";
    foreach ($teatros as $t) {
      $cap = !empty($t['CapacidadMax']) ? $t['CapacidadMax'] : '?';
      $out[] = "• {$t['Sala']} — {$t['Municipio']} ({$t['Provincia']}) · Cap. {$cap}";
    }
  }

  if ($obras) {
    $out[] = "";
    $out[] = "📚 Obras:";
    foreach ($obras as $o) {
      $y = $o['Anio'] ? " ({$o['Anio']})" : "";
      $out[] = "• {$o['Titulo']} — {$o['Autor']}{$y}";
    }
  }

  if ($hor) {
    $out[] = "";
    $out[] = "📅 Próximos horarios:";
    foreach ($hor as $h) {
      $out[] = "• {$h['FechaHora']} — {$h['Obra']} — {$h['Teatro']} ({$h['Municipio']}, {$h['Provincia']}) · {$h['Precio']}€";
    }
  }

  return implode("\n", $out);
}

/* =========================================================
   UTILS
========================================================= */

function title_case_es(string $s): string {
  $s = mb_strtolower($s);
  $words = preg_split('/\s+/', $s);
  $words = array_map(fn($w) => mb_strtoupper(mb_substr($w, 0, 1)) . mb_substr($w, 1), $words);
  return implode(' ', $words);
}
