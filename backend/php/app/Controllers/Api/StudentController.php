<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Database;
use PDOException;

final class StudentController extends Controller
{
    public function index(): void
    {
        $this->json([
            'success' => true,
            'data' => [
                ['name' => 'João Silva', 'academic_number' => '2021001', 'course' => 'Engenharia Informática', 'status' => 'active'],
                ['name' => 'Maria Santos', 'academic_number' => '2021002', 'course' => 'Medicina', 'status' => 'active'],
            ],
        ]);
    }

    public function store(): void
    {
        $data = $this->input();
        $fullName = trim((string) ($data['full_name'] ?? $data['name'] ?? ''));
        $academicNumber = trim((string) ($data['academic_number'] ?? ''));
        $email = trim((string) ($data['email'] ?? ''));
        $courseId = (int) ($data['course_id'] ?? 1);

        if ($fullName === '' || $academicNumber === '' || $email === '') {
            $this->json([
                'success' => false,
                'message' => 'Envie full_name, academic_number e email via POST.',
            ], 422);
        }

        try {
            $db = Database::connection();
            $statement = $db->prepare(
                'INSERT INTO students (course_id, full_name, academic_number, email, academic_status)
                 VALUES (:course_id, :full_name, :academic_number, :email, :academic_status)'
            );
            $statement->execute([
                'course_id' => $courseId,
                'full_name' => $fullName,
                'academic_number' => $academicNumber,
                'email' => $email,
                'academic_status' => (string) ($data['academic_status'] ?? 'active'),
            ]);

            $this->json([
                'success' => true,
                'message' => 'Estudante registado com sucesso.',
                'id' => (int) $db->lastInsertId(),
            ], 201);
        } catch (PDOException $exception) {
            $this->json([
                'success' => false,
                'message' => 'Erro ao registar estudante. Verifique a ligação MySQL e os dados enviados.',
            ], 500);
        }
    }
}
