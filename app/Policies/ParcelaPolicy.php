<?php

namespace App\Policies;

use App\Models\Parcela;
use App\Models\User;

class ParcelaPolicy
{
    /**
     *  Determina si el usuario puede ver la parcela
     */
    public function view(User $user, Parcela $parcela): bool
    {
        return $user->id === $parcela->user_id;
    }

    /**
     *  Determina si el usuario puede crear parcelas
     */
    public function create(User $user): bool
    {
        return true; // Cualquier usuario autenticado puede crear
    }

    /**
     *  Determina si el usuario puede actualizar la parcela
     */
    public function update(User $user, Parcela $parcela): bool
    {
        return $user->id === $parcela->user_id;
    }

    /**
     *  Determina si el usuario puede eliminar la parcela
     */
    public function delete(User $user, Parcela $parcela): bool
    {
        return $user->id === $parcela->user_id;
    }

    /**
     *  Determina si el usuario puede restaurar la parcela
     */
    public function restore(User $user, Parcela $parcela): bool
    {
        return $user->id === $parcela->user_id;
    }

    /**
     *  Determina si el usuario puede eliminar permanentemente
     */
    public function forceDelete(User $user, Parcela $parcela): bool
    {
        return $user->id === $parcela->user_id;
    }

    
}