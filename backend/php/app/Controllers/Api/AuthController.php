<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Database;
use App\Models\User;
use PDOException;

final class AuthController extends Controller
{
    public function login(): void
    {
        $data = $this->input();
        $email = trim((string) ($data['email'] ?? ''));
        $password = trim((string) ($data['password'] ?? ''));

        if ($email === '' || $password === '') {
            $this->json([
                'success' => false,
                'message' => 'Envie email e palavra-passe via POST.',
            ], 422);
        }

        try {
            $user = (new User())->findByEmail($email);
        } catch (PDOException $exception) {
            $this->json([
                'success' => false,
                'message' => 'Não foi possível ligar à base de dados. Verifique o MySQL/phpMyAdmin e o ficheiro .env.',
            ], 500);
        }

        if ($user && password_verify($password, (string) $user['password'])) {
            unset($user['password']);

            // Tentar enriquecer o utilizador com dados do estudante (se existir)
            try {
                $db = Database::connection();
                $stmt = $db->prepare(
                    'SELECT s.academic_number, s.full_name AS student_name, s.course_id, c.name AS course_name, s.photo_path
                     FROM students s
                     LEFT JOIN courses c ON c.id = s.course_id
                     WHERE s.user_id = :user_id OR s.email = :email
                     LIMIT 1'
                );
                $stmt->execute(['user_id' => $user['id'], 'email' => $user['email']]);
                $student = $stmt->fetch();

                if ($student) {
                    if (!empty($student['student_name'])) {
                        $user['full_name'] = $student['student_name'];
                    }
                    $user['academic_number'] = $student['academic_number'] ?? null;
                    $user['course'] = $student['course_name'] ?? null;
                    $user['photo_path'] = $student['photo_path'] ?? null;
                }
            } catch (PDOException $exception) {
                // Se houver erro ao buscar dados do estudante, prossegue sem eles
            }

            $this->json([
                'success' => true,
                'message' => 'Login efetuado.',
                'user' => $user,
            ]);
        }

        $this->json([
            'success' => false,
            'message' => 'Credenciais inválidas.',
        ], 401);
    }

    public function register(): void
    {
        $data = $this->input();
        $name = trim((string) ($data['name'] ?? ''));
        $email = trim((string) ($data['email'] ?? ''));
        $password = trim((string) ($data['password'] ?? ''));
        $passwordConfirmation = trim((string) ($data['password_confirmation'] ?? ''));

        if ($name === '' || $email === '' || $password === '' || $passwordConfirmation === '') {
            $this->json([
                'success' => false,
                'message' => 'Envie name, email, password e password_confirmation via POST.',
            ], 422);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->json([
                'success' => false,
                'message' => 'Email inválido.',
            ], 422);
        }

        if (!$this->passwordIsValid($password)) {
            $this->json([
                'success' => false,
                'message' => 'A palavra-passe deve ter no mínimo 8 caracteres, uma letra maiúscula, uma minúscula, um número e um caractere especial.',
            ], 422);
        }

        if ($password !== $passwordConfirmation) {
            $this->json([
                'success' => false,
                'message' => 'A confirmação da palavra-passe não corresponde.',
            ], 422);
        }

        try {
            $db = Database::connection();
            $roleId = $db->query("SELECT id FROM roles WHERE name = 'Administrador' LIMIT 1")->fetchColumn();

            if (!$roleId) {
                $db->exec("INSERT INTO roles (name) VALUES ('Administrador')");
                $roleId = $db->lastInsertId();
            }

            $statement = $db->prepare(
                'INSERT INTO users (role_id, name, email, password)
                 VALUES (:role_id, :name, :email, :password)'
            );
            $statement->execute([
                'role_id' => (int) $roleId,
                'name' => $name,
                'email' => $email,
                'password' => password_hash($password, PASSWORD_DEFAULT),
            ]);

            $this->json([
                'success' => true,
                'message' => 'Conta criada com sucesso. Já pode entrar no sistema.',
            ], 201);
        } catch (PDOException $exception) {
            $this->json([
                'success' => false,
                'message' => 'Erro ao criar conta. Verifique se a base de dados está importada.',
            ], 500);
        }
    }

    private function passwordIsValid(string $password): bool
    {
        return strlen($password) >= 8
            && preg_match('/[A-Z]/', $password) === 1
            && preg_match('/[a-z]/', $password) === 1
            && preg_match('/[0-9]/', $password) === 1
            && preg_match('/[^A-Za-z0-9]/', $password) === 1;
    }
}
