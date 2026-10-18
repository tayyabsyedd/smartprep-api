<?php

namespace App\Http\Resources;

use App\Models\Bundle;
use App\Models\Module;
use App\Models\Test;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $this->resource;

        $modules = Module::with('subject')->orderBy('id')->get()->map(fn ($module) => [
            'id' => $module->id,
            'name' => $module->name,
            'subject' => $module->subject?->name,
            'free_tests_remaining' => $user->freeTestsRemaining($module),
        ]);

        $entitlements = $user->activeEntitlements()->with('entitled')->get()->map(fn ($entitlement) => [
            'type' => strtolower(class_basename($entitlement->entitled_type)),
            'id' => $entitlement->entitled_id,
            'title' => match ($entitlement->entitled_type) {
                Test::class => $entitlement->entitled?->title,
                Bundle::class => $entitlement->entitled?->name,
                default => null,
            },
            'source' => $entitlement->source,
            'granted_at' => $entitlement->granted_at?->toISOString(),
        ]);

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'role' => $user->role,
            'is_enrolled' => (bool) $user->is_enrolled,
            'enrollment_id' => $user->enrollment_id,
            'modules' => $modules,
            'entitlements' => $entitlements->values(),
        ];
    }
}
