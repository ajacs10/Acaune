<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Database;
use PDOException;

final class DashboardController extends Controller
{
    public function index(): void
    {
        try {
            $db = Database::connection();
            $courseFilter = "('LGC', 'GRH', 'CF', 'RT', 'ISI')";
            $db->exec(
                "INSERT INTO courses (code, name, department, semester, capacity, status)
                 VALUES
                    ('LGC', 'Logística e Gestão Comercial', 'INSUTEC', 4, 120, 'available'),
                    ('GRH', 'Gestão de Recursos Humanos', 'INSUTEC', 4, 120, 'available'),
                    ('CF', 'Contabilidade e Finanças', 'INSUTEC', 4, 120, 'available'),
                    ('RT', 'Redes e Telecomunicações', 'INSUTEC', 5, 100, 'available'),
                    ('ISI', 'Informática e Sistemas de Informação', 'INSUTEC', 5, 100, 'available')
                 ON DUPLICATE KEY UPDATE
                    name = VALUES(name),
                    department = VALUES(department),
                    semester = VALUES(semester),
                    capacity = VALUES(capacity),
                    status = VALUES(status)"
            );
            $requiredCourses = [
                'Logística e Gestão Comercial',
                'Gestão de Recursos Humanos',
                'Contabilidade e Finanças',
                'Redes e Telecomunicações',
                'Informática e Sistemas de Informação',
            ];
            $students = (int) $db->query(
                "SELECT COUNT(*)
                 FROM students s
                 INNER JOIN courses c ON c.id = s.course_id
                 WHERE c.code IN {$courseFilter}"
            )->fetchColumn();
            $activeEnrollments = (int) $db->query(
                "SELECT COUNT(*)
                 FROM enrollments e
                 INNER JOIN students s ON s.id = e.student_id
                 INNER JOIN courses c ON c.id = s.course_id
                 WHERE e.status = 'active' AND c.code IN {$courseFilter}"
            )->fetchColumn();
            $courses = (int) $db->query("SELECT COUNT(*) FROM courses WHERE status <> 'closed' AND code IN {$courseFilter}")->fetchColumn();
            $average = (float) ($db->query(
                "SELECT COALESCE(AVG(g.score), 0)
                 FROM grades g
                 INNER JOIN subjects su ON su.id = g.subject_id
                 INNER JOIN courses c ON c.id = su.course_id
                 WHERE c.code IN {$courseFilter}"
            )->fetchColumn() ?: 0);
            $approved = (int) $db->query(
                "SELECT COUNT(*)
                 FROM grades g
                 INNER JOIN subjects su ON su.id = g.subject_id
                 INNER JOIN courses c ON c.id = su.course_id
                 WHERE g.status = 'approved' AND c.code IN {$courseFilter}"
            )->fetchColumn();
            $failed = (int) $db->query(
                "SELECT COUNT(*)
                 FROM grades g
                 INNER JOIN subjects su ON su.id = g.subject_id
                 INNER JOIN courses c ON c.id = su.course_id
                 WHERE g.status = 'failed' AND c.code IN {$courseFilter}"
            )->fetchColumn();
            $pending = (int) $db->query(
                "SELECT COUNT(*)
                 FROM grades g
                 INNER JOIN subjects su ON su.id = g.subject_id
                 INNER JOIN courses c ON c.id = su.course_id
                 WHERE g.status = 'pending' AND c.code IN {$courseFilter}"
            )->fetchColumn();
            $totalGrades = $approved + $failed + $pending;

            $courseRows = $db->query(
                'SELECT c.name, COUNT(s.id) AS total
                 FROM courses c
                 LEFT JOIN students s ON s.course_id = c.id
                 WHERE c.code IN (\'LGC\', \'GRH\', \'CF\', \'RT\', \'ISI\')
                 GROUP BY c.id, c.name
                 ORDER BY total DESC, c.name ASC
                 LIMIT 5'
            )->fetchAll();

            if (!$courseRows) {
                $courseRows = array_map(static fn (string $name): array => [
                    'name' => $name,
                    'total' => 0,
                ], $requiredCourses);
            }
            $trendRows = $db->query(
                "SELECT DATE_FORMAT(e.enrolled_at, '%Y-%m') AS period, COUNT(*) AS total
                 FROM enrollments e
                 INNER JOIN students s ON s.id = e.student_id
                 INNER JOIN courses c ON c.id = s.course_id
                 WHERE c.code IN {$courseFilter}
                   AND e.enrolled_at >= DATE_SUB(CURDATE(), INTERVAL 5 MONTH)
                 GROUP BY period
                 ORDER BY period ASC"
            )->fetchAll();
            $trendByPeriod = [];

            foreach ($trendRows as $row) {
                $trendByPeriod[(string) $row['period']] = (int) $row['total'];
            }

            $trend = [];
            $start = new \DateTimeImmutable('first day of -5 months');

            for ($index = 0; $index < 6; $index++) {
                $periodDate = $start->modify("+{$index} months");
                $period = $periodDate->format('Y-m');
                $trend[] = [
                    'period' => $period,
                    'label' => $periodDate->format('m/Y'),
                    'total' => $trendByPeriod[$period] ?? 0,
                ];
            }
            $activities = $db->query(
                "SELECT type, title, description, occurred_at
                 FROM (
                    SELECT 'student' AS type, s.full_name AS title, c.name AS description, s.created_at AS occurred_at
                    FROM students s
                    INNER JOIN courses c ON c.id = s.course_id
                    WHERE c.code IN {$courseFilter}

                    UNION ALL

                    SELECT 'enrollment' AS type, s.full_name AS title, CONCAT('Matrícula ', e.semester) AS description, e.enrolled_at AS occurred_at
                    FROM enrollments e
                    INNER JOIN students s ON s.id = e.student_id
                    INNER JOIN courses c ON c.id = s.course_id
                    WHERE c.code IN {$courseFilter}

                    UNION ALL

                    SELECT 'grade' AS type, s.full_name AS title, CONCAT(su.name, ' · ', g.score) AS description, COALESCE(g.published_at, g.updated_at, g.created_at) AS occurred_at
                    FROM grades g
                    INNER JOIN students s ON s.id = g.student_id
                    INNER JOIN subjects su ON su.id = g.subject_id
                    INNER JOIN courses c ON c.id = su.course_id
                    WHERE c.code IN {$courseFilter}
                 ) recent
                 ORDER BY occurred_at DESC
                 LIMIT 6"
            )->fetchAll();

            $this->json([
                'success' => true,
                'data' => [
                    'students' => $students,
                    'active_enrollments' => $activeEnrollments,
                    'courses' => $courses,
                    'average' => round($average, 1),
                    'approval_rate' => $totalGrades > 0 ? round(($approved / $totalGrades) * 100, 1) : 0,
                    'grades' => [
                        'approved' => $approved,
                        'failed' => $failed,
                        'pending' => $pending,
                    ],
                    'course_distribution' => array_map(static fn (array $row): array => [
                        'name' => $row['name'],
                        'total' => (int) $row['total'],
                    ], $courseRows),
                    'enrollment_trend' => $trend,
                    'recent_activities' => $activities,
                ],
            ]);
        } catch (PDOException $exception) {
            $this->json([
                'success' => false,
                'message' => 'Não foi possível carregar os dados do dashboard.',
            ], 500);
        }
    }
}
