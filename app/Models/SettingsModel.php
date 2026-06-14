<?php

class SettingsModel
{
    public function __construct(private PDO $pdo)
    {
    }

    public function deleteUser(string $userId): bool
    {
        $stmt = $this->pdo->prepare("
            delete from public.users
            where id = :user_id
        ");

        $stmt->execute([
            ':user_id' => $userId,
        ]);

        return $stmt->rowCount() > 0;
    }
}
