<?php

namespace App;

use App\Traits\HistoryTrait;
use App\Traits\RecordSignature;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;

//use Illuminate\Database\Eloquent\SoftDeletes;

class [[model_uc]] extends Model
{
//    use SoftDeletes;
    use RecordSignature;
    use HistoryTrait;

    /**
     * fillable - attributes that can be mass-assigned.
     */
    protected $fillable = [
        [[foreach:columns]]
        '[[i.name]]',
    [[endforeach]]
    ];

    protected $hidden = [
    'active',
    'created_by',
    'modified_by',
    'purged_by',
    'created_at',
    'updated_at',
];

    public function add($attributes)
{
    try {
        $this->fill($attributes)->save();
    } catch (Exception $e) {
        throw new Exception($e->getMessage());
    } catch (QueryException $e) {
        info(__METHOD__ . ' line: ' . __LINE__ . ':  ' . $e->getMessage());
        throw new Exception($e->getMessage());
    }

    return true;
}

    public function canDelete()
{
    return true;
}

    /**
     * Get Grid/index data PAGINATED.
     *
     * @param $per_page
     * @param $column
     * @param $direction
     * @param string $keyword
     * @return mixed
     */
    public static function indexData(
        $per_page,
        $column,
        $direction,
        $keyword = '')
    {
        return self::buildBaseGridQuery($column, $direction, $keyword,
            ['id',
                [[foreach:grid_columns]]
                    '[[i.name]]',
[[endforeach]]
            ])
        ->paginate($per_page);
    }

    /**
     * Create base query to be used by Grid, Download, and PDF.
     *
     * NOTE: to override the select you must supply all fields, ie you cannot add to the
     *       fields being selected.
     *
     * @param $column
     * @param $direction
     * @param string $keyword
     * @param string|array $columns
     * @return mixed
     */
    public static function buildBaseGridQuery(
        $column,
        $direction,
        $keyword = '',
        $columns = '*')
    {
        // Map sort direction from 1/-1 integer to asc/desc sql keyword
        switch ($direction) {
            case '1':
                $direction = 'desc';
                break;
            case '-1':
                $direction = 'asc';
                break;
            default:
                $direction = 'asc';
                break;
        }

        $query = self::select($columns)
        ->orderBy($column, $direction);

        if ($keyword) {
            $query->where('name', 'ilike', '%' . $keyword . '%');
        }

        $query->where('organization_id', session('organization_id', 0));

        return $query;
    }

    /**
     * Get export/Excel/download data query to send to Excel download library.
     *
     * @param $per_page
     * @param $column
     * @param $direction
     * @param string $keyword
     * @param array $columns
     * @return mixed
     */
    public static function exportDataQuery(
        $column,
        $direction,
        $keyword = '',
        $columns = '*')
    {
        return self::buildBaseGridQuery($column, $direction, $keyword, [
            [[foreach:grid_columns]]
                    '[[i.name]]',
[[endforeach]]
        ]);
    }

        /**
         * Get export/Excel/download data query to send to PDF  library.
         *
         * @param $per_page
         * @param $column
         * @param $direction
         * @param string $keyword
         * @param array $columns
         * @return mixed
         */
    public static function pdfDataQuery(
        $column,
        $direction,
        $keyword = '',
        $columns = '*')
    {
        return self::buildBaseGridQuery($column, $direction, $keyword, [
            [[foreach:grid_columns]]
                    '[[i.name]]',
[[endforeach]]
        ]);
    }

    /**
     * Get "options" for HTML select tag.
     *
     * If flat return an array.
     * Otherwise, return an array of records.  Helps keep in proper order durring ajax calls to Chrome
     */
    public static function getOptions($flat = false, $organization_id = 0)
{
    $thisModel = new static;

    $records = $thisModel::select('id',
        'name')
        ->where('organization_id', $organization_id)
        ->orderBy('name')
        ->get();

    if (!$flat) {
        return $records;
    } else {
        $data = [];

        foreach ($records as $rec) {
            $data[] = ['id' => $rec['id'], 'name' => $rec['name']];
        }

        return $data;
    }
}

    // ========================================
    // To support Old Code
    // ========================================

    /**
     * getForSelect, returns options (id and name) for a HTML Select as an collection of Eloquent models.
     *
     * @param null $organization_id
     * @return Collection|static[]
     *
     *   You can think of the return as nested arrays
     *   ````
     *     [0] = ['id' => 23, 'name' => 'Alaska']
     *     [1] = ['id' => 11, 'name' => 'Tom']
     *   ````
     *
     *   If you want a flat array like
     *   ````
     *      [23] = 'Alaska'
     *      [11] = 'Tom'
     *   ````
     *
     *   convert the result with `->toArray()`
     *   ````
     *      $results_as_array = MODEL::getForSelect($organization_id)->toArray()
     *   ````
     */
    public static function getForSelect($organization_id = null)
{
    $instance = new static;

    $options = $instance::select('id as id',
        'name as name')
        ->orderBy('name')
        ->where('organization_id', $organization_id)
        ->get();

    return $options;
}
}
