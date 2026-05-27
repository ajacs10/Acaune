<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Controller;

final class EnrollmentController extends Controller
{
    public function index(): void
    {
        $this->json([
            'success' => true,
            'data' => [
                ['semester' => '2024/2', 'status' => 'active', 'subject_count' => 5],
                ['semester' => '2024/1', 'status' => 'closed', 'subject_count' => 4],
            ],
        ]);
    }
}
