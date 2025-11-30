<?php
/**
 * Doctors API
 * GET /api/doctors.php
 *
 * Retrieves doctor information
 */

require_once __DIR__ . '/bootstrap.php';

// Require GET method
requireMethod('GET');

try {
    $doctorModel = new DoctorModel();

    $doctorId = getParam('id');
    $doctorCode = getParam('code');
    $department = getParam('department');

    if ($doctorId) {
        // Get single doctor by ID
        $doctor = $doctorModel->getById((int)$doctorId);

        if (!$doctor) {
            errorResponse('Doctor not found', 404);
        }

        successResponse(['doctor' => $doctor]);
    } elseif ($doctorCode) {
        // Get single doctor by code
        $doctor = $doctorModel->getByCode($doctorCode);

        if (!$doctor) {
            errorResponse('Doctor not found', 404);
        }

        successResponse(['doctor' => $doctor]);
    } elseif ($department) {
        // Get doctors by department
        $doctors = $doctorModel->getByDepartment($department);

        successResponse([
            'doctors' => $doctors,
            'department' => $department,
            'total' => count($doctors)
        ]);
    } else {
        // Get all active doctors
        $doctors = $doctorModel->getActive();

        // Get unique departments
        $departments = array_unique(array_filter(array_column($doctors, 'department')));

        successResponse([
            'doctors' => $doctors,
            'departments' => array_values($departments),
            'total' => count($doctors)
        ]);
    }

} catch (Exception $e) {
    errorResponse($e->getMessage(), 500);
}
