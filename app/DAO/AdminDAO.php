<?php
// DAO/AdminDAO.php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php'; // deja $pdo disponible :contentReference[oaicite:5]{index=5}

final class AdminDAO {
  private PDO $pdo;

  public function __construct(PDO $pdo) { $this->pdo = $pdo; }

  public function buscarPorEmail(string $email): ?array {
    $sql = "SELECT * FROM admins WHERE Email = ? LIMIT 1";
    $st = $this->pdo->prepare($sql);
    $st->execute([$email]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
  }

  public function insertar(string $nombre, string $email, string $passwordHash): bool {
    $sql = "INSERT INTO admins (Nombre, Email, PasswordHash) VALUES (?, ?, ?)";
    $st = $this->pdo->prepare($sql);
    return $st->execute([$nombre, $email, $passwordHash]);
  }

  public function actualizarUltimoLogin(int $idAdmin): void {
    $sql = "UPDATE admins SET ultimo_login = NOW() WHERE idAdmin = ?";
    $st = $this->pdo->prepare($sql);
    $st->execute([$idAdmin]);
  }
}
