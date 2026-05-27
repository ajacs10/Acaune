<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Database;
use PDOException;

final class GradeController extends Controller
{
    public function index(): void
    {
        try {
            $db = Database::connection();
            $grades = $db->query(
                'SELECT g.id, s.full_name AS student, su.name AS subject, g.score, g.status, g.published_at
                 FROM grades g
                 INNER JOIN students s ON s.id = g.student_id
                 INNER JOIN subjects su ON su.id = g.subject_id
                 INNER JOIN courses c ON c.id = su.course_id
                 WHERE c.code IN (\'LGC\', \'GRH\', \'CF\', \'RT\', \'ISI\')
                 ORDER BY g.updated_at DESC, s.full_name ASC'
            )->fetchAll();

            $summary = $db->query(
                "SELECT
                    COALESCE(AVG(score), 0) AS average,
                    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) AS approved,
                    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) AS failed,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending
                 FROM grades g
                 INNER JOIN subjects su ON su.id = g.subject_id
                 INNER JOIN courses c ON c.id = su.course_id
                 WHERE c.code IN ('LGC', 'GRH', 'CF', 'RT', 'ISI')"
            )->fetch();

            $this->json([
                'success' => true,
                'data' => [
                    'grades' => $grades,
                    'summary' => [
                        'average' => round((float) ($summary['average'] ?? 0), 1),
                        'approved' => (int) ($summary['approved'] ?? 0),
                        'failed' => (int) ($summary['failed'] ?? 0),
                        'pending' => (int) ($summary['pending'] ?? 0),
                    ],
                ],
            ]);
        } catch (PDOException $exception) {
            $this->json([
                'success' => false,
                'message' => 'Não foi possível carregar notas.',
            ], 500);
        }
    }
}
