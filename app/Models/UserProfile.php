<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserProfile extends Model
{
    protected $table = 'user_profiles';

    protected $fillable = [
        "user_id",
        "gender",
        "date_of_birth",
        "address",
        "phone_number",
        "avatar",
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
