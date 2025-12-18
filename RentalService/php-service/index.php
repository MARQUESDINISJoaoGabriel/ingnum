<?php
/**
 * PHP RESTful Microservice API
 * A basic Task management API with CRUD operations
 */

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', '0');

// Constants
define('DATA_DIR', __DIR__ . '/data');
define('DATA_FILE', DATA_DIR . '/tasks.json');

/**
 * Router - Handles URL routing and request parsing
 */
class Router
{
    private $method;
    private $path;
    private $pathParts;

    public function __construct()
    {
        $this->method = $_SERVER['REQUEST_METHOD'];
        $this->path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $this->pathParts = array_values(array_filter(explode('/', $this->path)));
    }

    public function getMethod()
    {
        return $this->method;
    }

    public function getPath()
    {
        return $this->path;
    }

    public function getPathParts()
    {
        return $this->pathParts;
    }

    public function getRequestBody()
    {
        $body = file_get_contents('php://input');
        return json_decode($body, true);
    }
}

/**
 * ResponseHandler - Manages HTTP responses
 */
class ResponseHandler
{
    public function success($data, $message = 'Success', $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'data' => $data,
            'message' => $message
        ], JSON_PRETTY_PRINT);
        exit;
    }

    public function error($error, $code = 400, $details = null)
    {
        http_response_code($code);
        header('Content-Type: application/json');
        $response = [
            'success' => false,
            'error' => $error,
            'code' => $code
        ];
        if ($details !== null) {
            $response['details'] = $details;
        }
        echo json_encode($response, JSON_PRETTY_PRINT);
        exit;
    }

    public function noContent()
    {
        http_response_code(204);
        exit;
    }
}

/**
 * Task - Entity class with validation
 */
class Task
{
    private $id;
    private $title;
    private $description;
    private $status;
    private $createdAt;
    private $updatedAt;

    private static $validStatuses = ['pending', 'in_progress', 'completed'];

    public function __construct($data)
    {
        $this->id = $data['id'] ?? null;
        $this->title = $data['title'] ?? '';
        $this->description = $data['description'] ?? '';
        $this->status = $data['status'] ?? 'pending';
        $this->createdAt = $data['created_at'] ?? date('c');
        $this->updatedAt = $data['updated_at'] ?? date('c');
    }

    public function validate()
    {
        $errors = [];

        if (empty(trim($this->title))) {
            $errors['title'] = 'Title is required';
        } elseif (strlen($this->title) > 255) {
            $errors['title'] = 'Title must not exceed 255 characters';
        }

        if (!in_array($this->status, self::$validStatuses)) {
            $errors['status'] = 'Status must be one of: ' . implode(', ', self::$validStatuses);
        }

        return $errors;
    }

    public function toArray()
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt
        ];
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function setUpdatedAt($timestamp)
    {
        $this->updatedAt = $timestamp;
    }
}

/**
 * TaskRepository - Handles data persistence
 */
class TaskRepository
{
    private $dataFile;

    public function __construct()
    {
        $this->dataFile = DATA_FILE;
        $this->initializeStorage();
    }

    private function initializeStorage()
    {
        if (!is_dir(DATA_DIR)) {
            mkdir(DATA_DIR, 0777, true);
        }

        if (!file_exists($this->dataFile)) {
            file_put_contents($this->dataFile, json_encode([]));
        }
    }

    private function readData()
    {
        $handle = fopen($this->dataFile, 'r');
        if (!$handle) {
            throw new Exception('Unable to open data file');
        }

        flock($handle, LOCK_SH);
        $content = fread($handle, filesize($this->dataFile) ?: 1);
        flock($handle, LOCK_UN);
        fclose($handle);

        $data = json_decode($content, true);
        return $data ?: [];
    }

    private function writeData($data)
    {
        $handle = fopen($this->dataFile, 'w');
        if (!$handle) {
            throw new Exception('Unable to open data file for writing');
        }

        flock($handle, LOCK_EX);
        fwrite($handle, json_encode($data, JSON_PRETTY_PRINT));
        flock($handle, LOCK_UN);
        fclose($handle);
    }

    public function getAll()
    {
        return $this->readData();
    }

    public function getById($id)
    {
        $tasks = $this->readData();
        foreach ($tasks as $task) {
            if ($task['id'] == $id) {
                return $task;
            }
        }
        return null;
    }

    public function create($taskData)
    {
        $tasks = $this->readData();

        // Generate new ID
        $maxId = 0;
        foreach ($tasks as $task) {
            if ($task['id'] > $maxId) {
                $maxId = $task['id'];
            }
        }
        $newId = $maxId + 1;

        $taskData['id'] = $newId;
        $taskData['created_at'] = date('c');
        $taskData['updated_at'] = date('c');

        $tasks[] = $taskData;
        $this->writeData($tasks);

        return $taskData;
    }

    public function update($id, $taskData)
    {
        $tasks = $this->readData();
        $updated = false;

        foreach ($tasks as &$task) {
            if ($task['id'] == $id) {
                $task['title'] = $taskData['title'] ?? $task['title'];
                $task['description'] = $taskData['description'] ?? $task['description'];
                $task['status'] = $taskData['status'] ?? $task['status'];
                $task['updated_at'] = date('c');
                $updated = true;
                $result = $task;
                break;
            }
        }

        if ($updated) {
            $this->writeData($tasks);
            return $result;
        }

        return null;
    }

    public function delete($id)
    {
        $tasks = $this->readData();
        $newTasks = [];
        $found = false;

        foreach ($tasks as $task) {
            if ($task['id'] == $id) {
                $found = true;
                continue;
            }
            $newTasks[] = $task;
        }

        if ($found) {
            $this->writeData($newTasks);
            return true;
        }

        return false;
    }
}

/**
 * TaskController - Business logic for API endpoints
 */
class TaskController
{
    private $repository;
    private $response;

    public function __construct(TaskRepository $repository, ResponseHandler $response)
    {
        $this->repository = $repository;
        $this->response = $response;
    }

    public function index()
    {
        $tasks = $this->repository->getAll();
        $this->response->success($tasks, 'Tasks retrieved successfully', 200);
    }

    public function show($id)
    {
        $task = $this->repository->getById($id);

        if ($task === null) {
            $this->response->error('Task not found', 404);
        }

        $this->response->success($task, 'Task retrieved successfully', 200);
    }

    public function store($data)
    {
        if ($data === null) {
            $this->response->error('Invalid JSON', 400);
        }

        $task = new Task($data);
        $errors = $task->validate();

        if (!empty($errors)) {
            $this->response->error('Validation failed', 400, $errors);
        }

        $created = $this->repository->create($task->toArray());
        $this->response->success($created, 'Task created successfully', 201);
    }

    public function update($id, $data)
    {
        if ($data === null) {
            $this->response->error('Invalid JSON', 400);
        }

        $existing = $this->repository->getById($id);
        if ($existing === null) {
            $this->response->error('Task not found', 404);
        }

        // Merge existing data with new data for validation
        $mergedData = array_merge($existing, $data);
        $task = new Task($mergedData);
        $errors = $task->validate();

        if (!empty($errors)) {
            $this->response->error('Validation failed', 400, $errors);
        }

        $updated = $this->repository->update($id, $data);
        $this->response->success($updated, 'Task updated successfully', 200);
    }

    public function destroy($id)
    {
        $deleted = $this->repository->delete($id);

        if (!$deleted) {
            $this->response->error('Task not found', 404);
        }

        $this->response->noContent();
    }

    public function health()
    {
        $this->response->success(['status' => 'ok'], 'Service is healthy', 200);
    }
}

/**
 * Application Bootstrap
 */
try {
    // Enable CORS
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');

    // Handle preflight requests
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit;
    }

    // Initialize components
    $router = new Router();
    $response = new ResponseHandler();
    $repository = new TaskRepository();
    $controller = new TaskController($repository, $response);

    $method = $router->getMethod();
    $pathParts = $router->getPathParts();

    // Route handling
    if (count($pathParts) >= 1 && $pathParts[0] === 'api') {
        if (count($pathParts) >= 2) {
            $resource = $pathParts[1];

            if ($resource === 'health') {
                $controller->health();
            } elseif ($resource === 'tasks') {
                if ($method === 'GET' && count($pathParts) === 2) {
                    // GET /api/tasks
                    $controller->index();
                } elseif ($method === 'GET' && count($pathParts) === 3) {
                    // GET /api/tasks/{id}
                    $controller->show($pathParts[2]);
                } elseif ($method === 'POST' && count($pathParts) === 2) {
                    // POST /api/tasks
                    $controller->store($router->getRequestBody());
                } elseif ($method === 'PUT' && count($pathParts) === 3) {
                    // PUT /api/tasks/{id}
                    $controller->update($pathParts[2], $router->getRequestBody());
                } elseif ($method === 'DELETE' && count($pathParts) === 3) {
                    // DELETE /api/tasks/{id}
                    $controller->destroy($pathParts[2]);
                } else {
                    $response->error('Method not allowed', 405);
                }
            } else {
                $response->error('Resource not found', 404);
            }
        } else {
            $response->error('Invalid API endpoint', 404);
        }
    } else {
        // Default welcome message
        header('Content-Type: application/json');
        echo json_encode([
            'service' => 'PHP Task Microservice API',
            'version' => '1.0.0',
            'endpoints' => [
                'GET /api/health' => 'Health check',
                'GET /api/tasks' => 'List all tasks',
                'GET /api/tasks/{id}' => 'Get specific task',
                'POST /api/tasks' => 'Create new task',
                'PUT /api/tasks/{id}' => 'Update task',
                'DELETE /api/tasks/{id}' => 'Delete task'
            ]
        ], JSON_PRETTY_PRINT);
    }
} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error',
        'code' => 500,
        'message' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
