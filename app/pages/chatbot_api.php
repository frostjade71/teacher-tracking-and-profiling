<?php
// app/pages/chatbot_api.php

// Start output buffering to catch any PHP errors/warnings
ob_start();

// Set error handling to prevent HTML output
ini_set('display_errors', 0);

header('Content-Type: application/json');

require_login();
require_role('student');

// Clear any output that might have been generated
ob_clean();

// Rate limiting: max 30 requests per minute per user
// Session already started by require_login()
$userId = current_user()['id'];
$rateLimitKey = "chatbot_rate_limit_{$userId}";
$rateLimitWindow = 60; // seconds
$maxRequests = 30;

if (!isset($_SESSION[$rateLimitKey])) {
    $_SESSION[$rateLimitKey] = ['count' => 0, 'start_time' => time()];
}

$rateLimit = $_SESSION[$rateLimitKey];
if (time() - $rateLimit['start_time'] > $rateLimitWindow) {
    // Reset window
    $_SESSION[$rateLimitKey] = ['count' => 1, 'start_time' => time()];
} else {
    if ($rateLimit['count'] >= $maxRequests) {
        echo json_encode([
            'success' => false,
            'error' => 'Rate limit exceeded. Please wait a moment before sending more messages.'
        ]);
        exit;
    }
    $_SESSION[$rateLimitKey]['count']++;
}

// Get request data
$input = json_decode(file_get_contents('php://input'), true);
$userMessage = $input['message'] ?? '';

if (empty(trim($userMessage))) {
    echo json_encode([
        'success' => false,
        'error' => 'Message is required'
    ]);
    exit;
}

try {
    require_once __DIR__ . '/../helpers/perplexity_helper.php';
    
    $pdo = db();
    $perplexity = new PerplexityAPI();
    
    // Extract teacher from query if mentioned
    $teacher = extractTeacherFromQuery($userMessage, $pdo);
    
    // CONTEXT MEMORY:
    // If no teacher found in current query, check if it's a follow-up question about the last discussed teacher
    // Keywords: room, schedule, class, where, time, subject, status
    if (!$teacher && isset($_SESSION['last_chatbot_teacher_id'])) {
        $followUpKeywords = ['room', 'schedule', 'class', 'where', 'time', 'subject', 'status', 'office', 'detail'];
        $isFollowUp = false;
        foreach ($followUpKeywords as $keyword) {
            if (stripos($userMessage, $keyword) !== false) {
                $isFollowUp = true;
                break;
            }
        }
        
        if ($isFollowUp) {
            // Retrieve last teacher details to inject into context
            error_log("Chatbot: Using context memory for teacher ID: " . $_SESSION['last_chatbot_teacher_id']);
            $teacher = ['id' => $_SESSION['last_chatbot_teacher_id']];
        }
    }

    // Build context
    if ($teacher) {
        $context = buildTeacherContext($pdo, $teacher['id']);
        // Save to session for next turn
        $_SESSION['last_chatbot_teacher_id'] = $teacher['id'];
    } else {
        $context = buildTeacherContext($pdo);
    }
    
    // System prompt for Perplexity
    $systemPrompt = "You are LinkyBot, a specialized Faculty Tracking Assistant for [School Name].
    
YOUR MAIN TASK is to help students find teachers, check their status, view schedules, and locate rooms.

VALID QUESTIONS YOU MUST ANSWER:
- \"Where is [Name]?\" or \"Status of [Name]?\"
- \"Who is in [Department]?\" (e.g., \"Computer Science teachers\")
- \"List all teachers\" or \"Available teachers\"
- \"Classes right now?\" or \"Ongoing classes\"
- \"Room number?\" or \"Schedule?\"
- \"Are there any available teachers?\"
- \"Who is online?\"

NOTE: Treat \"Ongoing Classes\" as \"What classes are active?\".

REFUSAL RULE: Only refuse questions CLEARLY UNRELATED to faculty tracking (e.g. math, coding, jokes). 
ALWAYS accept greetings, closings, and gratitude.

ALLOWED FOLLOW-UP QUESTIONS: If context exists, answer short questions like \"Room?\", \"Subject?\", \"What class?\", or \"Status?\".

DEFINITIONS:
- \"Online Teachers\" or \"Active Teachers\" includes anyone with status: AVAILABLE, BUSY, IN CLASS, or OFF CAMPUS. (Only EXCLUDE 'OFFLINE' or 'UNAVAILABLE').

RESPONSE FORMATS:
1. IF GREETING OR OPENING (Hi, Hello, Hi there, Are you LinkyBot?, Start, Help, Question):
   \"Hello! I am LinkyBot, your Faculty Tracking Assistant. 😊 How can I help you find a teacher today?\"

2. IF NO MORE QUESTIONS (No, None, Nothing, No thanks, None thanks):
   \"Great!😊 If you have any questions in mind let me know\"

3. IF GRATITUDE (Thanks, Thank you, Appreciate, Good job):
   \"Glad I could help! 😊 Is there anything else I can help you with?\"

4. IF CLOSING (Okay, Ok, k, bye, cya, good):
   \"Is there anything else I can help with?\"

5. IF ASKING ABOUT GROUP STATUS (Who is available? Are there any in class teachers?):
   IF TEACHERS FOUND:
   \"Here are the teachers who are [Status]:
   - [Teacher Name] ([Department])
   - [Teacher Name] ([Department])
   
   Please see the [Live Campus Map] for details.\"
   
   IF NO TEACHERS FOUND:
   \"Currently, there are no teachers who are [Status] right now.\"\"

6. IF ASKING ABOUT ACTIVE ROOMS (What rooms are active? Where are classes held?):
   IF ROOMS ARE ACTIVE:
   \"There is only [Number] classes being held right now:
   - [Room Name] ([Teacher Name]) - [Subject if known]
   - [Room Name] ([Teacher Name]) - [Subject if known]
   
   Please check the [Live Campus Map] to see exact locations.\"
   
   IF NO ROOMS ACTIVE:
   \"There is no classes being held right now.\"

7. IF IRRELEVANT QUESTION:
   \"I am only available for faculty tracking assistance. Please ask about a teacher, schedule, or location.\"

8. IF ASKING FOR TIMETABLE/SCHEDULE:
   \"[ [Teacher Name] Timetable ](index.php?page=student_teacher&id=[ID])\"

9. IF TEACHER IS OFFLINE or NO LOCATION DATA:
   \"Currently [Teacher Name] is Offline. (Updated [Time Since Update])\"

10. IF TEACHER HAS LOCATION/STATUS (Available, In Class, Busy):
   \"[Teacher Name] is [Status] please see the [Live Campus Map]\"
   (Always include the exact text '[Live Campus Map]' when location is available)

Current Teacher Data:
$context

Please follow these rules strictly.";
    
    // Get response from Perplexity
    $response = $perplexity->ask($userMessage, $systemPrompt);
    
    // Log the interaction (optional)
    try {
        $stmt = $pdo->prepare("
            INSERT INTO audit_logs (actor_user_id, action, entity_type, metadata_json)
            VALUES (?, 'chatbot_query', 'chatbot', ?)
        ");
        $stmt->execute([
            $userId,
            json_encode([
                'query' => substr($userMessage, 0, 200),
                'teacher_id' => $teacher['id'] ?? null,
                'teacher_name' => $teacher['name'] ?? null
            ])
        ]);
    } catch (Exception $logError) {
        // Silently fail logging - don't break the response
        error_log("Chatbot logging error: " . $logError->getMessage());
    }
    
    echo json_encode([
        'success' => true,
        'message' => $response,
        'metadata' => [
            'teacher_id' => $teacher['id'] ?? null,
            'teacher_name' => $teacher['name'] ?? null,
            'timestamp' => date('c')
        ]
    ]);
    
} catch (Exception $e) {
    // Log error
    error_log("Chatbot API Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    // Friendly fallback response
    $fallbackResponse = "";
    
    // Try to provide basic info if teacher was identified
    if (isset($teacher) && isset($pdo)) {
        try {
            $stmt = $pdo->prepare("
                SELECT 
                    (SELECT status FROM teacher_status_events WHERE teacher_user_id = ? ORDER BY set_at DESC LIMIT 1) as status,
                    (SELECT set_at FROM teacher_status_events WHERE teacher_user_id = ? ORDER BY set_at DESC LIMIT 1) as status_time
            ");
            $stmt->execute([$teacher['id'], $teacher['id']]);
            $basicInfo = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($basicInfo && $basicInfo['status']) {
                $status = $basicInfo['status'];
                // Format status for display (e.g. OFF_CAMPUS -> Off Campus)
                if ($status == 'OFF_CAMPUS') $status = 'OFF CAMPUS';
                
                $fallbackResponse .= "{$teacher['name']} is currently {$status}.";
                if ($basicInfo['status_time']) {
                    $fallbackResponse .= " (Updated " . date('M j, g:i a', strtotime($basicInfo['status_time'])) . ")";
                }
                
                if ($status == 'AVAILABLE' || $status == 'IN_CLASS' || $status == 'BUSY') {
                     $fallbackResponse .= " please see the [Live Campus Map]";
                }
            } else {
                $fallbackResponse .= "I found {$teacher['name']} but status information is unavailable.";
            }
        } catch (Exception $fbError) {
             $fallbackResponse = "Try asking about a specific teacher like 'Where is Dr. Smith?' or 'What's Professor Johnson's schedule?'";
        }
    } else {
        $fallbackResponse = "Try asking about a specific teacher like 'Where is Dr. Smith?' or 'What's Professor Johnson's schedule?'";
    }
    
    echo json_encode([
        'success' => true,
        'message' => $fallbackResponse,
        'fallback' => true,
        'error_type' => get_class($e)
    ]);
}

// End output buffering and send
ob_end_flush();

