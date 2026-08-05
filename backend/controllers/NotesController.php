<?php
namespace Controllers;
use Models\Notes;
use Middleware\AuthMiddleware;

class NotesController {
    public function handleRequest($method, $id = null, $action = null) {
        $decoded = AuthMiddleware::authenticate();
        $model = new Notes();

        if ($method === 'GET') {
            if ($action === 'search') {
                $q = $_GET['q'] ?? '';
                echo json_encode(['success' => true, 'data' => $model->search($q)]);
                return;
            }
            if ($action === 'download' && $id) {
                echo json_encode(['success' => true, 'url' => $model->download($id)]);
                return;
            }
            $filters = $_GET;
            $filters['enrollmentNo'] = $decoded->enrollmentNo ?? null;
            echo json_encode(['success' => true, 'data' => $model->view($id, $filters)]);
        } elseif ($method === 'POST') {
            $data = $_POST;
            $data['enrollmentNo'] = $decoded->enrollmentNo ?? $decoded->enrollment_no ?? null;
            $data['userID'] = $decoded->userID ?? null;
            $data['file_url'] = null; // Default value to prevent undefined key error
            
            if (isset($_FILES['file'])) {
                if ($_FILES['file']['error'] !== UPLOAD_ERR_OK) {
                    $uploadErrors = [
                        UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize in php.ini',
                        UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE in HTML form',
                        UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
                        UPLOAD_ERR_NO_FILE => 'No file was uploaded',
                        UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder',
                        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                        UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload',
                    ];
                    $errorMsg = $uploadErrors[$_FILES['file']['error']] ?? 'Unknown upload error';
                    echo json_encode(['success' => false, 'message' => 'Upload failed: ' . $errorMsg]);
                    return;
                }
                
                $mimeType = mime_content_type($_FILES['file']['tmp_name']);
                $allowedDocTypes = [
                    'application/pdf', 
                    'application/msword', 
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document', // docx
                    'application/vnd.ms-powerpoint', 
                    'application/vnd.openxmlformats-officedocument.presentationml.presentation', // pptx
                    'image/jpeg', 'image/png', 'image/webp'
                ];
                
                if (!in_array($mimeType, $allowedDocTypes)) {
                    echo json_encode(['success' => false, 'message' => 'Invalid file type. Only PDF, DOC, PPT, and Images are allowed.']);
                    return;
                }

                try {
                    $url = \Services\CloudinaryUploader::upload($_FILES['file']['tmp_name'], 'auto');
                    $data['file_url'] = $url;
                } catch (\Exception $e) {
                    file_put_contents(__DIR__ . '/../error_log.txt', date('[Y-m-d H:i:s] ') . "Notes Cloudinary Upload Error: " . $e->getMessage() . "\n", FILE_APPEND);
                    
                    // Fallback to local upload
                    $uploadDir = __DIR__ . '/../uploads/notes/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }
                    $fileName = time() . '_' . uniqid() . '_' . preg_replace("/[^a-zA-Z0-9\._-]/", "", basename($_FILES['file']['name']));
                    $destPath = $uploadDir . $fileName;
                    if (move_uploaded_file($_FILES['file']['tmp_name'], $destPath)) {
                        $data['file_url'] = 'uploads/notes/' . $fileName;
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Cloudinary upload failed, and local fallback also failed.']);
                        return;
                    }
                }
            }

            if (!$data['file_url']) {
                echo json_encode(['success' => false, 'message' => 'File upload failed.']);
                return;
            }

            $nid = $model->upload($data);
            echo json_encode(['success' => true, 'noteID' => $nid]);
        } elseif ($method === 'PUT') {
            $data = json_decode(file_get_contents("php://input"), true);
            echo json_encode(['success' => $model->update($id, $data)]);
        } elseif ($method === 'DELETE') {
            $note = $model->view($id);
            if (!$note) {
                echo json_encode(['success' => false, 'message' => 'Note not found']);
                return;
            }
            
            $canDelete = false;
            if ($decoded->role === 'student' || $decoded->role === 'student') { // Just to be safe if it's student
                if (isset($decoded->enrollmentNo) && strtolower($decoded->enrollmentNo) === strtolower($note['enrollmentNo'])) {
                    $canDelete = true;
                }
            } elseif ($decoded->role === 'course_representative' || $decoded->role === 'rep') {
                // Check if rep belongs to the same course and year
                $db = \Config\Database::getInstance()->getConnection();
                $stmt = $db->prepare("SELECT s.courseID, s.std_year FROM student s WHERE s.enrollmentNo = :enr");
                $stmt->execute([':enr' => $decoded->enrollmentNo]);
                $repData = $stmt->fetch(\PDO::FETCH_ASSOC);
                
                if ($repData && $repData['courseID'] == $note['courseID']) {
                    $canDelete = true;
                }
            } elseif ($decoded->role === 'admin') {
                $canDelete = true;
            }

            if ($canDelete) {
                echo json_encode(['success' => $model->delete($id)]);
            } else {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Unauthorized to delete this note']);
            }
        }
    }
}
