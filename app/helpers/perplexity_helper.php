<?php
// app/helpers/perplexity_helper.php

/**
 * Perplexity API Helper
 * Handles integration with Perplexity AI API for chatbot functionality
 */

class PerplexityAPI {
    private $apiKey;
    private $apiUrl = 'https://api.perplexity.ai/chat/completions';
    private $model = 'sonar'; // Using the most cost-effective model
    
    public function __construct($apiKey = null) {
        $this->apiKey = $apiKey ?: getenv('PERPLEXITY_API_KEY');
        
        if (!$this->apiKey) {
            throw new Exception('Perplexity API key is required. Set PERPLEXITY_API_KEY environment variable.');
        }
    }
    
    /**
     * Send a chat completion request to Perplexity API
     * 
     * @param array $messages Array of message objects with 'role' and 'content'
     * @param array $options Additional options (temperature, max_tokens, etc.)
     * @return array API response with 'content' and usage info
     */
    public function chat(array $messages, array $options = []): array {
        $defaultOptions = [
            'model' => $this->model,
            'messages' => $messages,
            'temperature' => 0.7,
            'max_tokens' => 500,
            'stream' => false
        ];
        
        $payload = array_merge($defaultOptions, $options);
        
        $ch = curl_init($this->apiUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->apiKey,
                'Content-Type: application/json'
            ],
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_TIMEOUT => 30
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($curlError) {
            throw new Exception("cURL Error: $curlError");
        }
        
        if ($httpCode !== 200) {
            $errorData = json_decode($response, true);
            $errorMsg = $errorData['error']['message'] ?? 'Unknown API error';
            throw new Exception("Perplexity API Error ($httpCode): $errorMsg");
        }
        
        $data = json_decode($response, true);
        
        if (!isset($data['choices'][0]['message']['content'])) {
            throw new Exception('Invalid API response format');
        }
        
        return [
            'content' => $data['choices'][0]['message']['content'],
            'usage' => $data['usage'] ?? [],
            'model' => $data['model'] ?? $this->model
        ];
    }
    
    /**
     * Simple convenience method for single-message queries
     */
    public function ask(string $question, ?string $systemPrompt = null): string {
        $messages = [];
        
        if ($systemPrompt) {
            $messages[] = [
                'role' => 'system',
                'content' => $systemPrompt
            ];
        }
        
        $messages[] = [
            'role' => 'user',
            'content' => $question
        ];
        
        $response = $this->chat($messages);
        return $response['content'];
    }
}

/**
 * Build context for Perplexity about available teachers and their data
 */
function buildTeacherContext(PDO $pdo, ?int $teacherId = null): string {
    if ($teacherId) {
        // Get specific teacher data
        $stmt = $pdo->prepare("
            SELECT 
                u.id, u.name, u.email,
                tp.employee_no, tp.department, tp.office_text, tp.current_room, tp.current_subject,
                (
                    SELECT status 
                    FROM teacher_status_events tse 
                    WHERE tse.teacher_user_id = u.id 
                    ORDER BY tse.set_at DESC 
                    LIMIT 1
                ) as latest_status,
                (
                    SELECT set_at 
                    FROM teacher_status_events tse 
                    WHERE tse.teacher_user_id = u.id 
                    ORDER BY tse.set_at DESC 
                    LIMIT 1
                ) as status_time,
                (
                    SELECT note
                    FROM teacher_notes tn
                    WHERE tn.teacher_user_id = u.id
                    AND (tn.expires_at IS NULL OR tn.expires_at > NOW())
                    ORDER BY tn.created_at DESC
                    LIMIT 1
                ) as latest_note
            FROM users u
            LEFT JOIN teacher_profiles tp ON u.id = tp.teacher_user_id
            WHERE u.id = ? AND u.role = 'teacher' AND u.is_active = 1
        ");
        $stmt->execute([$teacherId]);
        $teacher = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$teacher) {
            return "Teacher not found.";
        }
        
        // Get timetable
        $stmt = $pdo->prepare("
            SELECT day, start_time, end_time, subject, room, course_text
            FROM teacher_timetables
            WHERE teacher_user_id = ?
            ORDER BY FIELD(day, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'), start_time
        ");
        $stmt->execute([$teacherId]);
        $timetable = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $context = "Teacher Information:\n";
        $context .= "ID: {$teacher['id']}\n";
        $context .= "Name: {$teacher['name']}\n";
        $context .= "Department: " . ($teacher['department'] ?? 'N/A') . "\n";
        $context .= "Office: " . ($teacher['office_text'] ?? 'N/A') . "\n";
        $context .= "Email: {$teacher['email']}\n";
        $context .= "Current Status: " . ($teacher['latest_status'] ?? 'UNKNOWN') . "\n";
        
        if ($teacher['status_time']) {
            $diff = time() - strtotime($teacher['status_time']);
            if ($diff < 60) {
                $timeAgo = 'just now';
            } elseif ($diff < 3600) {
                $timeAgo = floor($diff / 60) . ' mins ago';
            } elseif ($diff < 86400) {
                $timeAgo = floor($diff / 3600) . ' hours ago';
            } else {
                $timeAgo = floor($diff / 86400) . ' days ago';
            }
            $context .= "Status Updated: {$timeAgo}\n";
        }
        
        // PRIVACY: Only show general location info, NEVER GPS coordinates
        if ($teacher['latest_status'] === 'OFF_CAMPUS') {
            $context .= "Location: Currently off campus\n";
        } elseif (!empty($teacher['current_room'])) {
            $context .= "Current Room: {$teacher['current_room']}\n";
        } elseif ($teacher['latest_status'] === 'AVAILABLE' || $teacher['latest_status'] === 'IN_CLASS') {
            $context .= "Location: On campus\n";
        }
        
        if (!empty($teacher['current_subject'])) {
            $context .= "Currently Teaching: {$teacher['current_subject']}\n";
        }
        
        if (!empty($teacher['latest_note'])) {
            $context .= "Note: {$teacher['latest_note']}\n";
        }
        
        if (!empty($timetable)) {
            $context .= "\nWeekly Schedule:\n";
            foreach ($timetable as $slot) {
                $context .= "- {$slot['day']} {$slot['start_time']}-{$slot['end_time']}: ";
                $context .= ($slot['subject'] ?? 'TBA');
                if (!empty($slot['course_text'])) {
                    $context .= " ({$slot['course_text']})";
                }
                if (!empty($slot['room'])) {
                    $context .= " in {$slot['room']}";
                }
                $context .= "\n";
            }
        } else {
            $context .= "\nNo schedule available.\n";
        }
        
        return $context;
    } else {
        // Get all teachers summary with their status and room
        $currentDay = date('l');
        $currentTime = date('H:i:s');
        
        $stmt = $pdo->query("
            SELECT 
                u.name, 
                tp.department,
                tp.current_room,
                (
                    SELECT status 
                    FROM teacher_status_events tse 
                    WHERE tse.teacher_user_id = u.id 
                    ORDER BY tse.set_at DESC 
                    LIMIT 1
                ) as latest_status,
                (
                    SELECT course_text
                    FROM teacher_timetables tt
                    WHERE tt.teacher_user_id = u.id
                    AND day = '$currentDay'
                    AND '$currentTime' BETWEEN start_time AND end_time
                    LIMIT 1
                ) as current_subject
            FROM users u
            LEFT JOIN teacher_profiles tp ON u.id = tp.teacher_user_id
            WHERE u.role = 'teacher' AND u.is_active = 1
            ORDER BY u.name
        ");
        $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $context = "Teacher Status Summary:\n";
        foreach ($teachers as $t) {
            $status = $t['latest_status'] ?? 'UNKNOWN';
            $context .= "- [{$status}] {$t['name']} (" . ($t['department'] ?? 'Faculty') . ")";
            
            if (!empty($t['current_room']) && $status !== 'OFF_CAMPUS' && $status !== 'OFFLINE') {
                $context .= " is in Room: {$t['current_room']}";
                if (!empty($t['current_subject'])) {
                    $context .= " teaching {$t['current_subject']}";
                }
            }
            $context .= "\n";
        }
        
        return $context;
    }
}

/**
 * Extract teacher name or ID from student query
 */
function extractTeacherFromQuery(string $query, PDO $pdo): ?array {
    // Try to find teacher name in the query
    $stmt = $pdo->query("
        SELECT id, name 
        FROM users 
        WHERE role = 'teacher' AND is_active = 1
    ");
    $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $queryLower = strtolower($query);
    
    foreach ($teachers as $teacher) {
        $nameLower = strtolower($teacher['name']);
        $nameParts = explode(' ', $nameLower);
        
        // Check full name
        if (strpos($queryLower, $nameLower) !== false) {
            return $teacher;
        }
        
        // Check first name
        if (count($nameParts) > 0 && strpos($queryLower, $nameParts[0]) !== false) {
            return $teacher;
        }
        
        // Check last name
        if (count($nameParts) > 1 && strpos($queryLower, end($nameParts)) !== false) {
            return $teacher;
        }
    }
    
    return null;
}
