<?php
declare(strict_types=1);

namespace App\Modules\Jobs;

use App\Core\CSRF;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;

class JobController extends \App\Core\BaseController
{
    private JobService $service;

    public function __construct()
    {
        $this->service = new JobService();
        $this->adminActions = [];
    }

    public function index(Request $request, array $params = []): void
    {
        $filters = [];
        $status  = $request->get('status', '');
        $search  = trim((string) $request->get('search', ''));
        if ($status !== '') {
            $filters['status'] = $status;
        }
        if ($search !== '') {
            $filters['search'] = $search;
        }
        $jobs = $this->service->getAll($filters);
        View::render('jobs/index', ['jobs' => $jobs, 'status' => $status, 'search' => $search]);
    }

    public function create(Request $request, array $params = []): void
    {
        $form = $this->service->getFormData();
        View::render('jobs/create', array_merge($form, ['errors' => [], 'old' => []]));
    }

    public function store(Request $request, array $params = []): void
    {
        $data   = $request->all();
        $result = $this->service->create($data);
        if ($result['success']) {
            $this->redirectWithMessage('/jobs/' . $result['id'], 'Job created successfully.');
        }
        $form = $this->service->getFormData();
        View::render('jobs/create', array_merge($form, ['errors' => $result['errors'], 'old' => $data]));
    }

    public function show(Request $request, array $params = []): void
    {
        $job = $this->service->getDetail((int) $params['id']);
        if ($job === null) {
            Response::abort(404, 'Job not found.');
        }
        View::render('jobs/show', ['job' => $job]);
    }

    public function edit(Request $request, array $params = []): void
    {
        $job = $this->service->getById((int) $params['id']);
        if ($job === null) {
            Response::abort(404, 'Job not found.');
        }
        $form = $this->service->getFormData();
        View::render('jobs/edit', array_merge($form, ['job' => $job, 'errors' => []]));
    }

    public function update(Request $request, array $params = []): void
    {
        $id     = (int) $params['id'];
        $data   = $request->all();
        $result = $this->service->update($id, $data);
        if ($result['success']) {
            $this->redirectWithMessage('/jobs/' . $id, 'Job updated successfully.');
        }
        $form = $this->service->getFormData();
        $job  = array_merge($this->service->getById($id) ?? [], $data);
        View::render('jobs/edit', array_merge($form, ['job' => $job, 'errors' => $result['errors']]));
    }

    public function showComplete(Request $request, array $params = []): void
    {
        $job = $this->service->getById((int) $params['id']);
        if ($job === null) {
            Response::abort(404, 'Job not found.');
        }
        $contractorId = !empty($job['contractor_id']) ? (int) $job['contractor_id'] : null;
        $catalog   = $this->service->getMaterialsCatalog($contractorId);
        $locations = (new \App\Modules\Inventory\LocationRepository())->findAll();
        View::render('jobs/complete', ['job' => $job, 'catalog' => $catalog, 'locations' => $locations]);
    }

    public function handleComplete(Request $request, array $params = []): void
    {
        $id        = (int) $params['id'];
        $materials = $_POST['materials'] ?? [];
        $materials['completed_at']             = $_POST['completed_at'] ?? '';
        $materials['payment_method']           = $_POST['payment_method'] ?? 'cash';
        $materials['is_special_closing']       = !empty($_POST['is_special_closing']);
        $materials['provider_retained_amount'] = isset($_POST['provider_retained_amount']) && $_POST['provider_retained_amount'] !== ''
            ? (float) $_POST['provider_retained_amount']
            : null;

        if ($materials['is_special_closing'] && $materials['provider_retained_amount'] === null) {
            $this->redirectWithMessage('/jobs/' . $id . '/complete', 'Retained amount is required for Special Closing.', 'error');
            return;
        }

        $result = $this->service->complete($id, $materials);
        if ($result['success']) {
            $this->redirectWithMessage('/jobs/' . $id, 'Job marked as completed. Financials calculated.');
        }
        $this->redirectWithMessage('/jobs/' . $id . '/complete', 'Could not complete job.', 'error');
    }
}
