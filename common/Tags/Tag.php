<?php

namespace Common\Tags;

use Carbon\Carbon;
use Common\Files\FileEntry;
use Common\Search\Searchable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Collection;

/**
 * Common\Tags\Tag
 *
 * @property int $id
 * @property string $name
 * @property string $display_name
 * @property string $type
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|FileEntry[] $files
 * @property-read int|null $files_count
 * @property-read string $model_type
 * @method static Builder|Tag matches(array $columns, string $value)
 * @method static Builder|Tag newModelQuery()
 * @method static Builder|Tag newQuery()
 * @method static Builder|Tag query()
 * @mixin \Eloquent
 * @method static Builder|Tag mysqlSearch(string $query)
 */
class Tag extends Model
{
    use Searchable;

    const MODEL_TYPE = 'tag';
    const DEFAULT_TYPE = 'default';

    protected $hidden = ['pivot'];
    protected $guarded = ['id'];
    protected $casts = ['id' => 'integer'];

    /**
     * @return MorphToMany
     */
    public function files()
    {
        return $this->morphedByMany(FileEntry::class, 'taggable');
    }

    /**
     * @param array $ids
     * @param null|int $userId
     */
    public function attachEntries($ids, $userId = null)
    {
        if ($userId) {
            $ids = collect($ids)->mapWithKeys(function ($id) use ($userId) {
                return [$id => ['user_id' => $userId]];
            });
        }

        $this->files()->syncWithoutDetaching($ids);
    }

    /**
     * @param array $ids
     * @param null|int $userId
     */
    public function detachEntries($ids, $userId = null)
    {
        $query = $this->files();

        if ($userId) {
            $query->wherePivot('user_id', $userId);
        }

        $query->detach($ids);
    }

    /**
     * @param Collection|array $tags
     * @return Collection|Tag[]
     */
    public function insertOrRetrieve($tags, ?string $type = 'custom')
    {
        if (!$tags instanceof Collection) {
            $tags = collect($tags);
        }

        $tags = $tags->filter();

        if (is_string($tags->first())) {
            $tags->transform(function ($tag) {
                return ['name' => $tag, 'display_name' => $tag];
            });
        }

        $tags->transform(function (array $tag) {
            $tag['name'] = slugify($tag['name']);
            return $tag;
        });

        $existing = $this->getByNames($tags->pluck('name'), $type);

        $new = $tags->filter(function ($tag) use ($existing) {
            return !$existing->first(function ($existingTag) use ($tag) {
                return slugify($existingTag['name']) === slugify($tag['name']);
            });
        });

        if ($new->isNotEmpty()) {
            $new->transform(function ($tag) use ($type) {
                $tag['created_at'] = Carbon::now();
                $tag['updated_at'] = Carbon::now();
                if ($type) {
                    $tag['type'] = $type;
                }
                return $tag;
            });
            $this->insert($new->toArray());
            return $this->getByNames($tags->pluck('name'), $type);
        } else {
            return $existing;
        }
    }

    public function getByNames(
        Collection $names,
        string $type = null
    ): Collection {
        $query = $this->whereIn('name', $names);
        if ($type) {
            $query->where('type', $type);
        }
        return $query->get();
    }

    public function toSearchableArray()
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'display_name' => $this->display_name,
            'type' => $this->type,
            'created_at' => $this->created_at->timestamp ?? '_null',
            'updated_at' => $this->updated_at->timestamp ?? '_null',
        ];
    }

    public static function filterableFields(): array
    {
        return ['id', 'type', 'created_at', 'updated_at'];
    }

    public static function getModelTypeAttribute(): string
    {
        return Tag::MODEL_TYPE;
    }
}
