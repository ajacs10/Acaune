<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Database;
use PDOException;

final class CourseController extends Controller
{
    public function index(): void
    {
        try {
            $db = Database::connection();
            $courses = $db->query(
                'SELECT id, code, name, department, semester, capacity, status
                 FROM courses
                 ORDER BY name ASC'
            )->fetchAll();

            if (!$courses) {
                $courses = [
                    ['id' => 0, 'code' => 'LGC', 'name' => 'Logística e Gestão Comercial', 'department' => 'INSUTEC', 'semester' => 4, 'capacity' => 120, 'status' => 'available'],
                    ['id' => 0, 'code' => 'GRH', 'name' => 'Gestão de Recursos Humanos', 'department' => 'INSUTEC', 'semester' => 4, 'capacity' => 120, 'status' => 'available'],
                    ['id' => 0, 'code' => 'CF', 'name' => 'Contabilidade e Finanças', 'department' => 'INSUTEC', 'semester' => 4, 'capacity' => 120, 'status' => 'available'],
                    ['id' => 0, 'code' => 'RT', 'name' => 'Redes e Telecomunicações', 'department' => 'INSUTEC', 'semester' => 5, 'capacity' => 100, 'status' => 'available'],
                    ['id' => 0, 'code' => 'ISI', 'name' => 'Informática e Sistemas de Informação', 'department' => 'INSUTEC', 'semester' => 5, 'capacity' => 100, 'status' => 'available'],
                ];
            }

            $this->json([
                'success' => true,
                'data' => $courses,
            ]);
        } catch (PDOException $exception) {
            $this->json([
                'success' => false,
                'message' => 'Não foi possível carregar cursos.',
            ], 500);
        }
    }
}
