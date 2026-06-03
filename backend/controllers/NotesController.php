<?php
require_once __DIR__ . '/../models/Notes.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/Validator.php';

class NotesController {
    
    public function getNotes($filters = []) {
        $model = new Notes();
        $notes = $model->getAll($filters);
        Response::success("Notes retrieved", $notes);
    }

    public function uploadNote($data, $user_id, $file) {
        $missing = Validator::required(['title', 'course_code', 'year', 'semester'], $data);
        if (!empty($missing)) {
            Response::error("Missing fields: " . implode(', ', $missing));
        }

        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            Response::error("File upload failed.");
        }

        // Handle file upload
        $uploadDir = __DIR__ . '/../uploads/notes/';
        $fileName = time() . '_' . basename($file['name']);
        $uploadFilePath = $uploadDir . $fileName;

        if (move_uploaded_file($file['tmp_name'], $uploadFilePath)) {
            $model = new Notes();
            $noteData = [
                'uploader_id' => $user_id,
                'title' => $data['title'],
                'description' => isset($data['description']) ? $data['description'] : null,
                'file_url' => 'uploads/notes/' . $fileName,
                'course_code' => $data['course_code'],
                'year' => $data['year'],
                'semester' => $data['semester']
            ];

            if ($model->create($noteData)) {
                Response::success("Note uploaded successfully.");
            } else {
                Response::error("Database insertion failed.", 500);
            }
        } else {
            Response::error("Failed to move uploaded file.", 500);
        }
    }
}
?>
