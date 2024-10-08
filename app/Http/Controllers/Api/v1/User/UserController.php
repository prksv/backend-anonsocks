<?php

namespace App\Http\Controllers\Api\v1\User;

use App\Http\Controllers\ApiController;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\Request;

/**
 * @group User
 */
class UserController extends ApiController
{
    private UserService $userService;

    public function __construct()
    {
        $this->userService = new UserService();
    }

    /**
     * Получить юзера
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        return $this->okResponse("User data", new UserResource($request->user()));
    }

    /**
     * Зарегистрироваться
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        $data = $this->userService->register();
        return $this->okResponse(__("success.token_created"), $data);
    }

    /**
     * Залогиниться
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Throwable
     */
    public function login(Request $request)
    {
        $request->validate([
            "authorization_token" => "required|string",
        ]);

        $user = $this->userService->login($request->authorization_token);

        return $this->okResponse("Logged in", new UserResource($user));
    }

    public function refreshToken(Request $request)
    {
        $user = $request->user();
        $token = $this->userService->refreshToken($user);

        return $this->okResponse("", $token);
    }
}
