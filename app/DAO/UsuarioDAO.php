<?php
// DAO/UsuarioDAO.php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php'; // deja $pdo disponible :contentReference[oaicite:4]{index=4}

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

  public function buscarPerfilPorId(int $idUsuario): ?array {
    $sql = "SELECT idUsuario, Nombre, Email, FotoPerfil, Puntos, FechaAlta FROM usuarios WHERE idUsuario = ? LIMIT 1";
    $st = $this->pdo->prepare($sql);
    $st->execute([$idUsuario]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
  }

  public function insertar(string $nombre, string $email, string $passwordHash, ?string $fotoPerfil = null): bool {
    $sql = "INSERT INTO usuarios (Nombre, Email, PasswordHash, FotoPerfil) VALUES (?, ?, ?, ?)";
    $st = $this->pdo->prepare($sql);
    return $st->execute([$nombre, $email, $passwordHash, $fotoPerfil]);
  }
}
