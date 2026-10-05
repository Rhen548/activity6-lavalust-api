<?php

defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class ApiController extends Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->call->database();
        $this->call->library('api');
    }

    public function create()
    {
        $this->api->require_method('POST');

        $input = $this->api->body();

        $username = trim($input['username'] ?? '');
        $email    = trim($input['email'] ?? '');
        $password = $input['password'] ?? '';
        $role     = $input['role'] ?? 'user';

        if ($username === '' || $email === '' || $password === '') {
            $this->api->respond_error(
                'Username, email, and password are required.',
                422
            );
        }

        if (strlen($password) < 8) {
            $this->api->respond_error(
                'Password must be at least 8 characters.',
                422
            );
        }

        // Check duplicate username/email
        $stmt = $this->db->raw(
            'SELECT id FROM users WHERE username = ? OR email = ?',
            [$username, $email]
        );

        if ($stmt->fetch(PDO::FETCH_ASSOC)) {
            $this->api->respond_error(
                'Username or email already exists.',
                409
            );
        }

        $this->db->raw(
            "INSERT INTO users
            (username, email, password, role, created_at)
            VALUES (?, ?, ?, ?, NOW())",
            [
                $username,
                $email,
                password_hash($password, PASSWORD_BCRYPT),
                $role
            ]
        );

        $this->api->respond([
            'message' => 'User created successfully.'
        ], 201);
    }

    public function login()
    {
        $this->api->require_method('POST');

        $input = $this->api->body();

        $username = trim($input['username'] ?? '');
        $password = $input['password'] ?? '';

        $stmt = $this->db->raw(
            'SELECT * FROM users WHERE username = ?',
            [$username]
        );

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($password, $user['password'])) {
            $this->api->respond_error(
                'Invalid credentials.',
                401
            );
        }

        $tokens = $this->api->issue_tokens([
            'id'   => $user['id'],
            'role' => $user['role']
        ]);

        $this->api->respond($tokens, 200);
    }

    public function refresh()
{
    $this->api->require_method('POST');

    $input = $this->api->body();

    $this->api->refresh_access_token(
        $input['refresh_token'] ?? ''
    );
}

public function profile()
{
    $auth = $this->api->require_jwt();

    $userId = $auth['sub'] ?? null;

    if (!$userId) {
        $this->api->respond_error('Invalid access token.', 401);
    }

    $stmt = $this->db->raw(
        'SELECT id, username, email, role, created_at
         FROM users
         WHERE id = ?
         LIMIT 1',
        [$userId]
    );

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $this->api->respond_error('User not found.', 404);
    }

    $this->api->respond([
        'status'  => true,
        'message' => 'Profile retrieved successfully.',
        'data'    => $user
    ], 200);
}

public function logout()
{
    $this->api->require_method('POST');

    $input = $this->api->body();

    $this->api->revoke_refresh_token(
        $input['refresh_token'] ?? ''
    );

    $this->api->respond([
        'message' => 'Logged out successfully.'
    ], 200);
}

public function options()
{
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

$allowedOrigins = [
    'http://localhost:5173',
    'http://localhost:5174',
    'https://activity6-react-frontend.vercel.app',
    'https://api-tester.marasigan.dev'
];

if (in_array($origin, $allowedOrigins, true)) {
    header('Access-Control-Allow-Origin: ' . $origin);
}
    header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    header('Access-Control-Max-Age: 86400');

    http_response_code(204);
    exit;
}

}