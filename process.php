<?php
// Database Configuration
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'student_db');

/**
 * Establishes a secure database connection
 *
 * @return mysqli Database connection object
 * @throws Exception If database connection fails
 */
function connectDatabase() {
    $conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

    if ($conn->connect_error) {
        error_log("Database Connection Failed: " . $conn->connect_error);
        throw new Exception("Database connection error. Please try again later.");
    }

    $conn->set_charset("utf8mb4");
    return $conn;
}

/**
 * Validates and sanitizes student input
 *
 * @param mysqli $conn Database connection object
 * @param array $data Input data from the user
 * @return array|false Sanitized data or false on validation failure
 */
function validateStudentData($conn, $data) {
    $name = trim($data['name']);
    $email = trim($data['email']);
    $age = filter_var($data['age'], FILTER_VALIDATE_INT);

    if (empty($name) || strlen($name) > 50) return false;
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return false;
    if ($age === false || $age < 16 || $age > 100) return false;

    return [
        'name' => $conn->real_escape_string($name),
        'email' => $conn->real_escape_string($email),
        'age' => $age
    ];
}

/**
 * Inserts student data into the database
 *
 * @param mysqli $conn Database connection object
 * @param array $data Sanitized student data
 * @return bool True on success, false otherwise
 */
function insertStudentData($conn, $data) {
    $stmt = $conn->prepare("INSERT INTO students (name, email, age) VALUES (?, ?, ?)");
    $stmt->bind_param("ssi", $data['name'], $data['email'], $data['age']);

    try {
        return $stmt->execute();
    } finally {
        $stmt->close();
    }
}

/**
 * Processes the student form submission
 */
function processStudentSubmission() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        die("Invalid request method.");
    }

    try {
        $conn = connectDatabase();
        $studentData = validateStudentData($conn, $_POST);
        if ($studentData === false) {
            throw new Exception("Invalid data.");
        }

        if (insertStudentData($conn, $studentData)) {
            echo "Student record added successfully!";
        } else {
            echo "Failed to add student record.";
        }

        $conn->close();
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
    }
}

// Call the function to process the submission
processStudentSubmission();
?>
