<?php

namespace App\Http\Controllers\API;

use Response;
use Illuminate\Support\Str;
use App\Http\Controllers\Controller;
use Illuminate\Validation\Validator;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class APIController extends Controller
{
    /**
     * @var int
     */
    protected $statusCode = HttpResponse::HTTP_OK;

    /**
     * @return int
     */
    protected function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * @param int $statusCode
     * @return void
     */
    protected function setStatusCode($statusCode)
    {
        $this->statusCode = $statusCode;
    }

    /**
     * Get total number of pages
     *
     * @param  Query 	$query
     * @param  Request 	$request
     * @return number
     */
    protected function countPages($query, $request)
    {
        if (!$request->hasPagination()) {
            return 1;
        }
        $query = clone $query;
        $query->getQuery()->orders = null; // No need for sorters to count
        $pagination = $request->getPagination();
        return intval(ceil($query->count() / $pagination['page_size']));
    }

    /**
     * Paginate a query
     *
     * @param  Query 	$query
     * @param  Request 	$request
     * @return Query
     */
    protected function paginate($query, $request)
    {
        if (!$request->hasPagination()) {
            return $query;
        }
        $pagination = $request->getPagination();
        return $query
            ->skip($pagination['page_size'] * ($pagination['page_number'] - 1))
            ->take($pagination['page_size']);
    }

    /**
     * Sort a query
     *
     * @param  Query 	$query
     * @param  Request 	$request
     * @return Query
     */
    protected function sort($query, $request)
    {
        if (!$request->hasSorter()) {
            return $query;
        }
        $sorter = $request->getSorter();
        return $query->orderBy($sorter['sort_by'], $sorter['sort_type']);
    }

    /**
     * Filter a query
     *
     * @param  Query 	$query
     * @param  Request 	$request
     * @return Query
     */
    protected function filter($query, $request)
    {
        if (!$request->hasFilters()) {
            return $query;
        }
        $filters = $request->getFilters();
        $query = $query->where($filters['where']);

        foreach ($filters['where_in'] as $key => $value) {
            $query = $query->whereIn($key, $value);
        }

        foreach ($filters['where_not_in'] as $key => $value) {
            $query = $query->whereNotIn($key, $value);
        }

        foreach ($filters['where_between'] as $key => $value) {
            $query = $query->whereBetween($key, $value);
        }

        foreach ($filters['where_not_between'] as $key => $value) {
            $query = $query->whereNotBetween($key, $value);
        }

        return $query;
    }

    /**
     * @return Response
     */
    protected function respondCreated($data)
    {
        $this->setStatusCode(HttpResponse::HTTP_CREATED);

        return $this->respond($data);
    }

    /**
     * @return Response
     */
    protected function respondAccepted()
    {
        $this->setStatusCode(HttpResponse::HTTP_ACCEPTED);

        return $this->respond();
    }

    /**
     * Add header X-Total-Pages to response
     *
     * @param  array $data
     * @param  array $totalPages
     * @return Response
     */
    protected function respondPaginated($data, $totalPages)
    {
        return $this->respond($data, [
            'X-Total-Pages' => $totalPages
        ]);
    }

    /**
     * Send a success response
     *
     * @param array $data
     * @param array $headers
     * @return Response
     */
    protected function respond($data = [], $headers = [])
    {
        $body = empty($data) ? [
            'status' => 'success',
        ] : [
            'status' => 'success',
            'data'   => $data,
        ];
        return Response::json($body, $this->getStatusCode(), $headers);
    }
}


?>