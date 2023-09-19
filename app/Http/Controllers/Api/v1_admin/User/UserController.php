<?php

namespace App\Http\Controllers\Api\v1_admin\User;

use App\Enums\User\UserRoles;
use App\Exceptions\CustomException;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use QuickChart;

class UserController extends ApiController
{
    private UserService $userService;

    public function __construct()
    {
        $this->userService = new UserService();
    }

    public function index(Request $request)
    {
        $users = User::paginate($request->per_page);
        return $this->resourceCollectionResponse(UserResource::collection($users), '', false);
    }

    public function view(Request $request)
    {
        $user = User::findOrFail($request->user_id);
        return $this->okResponse('', new UserResource($user));
    }

    public function me(Request $request)
    {
        $user = $request->user();

        return $this->okResponse('', new UserResource($user));
    }

    public function giveMoney(Request $request)
    {
        $request->validate([
            'amount' => 'required|integer',
        ]);

        $user = User::findOrFail($request->user_id);

        $user->increment('balance', $request->amount);

        return true;
    }

    public function findByTelegram(Request $request)
    {
        $user = User::whereTelegramId($request->telegram_id)->firstOrFail();
        return $this->okResponse('', new UserResource($user));
    }

    /**
     * @throws \Throwable
     */
    public function linkTelegramByToken(Request $request)
    {
        $request->validate([
            'token' => 'required'
        ]);

        $user = User::findByTokenOrFail($request->token);

        $user = $this->userService->linkTelegram($user, $request->telegram_id);

        return $this->okResponse('', new UserResource($user));
    }

    /**
     * @throws \Throwable
     */
    public function giveRole(Request $request)
    {
        $user = User::findOrFail($request->user_id);

        $role = UserRoles::tryFrom($request->role)->name;

        throw_if($user->isA($role), new CustomException('User already have this role.'));

        $user->assign($role);

        return $this->successResponse('OK');
    }

    /**
     * @throws \Throwable
     */
    public function takeRole(Request $request)
    {
        $user = User::findOrFail($request->user_id);

        $role = UserRoles::tryFrom($request->role)->name;

        throw_if(!$user->isA($role), new CustomException('User dont have this role.'));

        $user->retract($role);

        return $this->successResponse('OK');
    }

}
