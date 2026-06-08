<?php



namespace App\Models;



use App\Enums\MasterStatus;

use App\Enums\RefereeSpecialty;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

use Illuminate\Database\Eloquent\SoftDeletes;



class Referee extends Model

{

    use SoftDeletes;



    protected $fillable = [

        'first_name',

        'last_name',

        'email',

        'phone',

        'grade_id',

        'specialty',

        'status',

        'user_id',

        'notes',

    ];



    protected function casts(): array

    {

        return [

            'specialty' => RefereeSpecialty::class,

            'status' => MasterStatus::class,

        ];

    }



    public function fullName(): string

    {

        return trim("{$this->first_name} {$this->last_name}");

    }



    public function grade(): BelongsTo

    {

        return $this->belongsTo(Grade::class);

    }



    public function user(): BelongsTo

    {

        return $this->belongsTo(User::class);

    }

}

