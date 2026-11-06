<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\Searchable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use Auditable, Searchable, SoftDeletes;

    protected $fillable = ['name', 'email', 'phone', 'address'];

    public function orders(): HasMany
    {
        return $this->hasMany(SalesOrder::class);
    }
}
