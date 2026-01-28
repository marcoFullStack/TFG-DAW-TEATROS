<?php
// app/DAO/AdminDAO.php
declare(strict_types=1);

require_once __DIR__ . '/../models/Admin.php';

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

  public function insertar(Admin $a): bool {
    $sql = "INSERT INTO admins (Nombre, Email, PasswordHash) VALUES (?, ?, ?)";
    $st = $this->pdo->prepare($sql);

    $ok = $st->execute([
      $a->getNombre(),
      $a->getEmail(),
      $a->getPasswordHash()
    ]);

    if ($ok) {
      $a->setIdAdmin((int)$this->pdo->lastInsertId());
    }

    return $ok;
  }

  public function actualizarUltimoLogin(int $idAdmin): void {
    $sql = "UPDATE admins SET ultimo_login = NOW() WHERE idAdmin = ?";
    $st = $this->pdo->prepare($sql);
    $st->execute([$idAdmin]);
  }
}
