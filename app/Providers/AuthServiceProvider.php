<?php

namespace App\Providers;

use App\Models\Parcela;
use App\Policies\ParcelaPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Parcela::class => ParcelaPolicy::class,
    ];

    /**
     * 
     */
    public function boot(): void
    {
        $this->registerPolicies();
    }
}