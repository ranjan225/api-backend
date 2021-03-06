<?php

declare(strict_types=1);

namespace Reconmap\Repositories;

class AuditLogRepository
{

    private $db;

    public function __construct(\mysqli $db)
    {
        $this->db = $db;
    }

    public function findAll(int $page = 0): array
    {
        $sql = <<<SQL
        SELECT al.insert_ts, INET_NTOA(al.client_ip) AS client_ip, al.action, u.id AS user_id, u.name, u.role
        FROM audit_log al
        INNER JOIN user u ON (u.id = al.user_id)
        ORDER BY al.insert_ts DESC
        LIMIT ?, ?
        SQL;

        $limitPerPage = 20;
        $limitOffset = $page * $limitPerPage;

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ii', $limitOffset, $limitPerPage);
        $stmt->execute();
        $rs = $stmt->get_result();
        $rows = $rs->fetch_all(MYSQLI_ASSOC);
        return $rows;
    }

    public function countAll(): int
    {
        $sql = <<<SQL
        SELECT COUNT(*) AS total
        FROM audit_log al
        INNER JOIN user u ON (u.id = al.user_id)
        SQL;

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $rs = $stmt->get_result();
        $row = $rs->fetch_assoc();
        return (int)$row['total'];
    }

    public function findByUserId(int $userId): array
    {
        $sql = <<<SQL
        SELECT al.insert_ts, INET_NTOA(al.client_ip) AS client_ip, al.action, u.id AS user_id, u.name, u.role
        FROM audit_log al
        INNER JOIN user u ON (u.id = al.user_id)
        WHERE al.user_id = ?
        ORDER BY al.insert_ts DESC
        SQL;

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $rs = $stmt->get_result();
        $rows = $rs->fetch_all(MYSQLI_ASSOC);
        return $rows;
    }

    public function findCountByDayStats(): array
    {
        $sql = <<<SQL
        SELECT DATE(insert_ts) AS log_date, COUNT(*) AS total
        FROM audit_log
        GROUP BY log_date
        ORDER BY log_date ASC
        SQL;

        $rs = $this->db->query($sql);
        $rows = $rs->fetch_all(MYSQLI_ASSOC);
        return $rows;
    }

    public function insert(int $userId, string $clientIp, string $action): void
    {
        $stmt = $this->db->prepare('INSERT INTO audit_log (user_id, client_ip, action) VALUES (?, INET_ATON(?), ?)');
        $stmt->bind_param('iss', $userId, $clientIp, $action);
        if (false === $stmt->execute()) {
            throw new \Exception($stmt->error);
        }
        $stmt->close();
    }
}
