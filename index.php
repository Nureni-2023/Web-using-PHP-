<?php
// Filename: index.php

// --- Configuration ---
$tasksFile = 'tasks.json'; // File to store tasks

// Ensure the tasks.json file exists and is writable
if (!file_exists($tasksFile)) {
    file_put_contents($tasksFile, json_encode([])); // Create an empty JSON array if file doesn't exist
}

// --- Helper Functions ---

/**
 * Reads tasks from the JSON file.
 * @return array An array of task objects.
 */
function getTasks() {
    global $tasksFile;
    $json = file_get_contents($tasksFile);
    return json_decode($json, true) ?: []; // Decode JSON, return empty array if file is empty or invalid
}

/**
 * Writes tasks to the JSON file.
 * @param array $tasks The array of tasks to save.
 */
function saveTasks(array $tasks) {
    global $tasksFile;
    file_put_contents($tasksFile, json_encode($tasks, JSON_PRETTY_PRINT)); // Save with pretty print for readability
}

/**
 * Generates a simple unique ID for tasks.
 * @return string A unique ID.
 */
function generateUniqueId() {
    return uniqid(); // PHP's built-in unique ID generator
}

// --- Task Actions (Handling POST requests) ---

// Handle Add Task
if (isset($_POST['action']) && $_POST['action'] === 'add') {
    $title = trim($_POST['title'] ?? '');
    if (!empty($title)) {
        $tasks = getTasks();
        $newTask = [
            'id' => generateUniqueId(),
            'title' => htmlspecialchars($title), // Sanitize input
            'completed' => false,
            'createdAt' => date('Y-m-d H:i:s') // Add timestamp
        ];
        $tasks[] = $newTask; // Add new task to the array
        saveTasks($tasks); // Save updated tasks
    }
    // Redirect to prevent form resubmission on refresh
    header('Location: index.php');
    exit();
}

// Handle Toggle Complete Status
if (isset($_POST['action']) && $_POST['action'] === 'toggle') {
    $taskId = $_POST['task_id'] ?? '';
    $tasks = getTasks();
    foreach ($tasks as &$task) { // Use reference to modify array elements directly
        if ($task['id'] === $taskId) {
            $task['completed'] = !$task['completed']; // Toggle status
            break;
        }
    }
    saveTasks($tasks);
    header('Location: index.php');
    exit();
}

// Handle Delete Task
if (isset($_POST['action']) && $_POST['action'] === 'delete') {
    $taskId = $_POST['task_id'] ?? '';
    $tasks = getTasks();
    $tasks = array_filter($tasks, function($task) use ($taskId) {
        return $task['id'] !== $taskId; // Filter out the task to be deleted
    });
    saveTasks(array_values($tasks)); // Re-index array after filtering
    header('Location: index.php');
    exit();
}

// --- Display Tasks ---
$tasks = getTasks(); // Get the current list of tasks to display

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHP Task Manager</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f3f4f6; /* Gray-100 */
            color: #1f2937; /* Gray-900 */
            display: flex;
            justify-content: center;
            align-items: flex-start; /* Align to top */
            min-height: 100vh;
            padding: 20px;
            box-sizing: border-box;
        }
        .container {
            background-color: #ffffff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            width: 100%;
            max-width: 600px;
        }
        h1 {
            font-size: 2.25rem; /* text-3xl */
            font-weight: 700; /* font-bold */
            color: #1f2937;
            margin-bottom: 25px;
            text-align: center;
        }
        .task-form {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
        }
        .task-form input[type="text"] {
            flex-grow: 1;
            padding: 12px 15px;
            border: 1px solid #d1d5db; /* Gray-300 */
            border-radius: 8px;
            font-size: 1rem;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .task-form input[type="text"]:focus {
            border-color: #3b82f6; /* Blue-500 */
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
        }
        .task-form button {
            background-color: #3b82f6; /* Blue-500 */
            color: #ffffff;
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            transition: background-color 0.2s, transform 0.1s;
        }
        .task-form button:hover {
            background-color: #2563eb; /* Blue-600 */
        }
        .task-form button:active {
            transform: scale(0.98);
        }
        .task-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .task-item {
            background-color: #f9fafb; /* Gray-50 */
            border: 1px solid #e5e7eb; /* Gray-200 */
            padding: 15px 20px;
            margin-bottom: 10px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: background-color 0.2s, border-color 0.2s;
        }
        .task-item.completed {
            background-color: #d1fae5; /* Green-100 */
            border-color: #34d399; /* Green-400 */
            text-decoration: line-through;
            color: #6b7280; /* Gray-500 */
        }
        .task-item span {
            flex-grow: 1;
            font-size: 1.125rem; /* text-lg */
            font-weight: 600; /* font-semibold */
        }
        .task-item .actions {
            display: flex;
            gap: 8px;
        }
        .task-item .actions button {
            padding: 8px 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.875rem; /* text-sm */
            font-weight: 500;
            transition: background-color 0.2s, transform 0.1s;
        }
        .task-item .actions button:hover {
            transform: translateY(-1px);
        }
        .task-item .actions button.toggle-btn {
            background-color: #10b981; /* Green-500 */
            color: white;
        }
        .task-item.completed .actions button.toggle-btn {
            background-color: #f59e0b; /* Amber-500 */
        }
        .task-item .actions button.delete-btn {
            background-color: #ef4444; /* Red-500 */
            color: white;
        }
        .task-item .actions button.toggle-btn:hover {
            background-color: #059669; /* Green-600 */
        }
        .task-item.completed .actions button.toggle-btn:hover {
            background-color: #d97706; /* Amber-600 */
        }
        .task-item .actions button.delete-btn:hover {
            background-color: #dc2626; /* Red-600 */
        }
        .no-tasks {
            text-align: center;
            color: #6b7280; /* Gray-500 */
            font-style: italic;
            margin-top: 20px;
        }

        /* Responsive adjustments */
        @media (max-width: 640px) {
            .container {
                padding: 20px;
                margin: 10px;
            }
            h1 {
                font-size: 1.75rem; /* text-2xl */
            }
            .task-form {
                flex-direction: column;
            }
            .task-form button {
                width: 100%;
            }
            .task-item {
                flex-direction: column;
                align-items: flex-start;
            }
            .task-item span {
                margin-bottom: 10px;
            }
            .task-item .actions {
                width: 100%;
                justify-content: flex-end;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>PHP Task Manager</h1>

        <!-- Add New Task Form -->
        <form action="index.php" method="POST" class="task-form">
            <input type="hidden" name="action" value="add">
            <input type="text" name="title" placeholder="Add a new task..." required>
            <button type="submit">Add Task</button>
        </form>

        <!-- Task List -->
        <ul class="task-list">
            <?php if (empty($tasks)): ?>
                <p class="no-tasks">No tasks yet. Add one above!</p>
            <?php else: ?>
                <?php foreach ($tasks as $task): ?>
                    <li class="task-item <?php echo $task['completed'] ? 'completed' : ''; ?>">
                        <span><?php echo $task['title']; ?></span>
                        <div class="actions">
                            <form action="index.php" method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="toggle">
                                <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                <button type="submit" class="toggle-btn">
                                    <?php echo $task['completed'] ? 'Unmark' : 'Complete'; ?>
                                </button>
                            </form>
                            <form action="index.php" method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                <button type="submit" class="delete-btn">Delete</button>
                            </form>
                        </div>
                    </li>
                <?php endforeach; ?>
            <?php endif; ?>
        </ul>
    </div>
</body>
</html>
