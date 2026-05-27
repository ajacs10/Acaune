<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Controller;

final class DashboardController extends Controller
{
    public function index(): void
    {
        $this->json([
            'success' => true,
            'data' => [
                'students' => 12456,
                'active_enrollments' => 8753,
                'courses' => 256,
                'average' => 15.7,
            ],
        ]);
    }
}
