<?php
declare(strict_types=1);

namespace App\Modules\Inventory;

use App\Core\Request;
use App\Core\Response;
use App\Core\View;

class LocationController extends \App\Core\BaseController
{
    private LocationService $service;

    public function __construct()
    {
        $this->service      = new LocationService();
        $this->adminActions = ['*'];
    }

    public function index(Request $request, array $params = []): void
    {
        View::render('inventory/locations', [
            'locations'   => $this->service->getAll(),
            'contractors' => $this->service->getContractors(),
            'editing'     => null,
            'errors'      => [],
        ]);
    }

    public function store(Request $request, array $params = []): void
    {
        $result = $this->service->create($request->all());
        if ($result['success']) {
            $this->redirectWithMessage('/inventory/locations', 'Location added.');
        }
        View::render('inventory/locations', [
            'locations'   => $this->service->getAll(),
            'contractors' => $this->service->getContractors(),
            'editing'     => null,
            'errors'      => $result['errors'],
        ]);
    }

    public function edit(Request $request, array $params = []): void
    {
        $location = $this->service->getById((int) $params['id']);
        if ($location === null) {
            Response::abort(404);
        }
        View::render('inventory/locations', [
            'locations'   => $this->service->getAll(),
            'contractors' => $this->service->getContractors(),
            'editing'     => $location,
            'errors'      => [],
        ]);
    }

    public function update(Request $request, array $params = []): void
    {
        $id     = (int) $params['id'];
        $data   = $request->all();
        $result = $this->service->update($id, $data);
        if ($result['success']) {
            $this->redirectWithMessage('/inventory/locations', 'Location updated.');
        }
        $location = array_merge($this->service->getById($id) ?? ['location_id' => $id], $data);
        View::render('inventory/locations', [
            'locations'   => $this->service->getAll(),
            'contractors' => $this->service->getContractors(),
            'editing'     => $location,
            'errors'      => $result['errors'],
        ]);
    }

    public function destroy(Request $request, array $params = []): void
    {
        if ($this->service->delete((int) $params['id'])) {
            $this->redirectWithMessage('/inventory/locations', 'Location deleted.');
        }
        $this->redirectWithMessage(
            '/inventory/locations',
            'Cannot delete this location. It is the default warehouse or has stock assigned to it.',
            'error'
        );
    }
}
