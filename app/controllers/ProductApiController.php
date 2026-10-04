<?php

defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class ProductApiController extends Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->call->database();
        $this->call->model('ProductModel');
        $this->call->library('api');
    }

    public function index()
    {
         $auth = $this->api->require_jwt();

        $products = $this->ProductModel->all();

        $this->api->respond([
            'status' => true,
            'message' => 'Products retrieved successfully.',
            'data' => $products
        ], 200);
    }

    public function store()
{
     $auth = $this->api->require_jwt();

    $this->api->require_method('POST');

    $input = $this->api->body();

    if (
        empty($input['product_name']) ||
        !isset($input['price']) ||
        !isset($input['quantity'])
    ) {
        $this->api->respond_error(
            'Product name, price, and quantity are required.',
            422
        );
    }

    $data = [
        'product_name' => trim($input['product_name']),
        'description'  => trim($input['description'] ?? ''),
        'price'        => $input['price'],
        'quantity'     => $input['quantity'],
        'created_at'   => date('Y-m-d H:i:s')
    ];

    $this->ProductModel->insert($data);

    $this->api->respond([
        'status' => true,
        'message' => 'Product added successfully.'
    ], 201);
}

public function update($id)
{
     $auth = $this->api->require_jwt();

    $this->api->require_method('PUT');

    $input = $this->api->body();

    $product = $this->ProductModel->find($id);

    if (!$product) {
        $this->api->respond_error(
            'Product not found.',
            404
        );
    }

    $data = [
        'product_name' => trim($input['product_name'] ?? $product['product_name']),
        'description'  => trim($input['description'] ?? $product['description']),
        'price'        => $input['price'] ?? $product['price'],
        'quantity'     => $input['quantity'] ?? $product['quantity']
    ];

    $this->ProductModel->update($id, $data);

    $this->api->respond([
        'status' => true,
        'message' => 'Product updated successfully.'
    ], 200);
}

public function delete($id)
{
     $auth = $this->api->require_jwt();

    $this->api->require_method('DELETE');

    $product = $this->ProductModel->find($id);

    if (!$product) {
        $this->api->respond_error(
            'Product not found.',
            404
        );
    }

    $this->ProductModel->delete($id);

    $this->api->respond([
        'status' => true,
        'message' => 'Product deleted successfully.'
    ], 200);
}

}