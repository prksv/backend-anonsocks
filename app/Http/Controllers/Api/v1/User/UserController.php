<?php

namespace App\Http\Controllers\Api\v1\User;

use App\Http\Controllers\ApiController;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class UserController extends ApiController
{
    private UserService $userService;

    public function __construct()
    {
        $this->userService = new UserService();
    }

    public function index(Request $request)
    {
        return $request->user();
    }

    public function register(Request $request)
    {
        $token = $this->userService->register();
        return $this->okResponse(__('success.token_created'), $token);
    }

    public function login(Request $request)
    {
        $request->validate([
            'authorization_token' => 'required|string'
        ]);

        $userData = $this->userService->login($request->authorization_token);

        return $this->okResponse('Logged in', $userData);
    }
}
