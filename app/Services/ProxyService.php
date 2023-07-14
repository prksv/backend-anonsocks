<?php

namespace App\Services;

use App\Enums\Proxy\ProxyType;
use App\Exceptions\CustomException;
use App\Facades\ProxyManager;
use App\Jobs\PurchaseProxy;
use App\Models\Category;
use App\Models\Proxy;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Spatie\UrlSigner\Laravel\Facades\UrlSigner;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProxyService
{
    public function getProxies(User $user)
    {
        return Proxy::whereHas("orders", function ($query) use ($user) {
            $query->where("user_id", $user->id);
        })->get();
    }

    public function export(User $user, array $proxy_ids = [])
    {
        $proxies = Proxy::whereUser($user)
            ->whereNotExpired()
            ->when($proxy_ids, function (Builder $query) use ($proxy_ids) {
                $query->whereIn("id", $proxy_ids);
            })
            ->get();

        $txt_content = "";

        foreach ($proxies as $proxy) {
            $txt_content .= "{$proxy->ip}:{$proxy->port}:{$proxy->username}:{$proxy->password}#{$proxy->country}\n";
        }

        Storage::put($this->getExportPath($user->id), $txt_content);

        return UrlSigner::sign(route("download-proxy", $user->id, now()->addMinute()));
    }

    public function download(User $user): StreamedResponse
    {
        return Storage::download($this->getExportPath($user->id));
    }

    private function getExportPath(int $user_id): string
    {
        return "proxies/{$user_id}.txt";
    }
}
