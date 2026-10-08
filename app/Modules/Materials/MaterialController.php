<?php
declare(strict_types=1);

namespace App\Modules\Materials;

use App\Core\Auth;
use App\Core\CSRF;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;

class MaterialController extends \App\Core\BaseController
{
    private MaterialService $service;

    public function __construct()
    {
        $this->service = new MaterialService();
        $this->adminActions = ['destroy'];
    }

    public function index(Request $request, array $params = []): void
    {
        View::render('materials/index', ['materials' => $this->service->getAll()]);
    }

    public function create(Request $request, array $params = []): void
    {
        View::render('materials/create', ['errors' => [], 'old' => []]);
    }

    public function store(Request $request, array $params = []): void
    {
        $data   = $request->all();
        $result = $this->service->create($data);
        if ($result['success']) {
            $this->redirectWithMessage('/materials', 'Material added to catalog.');
        }
        View::render('materials/create', ['errors' => $result['errors'], 'old' => $data]);
    }

    public function edit(Request $request, array $params = []): void
    {
        $material = $this->service->getById((int) $params['id']);
        if ($material === null) {
            Response::abort(404);
        }
        View::render('materials/edit', ['material' => $material, 'errors' => []]);
    }

    public function update(Request $request, array $params = []): void
    {
        $id     = (int) $params['id'];
        $data   = $request->all();
        $result = $this->service->update($id, $data);
        if ($result['success']) {
            $this->redirectWithMessage('/materials', 'Material updated.');
        }
        $material = array_merge($this->service->getById($id) ?? [], $data);
        View::render('materials/edit', ['material' => $material, 'errors' => $result['errors']]);
    }

    public function destroy(Request $request, array $params = []): void
    {
        $this->service->delete((int) $params['id']);
        $this->redirectWithMessage('/materials', 'Material deleted.');
    }
}
