<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

trait ApiResponse
{
    protected function successResponse($data, $statusCode = 200, $text = false)
    {
        if (!$text) {
            $data          = (object) $data;
            $data->success = true;
            return response()->json($data, $statusCode);
        } else {
            return response()->json((object) [
                'success' => true,
                'data'    => $data
            ], $statusCode);
        }
    }

    protected function errorResponse($message, $statusCode)
    {
        return response()->json((object) [
            'success' => false,
            'error'   => $message,
            'code'    => $statusCode
        ], $statusCode);
    }

    protected function showAll(Collection $collection, $statusCode = 200)
    {
        $collection = $this->filterData($collection);
        $collection = $this->sortData($collection);
        $collection = $this->paginate($collection);
        $collection = $this->cacheResponse($collection);

        return $this->successResponse($collection, $statusCode);
    }

    protected function showOne(Model $model, $statusCode = 200)
    {
        return $this->successResponse($model, $statusCode, true);
    }

    public function filterData(Collection $collection)
    {
        // Copy request()->query()
        $reqQuery = (array) request()->query();

        // Fields to exclude
        $removeFields = ['page', 'sort_by', 'per_page'];

        // Loop over removeFields and delete them from reqQuery
        foreach ($removeFields as $param) {
            if (array_key_exists($param, $reqQuery) !== false) {
                unset($reqQuery[$param]);
            }
        };

        foreach ($reqQuery as $query => $value) {
            if (isset($query, $value)) {
                $collection = $collection->where($query, $value);
            }
        }

        return $collection;
    }

    protected function sortData(Collection $collection)
    {
        if (request()->has('sort_by')) {
            $attribute  = request()->sort_by;
            $collection = $collection->sortBy->{$attribute};
        } else {
            $collection = $collection->sortByDesc->{'created_at'};
        }
        return $collection;
    }

    protected function paginate(Collection $collection)
    {
        $rules = [
            'per_page' => 'integer|min:2|max:50'
        ];

        Validator::validate(request()->all(), $rules);

        $page = LengthAwarePaginator::resolveCurrentPage();

        $perPage = 25;

        if (request()->has('per_page')) {
            $perPage = (int) request()->per_page;
        }

        $results = $collection->slice(($page - 1) * $perPage, $perPage)->values();

        $paginated = new LengthAwarePaginator($results, $collection->count(), $perPage, $page, [
            'path' => LengthAwarePaginator::resolveCurrentPath()
        ]);

        $paginated->appends(request()->all());

        return $paginated;
    }

    protected function cacheResponse($data)
    {
        $url         = request()->url();
        $queryParams = request()->query();

        ksort($queryParams);

        $queryString = http_build_query($queryParams);

        $fullUrl = "{$url}?{$queryString}";

        return Cache::remember($fullUrl, 30 / 60, function () use ($data) {
            return $data;
        });
    }
}
