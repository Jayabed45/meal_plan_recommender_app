# Meal Plan Recommender Application Documentation

## Table of Contents
1. [Project Overview](#project-overview)
2. [System Architecture](#system-architecture)
3. [Database Structure](#database-structure)
4. [User Interface Components](#user-interface-components)
5. [Core Features](#core-features)
6. [File Structure and Code Explanation](#file-structure-and-code-explanation)
7. [Security Features](#security-features)
8. [Cron Jobs and Automation](#cron-jobs-and-automation)

## Project Overview
The Meal Plan Recommender Application is a web-based system designed to help users manage their nutrition and meal planning. The application provides personalized meal recommendations based on user preferences, dietary requirements, and health goals.

## System Architecture
The application is built using:
- PHP for backend processing
- MySQL for database management
- HTML/CSS/JavaScript for frontend interface
- Bootstrap for responsive design

## Database Structure
The main database is named `meal_plan_recommender_main` and contains several tables:
- Users table for user information
- Meal plans table for storing recommended meal plans
- Survey responses table for user preferences
- Notifications table for system alerts
- Progress tracking table for user achievements

## User Interface Components

### User Section
1. **Login/Registration System**
   - `login.php`: Handles user authentication
   - `register.php`: Manages new user registration
   - `logout.php`: Handles user session termination

2. **User Dashboard**
   - `user_dashboard.php`: Main interface for users
   - Features:
     - View recommended meal plans
     - Track progress
     - Receive notifications
     - Complete surveys

3. **Survey System**
   - `survey_form.php`: Collects user preferences
   - `submit_survey.php`: Processes survey responses

4. **Progress Tracking**
   - `track_progress.php`: Monitors user achievements
   - `check_meal_times.php`: Tracks meal adherence

### Admin Section
1. **Admin Dashboard**
   - `admin_dashboard.php`: Main admin interface
   - Features:
     - User management
     - Meal plan recommendations
     - System monitoring

2. **User Management**
   - `manage_users.php`: List and manage users
   - `add_user.php`: Create new user accounts
   - `edit_user.php`: Modify user information
   - `delete_user.php`: Remove user accounts

3. **Meal Plan Management**
   - `recommend_meal_plan.php`: Generate and manage meal recommendations

## Core Features

### 1. User Authentication
- Secure login system
- Password hashing
- Session management
- Role-based access control

### 2. Meal Plan Recommendation
- Personalized meal suggestions
- Dietary requirement consideration
- Health goal alignment
- Nutritional balance

### 3. Progress Tracking
- Meal adherence monitoring
- Achievement tracking
- Progress visualization
- Goal setting and monitoring

### 4. Notification System
- Real-time alerts
- Meal reminders
- Achievement notifications
- System updates

## File Structure and Code Explanation

### Configuration Files
1. `config.php`
```php
<?php
// Database credentials - renamed variables to avoid conflict with form inputs
$db_host = "localhost";     // Database server location
$db_user = "root";         // Database username
$db_pass = "";             // Database password
$db_name = "meal_plan_recommender_main";  // Database name

// Create database connection using mysqli object-oriented approach
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Check connection and handle errors
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
```
This file establishes the database connection and contains essential configuration parameters.

### User Interface Files
1. `user_dashboard.php`
- Main user interface
- Displays meal plans
- Shows progress tracking
- Manages notifications

2. `admin_dashboard.php`
- Administrative interface
- User management
- System monitoring
- Meal plan management

### Processing Files
1. `submit_survey.php`
- Processes user survey responses
- Updates user preferences
- Triggers meal plan updates

2. `recommend_meal_plan.php`
- Generates personalized meal plans
- Considers user preferences
- Ensures nutritional balance

## Detailed File-by-File Explanations

### 1. Configuration Files

#### config.php
```php
<?php
// Database credentials
$db_host = "localhost";     // Database server location
$db_user = "root";         // Database username
$db_pass = "";             // Database password
$db_name = "meal_plan_recommender_main";  // Database name

// Create database connection
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
```
**Purpose:**
- Establishes database connection
- Contains essential configuration parameters
- Implements error handling for database connections
- Uses mysqli for secure database operations

### 2. Authentication Files

#### login.php
```php
<?php
session_start();
require 'config.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username_or_email = trim($_POST['username_or_email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Input validation
    if (!$username_or_email) $errors[] = "Username or email is required.";
    if (!$password) $errors[] = "Password is required.";

    if (!$errors) {
        // User authentication
        $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username_or_email, $username_or_email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows === 1) {
            $stmt->bind_result($id, $username, $hashed_password, $role);
            $stmt->fetch();

            if (password_verify($password, $hashed_password)) {
                // Session management
                $_SESSION['user_id'] = $id;
                $_SESSION['username'] = $username;
                $_SESSION['role'] = $role;

                // Role-based redirection
                if ($role === 'admin') {
                    header("Location: admin/admin_dashboard.php");
                } else {
                    header("Location: user/user_dashboard.php");
                }
                exit;
            }
        }
    }
}
?>
```
**Purpose:**
- Handles user authentication
- Implements secure session management
- Uses prepared statements for SQL injection prevention
- Includes input validation
- Manages role-based access control
- Provides error handling and user feedback

#### register.php
```php
<?php
session_start();
require 'config.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Input validation and sanitization
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validation checks
    if (!$username) $errors[] = "Username is required.";
    if (!$email) $errors[] = "Valid email is required.";
    if (!$password) $errors[] = "Password is required.";
    if ($password !== $confirm_password) $errors[] = "Passwords do not match.";

    if (!$errors) {
        // Check for existing user
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows === 0) {
            // Create new user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $insert = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            $insert->bind_param("sss", $username, $email, $hashed_password);
            $insert->execute();
        }
    }
}
?>
```
**Purpose:**
- Handles new user registration
- Implements secure password hashing
- Validates user input
- Checks for duplicate users
- Uses prepared statements for security
- Provides error handling and user feedback

### 3. User Interface Files

#### user_dashboard.php
```php
<?php
session_start();
require '../config.php';

// Authentication check
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

// Fetch user's meal plans
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("
    SELECT mp.*, m.meal_name, m.nutritional_info 
    FROM meal_plans mp 
    JOIN meals m ON mp.meal_id = m.id 
    WHERE mp.user_id = ? 
    ORDER BY mp.meal_date
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$meal_plans = $stmt->get_result();

// Fetch user's progress
$progress_stmt = $conn->prepare("
    SELECT 
        g.goal_type,
        g.target_value,
        COUNT(a.id) as achievements
    FROM user_goals g
    LEFT JOIN achievements a ON g.id = a.goal_id
    WHERE g.user_id = ?
    GROUP BY g.id
");
$progress_stmt->bind_param("i", $user_id);
$progress_stmt->execute();
$progress = $progress_stmt->get_result();
?>
```
**Purpose:**
- Main user interface
- Displays personalized meal plans
- Shows progress tracking
- Provides navigation to other features
- Implements session-based authentication

### 4. Admin Interface Files

#### admin_dashboard.php
```php
<?php
session_start();
require '../config.php';

// Admin authentication check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Fetch system statistics
$stmt = $conn->prepare("
    SELECT 
        COUNT(DISTINCT u.id) as total_users,
        COUNT(DISTINCT mp.id) as total_meal_plans,
        COUNT(DISTINCT s.id) as total_surveys
    FROM users u
    LEFT JOIN meal_plans mp ON u.id = mp.user_id
    LEFT JOIN surveys s ON u.id = s.user_id
");
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
?>
```
**Purpose:**
- Main admin interface
- Displays system statistics
- Provides user management
- Shows meal plan overview
- Implements admin authentication

### 5. Survey System Files

#### survey_form.php
```php
<?php
session_start();
require '../config.php';

// Authentication check
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

// Fetch survey questions
$stmt = $conn->prepare("
    SELECT 
        id,
        question_text,
        question_type,
        options
    FROM survey_questions
    ORDER BY question_order
");
$stmt->execute();
$questions = $stmt->get_result();
?>
```
**Purpose:**
- Displays survey questions
- Collects user preferences
- Implements form validation
- Stores survey responses
- Updates user preferences

### 6. Progress Tracking Files

#### track_progress.php
```php
<?php
session_start();
require '../config.php';

// Authentication check
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

function calculateUserProgress($user_id) {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT 
            g.goal_type,
            g.target_value,
            COUNT(a.id) as achievements
        FROM user_goals g
        LEFT JOIN achievements a ON g.id = a.goal_id
        WHERE g.user_id = ?
        GROUP BY g.id
    ");
    
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    return $stmt->get_result();
}

// Fetch user's progress
$user_id = $_SESSION['user_id'];
$progress = calculateUserProgress($user_id);
?>
```
**Purpose:**
- Tracks user progress
- Calculates achievement percentages
- Displays progress charts
- Updates goal status
- Provides progress feedback

### 7. Notification System Files

#### get_notifications.php
```php
<?php
session_start();
require '../config.php';

// Check user authentication
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

// Fetch unread notifications
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("
    SELECT 
        id,
        message,
        type,
        created_at
    FROM notifications
    WHERE user_id = ? 
    AND is_read = 0
    ORDER BY created_at DESC
    LIMIT 10
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$notifications = $stmt->get_result();
?>
```
**Purpose:**
- Manages user notifications
- Handles notification status
- Implements real-time updates
- Provides notification history
- Manages notification preferences

### 8. Meal Plan Recommendation Files

#### recommend_meal_plan.php
```php
<?php
session_start();
require '../config.php';

// Admin authentication check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

function generateMealPlan($user_id) {
    global $conn;
    
    // Get user preferences
    $preferences = getUserPreferences($user_id);
    
    // Get nutritional requirements
    $requirements = getNutritionalRequirements($user_id);
    
    // Generate meal plan
    $meal_plan = [];
    $daily_goals = calculateDailyGoals($requirements);
    $meals = selectMatchingMeals($preferences, $daily_goals);
    $meal_plan = distributeMeals($meals, $daily_goals);
    
    return $meal_plan;
}
?>
```
**Purpose:**
- Generates meal plans
- Considers user preferences
- Calculates nutritional requirements
- Distributes meals appropriately
- Ensures balanced nutrition

## Security Features
1. Password Hashing
2. SQL Injection Prevention
3. XSS Protection
4. Session Management
5. Input Validation
6. Access Control

## Cron Jobs and Automation
The system includes automated tasks:
1. Meal time checks
2. Notification generation
3. Progress updates
4. System maintenance

## Best Practices Implemented
1. Code Organization
2. Error Handling
3. Input Validation
4. Security Measures
5. Performance Optimization
6. User Experience Design

## Future Enhancements
1. Mobile Application
2. API Integration
3. Advanced Analytics
4. Social Features
5. Recipe Database
6. Meal Planning Calendar

## Maintenance and Support
1. Regular Updates
2. Bug Fixes
3. Performance Monitoring
4. Security Patches
5. User Support
6. Documentation Updates

## User Directory Files Explanation

### 1. user_dashboard.php
```php
<?php
session_start();
require '../config.php';

// Authentication check
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

// Fetch user's meal plans
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("
    SELECT mp.*, m.meal_name, m.nutritional_info 
    FROM meal_plans mp 
    JOIN meals m ON mp.meal_id = m.id 
    WHERE mp.user_id = ? 
    ORDER BY mp.meal_date
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$meal_plans = $stmt->get_result();

// Fetch user's progress
$progress_stmt = $conn->prepare("
    SELECT 
        g.goal_type,
        g.target_value,
        COUNT(a.id) as achievements
    FROM user_goals g
    LEFT JOIN achievements a ON g.id = a.goal_id
    WHERE g.user_id = ?
    GROUP BY g.id
");
$progress_stmt->bind_param("i", $user_id);
$progress_stmt->execute();
$progress = $progress_stmt->get_result();
?>
```
**Purpose:**
- Main user interface
- Displays personalized meal plans
- Shows progress tracking
- Provides navigation to other features
- Implements session-based authentication

### 2. view_meal_plan.php
```php
<?php
session_start();
require '../config.php';

// Authentication check
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

// Fetch specific meal plan
$meal_plan_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("
    SELECT 
        mp.*,
        m.meal_name,
        m.ingredients,
        m.instructions,
        m.nutritional_info
    FROM meal_plans mp
    JOIN meals m ON mp.meal_id = m.id
    WHERE mp.id = ? AND mp.user_id = ?
");
$stmt->bind_param("ii", $meal_plan_id, $user_id);
$stmt->execute();
$meal_plan = $stmt->get_result()->fetch_assoc();
?>
```
**Purpose:**
- Displays detailed meal plan information
- Shows ingredients and instructions
- Provides nutritional information
- Implements security checks
- Handles user-specific meal plans

### 3. survey_form.php
```php
<?php
session_start();
require '../config.php';

// Authentication check
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

// Fetch survey questions
$stmt = $conn->prepare("
    SELECT 
        id,
        question_text,
        question_type,
        options
    FROM survey_questions
    ORDER BY question_order
");
$stmt->execute();
$questions = $stmt->get_result();
?>
```
**Purpose:**
- Displays survey questions
- Collects user preferences
- Implements form validation
- Stores survey responses
- Updates user preferences

### 4. submit_survey.php
```php
<?php
session_start();
require '../config.php';

// Authentication check
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $responses = $_POST['responses'];
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Insert survey responses
        $stmt = $conn->prepare("
            INSERT INTO survey_responses 
            (user_id, question_id, response, created_at) 
            VALUES (?, ?, ?, NOW())
        ");
        
        foreach ($responses as $question_id => $response) {
            $stmt->bind_param("iis", $user_id, $question_id, $response);
            $stmt->execute();
        }
        
        // Update user preferences
        updateUserPreferences($user_id, $responses);
        
        $conn->commit();
        header("Location: user_dashboard.php");
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        $error = "Error submitting survey: " . $e->getMessage();
    }
}
?>
```
**Purpose:**
- Processes survey submissions
- Updates user preferences
- Implements transaction management
- Handles error cases
- Redirects after submission

### 5. track_progress.php
```php
<?php
session_start();
require '../config.php';

// Authentication check
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

function calculateUserProgress($user_id) {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT 
            g.goal_type,
            g.target_value,
            COUNT(a.id) as achievements
        FROM user_goals g
        LEFT JOIN achievements a ON g.id = a.goal_id
        WHERE g.user_id = ?
        GROUP BY g.id
    ");
    
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    return $stmt->get_result();
}

// Fetch user's progress
$user_id = $_SESSION['user_id'];
$progress = calculateUserProgress($user_id);
?>
```
**Purpose:**
- Tracks user progress
- Calculates achievement percentages
- Displays progress charts
- Updates goal status
- Provides progress feedback

### 6. check_meal_times.php
```php
<?php
session_start();
require '../config.php';

// Authentication check
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

function checkMealTimes($user_id) {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT 
            mp.meal_time,
            m.meal_name
        FROM meal_plans mp
        JOIN meals m ON mp.meal_id = m.id
        WHERE mp.user_id = ?
        AND mp.meal_date = CURDATE()
        ORDER BY mp.meal_time
    ");
    
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    return $stmt->get_result();
}

// Check today's meals
$user_id = $_SESSION['user_id'];
$meals = checkMealTimes($user_id);
?>
```
**Purpose:**
- Checks meal times
- Sends reminders
- Tracks meal adherence
- Updates meal status
- Provides meal notifications

## Admin Directory Files Explanation

### 1. admin_dashboard.php
```php
<?php
session_start();
require '../config.php';

// Admin authentication check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Fetch system statistics
$stmt = $conn->prepare("
    SELECT 
        COUNT(DISTINCT u.id) as total_users,
        COUNT(DISTINCT mp.id) as total_meal_plans,
        COUNT(DISTINCT s.id) as total_surveys
    FROM users u
    LEFT JOIN meal_plans mp ON u.id = mp.user_id
    LEFT JOIN surveys s ON u.id = s.user_id
");
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
?>
```
**Purpose:**
- Main admin interface
- Displays system statistics
- Provides user management
- Shows meal plan overview
- Implements admin authentication

### 2. manage_users.php
```php
<?php
session_start();
require '../config.php';

// Admin authentication check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Fetch users with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$stmt = $conn->prepare("
    SELECT 
        u.id,
        u.username,
        u.email,
        u.created_at,
        COUNT(mp.id) as meal_plans
    FROM users u
    LEFT JOIN meal_plans mp ON u.id = mp.user_id
    GROUP BY u.id
    ORDER BY u.created_at DESC
    LIMIT ? OFFSET ?
");
$stmt->bind_param("ii", $limit, $offset);
$stmt->execute();
$users = $stmt->get_result();
?>
```
**Purpose:**
- Lists all users
- Implements pagination
- Shows user statistics
- Provides user management options
- Handles user data display

### 3. add_user.php
```php
<?php
session_start();
require '../config.php';

// Admin authentication check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    $stmt = $conn->prepare("
        INSERT INTO users (username, email, password, role)
        VALUES (?, ?, ?, 'user')
    ");
    $stmt->bind_param("sss", $username, $email, $password);
    $stmt->execute();
}
?>
```
**Purpose:**
- Creates new user accounts
- Implements input validation
- Handles password hashing
- Sets user roles
- Provides error handling

### 4. edit_user.php
```php
<?php
session_start();
require '../config.php';

// Admin authentication check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$user_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    
    $stmt = $conn->prepare("
        UPDATE users 
        SET username = ?, email = ?
        WHERE id = ?
    ");
    $stmt->bind_param("ssi", $username, $email, $user_id);
    $stmt->execute();
}
?>
```
**Purpose:**
- Edits user information
- Updates user details
- Implements input validation
- Handles user updates
- Provides error handling

### 5. delete_user.php
```php
<?php
session_start();
require '../config.php';

// Admin authentication check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$user_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($user_id) {
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Delete user's data
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        
        $conn->commit();
        header("Location: manage_users.php");
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        $error = "Error deleting user: " . $e->getMessage();
    }
}
?>
```
**Purpose:**
- Deletes user accounts
- Implements transaction management
- Handles data cleanup
- Provides error handling
- Redirects after deletion

### 6. recommend_meal_plan.php
```php
<?php
session_start();
require '../config.php';

// Admin authentication check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

function generateMealPlan($user_id) {
    global $conn;
    
    // Get user preferences
    $preferences = getUserPreferences($user_id);
    
    // Get nutritional requirements
    $requirements = getNutritionalRequirements($user_id);
    
    // Generate meal plan
    $meal_plan = [];
    $daily_goals = calculateDailyGoals($requirements);
    $meals = selectMatchingMeals($preferences, $daily_goals);
    $meal_plan = distributeMeals($meals, $daily_goals);
    
    return $meal_plan;
}
?>
```
**Purpose:**
- Generates meal plans
- Considers user preferences
- Calculates nutritional requirements
- Distributes meals appropriately
- Ensures balanced nutrition