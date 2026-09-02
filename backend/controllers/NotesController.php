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
                if (!$this->validatePayload(['q' => $q], ['q' => 'required|string|maxLength:100'])) {
                    return;
                }
                echo json_encode(['success' => true, 'data' => $model->search($q)]);
                return;
            }
            if ($action === 'download' && $id) {
                if (!$this->validatePayload(['noteID' => $id], ['noteID' => 'required|positiveInteger'])) {
                    return;
                }
                echo json_encode(['success' => true, 'url' => $model->download($id)]);
                return;
            }

            if ($id !== null && !$this->validatePayload(['noteID' => $id], ['noteID' => 'required|positiveInteger'])) {
                return;
            }

            $filters = $_GET;
            if (!$this->validatePayload($filters, [
                'courseUnitID' => 'nullable|string|maxLength:20',
                'academicYear' => 'nullable|integer|min:1|max:4',
                'semester' => 'nullable|integer|min:1|max:2',
                'courseCode' => 'nullable|string|maxLength:10|regex:/^[A-Za-z]+$/D'
            ])) {
                return;
            }
            $filters['enrollmentNo'] = $decoded->enrollmentNo ?? null;
            echo json_encode(['success' => true, 'data' => $model->view($id, $filters)]);
        } elseif ($method === 'POST') {
            $data = $_POST;
            
            if (!$this->validatePayload($data, [
                'title' => 'required|string|maxLength:200',
                'courseUnitID' => 'required|string|maxLength:20',
                'description' => 'nullable|string|maxLength:5000',
                'academicYear' => 'required|integer|min:1|max:4',
                'noteType' => 'required|string|in:notes,past_paper,scheme'
            ])) {
                return;
            }

            if (!$this->validatePayload(
                ['file' => $_FILES['file'] ?? null],
                [
                    'file' => 'uploaded|maxFileSize:10485760|mimes:application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-powerpoint,application/vnd.openxmlformats-officedocument.presentationml.presentation,image/jpeg,image/png,image/webp'
                ]
            )) {
                return;
            }
            
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

            if (!isset($data['file_url']) || !$data['file_url']) {
                echo json_encode(['success' => false, 'message' => 'File upload failed.']);
                return;
            }

            $model->hydrateFromRequest($data);
            $model->setEnrollmentNo($data['enrollmentNo']);
            $model->setUserID($data['userID']);
            $nid = $model->upload();
            echo json_encode(['success' => true, 'noteID' => $nid]);
        } elseif ($method === 'PUT') {
            $data = json_decode(file_get_contents("php://input"), true) ?? [];
            
            if (!$this->validatePayload(array_merge($data, ['noteID' => $id]), [
                'noteID' => 'required|positiveInteger',
                'title' => 'required|string|maxLength:200',
                'description' => 'nullable|string|maxLength:5000'
            ])) {
                return;
            }
            
            $model->hydrateFromRequest($data);
            $model->setNoteID($id);
            echo json_encode(['success' => $model->update()]);
        } elseif ($method === 'DELETE') {
            if (!$this->validatePayload(['noteID' => $id], ['noteID' => 'required|positiveInteger'])) {
                return;
            }
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
                $studentDao = new \DAO\StudentDAO();
                $repData = $studentDao->getStudentByEnrollmentNo($decoded->enrollmentNo);
                
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
        } else {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
        }
    }

    private function validatePayload(array $data, array $rules): bool {
        $validator = new \Utils\Validator($data);
        $validator->validate($rules);

        if ($validator->passes()) {
            return true;
        }

        echo json_encode([
            'success' => false,
            'message' => $validator->getFirstError(),
            'errors' => $validator->getErrors()
        ]);
        return false;
    }
}
