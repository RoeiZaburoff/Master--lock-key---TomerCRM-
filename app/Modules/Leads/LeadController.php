<?php
declare(strict_types=1);

namespace App\Modules\Leads;

use App\Core\Auth;
use App\Core\CSRF;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;

class LeadController extends \App\Core\BaseController
{
    private LeadService $service;

    public function __construct()
    {
        $this->service = new LeadService();
        $this->adminActions = ['destroy'];
    }

    public function index(Request $request, array $params = []): void
    {
        $filters = [];
        $status  = $request->get('status', '');
        $search  = trim((string) $request->get('q', ''));
        $period  = $request->get('period', '');

        if ($status !== '') {
            $filters['status'] = $status;
        }
        if ($search !== '') {
            $filters['search'] = $search;
        }
        if (in_array($period, ['daily', 'weekly', 'monthly'])) {
            $filters['period'] = $period;
        }

        $leads = $this->service->getAll($filters);
        View::render('leads/index', [
            'leads'  => $leads,
            'status' => $status,
            'q'      => $search,
            'period' => $period,
        ]);
    }

    public function create(Request $request, array $params = []): void
    {
        $form         = $this->service->getFormData();
        $preselect    = (int) $request->get('customer_id', 0);
        $predate      = $request->get('date', '');
        View::render('leads/create', array_merge($form, [
            'errors'     => [],
            'old'        => ['customer_id' => $preselect ?: '', 'scheduled_date' => $predate],
        ]));
    }

    public function store(Request $request, array $params = []): void
    {
        $data   = $request->all();
        $result = $this->service->create($data);
        $isAjax = (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest');

        if ($result['success']) {
            if ($isAjax) {
                Response::json(['success' => true, 'id' => $result['id']]);
            }
            $this->redirectWithMessage('/leads/' . $result['id'], 'Lead created successfully.');
        }

        if ($isAjax) {
            Response::json(['success' => false, 'errors' => $result['errors']]);
        }

        $form = $this->service->getFormData();
        View::render('leads/create', array_merge($form, ['errors' => $result['errors'], 'old' => $data]));
    }

    public function show(Request $request, array $params = []): void
    {
        $lead = $this->service->getDetail((int) $params['id']);
        if ($lead === null) {
            Response::abort(404, 'Lead not found.');
        }
        View::render('leads/show', ['lead' => $lead]);
    }

    public function edit(Request $request, array $params = []): void
    {
        $lead = $this->service->getById((int) $params['id']);
        if ($lead === null) {
            Response::abort(404, 'Lead not found.');
        }
        $form = $this->service->getFormData();
        View::render('leads/edit', array_merge($form, ['lead' => $lead, 'errors' => []]));
    }

    public function update(Request $request, array $params = []): void
    {
        $id     = (int) $params['id'];
        $data   = $request->all();
        $result = $this->service->update($id, $data);
        if ($result['success']) {
            $this->redirectWithMessage('/leads/' . $id, 'Lead updated successfully.');
        }
        $form = $this->service->getFormData();
        $lead = array_merge($this->service->getById($id) ?? [], $data);
        View::render('leads/edit', array_merge($form, ['lead' => $lead, 'errors' => $result['errors']]));
    }

    public function destroy(Request $request, array $params = []): void
    {
        $this->service->delete((int) $params['id']);
        $this->redirectWithMessage('/leads', 'Lead deleted.');
    }

    public function duplicate(Request $request, array $params = []): void
    {
        $result = $this->service->duplicate((int) $params['id']);
        if ($result['success']) {
            $this->redirectWithMessage('/leads/' . $result['id'] . '/edit', 'Lead duplicated. Review and update as needed.');
        }
        $this->redirectWithMessage('/leads/' . $params['id'], 'Could not duplicate lead.', 'error');
    }

    public function showComplete(Request $request, array $params = []): void
    {
        $lead = $this->service->getById((int) $params['id']);
        if ($lead === null) {
            Response::abort(404, 'Lead not found.');
        }
        $catalog   = $this->service->getMaterialsCatalog();
        $locations = (new \App\Modules\Inventory\LocationRepository())->findAll();
        View::render('leads/complete', ['lead' => $lead, 'catalog' => $catalog, 'locations' => $locations]);
    }

    public function handleComplete(Request $request, array $params = []): void
    {
        $id    = (int) $params['id'];
        $data  = $request->all();
        $data['materials'] = $_POST['materials'] ?? [];
        $result = $this->service->complete($id, $data);

        if ($result['success']) {
            $this->redirectWithMessage('/leads/' . $id, 'Lead marked as completed. Financials calculated.');
        }

        $errorMsg = 'Could not complete lead.';
        if (!empty($result['errors']['provider_retained_amount'])) {
            $errorMsg = $result['errors']['provider_retained_amount'][0];
        }

        $this->redirectWithMessage('/leads/' . $id . '/complete', $errorMsg, 'error');
    }

    public function showEditMaterials(Request $request, array $params = []): void
    {
        $id   = (int) $params['id'];
        $lead = $this->service->getById($id);
        if ($lead === null) {
            Response::abort(404, 'Lead not found.');
        }
        $catalog   = $this->service->getMaterialsCatalog();
        $materials = $this->service->getMaterials($id);
        $locations = (new \App\Modules\Inventory\LocationRepository())->findAll();
        View::render('leads/edit_materials', ['lead' => $lead, 'catalog' => $catalog, 'materials' => $materials, 'locations' => $locations]);
    }

    public function handleEditMaterials(Request $request, array $params = []): void
    {
        $id   = (int) $params['id'];
        $data = $request->all();
        $data['materials'] = $_POST['materials'] ?? [];
        $result = $this->service->editMaterials($id, $data);
        if ($result['success']) {
            Session::flash('success', 'Costs updated and profit recalculated.');
        } else {
            Session::flash('error', 'Could not update costs.');
        }
        Response::redirect('/leads/' . $id);
    }

    public function convertToJob(Request $request, array $params = []): void
    {
        $id     = (int) $params['id'];
        $result = $this->service->convertToJob($id, $request->all());
        if ($result['success']) {
            $this->redirectWithMessage('/leads/' . $id, 'Estimate converted to job successfully.');
        } else {
            $this->redirectWithMessage('/leads/' . $id, 'Could not convert estimate.', 'error');
        }
    }

    public function estimatePdf(Request $request, array $params = []): void
    {
        $lead = $this->service->getDetail((int) $params['id']);
        if ($lead === null || ($lead['status'] ?? '') !== 'estimate') {
            Response::abort(404, 'Estimate not found.');
        }
        $pdf = new \App\Modules\Leads\EstimatePdf();
        $pdf->generate($lead);
    }
}
