<?php

namespace Devdojo\Foundation\Models;

use Illuminate\Database\Eloquent\Model;

class FoundationSetting extends Model
{
    protected $table = 'foundation_settings';

    protected $fillable = ['key', 'value'];
}
