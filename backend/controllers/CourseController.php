<?php
require_once __DIR__ . '/../models/Course.php';
require_once __DIR__ . '/../utils/Response.php';

class CourseController {
    public function getModules($courseID, $year, $semester) {
        if (empty($courseID) || empty($year) || empty($semester)) {
            Response::error("Missing courseID, year, or semester.");
        }
        $model = new Course();
        $modules = $model->getModulesByCourseYearSemester($courseID, $year, $semester);
        Response::success("Modules retrieved successfully", $modules);
    }
}
?>
