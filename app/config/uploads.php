<?php
// app/config/uploads.php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

function app_root_path(): string {
  return realpath(__DIR__ . '/..') ?: (__DIR__ . '/..');
}

function ensure_dir(string $dir): void {
  if (!is_dir($dir)) mkdir($dir, 0777, true);
}

function is_allowed_image(string $filename): bool {
  $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
  return in_array($ext, ['jpg','jpeg','png','webp'], true);
}

/**
 * Guarda una imagen subida en subcarpeta.
 * Devuelve ruta relativa (desde /app/) para guardar en BD.
 */
function save_uploaded_image(array $file, string $relativeBaseDir, string $subfolder): ?string {
  if (empty($file['name']) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) return null;
  if (!is_allowed_image($file['name'])) return null;

  $size = (int)($file['size'] ?? 0);
  if ($size <= 0 || $size > 5 * 1024 * 1024) return null; // 5MB

  $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
  $safeName = 'img_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;

  $baseAbs = rtrim(app_root_path(), '/\\') . DIRECTORY_SEPARATOR . trim($relativeBaseDir, '/\\');
  $targetAbsDir = $baseAbs . DIRECTORY_SEPARATOR . trim($subfolder, '/\\');

  ensure_dir($targetAbsDir);

  $tmp = (string)$file['tmp_name'];
  $destAbs = $targetAbsDir . DIRECTORY_SEPARATOR . $safeName;

  if (!move_uploaded_file($tmp, $destAbs)) return null;

  // ruta relativa desde app/
  $rel = trim($relativeBaseDir, '/\\') . '/' . trim($subfolder, '/\\') . '/' . $safeName;
  return preg_replace('#/+#', '/', $rel);
}

/** Convierte ruta relativa (desde app/) a URL web completa */
function rel_to_url(string $rel): string {
  $rel = ltrim($rel, '/');
  return BASE_URL . $rel;
}

/** Borra un archivo dado por ruta relativa desde app/ (si existe) */
function delete_rel_file(string $rel): void {
  $abs = rtrim(app_root_path(), '/\\') . DIRECTORY_SEPARATOR . ltrim($rel, '/\\');
  if (is_file($abs)) @unlink($abs);
}
