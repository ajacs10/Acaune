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
            // Build dynamic filters from query string: course_id, subject_id, year
            $filters = [];
            $where = [];

            if (!empty($_GET['course_id'])) {
                $where[] = 'c.id = :course_id';
                $filters['course_id'] = (int) $_GET['course_id'];
            }
            if (!empty($_GET['subject_id'])) {
                $where[] = 'su.id = :subject_id';
                $filters['subject_id'] = (int) $_GET['subject_id'];
            }
            if (!empty($_GET['year'])) {
                // Academic year starts in October and ends in August.
                $academicYearStart = (int) $_GET['year'];
                $where[] = 'COALESCE(g.published_at, g.created_at) >= :year_start AND COALESCE(g.published_at, g.created_at) < :year_end';
                $filters['year_start'] = sprintf('%04d-10-01 00:00:00', $academicYearStart);
                $filters['year_end'] = sprintf('%04d-09-01 00:00:00', $academicYearStart + 1);
            }

            $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

            $sql = "SELECT g.id, s.full_name AS student, su.name AS subject, g.score, g.status, g.published_at
                 FROM grades g
                 INNER JOIN students s ON s.id = g.student_id
                 INNER JOIN subjects su ON su.id = g.subject_id
                 INNER JOIN courses c ON c.id = su.course_id
                 {$whereSql}
                 ORDER BY g.updated_at DESC, s.full_name ASC";

            $stmt = $db->prepare($sql);
            $stmt->execute($filters);
            $grades = $stmt->fetchAll();

            $summarySql = "SELECT
                    COALESCE(AVG(score), 0) AS average,
                    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) AS approved,
                    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) AS failed,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending
                 FROM grades g
                 INNER JOIN subjects su ON su.id = g.subject_id
                 INNER JOIN courses c ON c.id = su.course_id
                 {$whereSql}";

            $sumStmt = $db->prepare($summarySql);
            $sumStmt->execute($filters);
            $summary = $sumStmt->fetch();

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
                'error' => $exception->getMessage(),
            ], 500);
        }
    }
}
