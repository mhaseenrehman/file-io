<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ImageFile extends Model
{
    use hasUuids;
    
    /**
     * The table associated with the model.
     * @var string
     */
    protected $table = 'imagefiles';

    protected $fillable = [
        'file_request_index',
        
        'orig_name',
        'orig_path',
        'orig_size',
        'orig_format',

        'compressed_name',
        'compressed_path',
        'compressed_size',
        'compressed_format',

        'current_status',
        'error_message'
    ];
}
