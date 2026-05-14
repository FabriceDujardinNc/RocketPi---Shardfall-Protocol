<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlayerLoadout extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(Operator::class);
    }

    public function operatorSkin(): BelongsTo
    {
        return $this->belongsTo(OperatorSkin::class);
    }

    public function weapon(): BelongsTo
    {
        return $this->belongsTo(Weapon::class);
    }

    public function weaponSkin(): BelongsTo
    {
        return $this->belongsTo(WeaponSkin::class);
    }

    public function headAccessory(): BelongsTo
    {
        return $this->belongsTo(Accessory::class, 'head_accessory_id');
    }

    public function faceAccessory(): BelongsTo
    {
        return $this->belongsTo(Accessory::class, 'face_accessory_id');
    }

    public function backAccessory(): BelongsTo
    {
        return $this->belongsTo(Accessory::class, 'back_accessory_id');
    }
}
