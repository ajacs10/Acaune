<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Controller;

final class GradeController extends Controller
{
    public function index(): void
    {
        $this->json([
            'success' => true,
            'data' => [
                ['student' => 'João Silva', 'subject' => 'Programação II', 'score' => 16.2],
                ['student' => 'Maria Santos', 'subject' => 'Matemática', 'score' => 12.8],
            ],
        ]);
    }
}
