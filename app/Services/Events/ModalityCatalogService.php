<?php



namespace App\Services\Events;



use App\Models\Modality;

use App\Support\ModalityCode;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

use Illuminate\Database\Eloquent\Collection;



class ModalityCatalogService

{

    /**

     * @param  array<string, mixed>  $filters

     */

    public function paginate(array $filters = []): LengthAwarePaginator

    {

        $query = Modality::query()->orderBy('sort_order')->orderBy('name');



        if (! empty($filters['search'])) {

            $search = (string) $filters['search'];

            $query->where('name', 'like', "%{$search}%");

        }



        $perPage = (int) ($filters['per_page'] ?? 20);



        return $query->paginate($perPage > 0 ? $perPage : 20)->withQueryString();

    }



    /**

     * @return Collection<int, Modality>

     */

    public function activeOptions(): Collection

    {

        return Modality::query()

            ->where('is_active', true)

            ->orderBy('sort_order')

            ->get(['id', 'code', 'name']);

    }



    /**

     * @param  array<string, mixed>  $data

     */

    public function create(array $data): Modality

    {

        $data['code'] = $this->resolveCode($data['name'], $data['code'] ?? null);

        $data['sort_order'] = $this->nextSortOrder();

        $data['is_active'] = $data['is_active'] ?? true;



        return Modality::query()->create($data);

    }



    /**

     * @param  array<string, mixed>  $data

     */

    public function update(Modality $modality, array $data): Modality

    {

        unset($data['sort_order']);



        if (array_key_exists('code', $data) && filled($data['code'])) {

            $data['code'] = ModalityCode::normalize((string) $data['code']);

        } else {

            unset($data['code']);

        }



        $modality->update($data);



        return $modality->fresh();

    }



    public function nextSortOrder(): int

    {

        $max = (int) Modality::query()->max('sort_order');



        return $max + 10;

    }



    private function resolveCode(string $name, ?string $code): string

    {

        $base = filled($code)

            ? ModalityCode::normalize($code)

            : ModalityCode::fromName($name);



        return $this->ensureUniqueCode($base);

    }



    private function ensureUniqueCode(string $base, ?int $ignoreId = null): string

    {

        $candidate = $base;

        $suffix = 2;



        while ($this->codeExists($candidate, $ignoreId)) {

            $candidate = $base.'_'.$suffix;

            $suffix++;

        }



        return $candidate;

    }



    private function codeExists(string $code, ?int $ignoreId = null): bool

    {

        return Modality::query()

            ->where('code', $code)

            ->when($ignoreId !== null, fn ($q) => $q->where('id', '!=', $ignoreId))

            ->exists();

    }

}

