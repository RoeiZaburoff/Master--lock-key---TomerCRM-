<?php
declare(strict_types=1);

namespace App\Modules\Inventory;

use App\Core\Request;
use App\Core\Response;
use App\Core\View;

class InventoryController extends \App\Core\BaseController
{
    private InventoryService $service;

    public function __construct()
    {
        $this->service      = new InventoryService();
        $this->adminActions = ['*'];
    }

    public function index(Request $request, array $params = []): void
    {
        $filters    = [];
        $search     = trim((string) $request->get('search', ''));
        $mfr        = (int) $request->get('manufacturer_id', 0);
        $rawLoc     = $request->get('location_id', '');
        $locationId = ($rawLoc !== '' && $rawLoc !== null) ? max(1, (int) $rawLoc) : 1;

        if ($search !== '') {
            $filters['search'] = $search;
        }
        if ($mfr > 0) {
            $filters['manufacturer_id'] = $mfr;
        }

        $items = $this->service->getAllForLocation($locationId, $filters);

        // Build hierarchical tree: $tree[CategoryName][ManufacturerName][] = $item
        $tree = [];
        foreach ($items as $item) {
            $catKey = ($item['category_name'] ?? '') !== '' ? $item['category_name'] : 'Uncategorized';
            $mfrKey = ($item['manufacturer_name'] ?? '') !== '' ? $item['manufacturer_name'] : 'No Brand';
            $tree[$catKey][$mfrKey][] = $item;
        }
        ksort($tree);
        foreach ($tree as &$mfrs) {
            ksort($mfrs);
        }
        unset($mfrs);

        View::render('inventory/index', [
            'tree'            => $tree,
            'catalog'         => $this->service->getCatalog(),
            'manufacturers'   => $this->service->getManufacturers(),
            'categories'      => $this->service->getCategories(),
            'locations'       => $this->service->getLocations(),
            'search'          => $search,
            'manufacturer_id' => $mfr,
            'location_id'     => $locationId,
        ]);
    }

    public function create(Request $request, array $params = []): void
    {
        View::render('inventory/create', [
            'manufacturers' => $this->service->getManufacturers(),
            'categories'    => $this->service->getCategories(),
            'errors'        => [],
            'old'           => [],
        ]);
    }

    public function store(Request $request, array $params = []): void
    {
        $data   = $request->all();
        $result = $this->service->create($data, $_FILES);
        if ($result['success']) {
            $this->redirectWithMessage('/inventory', 'Inventory item added.');
        }
        View::render('inventory/create', [
            'manufacturers' => $this->service->getManufacturers(),
            'categories'    => $this->service->getCategories(),
            'errors'        => $result['errors'],
            'old'           => $data,
        ]);
    }

    public function edit(Request $request, array $params = []): void
    {
        $item = $this->service->getById((int) $params['id']);
        if ($item === null) {
            Response::abort(404);
        }
        View::render('inventory/edit', [
            'item'          => $item,
            'manufacturers' => $this->service->getManufacturers(),
            'categories'    => $this->service->getCategories(),
            'errors'        => [],
        ]);
    }

    public function update(Request $request, array $params = []): void
    {
        $id     = (int) $params['id'];
        $data   = $request->all();
        $result = $this->service->update($id, $data, $_FILES);
        if ($result['success']) {
            $this->redirectWithMessage('/inventory', 'Inventory item updated.');
        }
        $item = array_merge($this->service->getById($id) ?? [], $data);
        View::render('inventory/edit', [
            'item'          => $item,
            'manufacturers' => $this->service->getManufacturers(),
            'categories'    => $this->service->getCategories(),
            'errors'        => $result['errors'],
        ]);
    }

    public function destroy(Request $request, array $params = []): void
    {
        $this->service->delete((int) $params['id']);
        $this->redirectWithMessage('/inventory', 'Inventory item deleted.');
    }

    public function transfer(Request $request, array $params = []): void
    {
        $id             = (int) $params['id'];
        $fromLocationId = max(1, (int) ($_POST['from_location_id'] ?? 1));
        $toLocationId   = max(1, (int) ($_POST['to_location_id'] ?? 1));
        $qty            = (float) ($_POST['qty'] ?? 0);

        $result = $this->service->transferStock($id, $fromLocationId, $toLocationId, $qty);
        if ($result['success']) {
            $this->redirectWithMessage('/inventory', 'Stock transferred successfully.');
        }
        $firstError = array_values($result['errors'])[0][0] ?? 'Transfer failed.';
        $this->redirectWithMessage('/inventory', $firstError, 'error');
    }

    public function transferBulk(Request $request, array $params = []): void
    {
        $fromLocationId = max(1, (int) ($_POST['from_location_id'] ?? 0));
        $toLocationId   = max(1, (int) ($_POST['to_location_id'] ?? 0));
        $rawItems       = is_array($_POST['items'] ?? null) ? $_POST['items'] : [];

        $items = [];
        foreach ($rawItems as $inventoryId => $itemData) {
            $qty = (float) ($itemData['qty'] ?? 0);
            if ($qty > 0) {
                $items[(int) $inventoryId] = $qty;
            }
        }

        $result = $this->service->transferBulkStock($items, $fromLocationId, $toLocationId);

        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    }

    public function adjustStock(Request $request, array $params = []): void
    {
        $id         = (int) $params['id'];
        $locationId = max(1, (int) ($_POST['location_id'] ?? 1));
        $newQty     = (float) ($_POST['new_qty'] ?? 0);

        $this->service->adjustStock($id, $locationId, $newQty);
        $this->redirectWithMessage('/inventory', 'Stock adjusted successfully.');
    }

    public function serveImage(Request $request, array $params = []): void
    {
        $file = basename($params['file']);
        $path = STORAGE_PATH . '/inventory/' . $file;
        if (!file_exists($path) || !is_file($path)) {
            Response::abort(404);
        }
        $ext  = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $mime = match ($ext) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png'         => 'image/png',
            'webp'        => 'image/webp',
            'gif'         => 'image/gif',
            default       => 'application/octet-stream',
        };
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . filesize($path));
        header('Cache-Control: public, max-age=2592000');
        readfile($path);
        exit;
    }

    // ── Manufacturer actions ──────────────────────────────────────────────────

    public function manufacturerIndex(Request $request, array $params = []): void
    {
        View::render('inventory/manufacturers', [
            'manufacturers' => $this->service->getManufacturers(),
            'editing'       => null,
            'errors'        => [],
        ]);
    }

    public function manufacturerStore(Request $request, array $params = []): void
    {
        $result = $this->service->createManufacturer($request->all());
        if ($result['success']) {
            $this->redirectWithMessage('/inventory/manufacturers', 'Manufacturer added.');
        }
        View::render('inventory/manufacturers', [
            'manufacturers' => $this->service->getManufacturers(),
            'editing'       => null,
            'errors'        => $result['errors'],
        ]);
    }

    public function manufacturerEdit(Request $request, array $params = []): void
    {
        $mfr = $this->service->getManufacturerById((int) $params['id']);
        if ($mfr === null) {
            Response::abort(404);
        }
        View::render('inventory/manufacturers', [
            'manufacturers' => $this->service->getManufacturers(),
            'editing'       => $mfr,
            'errors'        => [],
        ]);
    }

    public function manufacturerUpdate(Request $request, array $params = []): void
    {
        $id     = (int) $params['id'];
        $data   = $request->all();
        $result = $this->service->updateManufacturer($id, $data);
        if ($result['success']) {
            $this->redirectWithMessage('/inventory/manufacturers', 'Manufacturer updated.');
        }
        $mfr = $this->service->getManufacturerById($id);
        View::render('inventory/manufacturers', [
            'manufacturers' => $this->service->getManufacturers(),
            'editing'       => array_merge($mfr ?? ['manufacturer_id' => $id], $data),
            'errors'        => $result['errors'],
        ]);
    }

    public function manufacturerDestroy(Request $request, array $params = []): void
    {
        $this->service->deleteManufacturer((int) $params['id']);
        $this->redirectWithMessage('/inventory/manufacturers', 'Manufacturer deleted.');
    }

    // ── Category actions ──────────────────────────────────────────────────────

    public function categoryIndex(Request $request, array $params = []): void
    {
        View::render('inventory/categories', [
            'categories' => $this->service->getCategories(),
            'editing'    => null,
            'errors'     => [],
        ]);
    }

    public function categoryStore(Request $request, array $params = []): void
    {
        $result = $this->service->createCategory($request->all());
        if ($result['success']) {
            $this->redirectWithMessage('/inventory/categories', 'Category added.');
        }
        View::render('inventory/categories', [
            'categories' => $this->service->getCategories(),
            'editing'    => null,
            'errors'     => $result['errors'],
        ]);
    }

    public function categoryEdit(Request $request, array $params = []): void
    {
        $cat = $this->service->getCategoryById((int) $params['id']);
        if ($cat === null) {
            Response::abort(404);
        }
        View::render('inventory/categories', [
            'categories' => $this->service->getCategories(),
            'editing'    => $cat,
            'errors'     => [],
        ]);
    }

    public function categoryUpdate(Request $request, array $params = []): void
    {
        $id     = (int) $params['id'];
        $data   = $request->all();
        $result = $this->service->updateCategory($id, $data);
        if ($result['success']) {
            $this->redirectWithMessage('/inventory/categories', 'Category updated.');
        }
        $cat = $this->service->getCategoryById($id);
        View::render('inventory/categories', [
            'categories' => $this->service->getCategories(),
            'editing'    => array_merge($cat ?? ['category_id' => $id], $data),
            'errors'     => $result['errors'],
        ]);
    }

    public function categoryDestroy(Request $request, array $params = []): void
    {
        $this->service->deleteCategory((int) $params['id']);
        $this->redirectWithMessage('/inventory/categories', 'Category deleted.');
    }
}
