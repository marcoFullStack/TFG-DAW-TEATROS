<?php
// DAO/UsuarioDAO.php
declare(strict_types=1);

require_once __DIR__ . '/../models/Usuario.php';

final class UsuarioDAO {
  private PDO $pdo;

  public function __construct(PDO $pdo) { $this->pdo = $pdo; }
  public function buscarPorEmail(string $email): ?array {
  $sql = "SELECT * FROM usuarios WHERE Email = ? LIMIT 1";
  $st = $this->pdo->prepare($sql);
  $st->execute([$email]);
  $row = $st->fetch(PDO::FETCH_ASSOC);
  return $row ?: null;
}

  public function insertar(Usuario $u): int {
  $sql = "INSERT INTO usuarios (Nombre, Email, PasswordHash, FotoPerfil, Puntos, FechaAlta)
          VALUES (?, ?, ?, ?, ?, COALESCE(?, CURRENT_DATE))";

  $st = $this->pdo->prepare($sql);
  $ok = $st->execute([
    $u->getNombre(),
    $u->getEmail(),
    $u->getPasswordHash(),
    $u->getFotoPerfil(),           // null si no hay
    $u->getPuntos(),
    $u->getFechaAlta()             // null => CURRENT_DATE
  ]);

  if (!$ok) return 0;

  $id = (int)$this->pdo->lastInsertId();
  $u->setIdUsuario($id);
  return $id;
}

  public function obtenerPorId(int $idUsuario): ?Usuario {
    $sql = "SELECT idUsuario, Nombre, Email, PasswordHash, FotoPerfil, Puntos, FechaAlta
            FROM usuarios WHERE idUsuario=? LIMIT 1";
    $st = $this->pdo->prepare($sql);
    $st->execute([$idUsuario]);
    $r = $st->fetch(PDO::FETCH_ASSOC);
    if (!$r) return null;

    return new Usuario(
      nombre: (string)$r['Nombre'],
      email: (string)$r['Email'],
      passwordHash: (string)$r['PasswordHash'],
      fotoPerfil: $r['FotoPerfil'] !== null ? (string)$r['FotoPerfil'] : null,
      idUsuario: (int)$r['idUsuario'],
      puntos: (int)($r['Puntos'] ?? 0),
      fechaAlta: $r['FechaAlta'] !== null ? (string)$r['FechaAlta'] : null
    );
  }

  public function actualizarFotoPerfil(int $idUsuario, ?string $fotoRel): bool {
    $st = $this->pdo->prepare("UPDATE usuarios SET FotoPerfil=? WHERE idUsuario=?");
    return $st->execute([$fotoRel, $idUsuario]);
  }

  public function sumarPuntos(int $idUsuario, int $puntos): void {
    $st = $this->pdo->prepare("UPDATE usuarios SET Puntos = Puntos + ? WHERE idUsuario=?");
    $st->execute([$puntos, $idUsuario]);
  }

  public function restarPuntosNoNegativo(int $idUsuario, int $puntos): void {
    $st = $this->pdo->prepare("UPDATE usuarios SET Puntos = GREATEST(Puntos - ?, 0) WHERE idUsuario=?");
    $st->execute([$puntos, $idUsuario]);
  }
}
