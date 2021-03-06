<?php

declare(strict_types=1);

namespace Reconmap\Repositories;

class ProjectRepository
{

    private $db;

    public function __construct(\mysqli $db)
    {
        $this->db = $db;
    }

    public function findAll(): array
    {
        $rs = $this->db->query('SELECT * FROM project LIMIT 20');
        $projects = $rs->fetch_all(MYSQLI_ASSOC);
        return $projects;
    }

    public function findById(int $id): array
    {
        $stmt = $this->db->prepare('SELECT * FROM project WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $rs = $stmt->get_result();
        $project = $rs->fetch_assoc();
        $stmt->close();

        return $project;
    }

    public function findTemplateProjects(int $isTemplate): array
    {
        $sql = <<<SQL
        SELECT
            *, (SELECT COUNT(*) FROM task WHERE project_id = id) AS num_tasks
        FROM project
        WHERE is_template = ?
        SQL;
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $isTemplate);
        $stmt->execute();
        $rs = $stmt->get_result();
        $projects = $rs->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $projects;
    }

    public function createFromTemplate(int $templateId): array
    {
        $this->db->begin_transaction();

        $projectSql = <<<SQL
        INSERT INTO project (name, description) SELECT CONCAT(name, ' - ', CURRENT_TIMESTAMP()), description FROM project WHERE id = ?
        SQL;
        $stmt = $this->db->prepare($projectSql);
        $stmt->bind_param('i', $templateId);
        $stmt->execute();
        $projectId = $stmt->insert_id;
        $stmt->close();

        $tasksSql = <<<SQL
        INSERT INTO task (project_id, parser, name, description) SELECT ?, parser, name, description FROM task WHERE project_id = ?
        SQL;
        $stmt = $this->db->prepare($tasksSql);
        $stmt->bind_param('ii', $projectId, $templateId);
        $stmt->execute();
        $stmt->close();

        $this->db->commit();

        return [
            'projectId' => $projectId
        ];
    }

    public function deleteById(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM project WHERE id = ?');
        $stmt->bind_param('i', $id);
        $result = $stmt->execute();
        $success = $result && 1 === $stmt->affected_rows;
        $stmt->close();

        return $success;
    }
}
