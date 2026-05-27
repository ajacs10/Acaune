<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Database;
use PDOException;

final class SubjectController extends Controller
{
    public function index(): void
    {
        try {
            $db = Database::connection();
            $courseId = isset($_GET['course_id']) ? (int) $_GET['course_id'] : 0;
            if ($courseId > 0) {
                $stmt = $db->prepare('SELECT id, course_id, name, code, semester FROM subjects WHERE course_id = :course_id ORDER BY name ASC');
                $stmt->execute(['course_id' => $courseId]);
                $subjects = $stmt->fetchAll();
            } else {
                $subjects = $db->query('SELECT id, course_id, name, code, semester FROM subjects ORDER BY name ASC')->fetchAll();
            }

            $this->json([
                'success' => true,
                'data' => $subjects,
            ]);
        } catch (PDOException $exception) {
            $this->json([
                'success' => false,
                'message' => 'Não foi possível carregar disciplinas.',
            ], 500);
        }
    }
}
