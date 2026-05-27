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
        try {
            $db = Database::connection();
            $statement = $db->query(
                'SELECT s.id, s.full_name AS name, s.academic_number, s.email, s.academic_status AS status,
                        s.photo_path, c.name AS course
                 FROM students s
                 INNER JOIN courses c ON c.id = s.course_id
                 ORDER BY s.created_at DESC, s.full_name ASC'
            );

            $this->json([
                'success' => true,
                'data' => $statement->fetchAll(),
            ]);
        } catch (PDOException $exception) {
            $this->json([
                'success' => false,
                'message' => 'Não foi possível carregar estudantes.',
            ], 500);
        }
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
            // Inserir estudante
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

            $studentId = (int) $db->lastInsertId();

            // Criar matrícula automática para o estudante (semestre corrente)
            try {
                $semester = (string) ($data['semester'] ?? (date('Y') . '-' . ((int) date('n') <= 6 ? '1' : '2')));
                $enrollStmt = $db->prepare(
                    'INSERT INTO enrollments (student_id, semester, status)
                     VALUES (:student_id, :semester, :status)'
                );
                $enrollStmt->execute([
                    'student_id' => $studentId,
                    'semester' => $semester,
                    'status' => 'active',
                ]);
                $enrollmentId = (int) $db->lastInsertId();
            } catch (PDOException $e) {
                $enrollmentId = null;
            }

            $this->json([
                'success' => true,
                'message' => 'Estudante registado com sucesso.',
                'id' => $studentId,
                'enrollment_id' => $enrollmentId,
            ], 201);
        } catch (PDOException $exception) {
            $this->json([
                'success' => false,
                'message' => 'Erro ao registar estudante. Verifique a ligação MySQL e os dados enviados.',
            ], 500);
        }
    }
}
