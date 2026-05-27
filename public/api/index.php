<?php
declare(strict_types=1);

session_start();

define('BASE_PATH', dirname(__DIR__, 2));
define('APP_PATH', BASE_PATH . '/backend/php/app');

spl_autoload_register(function (string $class): void {
    $relative = str_replace('App\\', '', $class);
    $relative = str_replace('\\', '/', $relative);
    $path = APP_PATH . '/' . $relative . '.php';

    if (file_exists($path)) {
        require_once $path;
    }
});

use App\Core\Router;

$router = new Router();
$router->post('/api/login', ['App\\Controllers\\Api\\AuthController', 'login']);
$router->post('/api/register', ['App\\Controllers\\Api\\AuthController', 'register']);
$router->get('/api/dashboard', ['App\\Controllers\\Api\\DashboardController', 'index']);
$router->get('/api/students', ['App\\Controllers\\Api\\StudentController', 'index']);
$router->post('/api/students', ['App\\Controllers\\Api\\StudentController', 'store']);
$router->get('/api/enrollments', ['App\\Controllers\\Api\\EnrollmentController', 'index']);
$router->get('/api/grades', ['App\\Controllers\\Api\\GradeController', 'index']);

$router->dispatch($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);
