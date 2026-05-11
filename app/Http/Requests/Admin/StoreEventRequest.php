<?php

namespace App\Http\Requests\Admin;

use App\Models\Event;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        $e = $this->route('event');
        if ($e instanceof Event) {
            return $this->user()?->can('update', $e) ?? false;
        }
        return $this->user()?->can('create', Event::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'name'             => ['required', 'string', 'max:128'],
            'lore'             => ['nullable', 'string', 'max:5000'],
            'banner_image_url' => ['nullable', 'url', 'max:255'],
            'type'             => ['required', Rule::in(Event::TYPES)],
            'banner_id'        => ['nullable', 'integer', 'exists:banners,id'],
            'rewards_pool'             => ['nullable', 'array', 'max:10'],
            'rewards_pool.*.type'      => ['required_with:rewards_pool', 'string', 'max:64'],
            'rewards_pool.*.amount'    => ['required_with:rewards_pool', 'integer', 'min:1', 'max:1000000'],
            'starts_at'        => ['required', 'date'],
            'ends_at'          => ['required', 'date', 'after:starts_at'],
            'is_active'        => ['boolean'],
        ];
    }
}
