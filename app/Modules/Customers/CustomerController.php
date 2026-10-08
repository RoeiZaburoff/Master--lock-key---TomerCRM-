<?php
declare(strict_types=1);

namespace App\Modules\Customers;

use App\Core\Auth;
use App\Core\CSRF;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;

class CustomerController extends \App\Core\BaseController
{
    private CustomerService $service;

    public function __construct()
    {
        $this->service = new CustomerService();
        $this->adminActions = ['destroy'];
    }

    public function index(Request $request, array $params = []): void
    {
        $search    = trim((string) $request->get('search', ''));
        $customers = $this->service->getAll($search !== '' ? ['search' => $search] : []);
        View::render('customers/index', ['customers' => $customers, 'search' => $search]);
    }

    public function create(Request $request, array $params = []): void
    {
        View::render('customers/create', ['old' => [], 'errors' => []]);
    }

    public function store(Request $request, array $params = []): void
    {
        $data   = $request->all();
        $result = $this->service->create($data);
        if ($result['success']) {
            $this->redirectWithMessage('/customers/' . $result['id'], 'Customer created successfully.');
        }
        View::render('customers/create', ['errors' => $result['errors'], 'old' => $data]);
    }

    public function quickStore(Request $request, array $params = []): void
    {
        $data   = $request->all();
        $result = $this->service->create($data);
        header('Content-Type: application/json');
        if ($result['success']) {
            $customer = $this->service->getById($result['id']);
            echo json_encode([
                'success'       => true,
                'id'            => $result['id'],
                'name'          => $customer['full_name'],
                'address_line1' => $customer['address_line1'] ?? '',
                'city'          => $customer['city']          ?? '',
                'state'         => $customer['state']         ?? '',
                'zip'           => $customer['zip']           ?? '',
            ]);
        } else {
            $messages = [];
            foreach ($result['errors'] as $field => $errs) {
                $messages[] = implode(', ', $errs);
            }
            echo json_encode(['success' => false, 'message' => implode(' ', $messages)]);
        }
        exit;
    }

    public function checkDuplicate(Request $request, array $params = []): void
    {
        $phone   = trim((string) $request->get('phone', ''));
        $email   = trim((string) $request->get('email', ''));
        $address = trim((string) $request->get('address', ''));

        header('Content-Type: application/json');
        $db = new \App\Core\QueryBuilder();
        $dup = null;
        $type = '';

        if ($phone !== '') {
            $digits = preg_replace('/\D/', '', $phone);
            if (strlen($digits) === 11 && $digits[0] === '1') $digits = substr($digits, 1);
            if (strlen($digits) === 10) {
                $f1 = sprintf('(%s) %s-%s', substr($digits,0,3), substr($digits,3,3), substr($digits,6,4));
                $f2 = sprintf('%s-%s-%s', substr($digits,0,3), substr($digits,3,3), substr($digits,6,4));
                $dup = $db->rawFirst("SELECT customer_id, full_name, phone, email, address_line1 as address FROM customers WHERE phone IN (:f1, :f2, :d) LIMIT 1", [':f1'=>$f1, ':f2'=>$f2, ':d'=>$digits]);
                if ($dup) $type = 'Customer Phone Match';
            }
        }

        if ($email !== '' && !$dup) {
            $dup = $db->rawFirst("SELECT customer_id, full_name, phone, email, address_line1 as address FROM customers WHERE LOWER(email) = :e LIMIT 1", [':e' => strtolower($email)]);
            if ($dup) $type = 'Customer Email Match';
        }

        if ($address !== '' && !$dup) {
            $like = $address . '%';
            $dup = $db->rawFirst("SELECT customer_id, full_name, phone, email, address_line1 as address FROM customers WHERE address_line1 LIKE :a LIMIT 1", [':a' => $like]);
            if ($dup) {
                $type = 'Customer Address Match';
            } else {
                $dup = $db->rawFirst("SELECT c.customer_id, c.full_name, c.phone, c.email, l.address FROM leads l JOIN customers c ON l.customer_id = c.customer_id WHERE l.address LIKE :a LIMIT 1", [':a' => $like]);
                if ($dup) {
                    $type = 'Lead Address Match';
                } else {
                    $dup = $db->rawFirst("SELECT c.customer_id, c.full_name, c.phone, c.email, j.address FROM jobs j JOIN customers c ON j.customer_id = c.customer_id WHERE j.address LIKE :a LIMIT 1", [':a' => $like]);
                    if ($dup) $type = 'Job Address Match';
                }
            }
        }

        if ($dup) {
            echo json_encode([
                'exists'     => true,
                'match_type' => $type,
                'customer'   => [
                    'customer_id'   => (int) $dup['customer_id'],
                    'full_name'     => $dup['full_name'],
                    'phone'         => $dup['phone'],
                    'email'         => $dup['email'] ?? '',
                    'address_line1' => $dup['address'] ?? '',
                ],
            ]);
        } else {
            echo json_encode(['exists' => false]);
        }
        exit;
    }

    public function show(Request $request, array $params = []): void
    {
        $customer = $this->service->getWithHistory((int) $params['id']);
        if ($customer === null) {
            Response::abort(404, 'Customer not found.');
        }
        View::render('customers/show', ['customer' => $customer]);
    }

    public function edit(Request $request, array $params = []): void
    {
        $customer = $this->service->getById((int) $params['id']);
        if ($customer === null) {
            Response::abort(404, 'Customer not found.');
        }
        View::render('customers/edit', ['customer' => $customer, 'errors' => []]);
    }

    public function update(Request $request, array $params = []): void
    {
        $id     = (int) $params['id'];
        $data   = $request->all();
        $result = $this->service->update($id, $data);
        if ($result['success']) {
            $this->redirectWithMessage('/customers/' . $id, 'Customer updated successfully.');
        }
        $customer = array_merge($this->service->getById($id) ?? [], $data);
        View::render('customers/edit', ['customer' => $customer, 'errors' => $result['errors']]);
    }

    public function destroy(Request $request, array $params = []): void
    {
        $id     = (int) $params['id'];
        $result = $this->service->delete($id);
        if (!$result['success']) {
            $this->redirectWithMessage('/customers/' . $id, $result['message'], 'error');
            return;
        }
        $this->redirectWithMessage('/customers', 'Customer deleted.');
    }
}
