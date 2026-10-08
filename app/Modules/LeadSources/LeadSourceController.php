<?php
declare(strict_types=1);

namespace App\Modules\LeadSources;

use App\Core\Auth;
use App\Core\CSRF;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;

class LeadSourceController extends \App\Core\BaseController
{
    private LeadSourceService $service;

    public function __construct()
    {
        $this->service = new LeadSourceService();
        $this->adminActions = ['*'];
    }

    public function index(Request $request, array $params = []): void
    {
        View::render('lead_sources/index', ['sources' => $this->service->getAll()]);
    }

    public function create(Request $request, array $params = []): void
    {
        View::render('lead_sources/create', ['errors' => [], 'old' => []]);
    }

    public function store(Request $request, array $params = []): void
    {
        $data   = $request->all();
        $result = $this->service->create($data);
        if ($result['success']) {
            $this->redirectWithMessage('/lead-sources', 'Lead source created.');
        }
        View::render('lead_sources/create', ['errors' => $result['errors'], 'old' => $data]);
    }

    public function edit(Request $request, array $params = []): void
    {
        $source = $this->service->getById((int) $params['id']);
        if ($source === null) {
            Response::abort(404);
        }
        View::render('lead_sources/edit', ['source' => $source, 'errors' => []]);
    }

    public function update(Request $request, array $params = []): void
    {
        $id     = (int) $params['id'];
        $data   = $request->all();
        $result = $this->service->update($id, $data);
        if ($result['success']) {
            $this->redirectWithMessage('/lead-sources', 'Lead source updated.');
        }
        $source = array_merge($this->service->getById($id) ?? [], $data);
        View::render('lead_sources/edit', ['source' => $source, 'errors' => $result['errors']]);
    }

    public function destroy(Request $request, array $params = []): void
    {
        $this->service->delete((int) $params['id']);
        $this->redirectWithMessage('/lead-sources', 'Lead source deleted.');
    }
}
