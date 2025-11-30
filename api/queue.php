<?php
/**
 * Front Desk Queue API
 * GET/POST/PUT /api/queue.php
 *
 * Manages front desk prescription queue
 */

require_once __DIR__ . '/bootstrap.php';

// Allow multiple methods
requireMethod(['GET', 'POST', 'PUT']);

try {
    $prescriptionService = new PrescriptionService();

    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            // Get queue items
            $status = getParam('status');
            $queueId = getParam('id');

            if ($queueId) {
                // Get specific queue item with full details
                $sql = "SELECT fq.*, c.*, p.prescription_number, p.medications as rx_medications,
                               d.name as doctor_name, d.specialty
                        FROM frontdesk_queue fq
                        JOIN cases c ON fq.case_id = c.id
                        LEFT JOIN prescriptions p ON c.id = p.case_id
                        LEFT JOIN doctors d ON c.doctor_id = d.id
                        WHERE fq.id = ?";

                $stmt = Database::execute($sql, [(int)$queueId]);
                $item = $stmt->fetch();

                if (!$item) {
                    errorResponse('Queue item not found', 404);
                }

                // Parse JSON fields
                $item['medications'] = json_decode($item['medications'], true) ?? [];
                $item['rx_medications'] = json_decode($item['rx_medications'], true) ?? [];
                $item['ai_structured_data'] = json_decode($item['ai_structured_data'], true) ?? [];

                successResponse(['queue_item' => $item]);
            } else {
                // Get queue list
                $queue = $prescriptionService->getFrontDeskQueue($status);
                $statistics = $prescriptionService->getQueueStatistics();

                successResponse([
                    'queue' => $queue,
                    'total' => count($queue),
                    'statistics' => $statistics
                ]);
            }
            break;

        case 'POST':
            // Manually add item to queue (usually done automatically when prescription is saved)
            $data = array_merge($_POST, getJsonBody());

            if (empty($data['case_id'])) {
                errorResponse('case_id is required');
            }

            $caseModel = new CaseModel();
            $case = $caseModel->getById((int)$data['case_id']);

            if (!$case) {
                errorResponse('Case not found', 404);
            }

            $queueId = $prescriptionService->addToFrontDeskQueue((int)$data['case_id'], [
                'patient_name' => $case['patient_name'],
                'diagnosis' => $case['diagnosis'],
                'medications' => $case['medications']
            ]);

            successResponse([
                'queue_id' => $queueId,
                'case_id' => (int)$data['case_id']
            ], 'Added to queue successfully');
            break;

        case 'PUT':
            // Update queue item status
            $data = array_merge($_POST, getJsonBody());

            if (empty($data['id'])) {
                errorResponse('Queue ID is required');
            }

            if (empty($data['status'])) {
                errorResponse('Status is required');
            }

            $validStatuses = ['waiting', 'processing', 'ready', 'dispensed', 'cancelled'];
            if (!in_array($data['status'], $validStatuses)) {
                errorResponse('Invalid status. Valid statuses: ' . implode(', ', $validStatuses));
            }

            $success = $prescriptionService->updateQueueStatus(
                (int)$data['id'],
                $data['status'],
                $data['assigned_to'] ?? null
            );

            if (!$success) {
                errorResponse('Failed to update queue status');
            }

            successResponse([
                'queue_id' => (int)$data['id'],
                'status' => $data['status'],
                'updated' => true
            ], 'Queue status updated');
            break;
    }

} catch (Exception $e) {
    errorResponse($e->getMessage(), 500);
}
