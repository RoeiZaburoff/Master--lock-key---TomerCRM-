<?php
declare(strict_types=1);

namespace App\Modules\Invoices;

use App\Core\Auth;
use App\Core\CSRF;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;

class InvoiceController extends \App\Core\BaseController
{
    private InvoiceService $service;

    public function __construct()
    {
        $this->service = new InvoiceService();
        $this->adminActions = ['*'];
    }

    public function index(Request $request, array $params = []): void
    {
        View::render('invoices/index', ['invoices' => $this->service->getAll()]);
    }

    public function create(Request $request, array $params = []): void
    {
        $jobId = (int) $request->get('job_id', 0);
        if ($jobId === 0) {
            Response::redirect('/invoices');
        }
        $data = $this->service->getCreateData($jobId);
        if ($data === null) {
            Response::abort(404, 'Job not found.');
        }
        View::render('invoices/create', $data);
    }

    public function store(Request $request, array $params = []): void
    {
        $result = $this->service->create($request->all());
        $this->redirectWithMessage('/invoices/' . $result['id'], 'Invoice created.');
    }

    public function show(Request $request, array $params = []): void
    {
        $invoice = $this->service->getById((int) $params['id']);
        if ($invoice === null) {
            Response::abort(404, 'Invoice not found.');
        }
        View::render('invoices/show', ['invoice' => $invoice]);
    }

    public function edit(Request $request, array $params = []): void
    {
        $invoice = $this->service->getById((int) $params['id']);
        if ($invoice === null) {
            Response::abort(404, 'Invoice not found.');
        }
        View::render('invoices/edit', ['invoice' => $invoice]);
    }

    public function update(Request $request, array $params = []): void
    {
        $id     = (int) $params['id'];
        $result = $this->service->update($id, $request->all());
        if (!$result['success']) {
            $this->redirectWithMessage('/invoices/' . $id . '/edit', $result['error'] ?? 'Update failed.', 'error');
        }
        $this->redirectWithMessage('/invoices/' . $id, 'Invoice updated.');
    }

    public function pdf(Request $request, array $params = []): void
    {
        $invoice = $this->service->getById((int) $params['id']);
        if ($invoice === null) {
            Response::abort(404, 'Invoice not found.');
        }
        $pdf = new InvoicePdf();
        $pdf->generate($invoice);
    }

    public function destroy(Request $request, array $params = []): void
    {
        $this->service->delete((int) $params['id']);
        $this->redirectWithMessage('/invoices', 'Invoice deleted.');
    }
}
