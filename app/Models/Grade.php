<?php



namespace App\Models;



use App\Enums\GradeCategory;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Relations\HasMany;



class Grade extends Model

{

    protected $fillable = [

        'name',

        'category',

        'sort_order',

        'is_active',

    ];



    protected function casts(): array

    {

        return [

            'category' => GradeCategory::class,

            'sort_order' => 'integer',

            'is_active' => 'boolean',

        ];

    }



    public function professors(): HasMany

    {

        return $this->hasMany(Professor::class);

    }



    public function competitors(): HasMany

    {

        return $this->hasMany(Competitor::class);

    }



    public function referees(): HasMany

    {

        return $this->hasMany(Referee::class);

    }

}

