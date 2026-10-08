<?php
declare(strict_types=1);

namespace App\Modules\Contractors;

use App\Core\Auth;
use App\Core\CSRF;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;

class ContractorController extends \App\Core\BaseController
{
    private ContractorService $service;

    public function __construct()
    {
        $this->service = new ContractorService();
        $this->adminActions = ['*'];
    }

    public function index(Request $request, array $params = []): void
    {
        View::render('contractors/index', ['contractors' => $this->service->getAll()]);
    }

    public function create(Request $request, array $params = []): void
    {
        View::render('contractors/create', ['errors' => [], 'old' => []]);
    }

    public function store(Request $request, array $params = []): void
    {
        $data   = $request->all();
        $result = $this->service->create($data);
        if ($result['success']) {
            $this->redirectWithMessage('/contractors', 'Contractor created.');
        }
        View::render('contractors/create', ['errors' => $result['errors'], 'old' => $data]);
    }

    public function edit(Request $request, array $params = []): void
    {
        $contractor = $this->service->getById((int) $params['id']);
        if ($contractor === null) {
            Response::abort(404);
        }
        View::render('contractors/edit', ['contractor' => $contractor, 'errors' => []]);
    }

    public function update(Request $request, array $params = []): void
    {
        $id     = (int) $params['id'];
        $data   = $request->all();
        $result = $this->service->update($id, $data);
        if ($result['success']) {
            $this->redirectWithMessage('/contractors', 'Contractor updated.');
        }
        $contractor = array_merge($this->service->getById($id) ?? [], $data);
        View::render('contractors/edit', ['contractor' => $contractor, 'errors' => $result['errors']]);
    }

    public function destroy(Request $request, array $params = []): void
    {
        $this->service->delete((int) $params['id']);
        $this->redirectWithMessage('/contractors', 'Contractor deleted.');
    }
}
