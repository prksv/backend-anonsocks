<?php

namespace App\Services;

use App\Exceptions\CustomException;
use App\Facades\ProxyManager;
use App\Models\Order;
use App\Models\Proxy;
use App\Models\ProxyRentalPeriod;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
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

    public function export(User $user, array $proxy_ids = []): string
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

    private function getExportPath(int $user_id): string
    {
        return "proxies/{$user_id}.txt";
    }

    public function download(User $user): StreamedResponse
    {
        return Storage::download($this->getExportPath($user->id));
    }

    public function replace(Proxy $proxy)
    {
        $replaced_proxy = ProxyManager::driver(mb_strtolower($proxy->provider->name))
            ->type($proxy->type)
            ->replaceProxy([$proxy->ip], [$proxy->country => 1])->first();

        $activeOrder = $proxy->getActiveOrder();

        $activeOrder?->orderProxy()->where('proxy_id', $proxy->id)->update([
            'proxy_id' => $replaced_proxy->id
        ]);

        return $replaced_proxy;
    }

    /**
     * @throws \Throwable
     */
    public function extend(User $user, Order $order, int $proxy_id, int $rental_days): Proxy
    {
        $proxy = $order->proxies()->find($proxy_id);

        throw_if(!$proxy, new CustomException("Proxy with id ${proxy_id} don`t exists in this order"));

        \Log::debug($proxy->orderProxy()->where('order_id', $order->id)->first()->rentalPeriods()->get());

        $rental_period = $proxy->orderProxy()->where('order_id', $order->id)->first()->rentalPeriods()->whereNotExpired()->first();

        throw_if(!$rental_period, new CustomException("Proxy is expired."));

        $category = $order->category;

        throw_if(!$category?->available, new CustomException("Category is not available"));

        $rentalTerm = $category
            ->rentalTerms()
            ->where("days", $rental_days)
            ->first();

        throw_if(!$rentalTerm, new CustomException("This rental period is not available"));

        $user->decrementBalance($rentalTerm->price);

        ProxyRentalPeriod::create([
            'order_proxy_id' => $order->orderProxy()->firstWhere('proxy_id', $proxy->id)->id,
            'rental_term_id' => $rentalTerm->id,
            'amount' => $rentalTerm->price,
            'expires_at' => $proxy->expires_at->addDays($rentalTerm->days)
        ]);

        return $proxy;
    }
}
