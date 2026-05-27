<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Database;
use PDOException;

final class EnrollmentController extends Controller
{
    public function index(): void
    {
        try {
            $db = Database::connection();
            $enrollments = $db->query(
                'SELECT e.id, e.semester, e.status, s.full_name AS student,
                        COUNT(es.id) AS subject_count
                 FROM enrollments e
                 INNER JOIN students s ON s.id = e.student_id
                 INNER JOIN courses c ON c.id = s.course_id
                 LEFT JOIN enrollment_subjects es ON es.enrollment_id = e.id
                 WHERE c.code IN (\'LGC\', \'GRH\', \'CF\', \'RT\', \'ISI\')
                 GROUP BY e.id, e.semester, e.status, s.full_name
                 ORDER BY e.enrolled_at DESC'
            )->fetchAll();

            $subjects = $db->query(
                'SELECT su.id, su.name, su.capacity, su.credits, c.name AS course, c.status,
                        COUNT(es.id) AS enrolled
                 FROM subjects su
                 INNER JOIN courses c ON c.id = su.course_id
                 LEFT JOIN enrollment_subjects es ON es.subject_id = su.id
                 WHERE c.code IN (\'LGC\', \'GRH\', \'CF\', \'RT\', \'ISI\')
                 GROUP BY su.id, su.name, su.capacity, su.credits, c.name, c.status
                 ORDER BY c.name ASC, su.name ASC'
            )->fetchAll();

            $this->json([
                'success' => true,
                'data' => [
                    'enrollments' => $enrollments,
                    'subjects' => array_map(static fn (array $subject): array => [
                        'id' => (int) $subject['id'],
                        'name' => $subject['name'],
                        'course' => $subject['course'],
                        'credits' => (int) $subject['credits'],
                        'capacity' => (int) $subject['capacity'],
                        'enrolled' => (int) $subject['enrolled'],
                        'status' => $subject['status'],
                    ], $subjects),
                ],
            ]);
        } catch (PDOException $exception) {
            $this->json([
                'success' => false,
                'message' => 'Não foi possível carregar matrículas.',
            ], 500);
        }
    }
}
