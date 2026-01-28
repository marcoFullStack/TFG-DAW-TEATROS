<?php
// DAO/UsuarioDAO.php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../models/Usuario.php';

final class UsuarioDAO {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function buscarPorEmail(string $email): ?array {
        $sql = "SELECT * FROM usuarios WHERE Email = ? LIMIT 1";
        $st = $this->pdo->prepare($sql);
        $st->execute([$email]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function buscarPerfilPorId(int $idUsuario): ?array {
        $sql = "SELECT idUsuario, Nombre, Email, FotoPerfil, Puntos, FechaAlta
                FROM usuarios WHERE idUsuario = ? LIMIT 1";
        $st = $this->pdo->prepare($sql);
        $st->execute([$idUsuario]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function insertar(Usuario $u): bool {
        $sql = "INSERT INTO usuarios (Nombre, Email, PasswordHash, FotoPerfil)
                VALUES (?, ?, ?, ?)";
        $st = $this->pdo->prepare($sql);

        $ok = $st->execute([
            $u->getNombre(),
            $u->getEmail(),
            $u->getPasswordHash(),
            $u->getFotoPerfil()
        ]);

        if ($ok) {
            $u->setIdUsuario((int)$this->pdo->lastInsertId());
        }

        return $ok;
    }
}
